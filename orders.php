<?php
include_once 'system/core/begin.php';

if (!checkAccess('orders') or !checkAccess('statuses')) redirect('/?denied');

if (!isset($_GET['status']) or (isset($_GET['status']) and ($_GET['status'] <> 'all' and !is_numeric($_GET['status'])))) { // Если вообще нет статуса или параметр пустой
    redirect('?status=all');
}
$data['title'] = 'Перечень заказов';
include_once 'system/core/header.php';

?>
            <!-- Content -->
            <section class="content" data-location="orders">
                <div class="border"></div>
                <div class="status-panel" style="padding-bottom: 1px">
                    <div class="status-panel__row">
                        <div class="status-panel__count"></div>
                        <div class="status-panel__search"></div>
                    </div>
                    <span style="background: #F9F9F9; padding: 2px 1px 2px 3px; border: 1px dashed #DDD; border-radius: 4px; margin-right: 7px;">
                        <i class="fa fa-refresh" title="Автоперезагрузка текущего статуса"></i> <input type="text" id="input-autoupdate" value="60" style="border: 1px solid #DDD; min-width: 30px; width: 30px; height: 20px;" spellcheck="false"> сек.
                        <label class="toggle" style="vertical-align: bottom" title="Автоперезагрузка текущего статуса">
                            <input type="checkbox" id="button-autoupdate" class="toggle__input">
                            <div class="toggle__control"></div>
                        </label>
                    </span>
                    <button id="button-search" style="font-size: 14px"><i class="fa fa-search"></i></button>
                    <div style="position: relative">
                        <button id="button-selected" style="font-size: 14px" onclick="showOptions();" disabled><i class="fa fa-cog"></i></button>
                        <div class="status-panel__options">
                            <ul>
                                <li id="print-table"><i class="fa fa-print"></i> Печать таблицы</li>
                                <li class="hr"></li>
                                <li id="button-copy"><i class="fa fa-files-o"></i> Копировать</li>
                                <li id="button-edit" style="color: #8A5A00"><i class="fa fa-edit"></i> Редактировать</li>
                                <li id="button-change-statuses" onclick="changeStatuses();"><i class="fa fa-random"></i> Сменить статусы</li>
                                <li class="hr"></li>
                                <li id="button-delete" style="color: #AE0000" onclick="deleteOrders();"><i class="fa fa-trash-o"></i> Удалить</li>
                            </ul>
                        </div>
                    </div>
                    <button id="button-add" style="color: green"><i class="fa fa-plus-square"></i> Добавить</button>
                    <button id="button-select-all" title="Выбрать все"><i class="fa fa-file-text"></i></button>
                </div>
<?
$right_id = getAccessID('statuses');
// SELECT `orders`.`id` FROM `orders` INNER JOIN `status_order` ON (`orders`.`status` = `status_order`.`id`) INNER JOIN `group_rights` ON (`group_rights`.`access_right` = '1') WHERE `status_order`.`status` = 'on' AND `orders`.`deleted` = '0' AND `orders`.`client_id` = '1' AND `group_rights`.`group_id` = '1' AND `group_rights`.`value` = `status_order`.`id` AND `orders`.`office_id` IN (1)
$count_orders = $db->query("SELECT COUNT(*) FROM `orders` INNER JOIN `status_order` ON (`orders`.`status` = `status_order`.`id_item`) INNER JOIN `group_rights` ON (`group_rights`.`access_right` = '" . $right_id . "') WHERE `status_order`.`status` = 'on' AND `orders`.`deleted_at` = '0' AND `orders`.`client_id` = '" . $chief['id'] . "' AND `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`value` = `status_order`.`id_item` AND `status_order`.`client_id` = '" . $chief['id'] . "' AND `group_rights`.`client_id` = '" . $chief['id'] . "'")->fetch_row();
?>
                <div id="orders-status-list" class="status-list">
                    <button class="tabs-arrow" id="button-arrow-left-tabs">◄</button>
                    <ul class="status-list__item">
                        <li id="tab-status-0">
                            <a href="javascript:void(0);" data-id="0" data-src="/orders.php?status=all"<?=(($_GET['status'] == 'all' or abs(intval($_GET['status'])) == 0)  ? ' class="tab-status-active"' : '')?>>Все (<b><?=$count_orders[0]?></b>)</a>
                        </li>
<?
 
