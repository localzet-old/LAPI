<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

class MIX extends API
{
    function GetConfig($POST)
    {
        $fromdb = API::MySQL('mixteen')->get('Wo_Config');
        if ($fromdb) {
            foreach ($fromdb as $config) {
                if ($config['name'] == '2checkout_currency') {
                    continue;
                } else {
                    $data[$config['name']] = $config['value'];
                }
            }
        }
        return $data;
    }
}
