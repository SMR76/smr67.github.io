<?php
/**
 * @author SMR
 * @copyright LGPLv3
 * 
 * base connector abstract class. 
 */
include_once("accessCode.php");

abstract class baseConnector {
    protected $connection;      
    protected $DBerror;
    protected string $databaseName;
    protected string $baseUrl;

    function __construct() {
        global $gdbUsername;
        global $gdbPass;
        global $gdbName;

        $this->databaseName = $gdbName;
        $this->baseUrl      = "./../";

        $this->connection = new mysqli('localhost', $gdbUsername, $gdbPass);
        
        if( $this->connection->connect_errno || $this->createDataBase($this->databaseName) == false) {
            $this->abort(601); //die and print contact message
        }
        $this->connection->select_db($this->databaseName);        
    }

    /**
     * destructor.
     * close DB connection.
     */
    function __destruct() {
        $this->connection->close();        
    }
    
    private function createDataBase($dbName):bool {
        $result = $this->connection->query("SELECT count(1) FROM information_schema.tables WHERE `TABLE_SCHEMA` = '$dbName'");
        if($result) {
            // if database doesn't exist create it.
            if($result->fetch_array()[0] == 0) {
                $cdres = $this->connection->query("CREATE DATABASE `$dbName`");
            }
            return true;
        }
        return false;
    }    

    
    protected function tableExist($dbName, $tableName):bool { 
        $result = $this->connection->query("SELECT count(1) FROM information_schema.tables 
                                            WHERE `TABLE_SCHEMA` = '$dbName' 
                                            and `TABLE_NAME` = '$tableName'");
        if($result) 
            return $result->fetch_array()[0] != 0;
        else
            return false;
    }

    public static function abort($code = 0, string $message = "") {    
        die(json_encode([
            "status"    => -1,
            "code"      => $code,
            "message"   => $message,
            "info"      => "contact with website administrator, gmail: seyyedmortezarazavi76@gmail.com"
        ]));
    }
}
?>