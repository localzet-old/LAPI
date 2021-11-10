<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

class Core
{
    private $Class;
    private $Function;
    private $Params;


    function __construct($Data)
    {
        foreach ($Data as $Func => $Params) {
            $this->Params = stripcslashes(json_encode($Params, JSON_UNESCAPED_UNICODE));
            $Function = explode('.', $Func);

            $this->Class = $Function[0];
            $this->Function = $Function[1];
            $this->Params = json_decode($this->Params);
            break;
        }

        if (file_exists('API/' . $this->Class . '.php')) {
            require_once 'LDB.php';
            require_once 'API/' . $this->Class . '.php';

            $APIClass = new $this->Class;
            $Reflection = new ReflectionClass($this->Class);

            try {
                $Reflection->getMethod($this->Function);
                $Func = $this->Function;
                if ($Params) {
                    if (isset($Params->responseBinary)) {
                        $APIClass->$Func($Params);
                        exit;
                    }
                } else {
                    Prnt::Error('Ошибка получения параметров');
                }
            } catch (Exception $ex) {
                Prnt::Error($ex->getMessage(), 500);
            }
        } else {
            Prnt::Error("API не найден");
        }
    }
}
