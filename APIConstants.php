<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

class APIConstants {

    //Результат запроса - параметр в JSON ответе
    public static string $RESULT_CODE="resultCode";
    
    //Ответ - используется как параметр в главном JSON ответе в apiEngine
    public static string $RESPONSE="response";
    
    //Нет ошибок
    public static int $ERROR_NO_ERRORS = 0;
    
    //Ошибка в переданных параметрах
    public static int $ERROR_PARAMS = 1;
    
    //Ошибка в подготовке SQL запроса к базе
    public static int $ERROR_STMP = 2;

    //Ошибка запись не найдена
    public static int $ERROR_RECORD_NOT_FOUND = 3;
    
    //Ошибка в параметрах запроса к серверу. Не путать с ошибкой переданных параметров в метод
    public static int $ERROR_ENGINE_PARAMS = 100;
    
    //Ошибка zip архива
    public static int $ERROR_ENSO_ZIP_ARCHIVE = 1001;
    
}
?>