<?php
////////////////////////////////////////////////////////////////////////////////
/**
 * @author Ivan Zorin <creator@localzet.ru>
 * @license GNU General Public License v3.0
 * @copyright Zorin Projects <www.localzet.ru>
 */
////////////////////////////////////////////////////////////////////////////////

require_once APP_ROOT . "/API.php";
API::setConfig();

// Определение типа вывода
$OUTPUT_CONTENT_TYPE = "application/json";

function set_output_content_type($type)
{
	global $OUTPUT_CONTENT_TYPE;
	$OUTPUT_CONTENT_TYPE = $type;
}

// ----------------------------------------------------------------------------------------------------- //
// --------------------------------API & METHOD -------------------------------------------------------- //
// ----------------------------------------------------------------------------------------------------- //

// Логируем POST
API::Logger('POST >> ' . $_POST . ' << POST', true);

// Логируем
API::Logger('GET >> ' . $_GET . ' << GET', true);

// Задаём переменные для Класса >> Функции >> Аргументов

// API = Класс (файл)
$api = "";
// METHOD = Функция
$method = "";
// PARAMS = Аргументы
$arguments = "";

// API & METHOD
if (isset($_GET['api'])) {
	$req = explode(":", $_GET['api']);
	$api = $req[0];
	$method = $req[1];
} else if (isset($_POST['api'])) {
	$req = explode(":", $_POST['api']);
	$api = $req[0];
	$method = $req[1];
}

// PARAMS
if (isset($_GET['param'])) {
	$arguments = $_GET['param'];
} else if (isset($_POST['param'])) {
	$arguments = $_POST['param'];
}

// Проверка API
if ($api == "") {
	API::Response(API::Error("API не указан"));
}

// Проверка метода
if ($method == "") {
	API::Response(API::Error("Метод не указан"));
}

// Проверка аргументов
if (strlen($arguments) > 0) {
	$arguments = json_decode($arguments, true);
	if ($arguments === FALSE) {
		API::Response(API::Error("Недостаточно аргументов"));
	}
}

// Собираем путь
$class_file = APP_ROOT . "/API/" . $api . ".php";

// Проверка существования
if (!is_file($class_file)) {
	API::Response(API::Error("API не найден"));
}

// ----------------------------------------------------------------------------------------------------- //
// ------------------------------------Инструментарный сервис------------------------------------------- //
// ----------------------------------------------------------------------------------------------------- //

// Включаем
require_once $class_file;

// Собираем
$rClass = new ReflectionClass($api);
$obj = $rClass->newInstanceArgs();

//Проверка существования метода
if (!method_exists($obj, $method)) {
	API::Response(API::Error("Метода не существует"));
}

// ЭТО ДОЛЖНО БЫТЬ МАССИВОМ!
if (!is_array($arguments))
	$arguments = array($arguments);

// ----------------------------------------------------------------------------------------------------- //
// --------------------------------------METHOD CALL --------------------------------------------------- //
// ----------------------------------------------------------------------------------------------------- //

// Даже если этот с*чий выродок отвалился по дороге - не останавливайся, иди до конца!
ignore_user_abort(true);

// >> Отладка:
$call_id = uniqid("CALL_");

// Логируем всё, что дышит
API::Logger(REQUEST_URL . "?api=$api:$method&param=" . json_encode($arguments), true);
API::Logger("$call_id -----------------> [$api][$method][" . json_encode($arguments) . "]", true);

// Время начала ответа
$start_time = microtime(true);

// Вызываем демонов (метод)
$response = call_user_func_array(array($obj, $method), $arguments);

// Если это массив или Std - переводи в JSON
if (is_array($response) || is_object($response)) {
	$response = json_encode($response);
}

// Время окончания
$end_time = microtime(true);

// Затраченное время
$seconds_consumed = $end_time - $start_time;

// Тип и время ответа
header("content-type: $OUTPUT_CONTENT_TYPE");
header("response-time: <" . round($seconds_consumed, 4) . "> seconds");

// Вывод
API::Response(API::Success($response));

// Очистка
ob_flush();
flush();

// Логируем
API::Logger("$call_id <----------------- Отправлен ответ: " . strlen($response) . " bytes", true);
API::Logger($response, true);
