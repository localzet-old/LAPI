<?php
////////////////////////////////////////////////////////////////////////////////
/**
 * @author Ivan Zorin <creator@localzet.ru>
 * @license GNU General Public License v3.0
 * @copyright Zorin Projects <www.localzet.ru>
 */
////////////////////////////////////////////////////////////////////////////////

class WSAPI extends API
{

	protected $_socket;
	protected $_config;
	protected $_request_data;

	public function __construct($socket, $config, $request_data)
	{
		$this->_socket = $socket;
		$this->_config = $config;
		$this->_request_data = $request_data;
		$this->run();
	}

	public function run()
	{
		$request_obj = json_decode($this->_request_data, true);
		if ($request_obj === FALSE) {
			API::Response(API::Error("Пожалуйста, отправьте запрос API в формате JSON"), $this->_socket);
			return 0;
		}

		// API = Класс (файл)
		$api = "";
		// METHOD = Функция
		$method = "";
		// PARAMS = Аргументы
		$arguments = "";

		if (isset($request_obj['api'])) {
			$req = explode(":", $request_obj['api']);
			$api = $req[0];
			$method = $req[1];
		}

		// PARAMS
		$arguments = "";
		if (isset($request_obj['param']))
			$arguments = $request_obj['param'];


		// Проверка API
		if ($api == "") {
			API::Response(API::Error("API не указан"), $this->_socket);
			exit;
		}

		// Проверка метода
		if ($method == "") {
			API::Response(API::Error("Метод не указан"), $this->_socket);
			exit;
		}

		// Проверка аргументов
		if (strlen($arguments) > 0) {
			$arguments = json_decode($arguments, true);
			if ($arguments === FALSE) {
				API::Response(API::Error("Недостаточно аргументов"), $this->_socket);
				exit;
			}
		}

		// Собираем путь
		$class_file = APP_ROOT . "/API/" . $api . ".php";

		// Проверка существования
		if (!is_file($class_file)) {
			API::Response(API::Error("API не найден"), $this->_socket);
			return 0;
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
			API::Response(API::Error(false, "Метода не существует"), $this->_socket);
			return 0;
		}

		// ЭТО ДОЛЖНО БЫТЬ МАССИВОМ!
		if (!is_array($arguments))
			$arguments = array($arguments);

		$api_call_response = call_user_func_array(array($obj, $method), $arguments);
		$final_response = API::Success($api_call_response);
		$final_response = json_encode($final_response);

		API::Response($final_response, $this->_socket);
	}
}
