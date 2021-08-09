<?php
/**
 * @author SMR
 * @copyright LGPLv3
 * 
 * update repository data.
 * cronjob.
 */

require_once("../functions/repositoryDataHandler.php");

global $cronjobToken;

if(isset($_GET["token"]) && $_GET["token"] == $cronjobToken) {
    try {
        $repoHandler = new repositoryDataHandler();
        $repoHandler->updateData();
        
        echo json_encode([
            "status"    => 1,
            "message"   => "done"
        ]);
    }
    catch (Exception $e) {
        repositoryDataHandler::abort(100);
    }
}
else {
    repositoryDataHandler::abort(100);    
}
?>