$statuses = $db->query("SELECT `status_order`.`id_item`, `status_order`.`color`, `status_order`.`name` FROM `status_order` INNER JOIN `group_rights` ON (`status_order`.`id_item` = `group_rights`.`value`) WHERE `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`access_right` = '" . $right_id . "' AND `status_order`.`client_id` = '" . $chief['id'] . "' AND `status_order`.`status` = 'on' AND `group_rights`.`client_id` = '" . $chief['id'] . "' ORDER BY `status_order`.`sort`");
while ($status = $statuses->fetch_assoc()) {
    $count = $db->query("SELECT COUNT(*) FROM `orders` INNER JOIN `status_order` ON (`orders`.`status` = `status_order`.`id_item`) INNER JOIN `group_rights` ON (`group_rights`.`access_right` = '" . $right_id . "') WHERE `status_order`.`status` = 'on' AND `orders`.`client_id` = '" . $chief['id'] . "' AND `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`value` = `status_order`.`id_item` AND `orders`.`status` = '" . $status['id_item'] . "' AND `orders`.`deleted_at` = '0' AND `status_order`.`client_id` = '" . $chief['id'] . "' AND `group_rights`.`client_id` = '" . $chief['id'] . "'")->fetch_row();
?>
                        <li id="tab-status-<?=$status['id_item']?>">
                            <a href="javascript:void(0);" data-id="<?=$status['id_item']?>" style="background: <?=protection($status['color'], 'display')?>"><?=protection($status['name'], 'display')?> (<b><?=$count[0]?></b>)</a>
                        </li>
<?
}
echo "\r\n";
?>
                    </ul>
                    <button class="tabs-arrow" id="button-arrow-right-tabs">►</button> 
                </div>      
                <div class="content__overflow" style="border-top: none">
                    <form id="form-orders" method="post" spellcheck="false" autocomplete="off">
                        <table id="orders__table" class="table has-tabs" cellpadding="0" cellspacing="0" style="border-top: none">
                        <!-- <col span="21"> -->
                            <thead>
                                <tr>
                                    <th data-name="id" style="min-width: 30px">id</th>
                                    <th data-name="id_order" style="min-width: 80px">order_id</th>
                                    <th data-name="customer" style="min-width: 160px">Покупатель</th>
                                    <th data-name="country" style="min-width: 200px">Направление</th>
                                    <th data-name="phone" style="min-width: 140px">Телефон</th>
                                    <th data-name="comment" style="min-width: 200px">Комментарий</th>
                                    <th data-name="amount" style="min-width: 110px">Сумма</th>
                                    <th data-name="product_id" style="min-width: 280px">Товар</th>
                                    <th data-name="payment_method" style="min-width: 130px">Оплата</th>
                                    <th data-name="delivery_method" style="min-width: 130px">Тип доставки</th>
                                    <th data-name="delivery_adress" style="min-width: 150px">Адрес доставки</th>
                                    <th data-name="ttn" style="min-width: 200px">ТТН</th>
                                    <th data-name="ttn_status" style="min-width: 180px">ТТН статус</th>
                                    <th data-name="departure_date" style="min-width: 150px">Отправлено</th>
                                    <th data-name="time_added" style="min-width: 150px">Добавлено</th>
                                    <th data-name="updated" style="min-width: 150px">Обновлено</th>
                                    <th data-name="employee" style="min-width: 150px">Сотрудник</th>
                                    <th data-name="site" style="min-width: 150px">Сайт</th>
                                    <th data-name="ip" style="min-width: 100px">IP</th>
                                    <th data-name="status" style="min-width: 100px">Статус</th>
                                    <th data-name="completed" style="min-width: 70px">Сдано</th>
                                </tr>
                                <tr class="table-row-search">
                                    <td><input type="text" name="id"></td>
                                    <td><input type="text" name="id_order"></td>
                                    <td><input type="text" name="customer"></td>
                                    <td>
                                        <select name="country" class="chosen-select">
<?
$countries = $db->query("SELECT COUNT(*) FROM `countries` INNER JOIN `countries_list` ON (`countries`.`id` = `countries_list`.`country_id`) WHERE `countries_list`.`client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($countries[0] == 0) {
?>
                                            <option value="">- Не указано -</option>
<?
} else {
    $countries = $db->query("SELECT `countries`.`id`, `countries`.`name`, `countries`.`code` FROM `countries` INNER JOIN `countries_list` ON (`countries`.`id` = `countries_list`.`country_id`) WHERE `countries_list`.`client_id` = '" . $chief['id'] . "' ORDER BY `id`");
?>
                                            <option value="">Все</option>
<?
    while ($country = $countries->fetch_assoc()) {
?>
                                            <option data-img-src="/img/countries/<?=strtolower($country['code'])?>.png" value="<?=$country['id']?>"><?=protection($country['name'] . ' (' . $country['code'] . ')', 'display')?></option>
<?
    }
}
?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="phone"></td>
                                    <td><input type="text" name="comment"></td>
                                    <td><input type="text" name="amount"></td>
                                    <td>
                                        <select name="product_id" class="chosen-select">
