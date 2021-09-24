<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

use Kreait\Firebase\Exception\DatabaseException;

class LDB extends API
{

    /** Создание пользователя
     *
     *  var post = {
     *      ...: "Данные пользователя"
     *  };
     *
     * @param $data
     * @return bool|string
     * @throws Exception
     */
    function CREATE($data)
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);
        $db = $data['service'];
        unset($data['service']);

        // Поиск существующих "username" и "email"
        foreach (API::MySQL($db)->get('users') as $user) {
            if ($data['username'] == $user['username'] || $data['email'] == $user['email']) {
                http_response_code(401);
                return "Пользователь уже существует";
            }
        }

        // Отправка данных
        if (!$data['type']) {
            $data['type'] = 'student';
        }
        $data['password'] = md5($data['password']);
        if ($db == 'receipt') {
            $data['points'] = 0;
        }
        $data['id'] = API::MySQL($db)->insert('users', $data);
        if ($data['id']) {
            // Генерация токена и обновление данных
            $data['token'] = API::TOKEN()->CREATE($data);
            $data['updated'] = floor(time());
            API::MySQL($db)->where('id', $data['id']);
            if (API::MySQL($db)->update('users', $data)) {
                return true;
            } else {
                http_response_code(401);
                return API::MySQL($db)->getLastError();
            }
        } else {
            http_response_code(401);
            return API::MySQL($db)->getLastError();
        }
    }

    /** Получение информации о пользователе
     *
     *  var post = {
     *      token: "Токен"
     *  };
     *
     * @param $data
     * @return string|array|MysqliDb
     * @throws Exception
     */
    function GET($data)
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);
        $db = $data['service'];
        unset($data['service']);

        $user = API::TOKEN()->DECODE($data['token']);
        if ($user) {
            API::MySQL($db)->where('id', $user['id']);
            $userbd = API::MySQL($db)->getOne('users');
            if ($userbd) {
                $userbd['token'] = API::TOKEN()->CREATE($userbd);
                $userbd['updated'] = API::MySQL($db)->now();
                API::MySQL($db)->where('id', $userbd['id']);
                if (API::MySQL($db)->update('users', $userbd)) {
                    unset($userbd['password']);
                    unset($userbd['code']);
                    return $userbd;
                } else {
                    http_response_code(401);
                    return API::MySQL($db)->getLastError();
                }
                http_response_code(401);
                return "Не найдено совпадений в БД по id из токена";
            } else {
                http_response_code(401);
                return API::MySQL($db)->getLastError();
            }
        } else {
            http_response_code(401);
            return "Ошибка декодирования токена";
        }
    }

    /** Редактирование пользователя
     *
     *  var post = {
     *      token: "Токен",
     *      ...: "Новые данные"
     *  };
     *
     * @param $data
     * @return bool|string|array|MysqliDb
     * @throws Exception
     */
    function EDIT($data)
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);
        $db = $data['service'];
        unset($data['service']);
        $result = [
            'first_name' => $data['user']['first_name'],
            'last_name' => $data['user']['last_name'],
            'username' => $data['user']['username'],
            'type' => $data['user']['type'],
            'email' => $data['user']['email']
        ];
        API::MySQL($db)->where('id', $data['user']['id']);
        if (API::MySQL($db)->update('users', $result)) {
            return true;
        } else {
            http_response_code(401);
            return API::MySQL($db)->getLastError();
        }
    }

    /** Вход в систему
     *
     *  var post = {
     *      login: "Логин",
     *      password: "Пароль"
     *  };
     *
     * @param $data
     * @return string|array|MysqliDb
     * @throws Exception
     */
    function SIGNIN($data)
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);
        $db = $data['service'];
        unset($data['service']);

        if (
            trim($data['login']) == '' ||
            trim($data['password']) == ''
        ) {
            http_response_code(401);
            return "Нет логина и/или пароля";
        }

        $users = API::MySQL($db)->get('users');
        if ($users && $data) {
            foreach ($users as $user) {
                if ($user['email'] == $data['login'] || $user['username'] == $data['login']) {
                    if (md5($data['password']) == $user['password']) {
                        $user['token'] = API::TOKEN()->CREATE($user);
                        $user['updated'] = API::MySQL($db)->now();
                        API::MySQL($db)->where('id', $user['id']);
                        if (API::MySQL($db)->update('users', $user)) {
                            unset($user['password']);
                            unset($user['code']);
                            return $user;
                        } else {
                            http_response_code(401);
                            return API::MySQL($db)->getLastError();
                        }
                    } else {
                        http_response_code(401);
                        return "Неверный пароль";
                    }
                }
            }
            http_response_code(404);
            return "Ползователь не найден";
        } else {
            http_response_code(401);
            return "Ошибка БД";
        }
    }

    /** Смена / Сброс пароля
     *
     *  var post = {
     *      password: "Новый пароль",
     *      token: "Токен",
     *  };
     *
     * @param $data
     * @return bool|string
     * @throws Exception
     */
    function PASS($data)
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);
        $user = $this->GET($data);
        $db = $data['service'];
        unset($data['service']);

        if ($data && $user && $data['password']) {
            $user['password'] = $data['password'];
            // $user['updated_pass'] = API::MySQL($db)->now();
            // Создай поле для подписи "Последнее обновление пароля"
            API::MySQL($db)->where('id', $user['id']);
            if (API::MySQL($db)->update('users', $user)) {
                return true;
            } else {
                http_response_code(401);
                return API::MySQL($db)->getLastError();
            }
        } else {
            http_response_code(401);
            return false;
        }
    }

    /** Восстановление пароля
     *
     *  var post = {
     *      email: "Электронная почта"
     *  };
     *
     * @param $data
     * @return bool
     * @throws Exception
     */
    function FORGOT($data): bool
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);
        $db = $data['service'];
        unset($data['service']);

        API::MySQL($db)->where("email", $data['email']);
        $user = API::MySQL($db)->getOne("users");
        if ($data['email'] == $user['email']) {
            return true;
        }

        http_response_code(401);
        return false;
    }

    function NAVIGATION($data)
    {
        $data = json_decode(json_encode($data), true);
        $db = $data['service'];
        unset($data['service']);

        $page = explode('/', $data['state'])[1];
        if ($page == "dashboard" || $page == "apps") {
            $state = "main";
        } else {
            $state = $page;
        }
        API::MySQL($db)->where("level", $data['level']);
        API::MySQL($db)->where("type", $state);
        $navigation = API::MySQL($db)->getOne("navigation");
        if (!$navigation) {
            API::MySQL($db)->where("level", "all");
            API::MySQL($db)->where("type", $state);
            $navigation = API::MySQL($db)->getOne("navigation");
        }
        if ($state == $navigation['type']) {
            return json_decode($navigation['json']);
        }
        return false;
    }

    function CONFIG()
    {
        return "Конфигурация"; // Здесь должно быть что-то типа получения массива из БД
    }
}
