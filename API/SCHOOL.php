<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

use Kreait\Firebase\Exception\DatabaseException;

class SCHOOL extends API
{
    function CLASSES($data)
    {
        if ($data['teacher']) {
            // Получаем предмет(ы), который ведёт учитель
            API::MySQL('school')->where('teacher', $data['teacher']);
        }
        if ($data['admin'] || $data['teacher']) {
            $subjects = API::MySQL('school')->get('subjects');
            $result = [];
            foreach ($subjects as $subject) {
                // Получаем класс по id, указанному в subject
                API::MySQL('school')->where('id', $subject['class']);
                $class = API::MySQL('school')->getOne('classes');
                // Формируем структуру ответа
                $result[$subject['name']]['title'] = $class['name'];
                $result[$subject['name']]['live'] = $subject['live'];
            }
            if ($result) {
                return $result;
            }
            http_response_code(404);
            return "Пустой результат";
        }
        http_response_code(403);
        return "Недостаточно прав пользователя";
    }
    /*  Примерный вид ответа:

        {
            [
                {
                    Математика: [{
                        title: 8А,
                        live: LIVEID
                    },
                    {
                        title: 8Б,
                        live: LIVEID
                    }]
                },
                {
                    Русский язык: [{
                        title: 8А,
                        live: LIVEID
                    },
                    {
                        title: 8Б,
                        live: LIVEID
                    }]
                },
            ]
        }
    */

    function SUBJECTS($data)
    {
        if ($data['class']) {
            API::MySQL('school')->where('id', $data['class']);
        }
        if ($data['admin'] || $data['class']) {
            $classes = API::MySQL('school')->get('classes');
            $result = [];
            foreach ($classes as $class) {
                // Получаем класс по id, указанному в subject
                API::MySQL('school')->where('class', $class['id']);
                $subjects = API::MySQL('school')->get('subjects');
                // Формируем структуру ответа
                foreach ($subjects as $subject) {
                    $result[$class['name']]['title'] = $subject['name'];
                    $result[$class['name']]['live'] = $subject['live'];
                }
            }
            if ($result) {
                return $result;
            }
            http_response_code(404);
            return "Пустой результат";
        }
        http_response_code(403);
        return "Недостаточно прав пользователя";
    }
}
/* Доп. БД:
*   subjects : id, name, 
*/
