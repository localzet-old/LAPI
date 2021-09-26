<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2021 Ivan Zorin & Zorin Projects (www.localzet.ru)
//
////////////////////////////////////////////////////////////////////////////////

class SCHOOL extends API
{
    // data: user (dev | service | admin)
    function ADMIN($data)
    {
        $data = json_decode(json_encode($data), true);
        API::MySQL('school')->where('id', $data['user']);
        $userAdmin = API::MySQL('school')->getOne('users');
        if ($userAdmin['type'] == 'dev' || $userAdmin['type'] == 'service' || $userAdmin['type'] == 'admin') {
            // Пользователи
            $result['users'] = API::MySQL('school')->get('users');
            foreach ($result['users'] as $user) {
                $result['users2'][$user['id']] = $user;
            }

            // Классы
            $classes = API::MySQL('school')->get('classes');
            foreach ($classes as $class) {
                API::MySQL('school')->where('id', $class['teacher']);
                $teacher = API::MySQL('school')->getOne('users');
                $class['teacher_name'] = $teacher['first_name'] . " " . $teacher['last_name'];
                $result['classes'][] = $class;
                $result['classes2'][$class['id']] = $class;
            }

            // Учителя
            API::MySQL('school')->where('type', 'teacher');
            $result['teachers'] = API::MySQL('school')->get('users');
            foreach ($result['teachers'] as $user) {
                $result['teachers2'][$user['id']] = $user;
            }

            // Конференции
            $result['subjects'] = API::MySQL('school')->get('subjects');
            foreach ($result['subjects'] as $subject) {
                $result['subjects2'][$subject['id']] = $subject;
            }

            return $result;
        }
        http_response_code(403);
        return "Недостаточно прав пользователя";
    }

