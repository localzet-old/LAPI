<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

// error_reporting(E_ALL);
header('Content-type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: * ");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header('Access-Control-Allow-Credential: true');
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Authorization, Origin, Accept, X-Requested-With, Content-Type, Cache-Control, Access-Control-Allow-Headers");

if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] != '') {
    foreach (Config::$Origins as $Origin) {
        if (in_array($_SERVER['HTTP_ORIGIN'], Config::$Origins) || preg_match('#' . $Origin . '#', $_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            break;
        }
    }
}

$Method = $_SERVER['REQUEST_METHOD'];
$Data = json_decode(file_get_contents('php://input'), true);
new API($Method, $Data);
