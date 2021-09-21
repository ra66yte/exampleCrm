<?php
// Показ ошибок
error_reporting(E_ALL);
// Стартуем сессию
session_name('PHPSESSID');
session_start();
// Внутренняя кодировка
mb_internal_encoding('UTF-8');
// Массив с настройками
$data = [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '',
    'db_base' => 'crm',
    'title'   => 'CRM',
    'time'    => time(),
    'CRM_v'   => '0.01 alpha',
    'orders_on_page' => '50'
];
// Подключаемся к mysql
$db = new mysqli($data['db_host'], $data['db_user'], $data['db_pass'], $data['db_base']);
if ($db->connect_errno) {
    die('Ошибка подключения к базе данных: ' . $db->connect_error);
}
$db->set_charset('utf-8');

date_default_timezone_set('Europe/Kiev');

// Токен
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['token'];

// Подключаем функции
include_once __DIR__ . '/functions.php';
$user = null;
if (isset($_SESSION['id_user']) and $result = $db->query("SELECT COUNT(*) FROM `user` WHERE `id` = '" . protection($_SESSION['id_user'], 'base') . "'")->fetch_row() and $result[0] <> 0) {
    $user = $db->query("SELECT * FROM `user` WHERE `id` = '" . protection($_SESSION['id_user'], 'base') . "'")->fetch_assoc();
} elseif (isset($_COOKIE['user_id']) and isset($_COOKIE['hash'])) {
    if ($result = $db->query("SELECT COUNT(*) FROM `user` WHERE `id` = '" . protection($_COOKIE['user_id'], 'base') . "' AND `password` = '" . protection($_COOKIE['hash'], 'base') . "'")->fetch_row() and $result[0] <> 0) {
        $user = $db->query("SELECT * FROM `user` WHERE `id` = '" . protection($_COOKIE['user_id'], 'base') . "'  AND `password` = '" . protection($_COOKIE['hash'], 'base') . "'")->fetch_assoc();
    }
}

if (isset($user)) {
    if ($user['chief_id'] != 0) {
        $chief = $db->query("SELECT `id`, `country` FROM `user` WHERE `id` = '" . $user['chief_id'] . "'")->fetch_assoc();
    } else {
        $chief = $user;
    }

    if ($data['time'] > ($data['time'] - $user['last_activity'] + 600)) {
        $db->query("UPDATE `user` SET `last_activity` = '" . $data['time'] . "' WHERE `id` = '" . $user['id'] . "'");
    }

    if (!isset($_COOKIE['user_id']) or !isset($_COOKIE['hash'])) { // Если по каким-то причинам у пользователя нету кук для авторизации
        setcookie('user_id', $user['id'], time() + 86400, '/');
        setcookie('hash', $user['password'], time() + 86400, '/');
    }
} else {
    if ($_SERVER['PHP_SELF'] <> '/login.php' and $_SERVER['PHP_SELF'] <> '/system/ajax/login.php') {
        header('Location: /login.php');
        exit;
    }
}
