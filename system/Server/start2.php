<?php

use Workerman\Lib\Timer;
use Workerman\Worker;

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

// Create a Websocket server
$ws_worker = new Worker('websocket://127.0.0.1:8088');

// 4 processes
$ws_worker->count = 4;
// Все пользователи
$connections = []; 

$ws_worker->onWorkerStart = function($ws_worker) use (&$connections) {
    $interval = 5; // пингуем каждые 5 секунд
    Timer::add($interval, function() use (&$connections) {
        foreach ($connections as $c) {
            // Если ответ от клиента не пришел 3 раза, то удаляем соединение из списка
            if ($c->pingWithoutResponseCount >= 3) {
                unset($connections[$c->id]); 
                $c->destroy(); // уничтожаем соединение
            } else {
                $c->send('{"action":"Ping"}');
                $c->pingWithoutResponseCount++; // увеличиваем счетчик пингов
            }
        }
    });
};

// Соединяемся
$ws_worker->onConnect = function ($connection) use (&$connections) {
    // Эта функция выполняется при подключении пользователя к WebSocket-серверу
    $connection->onWebSocketConnect = function ($connection) use (&$connections) {
        $connection->pingWithoutResponseCount = 0;

        $connections[$connection->id] = $connection;

        $connection->send(json_encode($connections));
        echo "New connection\n";
    };
};

// Emitted when data received
$ws_worker->onMessage = function ($connection, $data) use (&$connections) {
    $messageData = json_decode($data, true);
    $action = isset($messageData['action']) ? $messageData['action'] : null;
    $data = isset($messageData['data']) ? $messageData['data'] : null;
    if (isset($action)) {
        switch ($action) {
            case 'Pong':
                $connection->pingWithoutResponseCount = 0;
                break;
            case 'enter':
                //$connection->login = $data['login'];
                //$connections[$connection->id] = $connection;
                foreach ($connections as $c) {
                    $c->send('{"action":"login","data":"' . json_encode($c) . '"}');
                }
                break;
        }
    }
};

// Emitted when connection closed
$ws_worker->onClose = function ($connection) use (&$connections) {
    if (!isset($connections[$connection->id])) {
        return;
    }
    // Удаляем соединение из списка
    unset($connections[$connection->id]);

    echo "Connection closed\n";
};

// Run worker
Worker::runAll();
