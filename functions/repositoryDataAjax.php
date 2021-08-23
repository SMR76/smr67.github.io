<?php 
/**
 * @author SMR
 * @copyright LGPLv3
 * @package dev
 * 
 * repository data handler.
 * return JSON data of repositories in database.
 */

header('Access-Control-Allow-Origin: *');

//* include repository data api.
include_once("repositoryData.php");

$repoDataHandler = new repositoryDataHandler();
echo $repoDataHandler->getRpositoriesAsJson();