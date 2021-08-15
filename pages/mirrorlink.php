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

        function getData() {

        }        
    </script>
</head>
<body>
    <?php
        include_once("mirrorLink.php");
        
        ini_set('display_errors', 'Off');
        $maxSpace   = 3500000000;
        $usedSpace     = 0;

        $unvarifiedList = [];
        
        $BASE_URL   = strtok($_SERVER['REQUEST_URI'],'?');
        $mLinkhandler = new mirrorLinkHandler($_SERVER['SERVER_ADDR'],$maxSpace);
        
        if(isset($_POST['regUsername'],$_POST['regPassword']) 
                    && $_POST['regUsername'] != '' && $_POST['regPassword'] != '' ) {
            
            $username = $_POST['regUsername'];
            $password = $_POST['regPassword'];
            
            if(preg_match('/^\w*$/', $input_line) == 1) {
                $mLinkhandler->addUser(array('username' => $username, 'password' => $password));
                $messageKey = 'registred';
            }
        }

    ?>
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
                <li class="nav-item">
                    <a class="nav-link" onclick="$('#register-modal').modal('show');">
                        Get Verification
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container w-75 pt-5 pb-5">
        <form  name='upload' method='post' action="<?php echo $BASE_URL; ?>">

            <div class="form-group">
                <?php
                    if(isset($messageKey)) {
                        echo mirrorLinkHandler::statusMessage($messageKey);
                    }
                ?>
            </div>
            <div class="form-group">
                <label for="url" class="text-dark">file url:</label>
                <div class="form-inline">
                    <input type="text"  class="form-control col-10" name="url" id="url" aria-describedby="helpId" placeholder="file url on web. e.g. https://example.com/file." onkeyup="urlKeyup(this);" required>
                    <small id="charCount" class="form-text  col-2 text-muted text-right">0/256</small>
                </div>
                <small id="helpId" class="form-text text-muted">
                    enter url of your desired file.
                </small>
            </div>
                    
            <div class="row mb-4">
                <div class="col-6">
                    used space:
                </div>
                <div class="col-6 text-right">
                    <?php echo round($usedSpace/1048576,2)."/".round($maxSpace/1048576,2)." MB";?>
                </div>
                <div class="col-12">
                    <div class="progress">
                    <?php
                        $color = 0;
                        if(isset($filesSize)) {
                            foreach($filesSize as $fs) {
                                echo "<div class='progress-bar' role='progressbar' 
                                        style='width: $fs%; background-color: hsl($color, 100%, 80%);'>
                                        ". ($fs > 10 ? round($fs * $maxSpace / 1048576)." MB" : '') ."</div>";
                                $color = ($color+35) % 350;
                            }
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
             if(isset($messageKey,$outputName) && $messageKey == 'downloaded') {
                 $basename = basename($outputName);
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
        <div class='container-fluid'>
            <?php if(count($fileList) > 0) :?>
            <div class="row">
                <div class="col-12">
                    current links:
                </div>
            </div>
            <?php endif ?>
            <?php
                $color = 0;
                $id = 1;
                foreach($fileList as $file) {
                    $baseName = substr($file,strpos($file, "-") + 1);
                    echo "
                        <div class='row mt-1 rounded bg-light'>
                            <div    class='col-2 rounded-left text-secondary' 
                                    style='background-color: hsl($color, 100%, 95%);'>
                                $id
                            </div>
                            <div class='col-10'>
                                <a  class='text-secondary text-decoration-none' href = '$file'>
                                    $baseName
                                </a>
                            </div>
                        </div>
                   ";
                    $color = ($color+35) % 350;
                    $id++;
                }
            ?> 
        </div>
    </div>

    <div  id="register-modal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div id="modal-body" class="modal-body container">
                        <div class="form-group form-inline">
                            <input type="text"  class="form-control col-6" name="regUsername" placeholder="username" required>
                            <div class="col-1"></div>
                            <input type="text"  class="form-control col-5" name="regPassword" placeholder="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">close</button>
                        <input type="submit"  class="btn btn-info" name="submit" value="apply">
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
