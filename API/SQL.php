<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

class SQL extends API
{

    /** Создание пользователя
     * 
     *  var data = {
     *      ...: "Данные пользователя"
     *  };
     * 
     * https://api.localzet.ru/?LDB.CREATE={...:"Данные пользователя"}
     */
    function CREATE($data)
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);

        // Поиск существующих "username" и "email"
        foreach (API::MySQL()->get('users') as $user) {
            if ($data['username'] == $user['username'] || $data['email'] == $user['email']) {
                http_response_code(401);
                API::COUNTS();
                return "Пользователь уже существует";
            }
        }

        // Отправка данных
        $data['password'] = md5($data['password']);
        $data['id'] = API::MySQL()->insert('users', $data);

        // Генерация токена и обновление данных
        $data['token'] = API::TOKEN()->CREATE($data);
        API::MySQL()->where('id', $data['id']);
        if (API::MySQL()->update('users', $data)) {
            API::COUNTS();
            return true;
        } else {
            http_response_code(401);
            API::COUNTS();
            return API::MySQL()->getLastError();
        }
    }

    /** Получение информации о пользователе
     * 
     *  var data = {
     *      token: "Токен"
     *  };
     * 
     * https://api.localzet.ru/?LDB.GET
     */
    function GET($data)
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);

        $user = API::TOKEN()->DECODE($data['token']);
        if ($user) {
            $users = API::MySQL()->get('users');
            if ($users) {
                foreach ($users as $userbd) {
                    if ($userbd['id'] == $user['id']) {
                        $userbd['token'] = API::TOKEN()->CREATE($userbd);
                        API::MySQL()->where('id', $userbd['id']);
                        if (API::MySQL()->update('users', $userbd)) {
                            API::COUNTS();
                            return $userbd;
                        } else {
                            http_response_code(401);
                            API::COUNTS();
                            return API::MySQL()->getLastError();
                        }
                    }
                }
                http_response_code(401);
                API::COUNTS();
                return "Не найдено совпадений в БД по id из токена";
            } else {
                http_response_code(401);
                API::COUNTS();
                return API::MySQL()->getLastError();
            }
        } else {
            http_response_code(401);
            API::COUNTS();
            return "Ошибка декодирования токена";
        }
    }

    /** Редактирование пользователя
     * 
     *  var data = {
     *      token: "Токен",
     *      ...: "Новые данные"
     *  };
     * 
     * https://api.localzet.ru/?LDB.EDIT
     */
    function EDIT($data)
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);

        $user = $this->GET($data);
        if ($user && $user['id'] == $data['id']) {
            API::MySQL()->where('id', $user['id']);
            if (API::MySQL()->update('users', $data)) {
                API::COUNTS();
                return API::MySQL()->get('users')[$user['id']];
            } else {
                http_response_code(401);
                API::COUNTS();
                return API::MySQL()->getLastError();
            }
        } else {
            http_response_code(401);
            API::COUNTS();
            return false;
        }
    }

    /** Вход в систему
     * 
     *  var data = {
     *      login: "Логин",
     *      password: "Пароль"
     *  };
     * 
     * https://api.localzet.ru/?LDB.SIGNIN
     */
    function SIGNIN($data)
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);

        if (
            trim($data['login']) == '' ||
            trim($data['password']) == ''
        ) {
            http_response_code(401);
            API::COUNTS();
            return "Нет логина и/или пароля";
        }

        $users = API::MySQL()->get('users');
        if ($users && $data) {
            foreach ($users as $user) {
                if ($user['email'] == $data['login'] || $user['username'] == $data['login']) {
                    if (md5($data['password']) == $user['password']) {
                        $user['token'] = API::TOKEN()->CREATE($user);
                        API::MySQL()->where('id', $user['id']);
                        if (API::MySQL()->update('users', $user)) {
                            API::COUNTS();
                            return $user;
                        } else {
                            http_response_code(401);
                            API::COUNTS();
                            return API::MySQL()->getLastError();
                        }
                    } else {
                        http_response_code(401);
                        API::COUNTS();
                        return "Неверный пароль";
                    }
                }
            }
            http_response_code(404);
            API::COUNTS();
            return "Ползователь не найден";
        } else {
            http_response_code(401);
            API::COUNTS();
            return "Ошибка БД";
        }
    }

    /** Смена / Сброс пароля
     * 
     *  var data = {
     *      password: "Новый пароль",
     *      token: "Токен",
     *  };
     * 
     * https://api.localzet.ru/?LDB.PASS
     */
    function PASS($data)
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);

        $user = $this->GET($data);
        if ($data && $user && $data['password']) {
            $user['password'] = $data['password'];
            API::MySQL()->where('id', $user['id']);
            if (API::MySQL()->update('users', $user)) {
                API::COUNTS();
                return true;
            } else {
                http_response_code(401);
                API::COUNTS();
                return API::MySQL()->getLastError();
            }
        } else {
            http_response_code(401);
            API::COUNTS();
            return false;
        }
    }

    /** Восстановление пароля
     *  
     *  var data = {
     *      email: "Электронная почта"
     *  };
     * 
     * https://api.localzet.ru/?LDB.FORGOT
     */
    function FORGOT($data)
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);

        $users = API::MySQL()->get('users');
        if ($users) {
            foreach ($users as $user) {
                if ($data['email'] == $user['email']) {
                    API::COUNTS();
                    return true;
                }
            }
            http_response_code(401);
            API::COUNTS();
            return false;
        } else {
            http_response_code(401);
            API::COUNTS();
            return false;
        }
    }


    function COUNTUSERS()
    {
        API::COUNTS();
        return API::DB()->getReference('counts/users')->getValue();
    }

    function COUNTREQUESTS()
    {
        API::COUNTS();
        return API::DB()->getReference('counts/requests')->getValue();
    }
}
