<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

class LDB extends API
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
        foreach (API::DB()->getReference('users')->getValue() as $user) {
            if ($data->username == $user['username'] || $data->email == $user['email']) {
                http_response_code(401);
                API::COUNTS();
                return "Пользователь уже существует";
                // $data->id = $user['id'];
                // $data->token = $user['token'];
                // $data->password = md5($data->password);
                // $this->EDIT($data);
            }
        }
        $data->id = array_key_last(API::DB()->getReference('users')->getValue()) + 1;
        $data->password = md5($data->password);
        API::DB()->getReference('users/' . $data->id)->set($data);
        unset($data->token);
        unset($data->password);
        API::DB()->getReference('users/' . $data->id . '/token')->set(API::TOKEN()->CREATE($data));
        if (API::DB()->getReference('users/' . $data->id)->getValue()['username'] == $data->username) {
            API::COUNTS();
            return true;
        }
        http_response_code(401);
        API::COUNTS();
        return false;
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
        $user = API::TOKEN()->DECODE($data->token);
        if ($user) {
            $users = API::DB()->getReference('users')->getValue();
            if ($users) {
                foreach ($users as $userbd) {
                    if ($userbd['id'] == $user->id) {
                        unset($userbd['token']);
                        unset($userbd['password']);
                        $userbd['token'] = API::TOKEN()->CREATE($userbd);
                        API::DB()->getReference('users/' . $userbd['id'])->update(['token' => $userbd['token']]);
                        API::COUNTS();
                        return $userbd;
                    }
                }
                // http_response_code(401);
                API::COUNTS();
                return "Не найдено совпадений в БД по id из токена: " . $user->id;
            } else {
                // http_response_code(401);
                API::COUNTS();
                return "Ошибка ДБ";
            }
        } else {
            // http_response_code(401);
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
        $user = $this->GET($data);
        if ($user && $user['id'] == $data->id) {
            API::DB()->getReference('users')->update([$user['id'] => $data]);
            API::COUNTS();
            return API::DB()->getReference('users/' . $user['id'])->getValue();
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
        if (
            trim($data->login) == '' ||
            trim($data->password) == ''
        ) {
            http_response_code(401);
            API::COUNTS();
            return "Нет логина или пароля";
        }

        $users = API::DB()->getReference('users')->getValue();
        if ($users && $data) {
            foreach ($users as $user) {
                if ($user['email'] == $data->login || $user['username'] == $data->login) {
                    if (md5($data->password) == $user['password']) {
                        unset($user['password']);
                        unset($user['token']);
                        $user['token'] = API::TOKEN()->CREATE($user);
                        API::DB()->getReference('users/' . $user['id'] . '/token')->set($user['token']);
                        API::COUNTS();
                        return $user;
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
        $password = $data->password;
        $user = $this->GET($data)->user;
        if ($data && $user && $password) {
            API::DB()->getReference('users/' . $user->id)->update(['password' => $password]);
            API::COUNTS();
            return true;
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
        $email = $data->email;
        $users = API::DB()->getReference('users')->getValue();
        if ($users) {
            foreach ($users as $user) {
                if ($email == $user['email']) {
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
