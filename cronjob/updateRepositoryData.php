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
    $repoHandler = new repositoryDataHandler();
    $repoHandler->updateData();
}
?>