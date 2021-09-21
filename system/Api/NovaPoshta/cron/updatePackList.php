<?php
chdir(__DIR__);
include_once 'init.php';

// Создаем временную таблицу
$ddl = "CREATE TABLE IF NOT EXISTS `np_pack_list_tmp` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `desc_ua` varchar(255) NOT NULL,
            `desc_ru` varchar(255) NOT NULL,
            `ref` char(36) NOT NULL,
            `length` decimal(24,2) not null,
            `width` decimal(24,2) not null,
            `height` decimal(24,2) not null,
            `volumetric_weight` decimal(24,2) not null,
            `type_of_packing` varchar(40) null,
            `packaging_for_place` int(11) not null,
            PRIMARY KEY (`id`)
        )";
if ($db->query($ddl)) {
    // Получаем список видов упаковки
    $request = $np
                ->model('Common')
                ->method('getPackList')
                ->params(array(
                    'Page' => 0
                ))
                ->execute();
    $items = $request['data'];
    
    // Добавляем все во временную таблицу
    $insert_items = "INSERT INTO `np_pack_list_tmp` (`id`, `desc_ua`, `desc_ru`, `ref`, `length`, `width`, `height`, `volumetric_weight`, `type_of_packing`, `packaging_for_place`) VALUES";
    foreach ($items as $item) {
        $insert_items .= " (null, '" . protection($item['Description'], 'base') . "', '" . protection($item['DescriptionRu'], 'base') . "', '" . protection($item['Ref'], 'base') . "', '" . number_format(abs(floatval($item['Length'])), 2, '.', '') . "', '" . number_format(abs(floatval($item['Width'])), 2, '.', '') . "', '" . number_format(abs(floatval($item['Height'])), 2, '.', '') . "', '" . number_format(abs(floatval($item['VolumetricWeight'])), 2, '.', '') . "', '" . protection($item['TypeOfPacking'], 'base') . "', '" . protection($item['PackagingForPlace'], 'int') . "'),";
    }
    $insert_items = rtrim($insert_items, ',');
    //echo $insert_items;
    if ($db->query($insert_items)) {
        // Если все успешно добавилось
        $db->query("DROP TABLE `np_pack_list`"); // Удаляем старую таблицу
        $db->query("RENAME TABLE `np_pack_list_tmp` TO `np_pack_list`"); // Меняем название временной таблицы
    } else {
        $error = true;
    }
} else {
    // Не удалось создать временную таблицу
    $error = true;
}

// строка, которую будем записывать
$text = date("F j, Y, g:i a") . " Справочник видов упаковки " . (isset($error) ? 'НЕ обновлен' : 'обновлен') . "\n";
//Открываем файл
$fp = fopen("cron.log", "a+");
// записываем в файл текст
fwrite($fp, $text);
// закрываем
fclose($fp);
