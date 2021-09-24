<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

use Kreait\Firebase\Exception\DatabaseException;

class RECEIPT extends API
{

    /** Создание QR
     *
     *  var post = {
     *      points: "Кол-во начисляемых баллов",    // По умолчанию: 1
     *      content: {
     *          org: "Наименование организации",
     *          cashbox: "Номер кассы",
     *          num_receipt: "Номер чека",
     *          cashier: "Кассир",
     *          products: [{
     *              title: "Название товара",
     *              price: "Цена товара",
     *              discount: "Скидка",
     *              price_with_discount: "Цена со скидкой",
     *              count: "Кол-во",
     *              total: "Итого"
     *          }],
     *          discount: "Скидка",
     *          total_with_discount: "Подытог",
     *          total: "Итог",
     *          cash: "Наличными",
     *          accepted: "Принято",
     *          noncash: "Безналичными",
     *          change: "Сдача",
     *          vat: "НДС",
     *          tin: "ИНН",
     *          sno: "СНО",
     *          address: "Адресс точки",
     *          ZNKKT: "ЗН ККТ",
     *          RNKKT: "РН ККТ",
     *          FP: "ФП",
     *          FN: "ФН",
     *          FD: "ФД",
     *          shift: "смена",
     *          datetime: "Дата и время",
     *      }
     *  };
     *
     */
    function CREATE($data)
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);

        // Запись данных в базу
        if ($data['content']) {
            if (!$data['points']) {
                $data['points'] = 1;
            }
            $points = API::MySQL('receipt')->insert('qrs', ['points' => $data['points'], 'activate' => 0, 'content' => $data['content']]);
            if (!$points) return API::MySQL('receipt')->getLastError();
        }

        // Генерация токена
        $token = API::TOKEN()->CREATE(["points" => $points]);

        if ($token) {
            // TODO: Найти библиотеку генерации QR-кода
            // Пока возвращаем токен
            return $token;
        } else {
            http_response_code(401);
            return false;
        }
    }

    /** Получение баллов по QR
     *
     *  var post = {
     *      data: "Токен с id QR-кода",
     *      to: "Токен пользователя"
     *  };
     *
     */
    function GET($data)
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);

        // Декодируем данные, полученные с QR и системы
        $data = API::TOKEN()->DECODE($data['data']);
        $touser = API::TOKEN()->DECODE($data['to']);

        if ($touser && $touser['id'] && $data && $data['points']) {
            // Получаем пользователя
            API::MySQL('receipt')->where('id', $touser['id']);
            $user = API::MySQL('receipt')->getOne('users');
            if (!$user) return API::MySQL('receipt')->getLastError();

            // Получаем данные qr
            API::MySQL('receipt')->where('id', $data['points']);
            $qr = API::MySQL('receipt')->getOne('qrs');
            if (!$qr) return API::MySQL('receipt')->getLastError();

            // Получаем историю транзакций
            API::MySQL('receipt')->where('user', $touser['id']);
            $history = API::MySQL('receipt')->get('history');
            if (!$history) return API::MySQL('receipt')->getLastError();

            // Проверяем существование транзакции
            foreach ($history as $transaction) {
                if ($transaction['qr'] == $qr['id']) {
                    return json_encode(['error' => 'Баллы уже начислены!']);
                }
            }

            // Занесение транзакции в базу
            $idtransaction = API::MySQL('receipt')->insert('history', [
                'user' => $user['id'],
                'qr' => $qr['id'],
                'datetime' => floor(date('d.m.Y H:i')),
                'organization' => $qr['organization'],
                'sum' => $qr['sum'],
            ]);
            if (!$idtransaction) return API::MySQL('receipt')->getLastError();

            // Пометка о том, что QR отсканирован
            API::MySQL('receipt')->where('id', $qr['id']);
            $qr = API::MySQL('receipt')->update('qrs', ['activate' => 1]);
            if (!$qr) return API::MySQL('receipt')->getLastError();

            // И только теперь добавление баллов
            $add = $user['points'] + $qr['points'];
            API::MySQL('receipt')->where('id', $user['id']);
            $user = API::MySQL('receipt')->update('users', ['points' => $add]);
            if (!$user) return API::MySQL('receipt')->getLastError();

            // Возвращаем кол-во начисленных баллов
            return "Начисленно баллов: " . $qr['points'];
        } else {
            http_response_code(401);
            return "Ошибка декодирования";
        }
    }

    /** Получение истории транзакций
     *
     *  var post = {
     *      user: "Токен пользователя"
     *  };
     *
     */
    function LIST($data)
    {
        // Class => Array
        $data = json_decode(json_encode($data), true);

        $user = API::TOKEN()->DECODE($data['user']);
        if ($user) {
            API::MySQL('receipt')->where('user', $user['id']);
            $history = API::MySQL('receipt')->get('history');
            if (!$history) return API::MySQL('receipt')->getLastError();
            return $history;
        }
    }
}

/*
    qrs:
        id
        points      - Кол-во начисляемых баллов
        activate    - Состояние чека (0 - не сканирован, 1 - отсканирован)
        json        - Содержание чека в формате JSON
    
    history:
        id
        qr          - id QR-кода
        user        - id пользователя
        datetime    - Дата и время транзакции

    users:
        points      - кол-во баллов
*/