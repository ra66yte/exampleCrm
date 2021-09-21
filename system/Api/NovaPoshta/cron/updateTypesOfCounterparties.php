<?php
chdir(__DIR__);
include_once 'init.php';

// Создаем временную таблицу
$ddl = "CREATE TABLE IF NOT EXISTS `np_types_of_counterparties_tmp` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `desc_ua` varchar(255) NOT NULL,
            `ref` varchar(30) NOT NULL,
            PRIMARY KEY (`id`)
        )";
if ($db->query($ddl)) {
    // Получаем список всех типов груза
    $request = $np
                ->model('Common')
                ->method('getTypesOfCounterparties')
                ->params(array(
                    'Page' => 0
                ))
                ->execute();
    $items = $request['data'];
    
    // Добавляем все во временную таблицу
    $insert_items = "INSERT INTO `np_types_of_counterparties_tmp` (`id`, `desc_ua`, `ref`) VALUES";
    foreach ($items as $item) {
        $insert_items .= " (null, '" . protection($item['Description'], 'base') . "', '" . protection($item['Ref'], 'base') . "'),";
    }
    $insert_items = rtrim($insert_items, ',');
    if ($db->query($insert_items)) {
        // Если все успешно добавилось
        $db->query("DROP TABLE `np_types_of_counterparties`"); // Удаляем старую таблицу
        $db->query("RENAME TABLE `np_types_of_counterparties_tmp` TO `np_types_of_counterparties`"); // Меняем название временной таблицы
    } else {
        $error = true;
    }
} else {
    // Не удалось создать временную таблицу
    $error = true;
}

// строка, которую будем записывать
$text = date("F j, Y, g:i a") . " Справочник типов контрагентов " . (isset($error) ? 'НЕ обновлен' : 'обновлен') . "\n";
//Открываем файл
$fp = fopen("cron.log", "a+");
// записываем в файл текст
fwrite($fp, $text);
// закрываем
fclose($fp);
