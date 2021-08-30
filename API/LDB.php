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
        $data['password'] = md5($data['password']);
        $data['id'] = API::MySQL($db)->insert('users', $data);

        // Генерация токена и обновление данных
        $data['token'] = API::TOKEN()->CREATE($data);
        API::MySQL($db)->where('id', $data['id']);
        if (API::MySQL($db)->update('users', $data)) {
            return true;
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
            $users = API::MySQL($db)->get('users');
            if ($users) {
                foreach ($users as $userbd) {
                    if ($userbd['id'] == $user['id']) {
                        $userbd['token'] = API::TOKEN()->CREATE($userbd);
                        API::MySQL($db)->where('id', $userbd['id']);
                        if (API::MySQL($db)->update('users', $userbd)) {
                            unset($userbd['password']);
                            unset($userbd['code']);
                            return $userbd;
                        } else {
                            http_response_code(401);
                            return API::MySQL($db)->getLastError();
                        }
                    }
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

        $user = $this->GET($data['user']);
        if ($user && $user['id'] == $data['id']) {
            API::MySQL($db)->where('id', $user['id']);
            if (API::MySQL($db)->update('users', $data)) {
                $userbd = API::MySQL($db)->get('users')[$user['id']];
                unset($userbd['password']);
                unset($userbd['code']);
                return $userbd;
            } else {
                http_response_code(401);
                return API::MySQL($db)->getLastError();
            }
        } else {
            http_response_code(401);
            return false;
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
        $db = $data['service'];
        unset($data['service']);

        $user = $this->GET($data);
        if ($data && $user && $data['password']) {
            $user['password'] = $data['password'];
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
        API::MySQL($db)->where("type", $state);
        $navigation = API::MySQL($db)->getOne("navigation");
        if ($state == $navigation['type']) {

            return json_decode($navigation['json']);
        }
    }

    function CONFIG()
    {
        return "Конфигурация"; // Здесь должно быть что-то типа получения массива из БД
    }
}
