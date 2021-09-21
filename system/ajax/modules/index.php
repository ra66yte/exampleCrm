<?php
include_once '../../core/begin.php';
if (isset($_GET['plugin_id']) and is_numeric($_GET['plugin_id'])) {
    $plugin_id = abs(intval($_GET['plugin_id']));
    $result = $db->query("SELECT COUNT(*) FROM `plugin` WHERE `id` = '" . $plugin_id . "'")->fetch_row();
    if ($result[0] > 0) {
        $plugin = $db->query("SELECT `module` FROM `plugin` WHERE `id` = '" . $plugin_id . "'")->fetch_assoc();
        if (file_exists(__DIR__ . '/' . $plugin['module'] . '.php')) {
            require_once $plugin['module'] . '.php';
        } else {
            die('Файл не найден!');
        }
    }
}