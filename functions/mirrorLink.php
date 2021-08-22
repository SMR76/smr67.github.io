<?php 
/**
 * @author SMR
 * @version 0.3.0
 * @copyright LGPLv3
 * @package dev
 */


include_once("baseConnector.php");

class mirrorlink extends baseConnector {
    private $maxSpace;

    public function __construct(string $serverAddr ,$maxSpace   = 3500000000) {
        // constructor body
        parent::__construct();
        $this->serverAddress    = $serverAddr;
        $this->maxSpace         = $maxSpace;
        

        $result = $this->createTable($this->databaseName,"passwordlist");
        if($result === true) {
            $this->abort(602,$this->connection->error);
        }
    }

    public function getUsername(string $password):?string {
        $md5pass = md5($password);
        $result = $this->connection->query("SELECT * FROM `passwordlist` WHERE `password` = '$md5pass'");

        if($result) {            
            if($row = $result->fetch_assoc()) {
                return $row['username'];
            }
        }
        return "";
    }

    /** 
     * 
     * check if password is correct.
     */
    public function varifyPassword($password):bool {
        return $this->getUsername($password) !== "";
    }

    public function varifiedUser(array $varified) {
        $varified = filter_var($varified ,FILTER_SANITIZE_ADD_SLASHES);    
        $query = "UPDATE `passwordlist` SET varify=TRUE WHERE `username`=$varified";
        $result = $this->connection->query($query);
        return $result ;
    }

    public function rejectedUser(string $rejected) {
        $rejected = filter_var($rejected ,FILTER_SANITIZE_ADD_SLASHES);            
        $query = "DROP FROM `passwordlist` WHERE `username`=$rejected";
        $result = $this->connection->query($query);
        return $result ;
    }

    public function addUser(string $username, string $password): bool {
        $username   = filter_var($username,FILTER_SANITIZE_ADD_SLASHES);
        $md5pass    = md5($password);
        $result     = $this->connection->query("INSERT INTO `passwordlist` VALUES NULL,$username,$md5pass,FALSE)");
        return $result ;
    }

    public function getMaxSpace() {
        return $this->maxSpace;
    }

    public function getUnvarifiedList() {        
        $result = $this->connection->query("SELECT * FROM `passwordlist` WHERE `varified` = FALSE");
        if($result) {
            return $result->fetch_assoc();
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
        if(file_exists('download/')) {
            foreach(glob('download/*.*') as $file) {
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
    public function createMirrorLink(string $url,string $password) {
        $url = filter_var($url , FILTER_SANITIZE_URL);
        $len = strlen($url);
        $usedSpace      = $this->calculateUsedSpace();
        $remainedSpace  = $this->getMaxSpace() - $usedSpace;
    
        if($this->varifyPassword($password) != null && 3 < $len && $len < 256) {

            /**
             * create download folder on 0777 mode.
             */
            if (!file_exists('download/')) {
                mkdir('download/', 0777, true);
            }

            if($this->isFileExist(basename($url)) == true)
                return array('repetitive',null);
            
            /**
             * validate  $url
             */
            if (filter_var($url, FILTER_VALIDATE_URL) == TRUE && $this->endsWith($url, ["php","sh","exe","html","js"]) == FALSE) {
                $hash = bin2hex(random_bytes(4));
                $outputName = "download/$hash-".basename($url);
    
    
                $fileSize = $this->urlFileSize($url);
    
                if($fileSize > 0 || $fileSize < $remainedSpace) {
                    $fp = fopen($url, 'r');
    
                    if ( $fp ) {
                        file_put_contents($outputName, $fp);    //write content to the file.
                        fclose($fp);                            //close file.
                        chmod($outputName, 0444);               //change file permission
                        
                        return array('downloaded',$outputName);
                    }
                    else {
                        return array('notFound',null);
                    }
                }
            }
        }
        return array('invalid',null);
    }

    
    /**
     * @return status message.
     */
    public static function statusMessage(string $status) {
        $messageArray = [
            'downloaded'    => "<div class='alert alert-success' role='alert'>Ready to download.</div>",
            'repetitive'    => "<div class='alert alert-info' role='alert'>The file alrady exist.</div>",
            'notFound'      => "<div class='alert alert-warrning' role='alert'>Download intrupted.</div>",
            'invalid'       => "<div class='alert alert-danger' role='alert'>Invalid url or password.</div>"
        ];

        return $messageArray[$status];
    }

    /**
     * @return true if $string match any of $substrs. 
     */
    public static function endsWith(string $string, array $subStrs ) {
        foreach($subStrs as $subStr) {
            $length = strlen( $subStr );
            if($length && substr( $string, -$length ) === $subStr)
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

    private function isFileExist(string $newFileName):bool {
        $root       = 'download/';
        $fileList   = glob($root.'*.*');
        foreach($fileList as $file) {
            if($newFileName == substr($file,strpos($file, "-") + 1))
                return true;
        }
        return false;
    }
}
?>