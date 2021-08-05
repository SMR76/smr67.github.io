<?php 
/**
 * @author SMR
 * @copyright LGPLv3
 * @package dev
 * 
 * repository data handler.
 * return JSON data of repositories in database.
 */

//* include repository data api.
include_once("repositoryDataHandler.php");

$repoDataHandler = new repositoryDataHandler();
echo $repoDataHandler->getRpositoriesAsJson();