<?php
////////////////////////////////////////////////////////////////////////////////
/**
 * @author Ivan Zorin <creator@localzet.ru>
 * @license GNU General Public License v3.0
 * @copyright Zorin Projects <www.localzet.ru>
 */
////////////////////////////////////////////////////////////////////////////////

error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_WARNING ^ E_ERROR ^ E_STRICT);
// error_reporting (E_ALL); ini_set('display_errors', 1);

define('APP_ROOT', str_replace('\\', '/', getcwd()));
define('APP_TEMP_ROOT', APP_ROOT . '/temp');

require_once APP_ROOT . '/API.php';
require_once APP_ROOT . '/LWS.php';

// LOAD CONFIG FILE
$config = parse_ini_file(APP_ROOT . "/config.ini", true);

date_default_timezone_set($config['global']['timezone']);

$server = new LWS($config);
$server->startServer();
