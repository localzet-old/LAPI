<?php
////////////////////////////////////////////////////////////////////////////////
/**
 * @author Ivan Zorin <creator@localzet.ru>
 * @license GNU General Public License v3.0
 * @copyright Zorin Projects <www.localzet.ru>
 */
////////////////////////////////////////////////////////////////////////////////

// =======================================================================//
// Composer libs
// =======================================================================//

require_once './vendor/autoload.php';

// =======================================================================//
// ЗАГОЛОВКИ
// =======================================================================//

error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_WARNING ^ E_ERROR ^ E_STRICT);
//error_reporting (E_ALL); ini_set('display_errors', 1);

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');

    // TODO: Здесь будет подключение к БД и запрос списка разрешённых серверов в $Origins

    // foreach (CONF::$Origins as $Origin) {
    //     if (in_array($_SERVER['HTTP_ORIGIN'], CONF::$Origins) || preg_match('#' . $Origin . '#', $_SERVER['HTTP_ORIGIN'])) {
    //         header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    //         break;
    //     }
    // }
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    // header("Access-Control-Allow-Headers: Authorization, Origin, Accept, X-Requested-With, Content-Type, Cache-Control, Access-Control-Allow-Headers");
    exit(0);
}

header('Content-type: application/json; charset=UTF-8');


// =======================================================================//
// КОНСТАНТЫ
// =======================================================================//

// /var/www/fastuser/data/www/api.localzet.ru
define('APP_ROOT', str_replace('\\', '/', getcwd()));

if ($_SERVER['SERVER_PORT'] != 80 || $_SERVER['SERVER_PORT'] != 443) {
    // https://api.localzet.ru:2021/
    // https://api.localzet.ru:2021/script.php
    define('APP_WEB_ROOT', dirname('https://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['SCRIPT_NAME']));

    // https://api.localzet.ru:2021
    define('APP_SERVER_WEB_ROOT', 'https://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT']);
} else {
    // https://api.localzet.ru/
    // https://api.localzet.ru/script.php
    define('APP_WEB_ROOT', dirname('https://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME']));

    // https://api.localzet.ru
    define('APP_SERVER_WEB_ROOT', 'https://' . $_SERVER['SERVER_NAME']);
}

// /var/www/fastuser/data/www/api.localzet.ru/temp
define('APP_TEMP_ROOT', APP_ROOT . "/temp");

// https://api.localzet.ru/temp
define('APP_TEMP_WEB_ROOT', APP_WEB_ROOT . "/temp");

// GET, POST, OPTIONS
define('REQUEST_TYPE', strtoupper($_SERVER['REQUEST_METHOD']));

if ($_SERVER['SERVER_PORT'] != 80 || $_SERVER['SERVER_PORT'] != 443) {
    // https://api.localzet.ru:2021/v3?api=Class:Func&param=Param
    define('REQUEST_URL', 'https://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI']);
} else {
    // https://api.localzet.ru/v3?api=Class:Func&param=Param
    define('REQUEST_URL', 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
}

// /v3?api=Class:Func&param=Param
define('COMMAND_URL', str_replace(APP_WEB_ROOT, '', REQUEST_URL));

require_once APP_ROOT . '/API.php';

// [0] => '/v3'
// [1] => 'api=Class:Func&param=Param'
$commandSplit = explode('?', COMMAND_URL);

// Импорт конфигурации
$config = parse_ini_file(APP_ROOT . "/config.ini", true);


// =======================================================================//
// РЕСУРСЫ & РАСПОЛОЖЕНИЕ
// =======================================================================//

// v3
// v3.php
$RESOURCE_NAME = preg_replace('/^\/|\/$/', '', $commandSplit[0]);

// /var/www/fastuser/data/www/api.localzet.ru/v3
// /var/www/fastuser/data/www/api.localzet.ru/v3.php
$RESOURCE_PATH = APP_ROOT . '/' . $RESOURCE_NAME;

// https://api.localzet.ru/v3
// https://api.localzet.ru/v3.php
$RESOURCE_WEB_PATH = APP_WEB_ROOT . '/' . $RESOURCE_NAME;

// Просто вывод расширения
$fileType = API::getFileType($RESOURCE_PATH);

if (is_file($RESOURCE_PATH)) {
    if ($fileType == 'php') {
        echo "Вывод1";
        // /var/www/fastuser/data/www/api.localzet.ru/v3.php
        include $RESOURCE_PATH;
    } else {
        header('Content-type: ' . API::getMimeType($fileType));
        header('content-length: ' . filesize($RESOURCE_PATH));
        echo file_get_contents($RESOURCE_PATH);
    }
    exit();
}


// =======================================================================//
// ИНТЕРПРЕТАЦИЯ
// =======================================================================//

// Это осталось от какого-то костыля...

/*

    То есть оно хочет добавлять к RESOURCE_PATH .php? 
    В надежде, что это название файла?
    Чел, пересмотри своё отношение к жизни, ты безполезный костыль...

    RESOURCE_PATH = APP_ROOT . '/' . RESOURCE_NAME
    APP_ROOT = '/var/www/fastuser/data/www/api.localzet.ru'
    RESOURCE_NAME = ...

    Только через мой т... погоди, а если отправить запрос на https://api.localzet.ru/v3
    Он не воспримет v3 как файл...
    Ладно, ты нужен на всякий случай...

*/

// /var/www/fastuser/data/www/api.localzet.ru/v3.php
$RESOURCE_PATH .= ".php";
if (!is_file($RESOURCE_PATH)) {
    API::Response(API::Error("Запрашиваемый ресурс не найден", 404));
}
require_once $RESOURCE_PATH;

exit();
