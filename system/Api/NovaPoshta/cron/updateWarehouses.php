<?php
chdir(__DIR__);
include_once 'init.php';

// Создаем временную таблицу `np_warehous_tmp`
$ddl = "CREATE TABLE IF NOT EXISTS `np_warehouses_tmp` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `site_key` int not null,
            `desc_ua` varchar(255) NOT NULL,
            `desc_ru` varchar(255) NOT NULL,
            `short_address_ua` varchar(180) not null,
            `short_address_ru` varchar(180) not null,
            `phone` varchar(20) not null,
            `type_of_warehouse` char(36),
            `ref` char(36) NOT NULL,
            `number` int not null,
            `city_ref` char(36) not null,
            `city_desc_ua` varchar(120) not null,
            `city_desc_ru` varchar(120) not null,
            `settlement_ref` char(36) not null,
            `settlement_desc` varchar(120) not null,
            `settlement_area_desc` varchar(120) not null,
            `settlement_regions_desc` varchar(120) not null,
            `settlement_type_desc` varchar(30) not null,
            `post_finance` enum('0','1') not null,
            `bicycle_parking` enum('0','1') not null,
            `payment_access` int not null,
            `pos_terminal` enum('0','1') not null,
            `international_shipping` enum('0','1') not null,
            `self_service_workplaces_count` int not null,
            `total_max_weight_allowed` decimal(24,2) not null,
            `place_max_weight_allowed` decimal(24,2) not null,
            `sending_limitations_on_dimensions` json not null,
            `receiving_limitations_on_dimensions` json not null,
            `reception` json not null,
            `delivery` json not null,
            `schedule` json not null,
            `district_code` varchar(60) not null,
            `warehouse_status` varchar(60) not null,
            `category_of_warehouse` varchar(60) not null,
            `direct` varchar(120) not null,
            `region_city` varchar(60) null,
            `warehouse_for_agent` enum('0','1') not null,
            `postomat_for` varchar(30) null,
            PRIMARY KEY (`id`)
        )";
if ($db->query($ddl)) {
    // Получаем список всех отделений
    $request = $np->getWarehouses();
    $items = $request['data'];
    
    // Добавляем все нас. пункты во временную таблицу
    $insert_items = "INSERT INTO `np_warehouses_tmp` (`id`, `site_key`, `desc_ua`, `desc_ru`, `short_address_ua`, `short_address_ru`, `phone`, `type_of_warehouse`, `ref`, `number`, `city_ref`, `city_desc_ua`, `city_desc_ru`, `settlement_ref`, `settlement_desc`, `settlement_area_desc`, `settlement_regions_desc`, `settlement_type_desc`, `post_finance`, `bicycle_parking`, `payment_access`, `pos_terminal`, `international_shipping`, `self_service_workplaces_count`, `total_max_weight_allowed`, `place_max_weight_allowed`, `sending_limitations_on_dimensions`, `receiving_limitations_on_dimensions`, `reception`, `delivery`, `schedule`, `district_code`, `warehouse_status`, `category_of_warehouse`, `direct`, `region_city`, `warehouse_for_agent`, `postomat_for`) VALUES";
    foreach ($items as $item) {
        $insert_items .= " (null, '" . protection($item['SiteKey'], 'int') . "', '" . protection($item['Description'], 'base') . "', '" . protection($item['DescriptionRu'], 'base') . "', '" . protection($item['ShortAddress'], 'base') . "', '" . protection($item['ShortAddressRu'], 'base') . "', '" . protection($item['Phone'], 'base') . "', '" . protection($item['TypeOfWarehouse'], 'base') . "', '" . protection($item['Ref'], 'base') . "', '" . protection($item['Number'], 'int') . "', '" . protection($item['CityRef'], 'base') . "', '" . protection($item['CityDescription'], 'base') . "', '" . protection($item['CityDescriptionRu'], 'base') . "', '" . protection($item['SettlementRef'], 'base') . "', '" . protection($item['SettlementDescription'], 'base') . "', '" . protection($item['SettlementAreaDescription'], 'base') . "', '" . protection($item['SettlementRegionsDescription'], 'base') . "', '" . protection($item['SettlementTypeDescription'], 'base') . "', '" . protection($item['PostFinance'], 'int') . "', '" . protection($item['BicycleParking'], 'int') . "', '" . protection($item['PaymentAccess'], 'int') . "', '" . protection($item['POSTerminal'], 'int') . "', '" . protection($item['InternationalShipping'], 'int') . "', '" . protection($item['SelfServiceWorkplacesCount'], 'int') . "', '" . number_format(abs(floatval($item['TotalMaxWeightAllowed'])), 2, '.', '') . "', '" . number_format(abs(floatval($item['PlaceMaxWeightAllowed'])), 2, '.', '') . "', '" . protection(json_encode($item['SendingLimitationsOnDimensions']), 'base') . "', '" . protection(json_encode($item['ReceivingLimitationsOnDimensions']), 'base') . "', '" . protection(json_encode($item['Reception']), 'base') . "', '" . protection(json_encode($item['Delivery']), 'base') . "', '" . protection(json_encode($item['Schedule']), 'base') . "', '" . protection($item['DistrictCode'], 'base') . "', '" . protection($item['WarehouseStatus'], 'base') . "', '" . protection($item['CategoryOfWarehouse'], 'base') . "', '" . protection($item['Direct'], 'base') . "', '" . protection($item['RegionCity'], 'base') . "', '" . protection($item['WarehouseForAgent'], 'int') . "', '" . (isset($item['PostomatFor']) ? protection($item['PostomatFor'], 'base') : '') . "'),";
    }
    $insert_items = rtrim($insert_items, ',');
    if ($db->query($insert_items)) {
        // Если все успешно добавилось
        $db->query("DROP TABLE `np_warehouses`"); // Удаляем старую таблицу
        $db->query("RENAME TABLE `np_warehouses_tmp` TO `np_warehouses`"); // Меняем название временной таблицы
    } else {
        $error = true;
    }
} else {
    // Не удалось создать временную таблицу
    $error = true;
}

// строка, которую будем записывать
$text = date("F j, Y, g:i a") . " Справочник отделений " . (isset($error) ? 'НЕ обновлен' : 'обновлен') . "\n";
//Открываем файл
$fp = fopen("cron.log", "a+");
// записываем в файл текст
fwrite($fp, $text);
// закрываем
fclose($fp);
