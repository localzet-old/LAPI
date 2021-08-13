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
    function DB()
    {
        $factory = (new Factory())
            ->withDatabaseUri('https://localzet-db-default-rtdb.europe-west1.firebasedatabase.app')
            ->withServiceAccount(__DIR__ . '/localzet-db-firebase-adminsdk-uw91d-0ef5e80361.json');
        $DB = $factory->createDatabase();
        return $DB;
    }

    function TOKEN()
    {
        return new TOKEN();
    }

    function COUNTS() {
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
