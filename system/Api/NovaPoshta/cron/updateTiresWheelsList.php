<?php
chdir(__DIR__);
include_once 'init.php';

// Создаем временную таблицу
$ddl = "CREATE TABLE IF NOT EXISTS `np_tires_wheels_list_tmp` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `desc_ua` varchar(255) NOT NULL,
            `desc_ru` varchar(255) NOT NULL,
            `ref` char(36) NOT NULL,
            `weight` decimal(24,2) not null,
            `description_type` enum('Tires', 'Wheels') not null,
            PRIMARY KEY (`id`)
        )";
if ($db->query($ddl)) {
    // Получаем список видов шин и дисков
    $request = $np
                ->model('Common')
                ->method('getTiresWheelsList')
                ->params(array(
                    'Page' => 0
                ))
                ->execute();
    $items = $request['data'];
    
    // Добавляем все во временную таблицу
    $insert_items = "INSERT INTO `np_tires_wheels_list_tmp` (`id`, `desc_ua`, `desc_ru`, `ref`, `weight`, `description_type`) VALUES";
    foreach ($items as $item) {
        $insert_items .= " (null, '" . protection($item['Description'], 'base') . "', '" . protection($item['DescriptionRu'], 'base') . "', '" . protection($item['Ref'], 'base') . "', '" . number_format(abs(floatval($item['Weight'])), 2, '.', '') . "', '" . protection($item['DescriptionType'], 'base') . "'),";
    }
    $insert_items = rtrim($insert_items, ',');
    //echo $insert_items;
    if ($db->query($insert_items)) {
        // Если все успешно добавилось
        $db->query("DROP TABLE `np_tires_wheels_list`"); // Удаляем старую таблицу
        $db->query("RENAME TABLE `np_tires_wheels_list_tmp` TO `np_tires_wheels_list`"); // Меняем название временной таблицы
    } else {
        $error = true;
    }
} else {
    // Не удалось создать временную таблицу
    $error = true;
}

// строка, которую будем записывать
$text = date("F j, Y, g:i a") . " Справочник видов шин и дисков " . (isset($error) ? 'НЕ обновлен' : 'обновлен') . "\n";
//Открываем файл
$fp = fopen("cron.log", "a+");
// записываем в файл текст
fwrite($fp, $text);
// закрываем
fclose($fp);
