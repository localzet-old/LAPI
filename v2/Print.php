<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

class Prnt
{
    public static function Info($info, $code = 100)
    {
        http_response_code(200);
        echo json_encode(array('status' => $code, 'info' => $info), JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function Success($data, $code = 200)
    {
        http_response_code(200);
        echo json_encode(array('status' => $code, 'data' => $data), JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function Error($error, $code = 400)
    {
        http_response_code(200);
        echo json_encode(array('status' => $code, 'error' => $error), JSON_UNESCAPED_UNICODE);
        exit;
    }
}
