<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

require_once 'vendor/autoload.php';
require_once 'utils/JWT.php';

use Kreait\Firebase\Factory;

class API
{
    public $MySQL;
    public $DB;
    public $TOKEN;

    function DB()
    {
        if (is_null($this->DB) || !isset($this->DB) || !$this->DB) {
            $factory = (new Factory())
                ->withDatabaseUri('https://localzet-db-default-rtdb.europe-west1.firebasedatabase.app')
                ->withServiceAccount(__DIR__ . '/localzet-db-firebase-adminsdk-uw91d-0ef5e80361.json');
            $this->DB = $factory->createDatabase();
        }
        return $this->DB;
    }

    function MySQL()
    {
        if (is_null($this->MySQL) || !isset($this->MySQL) || !$this->MySQL) {
            $this->MySQL = new MysqliDb(array(
                'host' => 'localhost',
                'username' => 'localzet',
                'password' => 'lvanZ2003',
                'db' => 'api',
                'port' => 3306,
                'prefix' => '',
                'charset' => 'utf8'
            ));
            if (mysqli_connect_errno()) {
                printf("Подключение невозможно: %s\n", mysqli_connect_error());
                $this->MySQL = null;
            }
        }
        return $this->MySQL;
    }

    function TOKEN()
    {
        if (is_null($this->TOKEN) || !isset($this->TOKEN) || !$this->TOKEN) {
            $this->TOKEN = new TOKEN();
        }
        return $this->TOKEN;
    }

    function COUNTS()
    {
        $this->UPUSER();
        $this->UPREQUEST();
    }

    function UPREQUEST()
    {
        $counts =  $this->DB()->getReference('counts/requests')->getValue() + 1;
        $this->DB()->getReference('counts')->update(['requests' => $counts]);
    }

    function UPUSER()
    {
        $counts = array_key_last($this->DB()->getReference('users')->getValue());
        $this->DB()->getReference('counts')->update(['users' => $counts]);
    }
}