<?php
$count_products = $db->query("SELECT COUNT(*) FROM `products` WHERE `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($count_products[0] == 0) {
?>
                                            <option value="">- Не указано -</option>
<?
} else {
    $query = $db->query("SELECT `id_item`, `name`, `model`, `status` FROM `products` WHERE `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'");
?>
                                            <option data-num="first" value="">Все</option>
<?
    while ($product = $query->fetch_assoc()) {
?>
                                            <option value="<?=$product['id_item']?>"<?=$product['status'] == 'off' ? ' disabled' : ''?>><?=protection($product['id_item'] . ' - ' . $product['name'] . ' ' . $product['model'], 'display')?></option>
<?
        echo "\r\n";
    }
}
?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="payment_method" class="chosen-select">
<?php
$count_payment_methods = $db->query("SELECT COUNT(*) FROM `payment_methods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($count_payment_methods[0] == 0) {
?>
                                            <option value="">- Не указано -</option>
<?
} else {
    $query = $db->query("SELECT `id_item`, `name`, `status` FROM `payment_methods` WHERE `client_id` = '" . $chief['id'] . "'");
?>
                                            <option data-num="first" value="">Все</option>
<?
    while ($payment_method = $query->fetch_assoc()) {
?>
                                            <option value="<?=$payment_method['id_item']?>"<?=$payment_method['status'] == 'off' ? ' disabled' : ''?>><?=protection($payment_method['name'], 'display')?></option>
<?
        echo "\r\n";
    }
}
?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="delivery_method" class="chosen-select">
<?php
$count_delivery_methods = $db->query("SELECT COUNT(*) FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($count_delivery_methods[0] == 0) {
?>
                                            <option value="">- Не указано -</option>
<?
} else {
    $query = $db->query("SELECT `id_item`, `name`, `status` FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "'");
    ?>
                                            <option data-num="first" value="">Все</option>
    <?
    while ($delivery_method = $query->fetch_assoc()) {
?>
                                            <option value="<?=$delivery_method['id_item']?>"<?=$delivery_method['status'] == 'off' ? ' disabled' : ''?>><?=protection($delivery_method['name'], 'display')?></option>
<?
        echo "\r\n";
    }
}
?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="delivery_adress"></td>
                                    <td><input type="text" name="ttn"></td>
                                    <td><input type="text" name="ttn_status"></td>
                                    <td>с <input type="text" id="departure_date_start" name="departure_date_start" class="pickerdate"> по <input type="text" id="departure_date_end" name="departure_date_end" class="pickerdate"></td>
                                    <td>с <input type="text" name="date_added" style="display: inline-block; width: 64px; font-size: 11px;"> по <input type="text" name="date_added2" style="display: inline-block; width: 64px; font-size: 11px;"></td>
                                    <td>с <input type="text" name="departure_date4" style="display: inline-block; width: 64px; font-size: 11px;"> по <input type="text" name="departure_date5" style="display: inline-block; width: 64px; font-size: 11px;"></td>
                                    <td style="max-width: 160px">
                                        <select name="employee" class="chosen-select">
<?
$employees = $db->query("SELECT COUNT(*) FROM `user` WHERE (`id` = '" . $chief['id'] . "' AND `chief_id` = 0) OR `chief_id` = '" . $chief['id'] . "'")->fetch_row();
if ($employees[0] == 0) {
?>
                                            <option value="">- Не указано -</option>
<?
} else {
    $employees = $db->query("SELECT `id_item`, `name` FROM `user` WHERE (`id` = '" . $chief['id'] . "' AND `chief_id` = 0) OR `chief_id` = '" . $chief['id'] . "'");
?>
                                            <option value="">Все</option>
<?
    while ($employee = $employees->fetch_assoc()) {
?>
                                            <option value="<?=$employee['id_item']?>"> <?=protection($employee['name'], 'display')?></option>
<?
    }
}
?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="site" class="chosen-select">
<?
$sites = $db->query("SELECT COUNT(*) FROM `sites` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($sites[0] == 0) {
?>
                                            <option value="">- Не указано -</option>
<?
} else {
    $sites = $db->query("SELECT `id_item`, `url` FROM `sites` WHERE `client_id` = '" . $chief['id'] . "'");
?>
                                            <option value="">Все</option>
<?
    while ($site = $sites->fetch_assoc()) {
?>
                                            <option value="<?=$site['id_item']?>"> <?=protection($site['url'], 'display')?></option>
<?
    }
}
?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="ip"></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </thead> 
                            <tbody>
                            </tbody>
                        </table>
                        <input type="submit" style="display: none">
                    </form>
                </div>
<?
include_once 'system/core/footer.php';
