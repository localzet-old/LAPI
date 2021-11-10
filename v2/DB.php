<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

class DB
{
    static private $MySQL;

    function __construct($db)
    {
        if (is_null($db) || !isset($db) || !$db) {
            Prnt::Error("База данных не подключена");
            return false;
            exit;
        }

        if (!self::$MySQL[$db]) {
            $connection = array(
                'host' => 'localhost',
                'username' => 'localzet',
                'password' => 'lvanZ2003',
                'db' => $db,
                'port' => 3306,
                'prefix' => '',
                'charset' => 'utf8'
            );

            self::$MySQL[$db] = new MysqliDb($connection);
            if (mysqli_connect_errno()) {
                Prnt::Error("Подключение невозможно: %s\n", mysqli_connect_error());
                return false;
                exit;
            }
        }

        return self::$MySQL[$db];
    }
}
