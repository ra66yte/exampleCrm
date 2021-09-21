<?php
chdir(__DIR__);
include_once 'init.php';
$error = null;
// Создаем временную таблицу `np_cities_tmp`
$ddl = "CREATE TABLE IF NOT EXISTS `np_cities_tmp` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `city_id` int(11) NOT NULL,
            `desc_ua` varchar(255) NOT NULL,
            `desc_ru` varchar(255) NOT NULL,
            `ref` char(36) NOT NULL,
            `d1` tinyint(4) NOT NULL,
            `d2` tinyint(4) NOT NULL,
            `d3` tinyint(4) NOT NULL,
            `d4` tinyint(4) NOT NULL,
            `d5` tinyint(4) NOT NULL,
            `d6` tinyint(4) NOT NULL,
            `d7` tinyint(4) NOT NULL,
            `is_branch` tinyint(4) NOT NULL,
            `area_ref` char(36) NOT NULL,
            `area_desc_ua` varchar(60) DEFAULT NULL,
            `area_desc_ru` varchar(60) DEFAULT NULL,
            `settlement_type` char(36) DEFAULT NULL,
            `settlement_type_desc_ua` varchar(30) DEFAULT NULL,
            `settlement_type_desc_ru` varchar(30) DEFAULT NULL,
            PRIMARY KEY (`id`)
        )";
if ($db->query($ddl)) {
    // Получаем список всех населенных пунктов, в которых есть отделения Новой Почты
    $request = $np->getCities();
    $cities = $request['data'];

    // Добавляем все нас. пункты во временную таблицу
    $insert_cities = "INSERT INTO `np_cities_tmp` (`id`, `city_id`, `desc_ua`, `desc_ru`, `ref`, `d1`, `d2`, `d3`, `d4`, `d5`, `d6`, `d7`, `is_branch`, `area_ref`, `area_desc_ua`, `area_desc_ru`, `settlement_type`, `settlement_type_desc_ua`, `settlement_type_desc_ru`) VALUES";
    foreach ($cities as $city) {
        $insert_cities .= " (null, '" . protection($city['CityID'], 'int') . "', '" . protection($city['Description'], 'base') . "', '" . protection($city['DescriptionRu'], 'base') . "', '" . protection($city['Ref'], 'base') . "', '" . protection($city['Delivery1'], 'int') . "', '" . protection($city['Delivery2'], 'int') . "', '" . protection($city['Delivery3'], 'int') . "', '" . protection($city['Delivery4'], 'int') . "', '" . protection($city['Delivery5'], 'int') . "', '" . protection($city['Delivery6'], 'int') . "', '" . protection($city['Delivery7'], 'int') . "', '" . protection($city['IsBranch'], 'int') . "', '" . protection($city['Area'], 'base') . "', '" . protection($city['AreaDescription'], 'base') . "', '" . protection($city['AreaDescriptionRu'], 'base') . "', '" . protection($city['SettlementType'], 'base') . "', '" . protection($city['SettlementTypeDescription'], 'base') . "', '" . protection($city['SettlementTypeDescriptionRu'], 'base') . "'),";
    }
    $insert_cities = rtrim($insert_cities, ',');
    if ($db->query($insert_cities)) {
        // Если все успешно добавилось
        $db->query("DROP TABLE `np_cities`"); // Удаляем старую таблицу с нас. пунктами
        $db->query("RENAME TABLE `np_cities_tmp` TO `np_cities`"); // Меняем название временной таблицы с нас. пунктами
    } else {
        $error = true;
    }
} else {
    // Не удалось создать временную таблицу
    $error = true;
}

// строка, которую будем записывать
$text = date("F j, Y, g:i a") . " Справочник населенных пунктов " . (isset($error) ? 'НЕ обновлен' : 'обновлен') . "\n";
//Открываем файл
$fp = fopen("cron.log", "a+");
// записываем в файл текст
fwrite($fp, $text);
// закрываем
fclose($fp);
