<?php
include_once 'system/core/begin.php';
$data['title'] = 'Рабочий стол';
include_once 'system/core/header.php';
?>
<script>
    $(function(){
    })
</script>
<?

/*
$result = checkAccess('statuses', 3);
var_dump($result);
*/
/*
$i = 1;
$customer = array('Вася', 'Алена', 'Коля', 'Ольга', 'Игорь', 'Helena');
$phone = array('+380965478511', '+380669485714', '+380987456378', '+380994789888', '+380679639117', '+380679326987');

while ($i <= 1000) {
    $db->query("INSERT INTo `orders` (`customer`, `id_order`, `phone`, `status`, `amount`, `date_added`, `client_id`, `country`) VALUES ('" . $customer[array_rand($customer)] . "', '" . rand(1000,9999) . "', '" . $phone[array_rand($phone)]  . "', '" .rand(1,10) . "', '" .rand(100,1000)."', '" . time() . "', '" . $chief['id'] . "', '216')");
    $i++;
}
*/
/*
include("system/classes/SxGeo/SxGeo.php");
// Создаем объект
// Первый параметр - имя файла с базой (используется оригинальная бинарная база SxGeo.dat)
// Второй параметр - режим работы: 
//     SXGEO_FILE   (работа с файлом базы, режим по умолчанию); 
//     SXGEO_BATCH (пакетная обработка, увеличивает скорость при обработке множества IP за раз)
//     SXGEO_MEMORY (кэширование БД в памяти, еще увеличивает скорость пакетной обработки, но требует больше памяти)
$SxGeo = new SxGeo();
//$SxGeo = new SxGeo('SxGeoCity.dat', SXGEO_BATCH | SXGEO_MEMORY); // Самый производительный режим, если нужно обработать много IP за раз

$ip = '88.248.55.136';

// var_dump($SxGeo->getCountry($ip));
*/
/*
if ($stmt = $db->query("SELECT COUNT(*) FROM `user`")->fetch_row() and $stmt[0] > 0) {
    echo 'yse';
} else {
    echo 'no';
}
*/

?>
            <!-- Content -->
            <section class="content">
                <h1>CRM v. <? echo $data['CRM_v']; ?> :: Рабочий стол</h1>
                <!-- put div here without scroll -->
                <div class="content__overflow">
                    Привет, мир!
                </div>
<?
$hide_pagination = true;
include_once 'system/core/footer.php';
