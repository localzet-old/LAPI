<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

use Firebase\JWT\JWT;

class TOKEN
{
    static private string $KEY = "31EFD9A378593B2A6E177615FB75A";

    static function CREATE($data, $service = "LDB")
    {
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

            return json_decode(json_encode(JWT::encode($token, self::$KEY)), true);
        } catch (Exception $error) {
            Prnt::Error($error, 500);
        }
    }

    static function DECODE($token)
    {
        try {
            $date = new DateTime();
            $date = floor($date->format('U'));
            $data = JWT::decode($token, self::$KEY, array('HS256'));
            if (
                $data &&
                $data->iss == "Zorin Projects" &&
                $data->iat <= $date &&
                $data->exp > $date
            ) {
                return json_decode(json_encode($data->data), true);
            }
        } catch (Exception $error) {
            Prnt::Error($error, 500);
        }
    }
}
