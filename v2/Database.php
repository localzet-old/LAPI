<?php

class Database
{
    public $MySQL;

    function __construct($db)
    {
        if (is_null($db) || !isset($db) || !$db) {
            $this->MySQL[$db] = null;
            return false;
        }
        if (is_null($this->MySQL) || !isset($this->MySQL) || !$this->MySQL || is_null($this->MySQL[$db]) || !isset($this->MySQL[$db]) || !$this->MySQL[$db]) {
            $connection = array(
                'host' => 'localhost',
                'username' => 'localzet',
                'password' => 'lvanZ2003',
                'db' => $db,
                'port' => 3306,
                'prefix' => '',
                'charset' => 'utf8'
            );
            $this->MySQL[$db] = new MysqliDb($connection);
            if (mysqli_connect_errno()) {
                printf("Подключение невозможно: %s\n", mysqli_connect_error());
                $this->MySQL[$db] = null;
                return false;
            }
        }
        return $this->MySQL[$db];

    }
}
