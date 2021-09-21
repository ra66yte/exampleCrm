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
    'db_base' => 'work'
];
// Подключаемся к mysql
$db = new mysqli($data['db_host'], $data['db_user'], $data['db_pass'], $data['db_base']);
if ($db->connect_errno) {
    die('Ошибка подключения к базе данных: ' . $db->connect_error);
}
$db->set_charset('utf-8');

date_default_timezone_set('Europe/Kiev');
// Подключаем функции
include_once '../../../core/functions.php';

include_once '../../../classes/Delivery/NovaPoshtaApi2.php';

$key = '21285098ceb2b7d0e0c0b8f6669be158'; // Системный api key

use LisDev\Delivery\NovaPoshtaApi2;
$np = new NovaPoshtaApi2($key);
