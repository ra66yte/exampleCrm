<?php
chdir(__DIR__);
include_once 'init.php';

// Создаем временную таблицу `np_cargo_types`
$ddl = "CREATE TABLE IF NOT EXISTS `np_cargo_types_tmp` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `desc_ua` varchar(255) NOT NULL,
            `ref` varchar(60) NOT NULL,
            PRIMARY KEY (`id`)
        )";
if ($db->query($ddl)) {
    // Получаем список всех типов груза
    $request = $np
                ->model('Common')
                ->method('getCargoTypes')
                ->params(array(
                    'Page' => 0
                ))
                ->execute();
    $items = $request['data'];
    
    // Добавляем все во временную таблицу
    $insert_items = "INSERT INTO `np_cargo_types_tmp` (`id`, `desc_ua`, `ref`) VALUES";
    foreach ($items as $item) {
        $insert_items .= " (null, '" . protection($item['Description'], 'base') . "', '" . protection($item['Ref'], 'base') . "'),";
    }
    $insert_items = rtrim($insert_items, ',');
    if ($db->query($insert_items)) {
        // Если все успешно добавилось
        $db->query("DROP TABLE `np_cargo_types`"); // Удаляем старую таблицу с типами отделений
        $db->query("RENAME TABLE `np_cargo_types_tmp` TO `np_cargo_types`"); // Меняем название временной таблицы
    } else {
        $error = true;
    }
} else {
    // Не удалось создать временную таблицу
    $error = true;
}

// строка, которую будем записывать
$text = date("F j, Y, g:i a") . " Справочник типов груза " . (isset($error) ? 'НЕ обновлен' : 'обновлен') . "\n";
//Открываем файл
$fp = fopen("cron.log", "a+");
// записываем в файл текст
fwrite($fp, $text);
// закрываем
fclose($fp);