    // data: user (dev | service | admin | teacher)
    function TEACHER($data)
    {
        $data = json_decode(json_encode($data), true);
        API::MySQL('school')->where('id', $data['user']);
        $userAdmin = API::MySQL('school')->getOne('users');
        if ($userAdmin['type'] == 'dev' || $userAdmin['type'] == 'service' || $userAdmin['type'] == 'admin'  || $userAdmin['type'] == 'teacher') {

            // Предметы
            API::MySQL('school')->where('teacher', $data['user']);
            $subjects = API::MySQL('school')->get('subjects');
            $subnames = [];
            foreach ($subjects as $subject) {
                if (!in_array($subject['name'], $subnames)) {
                    $subnames[] = $subject['name'];
                }
                $result['subjects'][] = $subject;
            }
            $result['subnames'] = $subnames;

            // Классы
            $classes = API::MySQL('school')->get('classes');
            foreach ($classes as $class) {
                $result['classes'][$class['id']] = $class;
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

    // data: class
    function STUDENT($data)
    {
        $data = json_decode(json_encode($data), true);
        API::MySQL('school')->where('id', $data['class']);
        $isClass = API::MySQL('school')->getOne('classes');
        if ($isClass) {

            // Предметы
            API::MySQL('school')->where('class', $data['class']);
            $result['subjects'] = API::MySQL('school')->get('subjects');

            // Классы
            $classes = API::MySQL('school')->get('classes');
            foreach ($classes as $class) {
                $result['classes'][$class['id']] = $class;
            }

            // Учителя
            API::MySQL('school')->where('type', 'teacher');
            $teachers = API::MySQL('school')->get('users');
            foreach ($teachers as $user) {
                $result['teachers'][$user['id']] = $user;
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

    // data: name, teacher
    function ADDCLASS($data)
    {
        $data = json_decode(json_encode($data), true);
        if ((string)$data['name'] && (string)$data['teacher']) {
            API::MySQL('school')->where('name', (string)$data['name']);
            $already = API::MySQL('school')->getOne('classes');
            if ((!$already) && (API::MySQL('school')->insert('classes', $data))) {
                return true;
            } else {
                $data['id'] = (string)$already;
                return $this->UPDATECLASS($data);
            }
        }
        http_response_code(401);
        return "Неверные аргументы";
    }

    // data: name, class, teacher
    function ADDSUBJECT($data)
    {
        $data = json_decode(json_encode($data), true);
        if ((string)$data['name'] && (string)$data['class'] && (string)$data['teacher']) {
            if (API::MySQL('school')->insert('subjects', $data)) {
                return true;
            } else {
                http_response_code(401);
                return API::MySQL('school')->getLastError();
            }
        }
        http_response_code(401);
        return "Неверные аргументы";
    }

    // data: id, name, teacher
    function UPDATECLASS($data)
    {
        $data = json_decode(json_encode($data), true);
        if ((string)$data['id'] && (string)$data['name'] && (string)$data['teacher']) {
            API::MySQL('school')->where('id', (string)$data['id']);
            if (API::MySQL('school')->update('classes', $data)) {
                return true;
            } else {
                http_response_code(401);
                return API::MySQL('school')->getLastError();
            }
        }
        http_response_code(401);
        return "Неверные аргументы";
    }

    // data: id, name, class, teacher
    function UPDATESUBJECT($data)
    {
        $data = json_decode(json_encode($data), true);
        if ((string)$data['id'] && (string)$data['name'] && (string)$data['class'] && (string)$data['teacher']) {
            API::MySQL('school')->where('id', (string)$data['id']);
            if (API::MySQL('school')->update('subjects', $data)) {
                return true;
            } else {
                http_response_code(401);
                return API::MySQL('school')->getLastError();
            }
        }
        http_response_code(401);
        return "Неверные аргументы";
    }

    function LIVE($data)
    {
        $data = json_decode(json_encode($data), true);
        API::MySQL('school')->where('id', $data['subject']);
        $isSub = API::MySQL('school')->getOne('subjects');
        if ($isSub) {
            return $isSub;
        }
        http_response_code(403);
        return "Недостаточно прав пользователя";
    }

    // function MIGRATE($data)
    // {
    //     $enroll = API::MySQL('school1')->get('enroll');
    //     foreach ($enroll as $roll) {
    //         $enroll_list[$roll['student_id']] = ['class_id' => $roll['class_id']];
    //     }
    //     if (!$enroll) {
    //         return 'enroll error';
    //     }
    //     $teachers = API::MySQL('school1')->get('teacher');
    //     foreach ($teachers as $teacher) {
    //         $teacher_id = API::MySQL('school')->insert('users', ['first_name' => $teacher['first_name'], 'last_name' => $teacher['last_name'], 'type' => 'teacher', 'email' => $teacher['email'], 'password' => $teacher['password'], 'username' => $teacher['username']]);
    //         $teacher_list[$teacher['teacher_id']] = ['id' => $teacher_id, 'first_name' => $teacher['first_name'], 'last_name' => $teacher['last_name'], 'type' => 'teacher', 'email' => $teacher['email'], 'password' => $teacher['password'], 'username' => $teacher['username']];
    //     }
    //     if (!$teachers || !$teacher_id) {
    //         return 'teachers error';
    //     }
    //     $classes = API::MySQL('school1')->get('class');
    //     foreach ($classes as $class) {
    //         $class_id = API::MySQL('school')->insert('classes', ['name' => $class['name'], 'teacher' => $teacher_list[$class['teacher_id']]['id']]);
    //         $class_list[$class['class_id']] = ['id' => $class_id, 'name' => $class['name'], 'teacher' => $teacher_list[$class['teacher_id']]['id']];
    //     }
    //     if (!$classes || !$class_id) {
    //         return 'classes error';
    //     }
    //     $students = API::MySQL('school1')->get('student');
    //     foreach ($students as $student) {
    //         $student_id = API::MySQL('school')->insert('users', ['class' => $class_list[$enroll_list[$student['student_id']]['class_id']]['id'], 'first_name' => $student['first_name'], 'last_name' => $student['last_name'], 'type' => 'student', 'email' => $student['email'], 'password' => $student['password'], 'username' => $student['username']]);
    //         $student_list[$student['student_id']] = ['id' => $student_id, 'first_name' => $student['first_name'], 'last_name' => $student['last_name'], 'type' => 'student', 'email' => $student['email'], 'password' => $student['password'], 'username' => $teacher['username']];
    //     }
    //     if (!$students || !$student_id) {
    //         return 'students error';
    //     }
    //     $subjects = API::MySQL('school1')->get('subject');
    //     foreach ($subjects as $subject) {
    //         $live = API::TOKEN()->CREATE(['name' => $subject['name'], 'class' => $class_list[$subject['class_id']]['id'], 'teacher' => $teacher_list[$subject['teacher_id']]['id']]);
    //         $subject_id = API::MySQL('school')->insert('subjects', ['name' => $subject['name'], 'class' => $class_list[$subject['class_id']]['id'], 'teacher' => $teacher_list[$subject['teacher_id']]['id'], 'live' => $live]);
    //     }
    //     if (!$subjects || !$subject_id) {
    //         return 'subjects error';
    //     }
    //     return true;
    // }
}
