<?php
////////////////////////////////////////////////////////////////////////////////
/**
 * @author Ivan Zorin <creator@localzet.ru>
 * @license GNU General Public License v3.0
 * @copyright Zorin Projects <www.localzet.ru>
 */
////////////////////////////////////////////////////////////////////////////////

require_once APP_ROOT . '/WSAPI.php';

/**
 * Localzet WebSocket Server.
 */
class LWS
{

    /**
     * Функция вызывается, когда получено сообщение от клиента
     */
    public $handler;

    /**
     * IP адрес сервера
     */
    private $ip;
    /**
     * Порт сервера
     */
    private $port;


    private $config;
    /**
     * Сокет для принятия новых соединений, прослушивает указанный порт
     */
    private $connection;
    /**
     * Для хранения всех подключений, принятых слушающим сокетом
     */
    private $connects;

    /**
     * Ограничение по времени работы сервера
     */
    private $timeLimit = 0;
    /**
     * Время начала работы сервера
     */
    private $startTime;
    /**
     * Выводить сообщения в консоль?
     */
    private $verbose = true;
    /**
     * Записывать сообщения в log-файл?
     */
    private $logging = true;
    /**
     * Имя log-файла
     */
    private $logFile = 'ws-log.txt';
    /**
     * Ресурс log-файла
     */
    private $resource;


    public function __construct($config)
    {
        $this->ip = $config['listen_ip'];
        $this->port = $config['listen_port'];
        $this->config = $config;

        $this->handler = function ($connection, $data) {
        };
    }

    public function __destruct()
    {
        if (is_resource($this->connection)) {
            $this->stopServer();
        }
        if ($this->logging) {
            fclose($this->resource);
        }
    }

    /**
     * Дополнительные настройки для отладки
     */
    public function settings($timeLimit = 0, $verbose = false, $logging = false, $logFile = 'ws-log.txt')
    {
        $this->timeLimit = $timeLimit;
        $this->verbose = $verbose;
        $this->logging = $logging;
        $this->logFile = $logFile;
        if ($this->logging) {
            $this->resource = fopen($this->logFile, 'a');
        }
    }

    /**
     * Запускает сервер в работу
     */
    public function startServer()
    {

        API::Logger('Запуск сервера...');

        $this->connection = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (false === $this->connection) {
            API::Logger('Ошибка socket_create(): ' . socket_strerror(socket_last_error()));
            return;
        }

        $bind = socket_bind($this->connection, $this->ip, $this->port); // привязываем к ip и порту
        if (false === $bind) {
            API::Logger('Ошибка socket_bind(): ' . socket_strerror(socket_last_error()));
            return;
        }

        // разрешаем использовать один порт для нескольких соединений
        $option = socket_set_option($this->connection, SOL_SOCKET, SO_REUSEADDR, 1);
        if (false === $option) {
            API::Logger('Ошибка socket_set_option(): ' . socket_strerror(socket_last_error()));
            return;
        }

        $listen = socket_listen($this->connection); // слушаем сокет
        if (false === $listen) {
            API::Logger('Ошибка socket_listen(): ' . socket_strerror(socket_last_error()));
            return;
        }

        API::Logger('Сервер запущен...');

        $this->connects = array($this->connection);
        $this->startTime = time();

        while (true) {

            API::Logger('Ожидание соединений...');

            // создаем копию массива, так что массив $this->connects не будет изменен функцией socket_select()
            $read = $this->connects;
            $write = $except = null;

            /*
             * Сокет $this->connection только прослушивает порт на предмет новых соединений. Как только поступило
             * новое соединение, мы создаем новый ресурс сокета с помощью socket_accept() и помещаем его в массив
             * $this->connects для дальнейшего чтения из него.
             */

            if (!socket_select($read, $write, $except, null)) { // ожидаем сокеты, доступные для чтения (без таймаута)
                break;
            }

            // если слушающий сокет есть в массиве чтения, значит было новое соединение
            if (in_array($this->connection, $read)) {
                // принимаем новое соединение и производим рукопожатие
                if (($connect = socket_accept($this->connection)) && $this->handshake($connect)) {
                    API::Logger('Новое соединение принято');
                    $this->connects[] = $connect; // добавляем его в список необходимых для обработки
                }
                // удаляем слушающий сокет из массива для чтения
                unset($read[array_search($this->connection, $read)]);
            }

            foreach ($read as $connect) { // обрабатываем все соединения, в которых есть данные для чтения
                $data = socket_read($connect, 100000);
                $decoded = self::decode($data);
                // если клиент не прислал данных или хочет разорвать соединение
                if (false === $decoded || 'close' === $decoded['type']) {
                    API::Logger('Закрытие соединения');
                    socket_write($connect, API::WSencode('Соединение закрыто клиентом', 'close'));
                    socket_shutdown($connect);
                    socket_close($connect);
                    unset($this->connects[array_search($connect, $this->connects)]);
                    API::Logger('Соединение закрыто');
                    continue;
                }
                // получено сообщение от клиента, вызываем пользовательскую
                // функцию, чтобы обработать полученные данные
                $th = new WSAPI($connect, $this->config, $decoded['payload']);
                exit(0);
            }

            // если истекло ограничение по времени, останавливаем сервер
            if ($this->timeLimit && time() - $this->startTime > $this->timeLimit) {
                API::Logger('Лимит времени. Остановка...');
                $this->stopServer();
                return;
            }
        }
    }

