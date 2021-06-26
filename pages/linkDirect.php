<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>direct link</title>

    <link rel="stylesheet" href="../resources/fonts/bootstrap-font/bootstrap-icons.css"/>
    <link rel="stylesheet" href="../libs/bootstrap-4.5.0-dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="../libs/hamberger-button.css"/>
    
    <script type="text/javascript" src="../libs/jquery/3.5.1/jquery-3.5.1.min.js"></script>
    <script type="text/javascript" src="../libs/bootstrap-4.5.0-dist/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="../libs/hamberger-button.js"></script>
    
    <script>
        function urlKeyup(obj) {
            let div = $('#charCount')
            let input = $(obj)
            div.html(obj.value.length + '/256');
            if(obj.value.length >= 256) {
                input.addClass("is-invalid");
            } else {
                input.removeClass("is-invalid");
            }
        }
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a id="navbarNavBrand" class="navbar-brand" href="..">
            SMR (home)
        </a>
        <a id="toggle"  type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav"
        aria-expanded="false" aria-label="Toggle navigation"><span></span></a>
        
        <div id="navbarNav" class="collapse navbar-collapse">
            <ul class="navbar-nav text-right">
                <li class="nav-item">
                    <a class="nav-link" href="../#myRepositories">
                        My Repositories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../#contactMe">
                        Contact Me
                    </a>
                </li>
            </ul>
        </div>
    </nav>
<?php
define('WP_DEBUG', false);

function endsWith( $string, array $subStrs ) {
    foreach($subStrs as $subStr) {
        $length = strlen( $subStr );
        if($length && substr( $string, -$length ) !== $subStr)
            return false;
    }
    return true;
}

function urlFileSize($url) {
    if($url) {        
        $headers = get_headers($url,true);
        $size = isset($headers['Content-Length']) ? $headers['Content-Length'] : -1;
        return $size;
    }
    return -1;
}

$BASE_URL = strtok($_SERVER['REQUEST_URI'],'?');
$messageArray = [
    'downloaded'=> "<div class='alert alert-success' role='alert'>ready to download.</div>",
    'notFound'  => "<div class='alert alert-warrning' role='alert'>download intrupted.</div>",
    'invalid'   => "<div class='alert alert-danger' role='alert'>invalid url or password.</div>"
];

$userIp         = $_SERVER['REMOTE_ADDR'];

$hardCodePass   = 'c7k7Mjb3JG77Ue3Y';
$messageKey     = '';

$maxSpace       = 3500000000;   //bytes
$usedSpace      = 0;            //bytes
$freeSpace      = 0;            //bytes

$filesSize      = [];

if(file_exists('download/')) {
    foreach(glob('download/*.*') as $file) {
        $fs = filesize($file);
        $filesSize[] = ($fs/$maxSpace)*100;
        
        $usedSpace += $fs;
    }
}

$freeSpace = $maxSpace - $usedSpace;


$dir = 'download/';
$fileList = glob($dir.'*.*');
foreach($fileList as $file) {
    if(time() - filemtime($file) > 14400) // 4 hour
        unlink($file);
}

if (isset($_POST['url'],$_POST['pass']) && !isset($_GET['message'])) {
        
    $url = filter_var($_POST['url'], FILTER_SANITIZE_URL);
    $len = strlen($url);

    if($_POST['pass'] != $hardCodePass) {
        $messageKey = 'invalid';
    }
    else if( 3 < $len && $len < 256) {
        if (filter_var($url, FILTER_VALIDATE_URL) == TRUE && endsWith($url, ["php","sh","exe","html","js"]) == FALSE) {
            $hash = bin2hex(random_bytes(16));
            $outputName = "download/$hash.". pathinfo($url, PATHINFO_EXTENSION);
            $basename   = basename($url);

            if (!file_exists('download/')) {
                mkdir('download/', 0655, true);
            }

            if(urlFileSize($url) < $freeSpace) {                

                $fp = fopen($url, 'r');

                if ( $fp ) {
                    file_put_contents($outputName, $fp);
                    $messageKey = 'downloaded';

                    fclose($fp); 
                }
                else {
                    $messageKey = 'notFound';
                }
            }
        }
        else {
            $messageKey = 'invalid';
        }
    }
}
?>
    <div class="container w-75 pt-4">
        <form  name='upload' method='post' action="<?php echo $BASE_URL; ?>">

            <div class="form-group">
                <?php
                    if($messageKey !== '')
                        echo ($messageArray[$messageKey]);
                ?>
            </div>
            <div class="form-group">
                <label for="url" class="text-dark">file url:</label>
                <div class="form-inline">
                    <input type="text"  class="form-control col-11" name="url" id="url" aria-describedby="helpId" placeholder="file url on web. e.g. https://example.com/file." onkeyup="urlKeyup(this);">
                    <small id="charCount" class="form-text  col-1 text-muted">0/256</small>
                </div>
                <small id="helpId" class="form-text text-muted">
                    enter url of your desired file.
                </small>
            </div>
                    
            <div class="row mb-4">
                <div class="col-8">
                    used space:
                </div>
                <div class="col-4 text-right">
                    <?php echo round($usedSpace/1048576,2)."/".round($maxSpace/1048576,2)." MB"; ?>
                </div>
                <div class="col-12">
                    <div class="progress">
                    <?php
                    $color = 0;
                    foreach($filesSize as $fs) {
                        echo "<div class='progress-bar' role='progressbar' 
                                style='width: $fs%; background-color: hsl($color, 100%, 80%);'>
                                ". ($fs > 10 ? round($fs * $maxSpace / 1048576)." MB" : '') ."</div>";
                        $color = ($color+35) % 350;
                    }
                    ?>
                    </div>
                </div>
            </div>

            <div class="form-inline">
                <label for="pass" class="col-12 col-sm-3 align-content-start">password: </label>
                <input type="password"  class="form-control col-12 col-sm-9" name="pass" id="pass">
            </div>
            <div class="form-group text-right mt-3">
                <input class="btn btn-light" type="submit" value="Upload" id='submit' name='submit'>
            </div>
             <?php
             if($messageKey == 'downloaded') {
                 echo "
                <div class='form-group text-info text-center small mt-3'>
                    here is your file link.<br>
                    $outputName<br>
                    <a  href='$outputName' class='mt-4 btn btn-dark' download='$basename'>
                        Download file
                    </a>
                </div>";
             }
            ?>
        </form>
    </div>
</body>
</html>
