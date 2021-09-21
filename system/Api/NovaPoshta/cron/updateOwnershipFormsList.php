<?php
chdir(__DIR__);
include_once 'init.php';

// Создаем временную таблицу
$ddl = "CREATE TABLE IF NOT EXISTS `np_ownership_forms_list_tmp` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `desc` varchar(30) NOT NULL,
            `full_name` varchar(255) not null,
            `ref` char(36) NOT NULL,
            PRIMARY KEY (`id`)
        )";
if ($db->query($ddl)) {
    // Получаем список всех типов груза
    $request = $np
                ->model('Common')
                ->method('getOwnershipFormsList')
                ->params(array(
                    'Page' => 0
                ))
                ->execute();
    $items = $request['data'];
    
    // Добавляем все во временную таблицу
    $insert_items = "INSERT INTO `np_ownership_forms_list_tmp` (`id`, `desc`, `full_name`, `ref`) VALUES";
    foreach ($items as $item) {
        $insert_items .= " (null, '" . protection($item['Description'], 'base') . "', '" . protection($item['FullName'], 'base') . "', '" . protection($item['Ref'], 'base') . "'),";
    }
    $insert_items = rtrim($insert_items, ',');
    if ($db->query($insert_items)) {
        // Если все успешно добавилось
        $db->query("DROP TABLE `np_ownership_forms_list`"); // Удаляем старую таблицу
        $db->query("RENAME TABLE `np_ownership_forms_list` TO `np_ownership_forms_list`"); // Меняем название временной таблицы
    } else {
        $error = true;
    }
} else {
    // Не удалось создать временную таблицу
    $error = true;
}

// строка, которую будем записывать
$text = date("F j, Y, g:i a") . " Справочник форм собственности " . (isset($error) ? 'НЕ обновлен' : 'обновлен') . "\n";
//Открываем файл
$fp = fopen("cron.log", "a+");
// записываем в файл текст
fwrite($fp, $text);
// закрываем
fclose($fp);
