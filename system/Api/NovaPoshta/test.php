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
    'db_pass' => 'retosi85',
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
include_once '../../core/functions.php';

include_once '../../classes/Delivery/NovaPoshtaApi2.php';

$key = '21285098ceb2b7d0e0c0b8f6669be158'; // Системный api key

use LisDev\Delivery\NovaPoshtaApi2;
$np = new NovaPoshtaApi2($key);

$senderInfo = $np->getCounterparties('Sender', 1, '', '');
$sender = $senderInfo['data'][0];


$result = $np
	->model('TrackingDocument')
	->method('getStatusDocuments')
	->params(array(
        "Documents" => array(array("DocumentNumber" => "20400048799000",
		"Phone" => ""))
	))
	->execute();
var_export($result) . '<br>';

//$counterparty = $result['data'][0];
//var_export($counterparty) . '<br>';
/*
$result2 = $np
	->model('Common')
	->method('getTimeIntervals')
	->params(array(
		'Page' => 0
	))
	->execute();
    
//var_export($result2) . '<br> 11 <br>';

$result3 = $np
	->model('Common')
	->method('getCargoDescriptionList')
	->params(array(
		"FirstName" => "Захарченко",
        "MiddleName" => "Виктор",
        "LastName" => "Сергеевич",
        "Phone" => "380997979749",
        "Email" => "",
        "CounterpartyType" => "PrivatePerson",
        "CounterpartyProperty" => "Recipient"
	))
	->execute();
//var_export($result3) . ' - test';
$result4 = $np->getCities();
//var_export($result4) . ' - test';

$result5 = $np->getWarehouses();
var_export($result5);
*/