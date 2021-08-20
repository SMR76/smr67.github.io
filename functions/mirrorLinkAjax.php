<?php
/**
 * @author SMR
 * @copyright LGPLv3
 * @since 1.0.0
 */

include_once("mirrorlink.php");

ini_set('display_errors', 'Off');
session_start();

// svae password in session.
if(isset($_POST['pass'])) {
    $pass       = isset($_POST['pass']);
    $mirrorlink = new mirrorlink($_SERVER['SERVER_ADDR']);
    if($mirrorlink->varifyPassword($pass) == true) {
        $_SESSION['pass'] = $pass;
        echo json_encode(["status" => 200]);
    } else {
        mirrorlink::abort(502, "incorrect password");
    }
} // use password in session and given URL.
else if(isset($_SESSION ['pass'])) {
    $pass       = $_SESSION['pass'];
    
    if($_POST['url']) {
        $unvarifiedList = [];
        $url        = $_POST['url'];
        $mirrorlink = new mirrorlink($_SERVER['SERVER_ADDR'], 3500000000);

        $usedSpace  = $mirrorlink->calculateUsedSpace();
        $freeSpace  = $mirrorlink->getMaxSpace() - $usedSpace;
        list($messageKey,$output) = $mirrorlink->createMirrorLink($url,$pass,$freeSpace);

        echo json_encode( [
                "status"            => 201,
                "usedSpace"         => $usedSpace,
                "output"            => $output
            ]
        );
    }
    else if(isset($_GET['getUnvarifiedList']) && $_GET['getUnvarifiedList'] == true) {
        if($mirrorlink->getUsername($pass) == 'admin') {
            echo json_encode([
                "status"            =>  201,
                "unvarifiedList"    =>  $mirrorlink->getUnvarifiedList()
            ]);
        }
    }
    else if(isset($_GET['getFiles'])) {
        $fileList = mirrorlink::fileList('download/');
        echo json_encode([
            "status"            =>  201,
            "currentFiles"    =>  $mirrorlink->getUnvarifiedList()
        ]);
    }
}  
else { 
    mirrorlink::abort(503, "no session password exist.");
}

?>