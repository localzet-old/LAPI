<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

class Jupiter extends API
{
    function dashboard($data)
    {
        $data = json_decode(json_encode($data), true);
        API::MySQL('jupiter')->where('user_id', $data['id']);
        return API::MySQL('jupiter')->get('meetings')->orderBy('id', 'DESC');
    }

    function createMeeting($data)
    {
        $data = json_decode(json_encode($data), true);
        $meeting = $data;
        $meeting['id'] = API::MySQL('jupiter')->insert('meetings', $data);
        if ($meeting['id']) {
            return $meeting;
        }
    }

    function deleteMeeting($data)
    {
        API::MySQL('jupiter')->where('id', $data['id']);
        $meeting = API::MySQL('jupiter')->getOne('meetings');
        if ($meeting) {
            API::MySQL('jupiter')->where('id', $data['id']);
            if (API::MySQL('jupiter')->delete('meetings')) {
                return true;
            }
        }
        http_response_code(404);
        return "Конференция не найдена";
    }

    function editMeeting($data) {
        $data = json_decode(json_encode($data), true);
        $meeting = $data;
        API::MySQL('jupiter')->where('id', $data['id']);
        if (API::MySQL('jupiter')->insert('meetings', $data)) {
            return $meeting;
        }
    }
}
