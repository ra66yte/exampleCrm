<?php
chdir(__DIR__);
include_once 'init.php';

// Создаем временную таблицу
$ddl = "CREATE TABLE IF NOT EXISTS `np_message_code_text_tmp` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `text` varchar(255) null,
            `desc_ua` varchar(255) NULL,
            `desc_ru` varchar(255) NULL,
            `code` char(11) NOT NULL,
            PRIMARY KEY (`id`)
        )";
if ($db->query($ddl)) {
    // Получаем список всех типов груза
    $request = $np
                ->model('CommonGeneral')
                ->method('getMessageCodeText')
                ->params(array(
                    'Page' => 0
                ))
                ->execute();
    $items = $request['data'];
    
    // Добавляем все во временную таблицу
    $insert_items = "INSERT INTO `np_message_code_text_tmp` (`id`, `text`, `desc_ua`, `desc_ru`, `code`) VALUES";
    foreach ($items as $item) {
        $insert_items .= " (null, '" . protection($item['MessageText'], 'base') . "', '" . protection($item['MessageDescriptionUA'], 'base') . "', '" . protection($item['MessageDescriptionRU'], 'base') . "', '" . protection($item['MessageCode'], 'base') . "'),";
    }
    $insert_items = rtrim($insert_items, ',');
    echo $insert_items;
    if ($db->query($insert_items)) {
        // Если все успешно добавилось
        $db->query("DROP TABLE `np_message_code_text`"); // Удаляем старую таблицу с типами отделений
        $db->query("RENAME TABLE `np_message_code_text_tmp` TO `np_message_code_text`"); // Меняем название временной таблицы
    } else {
        $error = true;
    }
} else {
    // Не удалось создать временную таблицу
    $error = true;
}

// строка, которую будем записывать
$text = date("F j, Y, g:i a") . " Справочник перечня ошибок " . (isset($error) ? 'НЕ обновлен' : 'обновлен') . "\n";
//Открываем файл
$fp = fopen("cron.log", "a+");
// записываем в файл текст
fwrite($fp, $text);
// закрываем
fclose($fp);
