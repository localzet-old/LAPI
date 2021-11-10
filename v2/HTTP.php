<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

class HTTP_Code
{
    static public function httpState($code)
    {
        switch ($code) {
                // 1XX
            case 100:
                return "Продолжай";
                break;
            case 101:
                return "Переключение протоколов";
                break;
            case 102:
                return "Обработка";
                break;
            case 103:
                return "Метаданные";
                break;
                // 2XX
            case 200:
                return "ОК";
                break;
            case 201:
                return "Создано";
                break;
            case 202:
                return "Принято";
                break;
            case 203:
                return "Информация не авторитетна";
                break;
            case 204:
                return "Нет содержимого";
                break;
            case 205:
                return "Сброс содержимого";
                break;
            case 206:
                return "Частичное содержимое";
                break;
            case 207:
                return "Мультистатус";
                break;
            case 208:
                return "Уже существует";
                break;
                // 3XX
            case 300:
                return "Множественный выбор";
                break;
            case 301:
                return "Перемещено навсегда";
                break;
            case 302:
                return "Перемещено временно";
                break;
            case 303:
                return "См. другое";
                break;
            case 304:
                return "Не изменялось";
                break;
            case 305:
                return "Используй прокси";
                break;
            case 307:
                return "Временное перенаправление";
                break;
            case 308:
                return "Постоянное перенаправление";
                break;
                // 4XX
        }
    }
}
