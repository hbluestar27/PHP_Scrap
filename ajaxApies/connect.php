<?php
    require_once('../mysql_i.php');

    function getConnect() {
        $host = "localhost";
        $user = "root";
        $password = "";
        $db = "betexplorer";

        $conn = sqlConnect($host, $user, $password, $db);
        return $conn;
    }
?>