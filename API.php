<?php
////////////////////////////////////////////////////////////////////////////////
/**
 * @author Ivan Zorin <creator@localzet.ru>
 * @license GNU General Public License v3.0
 * @copyright Zorin Projects <www.localzet.ru>
 */
////////////////////////////////////////////////////////////////////////////////

use Firebase\JWT\JWT;

abstract class API
{
	static private $CONFIG;
	static private $MySQL;
	static private $RTDB;
	static private string $JWTKEY = "31EFD9A378593B2A6E177615FB75A";

	public static function setConfig()
	{
		self::$CONFIG = parse_ini_file(APP_ROOT . "/config.ini", true);
	}

	public static function DB($db, $server = 'MySQL')
	{
		try {
			if ($server == 'MySQL') {
				if (is_null($db) || !isset($db) || !$db) {
					API::Response(API::Error("База данных не подключена"));
				}

				if (!self::$MySQL[$db]) {
					$connection = array(
						'host' => 'localhost',
						'username' => self::$CONFIG['database']['user'],
						'password' => self::$CONFIG['database']['pass'],
						'db' => $db,
						'port' => 3306,
						'prefix' => '',
						'charset' => 'utf8'
					);

					self::$MySQL[$db] = new MysqliDb($connection);
					if (mysqli_connect_errno()) {
						API::Response(API::Error("Подключение невозможно: %s\n", mysqli_connect_error()));
					}
				}

				return self::$MySQL[$db];
			}
		} catch (Exception $error) {
			API::Response(API::Error($error, 500));
		}
	}

	public static function EnToken($data, $service = "LDB")
	{
		try {
			$date = new DateTime();
			$date->setDate(date('Y'), date('m'), date('d') + 7);
			$exp = floor($date->format('U'));

			$token = array(
				"iss" => "Zorin Projects",
				"aud" => $service,
				"iat" => floor(time()),
				"exp" => $exp,
				"data" => $data
			);

			return json_decode(json_encode(JWT::encode($token, self::$JWTKEY)), true);
		} catch (Exception $error) {
			API::Response(API::Error($error, 500));
		}
	}

	public static function DeToken($token)
	{
		try {
			$date = new DateTime();
			$date = floor($date->format('U'));
			$data = JWT::decode($token, self::$JWTKEY, array('HS256'));
			if (
				$data &&
				$data->iss == "Zorin Projects" &&
				$data->iat <= $date &&
				$data->exp > $date
			) {
				return json_decode(json_encode($data->data), true);
			}
		} catch (Exception $error) {
			API::Response(API::Error($error, 500));
		}
	}

	public static function Logger($message, $V3 = false)
	{
		try {
			$type = gettype($message);

			$str = "";
			if ($type == 'array')
				$str = json_encode($message);
			else if ($type == 'string' || $type == 'integer' || $type == 'double')
				$str = $message;
			else if ($type == 'object') {
				$str = json_encode($message);
			}

			$str = "[" . date('D M d, Y G:i:s') . "] [" . debug_backtrace()[1]['function'] . "]" . $str . PHP_EOL;

			if (!$V3) {
				file_put_contents(APP_ROOT . '/Logger/WS_' . date("d-M-Y") . '.txt', $str, FILE_APPEND);
			} else {
				file_put_contents(APP_ROOT . '/Logger/V3_' . date("d-M-Y") . '.txt', $str, FILE_APPEND);
			}
		} catch (Exception $error) {
			API::Response(API::Error($error, 500));
		}
	}

	public static function Response($data, $connect = null)
	{
		try {
			if ($connect == null) {
				socket_write($connect, API::WSencode($data));
			} else {
				echo $data;
			}
			exit;
		} catch (Exception $error) {
			echo API::Error($error, 500);
		}
	}

	public static function Info($info, $code = 100)
	{
		return json_encode(array('status' => $code, 'info' => $info), JSON_UNESCAPED_UNICODE);
	}

	public static function Success($data, $code = 200)
	{
		return json_encode(array('status' => $code, 'data' => $data), JSON_UNESCAPED_UNICODE);
	}

	public static function Error($error, $code = 400)
	{
		return json_encode(array('status' => $code, 'error' => $error), JSON_UNESCAPED_UNICODE);
	}

	public static function getFileType($filename)
	{
		try {
			return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		} catch (Exception $error) {
			API::Response(API::Error($error, 500));
		}
	}

	public static function WSencode($payload, $type = 'text', $masked = false)
	{
		try {
			$frameHead = array();
			$payloadLength = strlen($payload);

			switch ($type) {
				case 'text':
					$frameHead[0] = 129;
					break;
				case 'close':
					$frameHead[0] = 136;
					break;
				case 'ping':
					$frameHead[0] = 137;
					break;
				case 'pong':
					$frameHead[0] = 138;
					break;
			}

			if ($payloadLength > 65535) {
				$payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
				$frameHead[1] = ($masked === true) ? 255 : 127;
				for ($i = 0; $i < 8; $i++) {
					$frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
				}
				if ($frameHead[2] > 127) {
					return array('type' => '', 'payload' => '', 'error' => 'frame too large (1004)');
				}
			} elseif ($payloadLength > 125) {
				$payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
				$frameHead[1] = ($masked === true) ? 254 : 126;
				$frameHead[2] = bindec($payloadLengthBin[0]);
				$frameHead[3] = bindec($payloadLengthBin[1]);
			} else {
				$frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
			}

			foreach (array_keys($frameHead) as $i) {
				$frameHead[$i] = chr($frameHead[$i]);
			}
			if ($masked === true) {
				$mask = array();
				for ($i = 0; $i < 4; $i++) {
					$mask[$i] = chr(rand(0, 255));
				}
				$frameHead = array_merge($frameHead, $mask);
			}
			$frame = implode('', $frameHead);

			for ($i = 0; $i < $payloadLength; $i++) {
				$frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
			}

			return $frame;
		} catch (Exception $error) {
			API::Response(API::Error($error, 500));
		}
	}

	public static function getMimeType($extension)
	{
		try {
			$mime_types = array(
				"mp4" => "video/mp4",
				"csv" => "text/csv",
				"pdf" => "application/pdf",
				"exe" => "application/octet-stream",
				"zip" => "application/zip",
				"docx" => "application/msword",
				"doc" => "application/msword",
				"xls" => "application/vnd.ms-excel",
				"ppt" => "application/vnd.ms-powerpoint",
				"gif" => "image/gif",
				"png" => "image/png",
				"jpeg" => "image/jpg",
				"jpg" => "image/jpg",
				"mp3" => "audio/mpeg",
				"wav" => "audio/x-wav",
				"mpeg" => "video/mpeg",
				"mpg" => "video/mpeg",
				"mpe" => "video/mpeg",
				"mov" => "video/quicktime",
				"avi" => "video/x-msvideo",
				"3gp" => "video/3gpp",
				"css" => "text/css",
				"jsc" => "application/javascript",
				"js" => "application/javascript",
				"php" => "text/html",
				"htm" => "text/html",
				"html" => "text/html",
				"swf" => "application/x-shockwave-flash",
				"xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
			);
			return $mime_types[$extension];
		} catch (Exception $error) {
			API::Response(API::Error($error, 500));
		}
	}
}
