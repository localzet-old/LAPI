<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

require_once('APIConstants.php');

class APIEngine
{

    /**
     * @var false|string[]
     */
    private $Function;
    /**
     * @var string
     */
    private string $Params;

    /**
     * @param $API
     * @return mixed
     */
    static function getApiEngineByName($API)
    {
        require_once 'API.php';
        require_once 'API/' . $API . '.php';
        $APIClass = new $API();
        return $APIClass;
    }

    /**
     * APIEngine constructor.
     * @param $Function
     * @param $Params
     */
    function __construct($Function, $Params)
    {
        $this->Params = stripcslashes($Params);
        $this->Function = explode('_', $Function);
    }

    /**
     * @return false|string
     * @throws \ReflectionException
     */
    function RUN()
    {
        $RESULT = json_decode('{}');
        $API = $this->Function[0];
        if (file_exists('API/' . $API . '.php')) {
            $APIClass = self::getApiEngineByName($API);
            $Reflection = new ReflectionClass($API);
            try {
                $FUNCTION = $this->Function[1];
                $Reflection->getMethod($FUNCTION);
                $PARAMS = json_decode($this->Params);
                if ($PARAMS) {
                    if (isset($PARAMS->responseBinary)) {
                        return $APIClass->$FUNCTION($PARAMS);
                    } else {
                        $RESULT = $APIClass->$FUNCTION($PARAMS);
                    }
                } else {
                    $RESULT->errno = APIConstants::$ERROR_ENGINE_PARAMS;
                    $RESULT->error = 'Ошибка получения параметров';
                }
            } catch (Exception $ex) {
                $RESULT->error = $ex->getMessage();
            }
        } else {
            $RESULT->errno = APIConstants::$ERROR_ENGINE_PARAMS;
            $RESULT->error = 'API не найден';
        }
        return json_encode($RESULT, JSON_UNESCAPED_UNICODE);
    }
}