    /**
     * Останавливает работу сервера
     */
    public function stopServer()
    {
        // закрываем слушающий сокет
        socket_close($this->connection);
        if (!empty($this->connects)) { // отправляем все клиентам сообщение о разрыве соединения
            foreach ($this->connects as $connect) {
                // if (is_resource($connect)) {
                socket_write($connect, API::WSencode('Соединение закрыто сервером', 'close'));
                socket_shutdown($connect);
                socket_close($connect);
                // }
            }
        }
    }

    /**
     * Для декодирования сообщений, полученных от клиента
     */
    private static function decode($data)
    {
        if (!strlen($data)) {
            return false;
        }

        $unmaskedPayload = '';
        $decodedData = array();

        // estimate frame type:
        $firstByteBinary = sprintf('%08b', ord($data[0]));
        $secondByteBinary = sprintf('%08b', ord($data[1]));
        $opcode = bindec(substr($firstByteBinary, 4, 4));
        $isMasked = ($secondByteBinary[0] == '1') ? true : false;
        $payloadLength = ord($data[1]) & 127;

        // unmasked frame is received:
        if (!$isMasked) {
            return array('type' => '', 'payload' => '', 'error' => 'protocol error (1002)');
        }

        switch ($opcode) {
                // text frame:
            case 1:
                $decodedData['type'] = 'text';
                break;
            case 2:
                $decodedData['type'] = 'binary';
                break;
                // connection close frame:
            case 8:
                $decodedData['type'] = 'close';
                break;
                // ping frame:
            case 9:
                $decodedData['type'] = 'ping';
                break;
                // pong frame:
            case 10:
                $decodedData['type'] = 'pong';
                break;
            default:
                return array('type' => '', 'payload' => '', 'error' => 'unknown opcode (1003)');
        }

        if ($payloadLength === 126) {
            $mask = substr($data, 4, 4);
            $payloadOffset = 8;
            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
        } elseif ($payloadLength === 127) {
            $mask = substr($data, 10, 4);
            $payloadOffset = 14;
            $tmp = '';
            for ($i = 0; $i < 8; $i++) {
                $tmp .= sprintf('%08b', ord($data[$i + 2]));
            }
            $dataLength = bindec($tmp) + $payloadOffset;
            unset($tmp);
        } else {
            $mask = substr($data, 2, 4);
            $payloadOffset = 6;
            $dataLength = $payloadLength + $payloadOffset;
        }

        /**
         * We have to check for large frames here. socket_recv cuts at 1024 bytes
         * so if websocket-frame is > 1024 bytes we have to wait until whole
         * data is transferd.
         */
        if (strlen($data) < $dataLength) {
            return false;
        }

        if ($isMasked) {
            for ($i = $payloadOffset; $i < $dataLength; $i++) {
                $j = $i - $payloadOffset;
                if (isset($data[$i])) {
                    $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
                }
            }
            $decodedData['payload'] = $unmaskedPayload;
        } else {
            $payloadOffset = $payloadOffset - 4;
            $decodedData['payload'] = substr($data, $payloadOffset);
        }

        return $decodedData;
    }

    /**
     * «Рукопожатие», т.е. отправка заголовков согласно протоколу WebSocket
     */
    private function handshake($connect)
    {

        $info = array();

        $data = socket_read($connect, 1000);
        $lines = explode("\r\n", $data);
        foreach ($lines as $i => $line) {
            if ($i) {
                if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                    $info[$matches[1]] = $matches[2];
                }
            } else {
                $header = explode(' ', $line);
                $info['method'] = $header[0];
                $info['uri'] = $header[1];
            }
            if (empty(trim($line))) break;
        }

        // получаем адрес клиента
        $ip = $port = null;
        if (!socket_getpeername($connect, $ip, $port)) {
            return false;
        }
        $info['ip'] = $ip;
        $info['port'] = $port;

        if (empty($info['Sec-WebSocket-Key'])) {
            return false;
        }

        // отправляем заголовок согласно протоколу вебсокета
        $SecWebSocketAccept =
            base64_encode(pack('H*', sha1($info['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept:" . $SecWebSocketAccept . "\r\n\r\n";
        socket_write($connect, $upgrade);

        return true;
    }
}
