<?php
/**
 * @author SMR
 * @copyright LGPLv3
 * @since 1.0.0
 */

include_once("mirrorlink.php");

//ini_set('display_errors', 'Off');
header('Access-Control-Allow-Origin: *');
session_start();

/**
 * To prevent spamming or brute forces.
 * A timer to limit 100 request in 1 hour for every session. 
 */
if(isset($_SESSION['req']['timer']) == false || time() - $_SESSION['req']['timer'] > 3600) {
    $_SESSION['req']['timer'] = time();
    $_SESSION['req']['count'] = 0;
}

if($_SESSION['req']['count'] < 500) {
    $_SESSION['req']['count']++;
} else {
    mirrorlink::abort(-1, "request limit exceeded");;
}

// save password in session.
if(isset($_POST['password'], $_POST['username'])) {
    $password   = $_POST['password'];
    $username   = $_POST['username'];

    $mirrorlink = new mirrorlink($_SERVER['SERVER_ADDR']);

    if($mirrorlink->varifyPassword($username, $password) == true) {
        $_SESSION['password'] = $password;
        $_SESSION['username'] = $username;

        session_write_close();
        
        echo json_encode(["status" => 1]);
    } else {
        mirrorlink::abort(502, "incorrect password");
    }
} // use password in session and given URL.
else if(isset($_SESSION ['password']) || isset($_POST['newUsername'])) {

    $mirrorlink = new mirrorlink($_SERVER['SERVER_ADDR'], 3500000000);
    
    if(isset($_POST['newUsername'])) {
        $result = $mirrorlink->addUser($_POST['newUsername'],$_POST['newPassword']);
        if($result) {
            echo json_encode(["status"        =>  1]);
        } else {
            mirrorlink::abort(606, "can't add user");
        }
        die();
    }

    $password   = $_SESSION['password'];
    $username   = $_SESSION['username'];
    
    if($mirrorlink->varifyPassword($username, $password) == false) {
        unset($_SESSION['password'],$_SESSION['username']);
        mirrorlink::abort(-5, "");
    }
    else if(isset($_POST['url'])) {
        $url        = $_POST['url'];
        list($status ,$output) = $mirrorlink->createMirrorLink($url, $username, $password);

        echo json_encode( [
                "status"            => $status,
                "output"            => $output
            ]
        );
    }
    else if(isset($_POST['getUnvarifiedList'])) {

        if($mirrorlink->getUsername('admin') == md5($password)) {
            echo json_encode([
                "status"            =>  1,
                "unvarifiedList"    =>  $mirrorlink->getUnvarifiedList()
            ]);
        } else {            
            echo json_encode([
                "status"            =>  0,
                "message"           => "unaccessible"
            ]);
        }
    }
    else if(isset($_POST['updateFileList'])) {
        $fileList = mirrorlink::fileList('../pages/download/');

        echo json_encode([
            "status"        =>  1,
            "files"         =>  $fileList,
            "maxSpace"      =>  $mirrorlink->getMaxSpace()
        ]);
    }
    else if(isset($_POST['removeFile'], $_POST['name'])) {
        $result = $mirrorlink->removeFile($_POST['name'], $password);

        if($result) {
            echo json_encode(["status"        =>  1]);
        } else {
            mirrorlink::abort(-302);
        }
    }
    else if(isset($_POST['checkSession'])) {
        echo json_encode(["status"        =>  1]);
    }
    else if(isset($_POST['useraction'],$_POST['userId'])) {

        if($_POST['useraction'] == 'accept') {
            $result = $mirrorlink->varifyUser($_POST['userId']);
        } else {
            $result = $mirrorlink->varifyUser($_POST['userId']);
        }

        if($result) {
            echo json_encode(["status"        =>  1]);
        } else {
            mirrorlink::abort(611);
        }
    }
    else {
        mirrorlink::abort(504, "no valid parameter recived");
    }
}  
else { 
    mirrorlink::abort(503, "no session password exist");
}
?>