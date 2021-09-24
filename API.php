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
    public $MySQL_DBName;
    public $DB;
    public $TOKEN;

    /**
     * @return \Kreait\Firebase\Contract\Database
     */
    function DB(): \Kreait\Firebase\Contract\Database
    {
        if (is_null($this->DB) || !isset($this->DB) || !$this->DB) {
            $factory = (new Factory())
                ->withDatabaseUri('https://localzet-db-default-rtdb.europe-west1.firebasedatabase.app')
                ->withServiceAccount(__DIR__ . '/localzet-db-firebase-adminsdk-uw91d-0ef5e80361.json');
            $this->DB = $factory->createDatabase();
        }
        return $this->DB;
    }

    /**
     * @return \MysqliDb|null|false
     */
    function MySQL($db): ?MysqliDb
    {
        if (is_null($db) || !isset($db) || !$db) {
            printf("Нет параметра service");
            $this->MySQL[$db] = null;
            return false;
        }
        if (is_null($this->MySQL) || !isset($this->MySQL) || !$this->MySQL || is_null($this->MySQL[$db]) || !isset($this->MySQL[$db]) || !$this->MySQL[$db]) {
            $this->MySQL[$db] = new MysqliDb(array(
                'host' => 'localhost',
                'username' => 'localzet',
                'password' => 'lvanZ2003',
                'db' => $db,
                'port' => 3306,
                'prefix' => '',
                'charset' => 'utf8'
            ));
            if (mysqli_connect_errno()) {
                printf("Подключение невозможно: %s\n", mysqli_connect_error());
                $this->MySQL[$db] = null;
                return false;
            }
        }
        return $this->MySQL[$db];
    }

    /**
     * @return \TOKEN
     */
    function TOKEN(): TOKEN
    {
        if (is_null($this->TOKEN) || !isset($this->TOKEN) || !$this->TOKEN) {
            $this->TOKEN = new TOKEN();
        }
        return $this->TOKEN;
    }

    /**
     *
     */
    function COUNTS()
    {
        $this->UPUSER();
        $this->UPREQUEST();
    }

    /**
     * @throws \Kreait\Firebase\Exception\DatabaseException
     */
    function UPREQUEST()
    {
        $counts =  $this->DB()->getReference('counts/requests')->getValue() + 1;
        $this->DB()->getReference('counts')->update(['requests' => $counts]);
    }

    /**
     * @throws \Kreait\Firebase\Exception\DatabaseException
     */
    function UPUSER()
    {
        $counts = array_key_last($this->DB()->getReference('users')->getValue());
        $this->DB()->getReference('counts')->update(['users' => $counts]);
    }
}
