<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

use \Firebase\JWT\JWT;

class TOKEN
{
    function CREATE($data, $service = "LDB")
    {
        $KEY = "UserZorinProjectsLDBSystemlocalzet";
        try {
            $date = new DateTime();
            $date->setDate(date('Y'), date('m'), date('d') + 7);
            $exp = floor($date->format('U'));

            $token = array(
                "iss" => "Zorin Projects",
                "aud" => $service,
                "iat" => floor(time()),
                "exp" => $exp,
                "data" => $data
            );
            return JWT::encode($token, $KEY);
        } catch (Exception $e) {
            return $e;
        }
    }

    function DECODE($token)
    {
        $KEY = "UserZorinProjectsLDBSystemlocalzet";
        try {
            $data = JWT::decode($token, $KEY, array('HS256'));
            if ($data->iss == "Zorin Projects") {
                return $data->data;
            }
        } catch (Exception $e) {
            return $e;
        }
    }
}
