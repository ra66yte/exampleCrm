<?php

use Workerman\Lib\Timer;
use Workerman\Worker;

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
require_once dirname(dirname(__DIR__)) . '/system/core/functions.php';
// Create a Websocket server
$ws_worker = new Worker('websocket://127.0.0.1:8088');

// 4 processes
$ws_worker->count = 1;
// Все пользователи
$connections = [];

function dbConnect() {
    // Массив с настройками
    $data = [
        'db_host' => 'localhost',
        'db_user' => 'root',
        'db_pass' => 'retosi85',
        'db_base' => 'work'
    ];
    // Подключаемся к mysql
    $db = new mysqli($data['db_host'], $data['db_user'], $data['db_pass'], $data['db_base']);
    if ($db->connect_errno) {
        die('Ошибка подключения к базе данных: ' . $db->connect_error);
    }
    $db->set_charset('utf-8');
    return $db;
}

$ws_worker->onWorkerStart = function($ws_worker) use (&$connections) {
    $interval = 5; // пингуем каждые 5 секунд
    Timer::add($interval, function() use(&$connections) {
        foreach ($connections as $c) {
            // Если ответ от клиента не пришел 3 раза, то удаляем соединение из списка
            if ($c->pingWithoutResponseCount >= 3) {
                unset($connections[$c->id]); 
                $c->destroy(); // уничтожаем соединение
            } else {
                $c->send('{"action":"Ping","data":{}}');
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
        
        // Подключаемся к базе
        $db = dbConnect();

        echo "New connection\n";
    };
};

// Получили сообщение
$ws_worker->onMessage = function ($connection, $json_data) use (&$connections) {
    $db = dbConnect();

    $messageData = json_decode($json_data, true);
    $action = isset($messageData['action']) ? $messageData['action'] : null;
    $data = isset($messageData['data']) ? $messageData['data'] : null;

    if (isset($action)) {
        switch ($action) {
            case 'Pong':
                $connection->pingWithoutResponseCount = 0;
                break;
            case 'enter':
                if ($user = $db->query("SELECT `id`, `chief_id`, `login` FROM `user` WHERE `id` = '" . abs(intval($data['id'])) . "' AND `password` = '" . $db->escape_string(trim($data['hash'])) . "'")->fetch_assoc()) {
                    $connection->login = $user['login'];
                    $connection->chiefId = ($user['chief_id'] == 0 ? $user['id'] : $user['chief_id']);
                    $connection->location = $data['location'];
                    $connection->activeStatuses = json_encode($data['activeStatuses']);
                    $connection->lockItems = json_encode(array());
    
                    $connections[$connection->id] = $connection;
                } else {
                    $connection->destroy();
                }

                
                /*
                foreach ($connections as $c) {
                    if ($c->id != $connection->id) {
                        $c->send('{"action":"enter","data":"' . $connection->login . ' присоединился"}');
                    } else {
                        $c->send('{"action":"enter","data":"Вы присоединились. Ваш логин - ' . $c->login . '"}');
                    }
                }
                break;
                /*
            case 'update counts': // Обновление счетчиков статусов (табов)
                $location = $data['location'];

                foreach ($connections as $c) {
                    if ($c->id != $connection->id and $connection->chiefId == $c->chiefId) {
                        $c->send('{"action":"update counts","data":{"location":"' . $data['location'] . '"}}');
                    }
                }

                break;
                */
            case 'set property':
                $propertyName = isset($data['propertyName']) ? $data['propertyName'] : null;
                $propertyValue = isset($data['propertyValue']) ? $data['propertyValue'] : null;

                if (is_array($propertyValue) and $propertyValue) {
                    $arrayProperties = json_decode($connection->$propertyName, true);
                    foreach ($arrayProperties as $key => $value) {
                        if (isset($propertyValue[$key]) && $propertyValue[$key] != $arrayProperties[$key]) $arrayProperties[$key] = $propertyValue[$key];
                    }
                    $connection->$propertyName = json_encode($arrayProperties);
                } else {
                    $connection->$propertyName = $propertyValue;
                }

                break;
            case 'add item':
                $item_id = $data['itemId'];
                $location = $data['location'];

                if ($location == 'orders') { // Заказы
                    if ($result = $db->query("SELECT COUNT(*) FROM `orders` WHERE `id` = '" . abs(intval($item_id)) . "' AND `client_id` = '" . abs(intval($connection->chiefId)) . "'")->fetch_row() and $result[0] > 0) {
                        $order = $db->query("SELECT * FROM `orders` WHERE `id` = '" . abs(intval($item_id)) . "' AND `client_id` = '" . abs(intval($connection->chiefId)) . "'")->fetch_assoc();
    
                        $this_status = $db->query("SELECT `name`, `color` FROM `status_order` WHERE `client_id` = '" . abs(intval($connection->chiefId)) . "' AND `id` = '" . $order['status'] . "'")->fetch_assoc();
                        $this_payment = $db->query("SELECT `icon`, `name` FROM `payment_methods` WHERE `client_id` = '" . abs(intval($connection->chiefId)) . "' AND `id` = '" . $order['payment_method'] . "'")->fetch_assoc();
                        $this_delivery = $db->query("SELECT `icon`, `name` FROM `delivery_methods` WHERE `client_id` = '" . abs(intval($connection->chiefId)) . "' AND `id` = '" . $order['delivery_method'] . "'")->fetch_assoc();
                        $country = $db->query("SELECT `name`, `code` FROM `countries` WHERE `id` = '" . $order['country'] . "'")->fetch_assoc();
                        $completed = $order['completed'] == 0 ? 'Нет' : 'Да';
    
                        $row_data = array(
                            'id' => $order['id'],
                            'id_order' => str_pad($order['id_order'], 4, '0', STR_PAD_LEFT),
                            'customer' => protection($order['customer'], 'display'),
                            'country' => '<img src="/img/countries/' . protection($country['code'], 'display') . '.png" alt="*"> ' . protection($country['name'], 'display'),
                            'phone' => protection($order['phone'], 'display'),
                            'comment' => protection($order['comment'], 'display'),
                            'amount' => number_format($order['amount'], 2, '.', ' '),
                            'products' => array(),
                            'payment_method' => '<img src="/system/images/payment/' . protection($this_payment['icon'], 'display') . '" alt="ico"> ' . protection($this_payment['name'], 'display'),
                            'delivery_method' => '<img src="/system/images/delivery/' . protection($this_delivery['icon'], 'display') . '" alt="ico"> ' . protection($this_delivery['name'], 'display'),
                            'delivery_adress' => protection($order['delivery_address'], 'display'),
                            'ttn' => protection($order['ttn'], 'display'),
                            'ttn_status' => protection($order['ttn_status'], 'display'),
                            'departure_date' => view_time($order['departure_date']),
                            'date_added' => view_time($order['date_added']),
                            'updated' => ($order['date_added'] == $order['updated'] ? true : view_time($order['updated'])),
                            'employee' => $order['employee'],
                            'site' => protection($order['site'], 'display'),
                            'ip' => long2ip($order['ip']),
                            'order_status' => protection($this_status['name'], 'display'),
                            'complete' => $completed,
                            'status_color' => protection($this_status['color'], 'display'),
                            'status' => $order['status'],
                            'blocked' => $order['blocked']
                        );
    
                        foreach ($connections as $c) {
                            if ($c->id != $connection->id and $connection->chiefId == $c->chiefId) {
                                $activeStatuses = json_decode($c->activeStatuses, true);
    
                                if ($activeStatuses['orders'] == $order['status'] or $activeStatuses['orders'] == 0) {
                                    $c->send('{"action":"add item","data":{"rowData":' . json_encode($row_data) . ',"location":"orders"}}');
                                } else {
                                    $c->send('{"action":"update counts","data":{"location":"orders"}}');
                                }
                            }
                        }
                    }
                }

                break;
            case 'remove item': // Убираем строку из таблицы
                $items_id = $data['itemsId'];
                $location = $data['location'];

                foreach ($connections as $c) {
                    if ($c->id != $connection->id and $connection->chiefId == $c->chiefId) {
                        foreach ($items_id as $item_id) {
                            $c->send('{"action":"remove item","data":{"itemId":"' . $item_id . '","location":"' . $data['location'] . '"}}');
                        }
                    }
                }
                
                break;
            case 'lock item':
                $item_id = $data['itemId'];
                $location = $data['location'];

                // Массив блокируемых элементов
                $lockItemsArray = json_decode($connection->lockItems, true);
                $lockItemsArray[$location][] = $item_id;
                $connection->lockItems = json_encode($lockItemsArray);

                foreach ($connections as $c) {
                    if ($c->id != $connection->id and $connection->chiefId == $c->chiefId) {
                        $c->send('{"action":"lock item","data":{"itemId":"' . $item_id . '","location":"' . $data['location'] . '"}}');
                    }
                }

                if ($location == 'orders') {
                    // Блокируем заказ в базе
                    if ($db->query("UPDATE `orders` SET `blocked` = '1' WHERE `id` = '" . abs(intval($item_id)) . "' AND `client_id` = '" . abs(intval($connection->chiefId)) . "'")){

                    } 
                }

                break;
            case 'unlock item':
                $item_id = $data['itemId'];
                $location = $data['location'];

                // Удаляем из списка блокируемых элементов
                $lockItemsArray = json_decode($connection->lockItems, true);
                unset($lockItemsArray[$location][$item_id]);
                $connection->lockItems = json_encode($lockItemsArray);

                foreach ($connections as $c) {
                    if ($c->id != $connection->id and $connection->chiefId == $c->chiefId) {
                        $c->send('{"action":"unlock item","data":{"itemId":"' . $item_id . '","location":"' . $data['location'] . '"}}');
                    }
                }

                if ($location == 'orders') {
                    // Снимаем блокровку заказа в базе
                    if ($db->query("UPDATE `orders` SET `blocked` = '0' WHERE `id` = '" . abs(intval($item_id)) . "' AND `client_id` = '" . abs(intval($connection->chiefId)) . "'")){

                    }
                }
                
                break;
        }
    }
};

// Закрытие соединения
$ws_worker->onClose = function ($connection) use (&$connections) {
    $db = dbConnect();

    if (!isset($connections[$connection->id])) {
        return;
    }

    // Снимаем блокировку с элементов
    foreach ($connections as $c) {
        if ($c->id != $connection->id and $connection->chiefId == $c->chiefId) {
            foreach (json_decode($connection->lockItems, true) as $location => $items) {
                $itemsArray = []; // Заблокированные элементы
                foreach ($items as $item) {
                    if ($location == 'orders') $itemsArray[] = abs(intval($item));
                    $c->send('{"action":"unlock item","data":{"itemId":"' . $item . '","location":"' . $location . '"}}');
                }

                $matches = implode(',', $itemsArray);
                if ($location == 'orders') {
                    // Снимаем блокировку
                    if ($db->query("UPDATE `orders` SET `blocked` = '0' WHERE `id` IN ($matches) AND `client_id` = '" . abs(intval($connection->chiefId)) . "'")){

                    }
                }
                
            }
        }
    }

    // Удаляем соединение из списка
    unset($connections[$connection->id]);
    
    echo "Connection closed\n";
};

// Run worker
Worker::runAll();
