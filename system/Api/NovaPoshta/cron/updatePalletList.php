<?php
chdir(__DIR__);
include_once 'init.php';

// Создаем временную таблицу
$ddl = "CREATE TABLE IF NOT EXISTS `np_pallet_list_tmp` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `desc_ua` varchar(255) NOT NULL,
            `desc_ru` varchar(255) NOT NULL,
            `ref` char(36) NOT NULL,
            PRIMARY KEY (`id`)
        )";
if ($db->query($ddl)) {
    // Получаем список всех типов груза
    $request = $np
                ->model('Common')
                ->method('getPalletsList')
                ->params(array(
                    'Page' => 0
                ))
                ->execute();
    $items = $request['data'];
    
    // Добавляем все во временную таблицу
    $insert_items = "INSERT INTO `np_pallet_list_tmp` (`id`, `desc_ua`, `desc_ru`, `ref`) VALUES";
    foreach ($items as $item) {
        $insert_items .= " (null, '" . protection($item['Description'], 'base') . "', '" . protection($item['DescriptionRu'], 'base') . "', '" . protection($item['Ref'], 'base') . "'),";
    }
    $insert_items = rtrim($insert_items, ',');
    if ($db->query($insert_items)) {
        // Если все успешно добавилось
        $db->query("DROP TABLE `np_pallet_list`"); // Удаляем старую таблицу с типами отделений
        $db->query("RENAME TABLE `np_pallet_list_tmp` TO `np_pallet_list`"); // Меняем название временной таблицы
    } else {
        $error = true;
    }
} else {
    // Не удалось создать временную таблицу
    $error = true;
}

// строка, которую будем записывать
$text = date("F j, Y, g:i a") . " Справочник видов паллет " . (isset($error) ? 'НЕ обновлен' : 'обновлен') . "\n";
//Открываем файл
$fp = fopen("cron.log", "a+");
// записываем в файл текст
fwrite($fp, $text);
// закрываем
fclose($fp);
