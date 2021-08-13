<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

error_reporting(E_ALL);
date_default_timezone_set('Asia/Yekaterinburg');
header('Content-type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: * ");
header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
header('Access-Control-Allow-Credential: true');
header("Access-Control-Allow-Headers: Authorization, Origin, Accept, X-Requested-With, Content-Type, Cache-Control");

$allowedOrigins = array(
    'http://localhost'
);

if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] != '') {
    foreach ($allowedOrigins as $allowedOrigin) {
        if (in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins) || preg_match('#' . $allowedOrigin . '#', $_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $DATA = json_decode(file_get_contents('php://input'), true);
    if ($DATA) {
        require_once 'APIEngine.php';
        foreach ($DATA as $Function => $Params) {
            $API = new APIEngine($Function, json_encode($Params, JSON_UNESCAPED_UNICODE));
            echo $API->RUN();
            break;
        }
    } else {
        http_response_code(401);
        echo "Неверный запрос";
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "Метод GET не поддерживается по соображениям безопасности";
} else {
    http_response_code(401);
}
