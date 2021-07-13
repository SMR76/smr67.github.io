<?php 
/**
 * @author SMR
 * @version 0.3.0
 * @copyright LGPLv3
 * @package dev
 */


class mirrorLinkHandler {
    private $serverAddress;
    private $maxSpace;
    private $connection;
    private $DBerror;

    public function __construct(
                            string $serverAddr ,
                            string $dbUsername,
                            string $dbPassword,
                            $maxSpace   = 3500000000,
                            $servername = 'localhost'
                        ) {
        // constructor body
        $this->serverAddress    = $serverAddr;
        $this->maxSpace         = $maxSpace;
        $this->DBerror          = false;

        $this->connection = new mysqli($servername, $dbUsername, $dbPassword);
       
        if( $this->connection->connect_errno || 
                $this->createDataBase() == false ||
                    $this->createTable() == false) {          

            $this->DBerror = true;
            die('- contact with administrator<br>- gmail: seyyedmortezarazavi76@gmail.com');
        }
        
        $this->connection->select_db("smrdb");
    }

    /**
     * destructor.
     * close DB connection.
     */
    public function __destruct() {
        $this->connection->close();        
    }

    private function createDataBase() {
        $result = $this->connection->query("SELECT count(1) FROM information_schema.tables WHERE `TABLE_SCHEMA` = 'smrdb'");
        if($result) {
            // if database doesn't exist create it.
            if($result->fetch_array()[0] == 0) {
                $cdres = $this->connection->query("CREATE DATABASE `smrdb`");
                $this->connection->select_db("smrdb");
            }
            return true;
        }
        return false;
    }

    private function createTable() {        
        $result = $this->connection->query("SELECT count(1) FROM information_schema.tables 
                                            WHERE `TABLE_SCHEMA` = 'smrdb' 
                                            and `TABLE_NAME` = 'passwordList'");

        if($result) {
            // if table doesn't exist create it.
            if($result->fetch_array()[0] == 0) {
                $ctres = $this->connection->query("CREATE TABLE `passwordList` (
                                            `id`            INT PRIMARY KEY AUTO_INCREMENT,
                                            `password`      VARCHAR(32) NOT NULL,
                                            `username`      VARCHAR(30) NOT NULL,
                                            `varified`      BOOLEAN DEFAULT FALSE)");
            }
            return true;
        }
        return false;
    }

    private function dbCheckPassword($password) {
        $md5pass = md5($password);
        $result = $this->connection->query("SELECT * FROM `passwordList` WHERE `password` = '$md5pass'");

        if($result) {
            if($row = $result->fetch_assoc()) {
                return $row['username'];
            }
        }
        return null;
    }

    /** 
     * @return string|null username.
     * 
     * check if password is true.
     */
    private function varifyPassword($password) {
        $whitelist = array(
            'localhost',
            '127.0.0.1',
            '::1'
        );

        if(in_array($this->serverAddress,$whitelist))
            ;//return 'admin';

        return $this->dbCheckPassword($password);
    }

    private function isFileExist(string $newFileName) {
        $dir = 'download/';
        $fileList = glob($dir.'*.*');
        foreach($fileList as $file) {
            if($newFileName == substr($file,strpos($file, "-") + 1))
                return true;
        }
        return false;
    }

    public function getMaxSpace() {
        return $this->maxSpace;
    }

    public function getUnvarifiedList() {        
        $result = $this->connection->query("SELECT * FROM `passwordList` WHERE `varified` = FALSE");
        $unvarifiedList = [];

        if($result) {
            while($row = $result->fetch_assoc()) {
                $unvarifiedList[] = $row;
            }
            return $unvarifiedList;
        }        
        return null;
    }

    public function getDBerror() {
        return $this->DBerror;
    }

    /**
     * @return array($remainedSpace, $usedSpace, $filesSize);
     * calulate free space in host and return max and free space and files size array.
     */
    public function calclulateFilesSpace() {
        $usedSpace = 0;
        $filesSize = [];
        if(file_exists('download/')) {
            foreach(glob('download/*.*') as $file) {
                $fs = filesize($file);
                $filesSize[] = ($fs/$this->maxSpace)*100;
                
                $usedSpace += $fs;
            }
        }    
        $remainedSpace = $this->maxSpace - $usedSpace;

        return array($remainedSpace, $usedSpace, $filesSize);
    }

    /** 
     * @return string 
     * create a mirror link from given URL.
     */
    public function createMirrorLink(string $url,string $password,int $remainedSpace) {
        $url = filter_var($url , FILTER_SANITIZE_URL);
        $len = strlen($url);
    
        if($this->varifyPassword($password) != null && 3 < $len && $len < 256) {

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
    public static function fileList($dir) {
        $fileList = glob($dir.'*.*');
        return $fileList;
    }
}
?>