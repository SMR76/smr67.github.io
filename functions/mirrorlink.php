<?php 
/**
 * @author SMR
 * @version 1.0.0
 * @copyright LGPLv3
 * @package dev
 */


include_once("baseConnector.php");

class mirrorlink extends baseConnector {
    private $maxSpace;
    private $downloadDir;

    public function __construct(string $serverAddr ,$maxSpace   = 3500000000) {
        // constructor body
        parent::__construct();
        $this->serverAddress    = $serverAddr;
        $this->maxSpace         = $maxSpace;
        $this->downloadDir      = $this->baseUrl.'pages/download/';
        

        $result = $this->createTable($this->databaseName,"passwordlist");
        if($result === true) {
            $this->abort(602,$this->connection->error);
        }
    }

    public function getUsername(string $username):?string {
        $username   = filter_var($username,FILTER_SANITIZE_ADD_SLASHES);
        $result = $this->connection->query("SELECT * FROM `passwordlist` WHERE `username` = '$username'");

        if($result) {
            if($row = $result->fetch_assoc()) {
                return $row['password'];
            }
        }
        return "";
    }

    /** 
     * 
     * check if password is correct.
     */
    public function varifyPassword($username, $password):bool {
        return $this->getUsername($username) == md5($password);
    }

    public function varifyUser(int $id): bool {
        $query      = "UPDATE `passwordlist` SET `varified`=TRUE WHERE `id`='$id'";
        $result     = $this->connection->query($query);
        return $result ;
    }

    public function rejectUser(int $id): bool {
        $result     = $this->connection->query("DROP `passwordlist` WHERE `id`= '$id'");
        return $result ;
    }

    public function addUser(string $username, string $password): bool {
        $username   = filter_var($username,FILTER_SANITIZE_ADD_SLASHES);
        $md5pass    = md5($password);
        $result     = $this->connection->query("INSERT INTO `passwordlist` VALUES (NULL,'$md5pass','$username',FALSE)");
        echo $this->connection->error;

        return $result ;
    }

    public function getMaxSpace() {
        return $this->maxSpace;
    }

    public function getUnvarifiedList() {        
        $result = $this->connection->query("SELECT `id`,`username` FROM `passwordlist` WHERE `varified` = FALSE");
        if($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }        
        return null;
    }

    public function getDBerror() {
        return $this->DBerror;
    }

    /**
     * @return $usedSpace;
     * calulate free space in server and return used space.
     */
    public function calculateUsedSpace():int {
        $usedSpace = 0;
        if(file_exists($this->baseUrl.'pages/download/')) {
            foreach(glob($this->baseUrl.'pages/download/*.*') as $file) {
                $fs = filesize($file);
                $usedSpace += $fs;
            }
        }

        return $usedSpace;
    }

    /** 
     * @return string 
     * create a mirror link from given URL.
     */
    public function createMirrorLink(string $url, string $username, string $password) {
        $url = filter_var($url , FILTER_SANITIZE_URL);
        $len = strlen($url);
        $usedSpace      = $this->calculateUsedSpace();
        $remainedSpace  = $this->getMaxSpace() - $usedSpace;
    
        if($this->varifyPassword($username, $password) == true && 3 < $len && $len < 512) {
            /**
             * create download folder on 0777 mode.
             */
            if (!file_exists($this->downloadDir)) {
                mkdir($this->downloadDir, 0777, true);
            }

            if($this->isFileExist($this->downloadDir, basename($url)) == true)
                return array(-102, null); //return error code (file is repetative)
            
            /**
             * validate  $urlEXE.
             */
            if (filter_var($url, FILTER_VALIDATE_URL) == TRUE && $this->endsWith($url, ["php","sh","html","js","py","SCR","PDF","VBS","RTF","DOC","XLS",]) == FALSE) {
                $hash = bin2hex(random_bytes(4));
                $outputName = $this->downloadDir."$hash-".basename($url);    
    
                $fileSize = $this->urlFileSize($url);
    
                if($fileSize > 0 || $fileSize < $remainedSpace) {
                    $fp = fopen($url, 'r');
    
                    if ( $fp ) {
                        if(file_put_contents($outputName, $fp) == false) //write content to the file.
                            return array(-101, null);           //return error code (can't get file)
                        fclose($fp);                            //close file.
                        chmod($outputName, 0744);               //change file permission
                        
                        return array(1 , basename($outputName));//return success code (file downloaded)
                    }
                    else {
                        return array(-100, null); //return error code (file not found)
                    }
                }
            }
        }
        return array(-101, null); //return error code (url is invalid)
    }

    /**
     * @return true if $string match any of $substrs. 
     */
    public static function endsWith(string $string, array $subStrs ) {
        foreach($subStrs as $subStr) {
            $length = strlen( $subStr );
            if($length && strtolower(substr( $string, -$length )) === strtolower( $subStr ))
                return true;
        }
        return false;
    }

    /**
     * @return  integer $size 
     * return -1 if couldn't get file size.
     */
    public static function urlFileSize(string $url) {
        if($url) {        
            $headers    = get_headers($url,true);
            $size       = isset($headers['Content-Length']) ? $headers['Content-Length'] : -1;
            return $size;
        }
        return -1;
    }

    /**
     * @return bool url-existance.
     * check if url is valid.
     */
    public static function urlExists(string $url=NULL)  
    {  
        if($url == NULL) return false;  
        $ch = curl_init($url);  
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);  
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
        $data = curl_exec($ch);  
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);  
        curl_close($ch);  
        if($httpcode>=200 && $httpcode<300){  
            return true;  
        } else {  
            return false;  
        }  
    } 
    
    /** 
     * @return array 
     * list of file names.
     */
    public static function fileList($root) {
        $glob   = glob($root.'*.*');
        $files  = [];

        foreach($glob as $file) {
            $files[] = ["name" => basename($file), "size" => filesize($file)];
        }

        return $files;
    }
    /** 
     * @return bool 
     * remove file.
     */
    public function removeFile(string $fileName,string $password) {
        if($this->getUsername("admin") == md5($password) && file_exists($this->downloadDir.$fileName)) {
            unlink($this->downloadDir.$fileName);
            return true;
        }
        return false;
    }
    
    private function createTable($dbName, $tableName): bool {    
        // if table does not exist create it.    
        if($this->tableExist($dbName, $tableName) == false) {    
            $ctres = $this->connection->query("CREATE TABLE `$tableName` (
                                        `id`            INT PRIMARY KEY AUTO_INCREMENT,
                                        `password`      VARCHAR(32) NOT NULL,
                                        `username`      VARCHAR(30) NOT NULL,
                                        `varified`      BOOLEAN DEFAULT FALSE)");
            return $ctres;
        }
        return false;
    }

    private function isFileExist(string $root, string $newFileName):bool {
        $fileList   = glob($root.'*.*');
        foreach($fileList as $file) {
            if($newFileName == substr($file,strpos($file, "-") + 1))
                return true;
        }
        return false;
    }
}
?>