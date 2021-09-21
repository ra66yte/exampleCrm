<?php
include_once '../core/begin.php';

if (isset($_GET['action']) and $_GET['action'] == 'setup_plugin' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    $plugin_id = isset($_POST['id']) ? abs(intval($_POST['id'])) : null;
    if (!isset($plugin_id)) $error = 'Модуль не указан!';
    if ($result = $db->query("SELECT COUNT(*) FROM `plugin` WHERE `id` = '" . $plugin_id . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Модуль не найден!';
    }
    if (!isset($error)) {
        $result = $db->query("SELECT COUNT(*) FROM `plugins` WHERE `plugin_id` = '" . $plugin_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
        if ($result[0] == 0) {
            $sql = "INSERT INTO `plugins` (`id`, `client_id`, `plugin_id`, `installed`, `status`) VALUES (null, '" . $chief['id'] . "', '" . $plugin_id . "', '1', '0')";
        } else {
            $sql = "UPDATE `plugins` SET `installed` = '1' WHERE `plugin_id` = '" . $plugin_id . "' AND `client_id` = '" . $chief['id'] . "'";
        }
        if ($db->query($sql)) {
            $success = 1;
        } else {
            $error = 'Не удалось установить модуль!';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['show']) and $_GET['show'] == 'true') {
    $items_on_page = isset($user['max_rows']) ? $user['max_rows'] : $data['orders_on_page'];
    $count = $db->query("SELECT COUNT(*) FROM `plugin`")->fetch_row();
    if ($count[0] > 0) {
        $countPages = k_page($count[0], $items_on_page);
        $currentPage = page($countPages);
        $start = ($currentPage * $items_on_page) - $items_on_page;
        $plugins = $db->query("SELECT * FROM `plugin` ORDER by `id` ASC LIMIT $start, $items_on_page");
        while ($plugin = $plugins->fetch_assoc()) {
            $plugin_country = $db->query("SELECT `name`, `code` FROM `countries` WHERE `id` = '" . $plugin['country'] . "'")->fetch_assoc();
            $country = '<img src="/img/countries/' . strtolower($plugin_country['code']) . '.png" alt="flag"> ' . protection($plugin_country['name'] . ' (' . $plugin_country['code'] . ')', 'display');
?>
        <tr data-id="<?=$plugin['id']?>" class="table__item<?=(isActivatedPlugin($plugin['id']) ? '' : ' disabled')?>">
            <td><?=$plugin['id']?></td>
            <td style="font-size: 14px"><?=(isInstalledPlugin($plugin['id']) ? '<b class="table__item-installed" style="font-size: 14px !important;"><i class="fa fa-check-circle"></i>' : '<b><i class="fa fa-hdd-o"></i>')?> <?=protection($plugin['name'], 'display')?></b></td>
            <td><img src="/system/images/modules/<?=protection($plugin['logo'], 'display')?>" alt="logo" class="table__item-plugin-logo"></td>
            <td class="center"><b><?=protection($plugin['module'], 'display')?></b></td>
            <td class="center"><?=$country?></td>
            <td><?=protection($plugin['comment'], 'display')?></td>
            <td class="center"><?=(isInstalledPlugin($plugin['id']) ? '<span class="table__item-installed"><i class="fa fa-check-circle"></i> установлен</span>' : '<a href="javascript:void(0);" onclick="setUpPlugin(\'' . $plugin['id'] . '\');" class="table__item-download"><i class="fa fa-download"></i> установить</a>')?></td>
            <td>
<?
            if (isInstalledPlugin($plugin['id'])) {
?>
                <label class="toggle">
                    <input type="checkbox"<?=(isActivatedPlugin($plugin['id']) ? ' checked' : '')?> onclick="changeStatus('plugins', '<?=(isActivatedPlugin($plugin['id']) ? '1' : '0')?>', '<?=$plugin['id']?>');" class="toggle__input">
                    <div class="toggle__control"></div>
                </label>
<?
            }
?>
            </td>
        </tr>
<?
        }
    } else {
?>
        <tr class="no-result">
            <td colspan="8">Здесь ничего нет.</td>
        </tr>
<?
    }
}