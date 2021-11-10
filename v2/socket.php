<?php

use Workerman\Worker;
use PHPSocketIO\SocketIO;

require_once __DIR__ . '/vendor/autoload.php';
$meetings = [];

// Присоединение
function handleJoin($socket, $data)
{
    global $meetings;
    $socket->meetingId = $data->meetingId;
    $socket->moderator = $data->moderator;
    $socket->join($data->meetingId);
    $data->socketId = $socket->id;
    sendToMeeting($socket, $data);
    // handleFileTransfer($socket, $data->meetingId);
}

//handle disconnect event
function handleDisconnect($socket, $io)
{
    global $meetings;
    //remove file_uploads folder by meetingId
    $dirName = '../public/file_uploads/' + $socket->meetingId;
    if (!$io->sockets->adapter->rooms[$socket->meetingId] && is_dir($dirName)) {
        rmdir($dirName);
    }

    if ($socket->moderator) unset($meetings[$socket->meetingId]);

    $socket->leave($socket->meetingId);
    //notify all the participants when anyone leaves the meeting
    sendToMeeting($socket, array('type' => 'leave', 'fromSocketId' => $socket->id, 'isModerator' => $socket->moderator));
}

//check meeting length and moderator availibility
function handleCheckMeeting($socket, $data, $io)
{
    global $meetings;
    $result = !$io->sockets->adapter->rooms[$data->meetingId] || count($io->sockets->adapter->rooms[$data->meetingId]) < 30;

    if ($result) {
        if ($data->authMode == "disabled" || $data->moderator || $data->moderatorRights == "disabled") {
            $meetings[$data->meetingId] = array(
                'isModeratorPresent' => true,
                'moderator' => $socket->id
            );
            //directly allow the user if he is the moderator or if the moderator rights are disabled
            sendToPeer($io, array('type' => 'checkMeetingResult', 'result' => true, 'toSocketId' => $socket->id, 'message' => ''));
        } else if ($meetings[$data->meetingId] && $meetings[$data->meetingId]->isModeratorPresent) {
            //notify the moderator for new request
            sendToPeer($io, array('type' => 'permission', 'toSocketId' => $meetings[$data->meetingId]->moderator, 'fromSocketId' => $socket->id, 'username' => $data->username));
            sendToPeer($io, array('type' => 'info', 'toSocketId' => $socket->id, 'message' => 'Пожалуйста подождите, пока модератор примет ваш запрос'));
        } else {
            //do not allow anyone in the meeting before moderator joins
            sendToPeer($io, array('type' => 'checkMeetingResult', 'result' => false, 'toSocketId' => $socket->id, 'message' => 'Конференция ещё не началась'));
        }
    } else {
        //USER_LIMIT_PER_MEETING capacity is reached
        sendToPeer($io, array('type' => 'checkMeetingResult', 'result' => false, 'toSocketId' => $socket->id, 'message' => 'Конференция переполнена'));
    }
}

//send the message to particular user
function sendToPeer($io, $data)
{
    global $meetings;
    $io->to($data->toSocketId)->emit('message', json_encode($data));
}

//send the message to particular meeting
function sendToMeeting($socket, $data)
{
    global $meetings;
    $socket->broadcast->to($socket->meetingId)->emit('message', json_encode($data));
}

// //handle file transfer
// function handleFileTransfer($socket, $meetingId) {
// global $meetings;
//     $uploader = new siofu();
//     $uploader->dir = '../public/file_uploads/' + $meetingId;

//     if (!fs.existsSync($uploader->dir)) {
//         fs.mkdir($uploader->dir);
//     }

//     uploader.maxFileSize = process.env.MAX_FILESIZE * 1024 * 1024;

//     uploader.listen(socket);

//     uploader.on("saved", function (event) {
//         event.file.clientDetail.file = event.file.base;
//         event.file.clientDetail.extension = event.file.meta.extension;
//         event.file.clientDetail.username = event.file.meta.username;

//         socket.broadcast.to(meetingId).emit('file', { file: event.file.base, extension: event.file.meta.extension, username: event.file.meta.username });
//     });

//     //keep this line to prevent crash
//     uploader.on("error", function (event) { });
// }
$context = array(
    'ssl' => array(
        'local_cert'  => '/var/www/httpd-cert/cert.pem',
        'local_pk'    => '/var/www/httpd-cert/key.pem',
        'verify_peer' => false
    )
);

$io = new SocketIO(2021, $context);
$io->on('connection', function ($socket) use ($io) {
    $socket->on('message', function ($data) use ($io, $socket) {
        switch ($data) {
            case 'join':
                handleJoin($socket, $data);
                break;
            case 'checkMeeting':
                handleCheckMeeting($socket, $data, $io);
                break;
            case 'offer':
            case 'answer':
            case 'candidate':
            case 'message':
            case 'permissionResult':
            case 'currentTime':
            case 'kick':
                sendToPeer($io, $data);
                break;
            case 'meetingMessage':
                sendToMeeting($socket, $data);
                break;
        }
        // $io->emit('chat message', $msg);
    });

    $socket->on('message', function () use ($io, $socket) {
        handleDisconnect($socket, $io);
    });
});

Worker::runAll();
