<?php 
/**
 * @copyright LGPLv3
 * 
 * repository data handler.
 * return JSON data of repositories in database.
 */

include_once("baseConnector.php");

class repository extends baseConnector {
    private $context;

    function __construct() {
        parent::__construct();

        global $githubOAuth2Token;

        $this->context = stream_context_create([
            "http" => [
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36\n".
                    "Authorization: token $githubOAuth2Token"
                ]
            ]);

        $result = $this->createTable($this->databaseName,"repositories");
        if($result !== "") {
            echo $result;
            $this->abort(602);
        }
    }

    function __destruct() {
        parent::__destruct();
    }

    public function getRpositoriesAsJson():?string {
        $result = $this->connection->query("SELECT * FROM `repositories`");
        if($result) {
            $arrayResult = $result->fetch_all(MYSQLI_ASSOC);

            array_walk($arrayResult,function(&$item, $key) {
                $item["lastCommits"] = unserialize($item["lastCommits"]);
            });

            $jsonResult = json_encode($arrayResult,JSON_PRETTY_PRINT);
            $jsonResult = str_replace("\\n","",$jsonResult);
            return $jsonResult;
        }
        return null;
    }

    public function updateData() {
        // die if can't get content.
        $reposTemp = file_get_contents("https://api.github.com/users/smr76/repos?per_page=100", false, $this->context); 
        if($reposTemp == false) {
            $this->abort(401);
        }
        
        $repositories   = json_decode($reposTemp ,true);

        foreach($repositories as $repository) {
            $repoUrl        = $repository["url"];

            $tags           = json_decode(file_get_contents($repository["tags_url"], false, $this->context), true);
            $lastTagName    = count($tags) > 0 ? $tags[0]["name"] : "";
            $githubCommits  = json_decode(file_get_contents("$repoUrl/commits?per_page=4", false, $this->context), true);
            $commits        = [];

            $commitsCount   = $this->getCommitCounts("$repoUrl/commits");
            if($commitsCount == -1) {
                $commitsCount = count($githubCommits);
            }
            
            foreach($githubCommits as $commit) {
                $commits[] = [
                    "id"            => $commitsCount--,
                    "author_name"   => $commit["commit"]["author"]["name"],
                    "date"          => $commit["commit"]["author"]["date"],
                    "message"       => $commit["commit"]["message"],
                    "url"           => $commit["html_url"],
                ];
            }
            
            $this->setRepositoryRecord(
                $repository["name"],
                $repository["fork"] ? "TRUE" : "FALSE",
                $lastTagName,
                $repository["html_url"]."/commits/".$repository["default_branch"],
                serialize($commits),
                $repository["description"] ? $repository["description"]: ""
            );
        }
    }

    private function createTable($dbName, $tableName) {    
        // if table does not exist create it.    
        if($this->tableExist($dbName, $tableName) == false) {
            $ctres = $this->connection->query("CREATE TABLE `$tableName` (
                                        `id`                INT PRIMARY KEY AUTO_INCREMENT,
                                        `name`              VARCHAR(64)     NOT NULL,
                                        `forked`            BOOLEAN         NOT NULL,
                                        `lastTagName`       VARCHAR(128)    NOT NULL,
                                        `mainBranchUrl`     VARCHAR(256)    NOT NULL,
                                        `description`       VARCHAR(1024)   NOT NULL,
                                        `lastCommits`       VARCHAR(4096)   NOT NULL)");
            if(!$ctres)
                return $this->connection->error;
        }
        return "";
    }

    private function setRepositoryRecord($name, $forked, $lastTagName, $mainBranchUrl, $lastCommits, $description): bool {
        $result = $this->connection->query("SELECT * FROM `repositories` WHERE `name` = '$name'");
        if($result == false) {
            $this->abort(604);
        }

        $name           = $this->filterSlash($name);
        $forked         = $this->filterSlash($forked);
        $lastTagName    = $this->filterSlash($lastTagName);
        $mainBranchUrl  = $this->filterSlash($mainBranchUrl);
        $lastCommits    = $this->filterSlash($lastCommits);
        $description    = $this->filterSlash($description);

        // insert if empty else update data.
        if(empty($result->fetch_all(MYSQLI_NUM)) == true) {
            $result = $this->connection->query("INSERT INTO `repositories` VALUES (
                                            NULL,'$name',$forked,
                                            '$lastTagName','$mainBranchUrl',
                                            '$description','$lastCommits')");
        }        
        else {
            $result = $this->connection->query("UPDATE `repositories` SET 
                                            `lastTagName`   = '$lastTagName',
                                            `description`   = '$description',
                                            `lastCommits`   = '$lastCommits'
                                            WHERE `repositories`.`name` = '$name'");
        }
        return $result;
    }
    
    private function getCommitCounts($commitUrl) {
        $commitHeaders = get_headers("$commitUrl?per_page=1", true, $this->context);
        if(preg_match('/\d+(?=>;.rel="last")/', $commitHeaders['Link'] ,$matches)){
            return $matches[0];
        }
        return -1;
    }

    private function filterSlash($var) {
        return filter_var($var,FILTER_SANITIZE_ADD_SLASHES);
    }
}
?>