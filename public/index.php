<?php
if (isset($_SERVER["HTTP_ORIGIN"])) {
    $http_origin = $_SERVER["HTTP_ORIGIN"];
    if ($http_origin == "http://localhost:3000" || $http_origin == "https://app.gcenter.vn") {
        header("Access-Control-Allow-Origin: $http_origin");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, trongateToken");
        header("Access-Control-Allow-Credentials: true");
        if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
            header("HTTP/1.1 200 OK");
            exit();
        }
    }
}

require_once "../engine/ignition.php";

//Init Core Library
$init = new Core;
