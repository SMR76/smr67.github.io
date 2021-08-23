<?php
/**
 * @author SMR
 * @copyright LGPLv3
 * 
 * update repository data.
 * cronjob.
 */

require_once("../functions/repository.php");

global $cronjobToken;

if(isset($_GET["token"]) && $_GET["token"] == $cronjobToken) {
    try {
        $repoHandler = new repository();
        $repoHandler->updateData();
        
        echo json_encode([
            "status"    => 1,
            "message"   => "done"
        ]);
    }
    catch (Exception $e) {
        repository::abort(98);
    }
}
else {
    repository::abort(99);    
}
?>