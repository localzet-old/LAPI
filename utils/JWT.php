<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

use Firebase\JWT\JWT;

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
                "exp" => $exp
            );
            if ($data['username'] && $data['first_name'] && $data['last_name'] && $data['email'] && $data['id']) {
                $token["data"] = array(
                    "username" => $data['username'],
                    "first_name" => $data['first_name'],
                    "last_name" => $data['last_name'],
                    "email" => $data['email'],
                    "id" => $data['id']
                );
            } else {
                $token["data"] = $data;
            }
            // Class => Array
            return json_decode(json_encode(JWT::encode($token, $KEY)), true);
        } catch (Exception $e) {
            echo $e;
            return false;
        }
    }

    function DECODE($token)
    {
        $KEY = "UserZorinProjectsLDBSystemlocalzet";
        $date = new DateTime();
        $date = floor($date->format('U'));
        try {
            $data = JWT::decode($token, $KEY, array('HS256'));
            if (
                $data->iss == "Zorin Projects" &&
                $data->iat <= $date &&
                $data->exp > $date
            ) {
                return json_decode(json_encode($data->data), true);
            }
        } catch (Exception $e) {
            echo $e;
            return false;
        }
    }
}
