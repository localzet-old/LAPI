<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

class API
{
    function __construct($Method, $Data)
    {
        if (!$Method || !$Data) {
            Prnt::Error("Неверный запрос", 401);
            exit;
        }

        switch ($Method) {
            case 'OPTIONS':
                Prnt::Success('OK');
                exit;
            case 'POST':
                new Core($Data);
                exit;
            default:
                Prnt::Error("Метод GET не поддерживается", 405);
                exit;
        }
    }
}
