<?php
include_once 'system/core/begin.php';
require_once 'system/classes/Time/TimeZone.php';

if (isset($_GET['action']) and $_GET['action'] == 'save' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    $settings = isset($_POST['settings']) ? $_POST['settings'] : null;

    if (is_array($settings) and $settings) {
        $api_key = protection($settings['api_key'], 'base');
        if (empty($api_key)) {
            $error = 'Не указан ключ для взаимодействия с CRM!';
        } elseif (mb_strlen($api_key, 'UTF-8') != 32 or !is_string($api_key) or is_numeric($api_key)) {
            $error = 'Указан некорректный ключ для взаимодействия с CRM!';
        } elseif ($result = $db->query("SELECT COUNT(*) FROM `user` WHERE `api_key` = '" . $api_key . "' AND `id` != '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $error = 'Ключ для взаимодействия с CRM недоступен! Сгенерируйте другой.';
        }

        $max_rows = $settings['max_rows'];
        if (empty($max_rows)) {
            $error = 'Необходимо указать количество отображаемых заказов!';
        } elseif (!in_array($max_rows, array(10, 25, 50, 100, 100, 200, 300, 400, 500))) {
            $error = 'Количество отображаемых заказов указано неверно!';
        }

        $type_sort = $settings['type_sort'];
        if ($type_sort != 'added' and $type_sort != 'changed') $type_sort = 'added';
        
        $country = $settings['country'];
        if (!empty($country) and $country != 0) {
            if ($result = $db->query("SELECT COUNT(*) FROM `countries` WHERE `id` = '" . $country . "'")->fetch_row() and $result[0] == 0) {
                $error = 'Страна не найдена!';
            }
        }

        $doubles = $settings['doubles'];
        if ($doubles != 'no' and $doubles != 'yes') $doubles = 'no';

        $time_zone = protection($settings['time_zone'], 'base');
        $time_zones = TimeZone::getTimeZoneArray();

        if (empty($time_zone)) {
            $time_zone = 0;
        } elseif (!in_array($time_zone, $time_zones)) {
            $error = 'Часовой пояс не найден!';
        }

    } else $error = 'Произошла ошибка!';

    if (!isset($error)) {
        if ($db->query("UPDATE `user` SET `api_key` = '" . $api_key . "', `max_rows` = '" . $max_rows . "', `type_sort` = '" . $type_sort . "', `country` = '" . $country . "', `doubles` = '" . $doubles . "', `timezone` = '" . $time_zone . "' WHERE `id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = 'Произошла ошибка при сохранении настроек!';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

$data['title'] = 'Настройки системы';
include_once 'system/core/header.php';
?>
<script src="/js/jquery.md5.js"></script>
<script>
    $(function(){
        loadSettings();
        $('#form-settings').on('submit', function(){
            saveSettings();
            return false;
        });
    });

    function createApiKey(event) {
        let el = event.target || event.srcElement,
            input = $(el).closest('td').find('input'),
            dt = new Date(),
            t = dt.getHours() + ':' + dt.getMinutes() + ':' + dt.getSeconds(),
            newkey = $.md5('user-<?= strrev(substr(md5($chief['id']), 0, 6)) ?>' + t);
            
        input.val(newkey);
    }
    function saveSettings() {
        $.ajax({
            type: "POST",
            url: "/set_system?action=save",
            data: $('#form-settings').serialize(),
            beforeSend: function() {
                startPreloader();
            },
            success: function(response) {
                let jsonData = JSON.parse(response);
                stopPreloader();
                if (jsonData.success == 1) {
                    showModalWindow(null, null, 'success', 'Настройки системы сохранены!');
                } else {
                    showModalWindow(null, null, 'error', jsonData.error);
                }
            }
        });
    }

    function loadSettings() {
        startPreloader();
        stopPreloader();
    }
</script>
<style>
    .api-key-random {
        opacity: 0;
        position: absolute;
        top: 0px;
        right: 8px;
        transform: translate(0%, 75%);
        transition: opacity .25s ease .1s;
    }
    #api-key:hover ~ .api-key-random, .api-key-random:hover {
        opacity: 1; 
    }
</style>
            <!-- Content -->
            <section class="content">
                <div class="status-panel">
                    <div class="status-panel__row">
                        <div class="status-panel__count"></div>
                        <div class="status-panel__search"></div>
                    </div>
                    <button id="button-save" style="color: green" onclick="saveSettings();"><i class="fa fa-save"></i> Сохранить настройки</button>
                </div>
                <!-- put div here without scroll -->         
                <div class="content__overflow">
                    <form id="form-settings" method="post">
                        <table id="settings__table" class="table" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th style="min-width: 30px">id</th>
                                    <th style="min-width: 300px">Название</th>
                                    <th style="min-width: 325px">Значение</th>
                                    <th style="min-width: 220px">По умолчанию</th>
                                </tr>
                            </thead> 
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>Ключ API для взаимодействия с CRM <i class="fa fa-info-circle" title="Секретный ключ API для взаимодействия с CRM"></i></td>
                                    <td style="position: relative">
                                        <input id="api-key" type="text" name="settings[api_key]" value="<?=protection($user['api_key'], 'display') ?>" style="width: 100%">
                                        <a href="javascript:void(0);" class="api-key-random" onclick="createApiKey(event);" title="Сгенерировать"><i class="fa fa-random"></i></a>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>Отображать заказов <i class="fa fa-info-circle" title="Количество отображаемых заказов на странице"></td>
                                    <td style="overflow: visible">
                                        <select id="max-rows" name="settings[max_rows]" class="chosen-select" style="width: 80px">
                                            <option <?php if ($user['max_rows'] == '10') { echo 'selected="true"'; } ?> value="10">10</option>
                                            <option <?php if ($user['max_rows'] == '25') { echo 'selected="true"'; } ?> value="25">25</option>
                                            <option <?php if ($user['max_rows'] == '50') { echo 'selected="true"'; } ?> value="50">50</option>
                                            <option <?php if ($user['max_rows'] == '100') { echo 'selected="true"'; } ?> value="100">100</option>
                                            <option <?php if ($user['max_rows'] == '200') { echo 'selected="true"'; } ?> value="200">200</option>
                                            <option <?php if ($user['max_rows'] == '300') { echo 'selected="true"'; } ?> value="300">300</option>
                                            <option <?php if ($user['max_rows'] == '400') { echo 'selected="true"'; } ?> value="400">400</option>
                                            <option <?php if ($user['max_rows'] == '500') { echo 'selected="true"'; } ?> value="500">500</option>
                                        </select>
                                    </td>
                                    <td style="color: #757575">50</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>Тип сортировки отображения заказов <i class="fa fa-info-circle" title="Сортировка заказов на странице"></td>
                                    <td style="overflow: visible">
                                        <select id="date-type" name="settings[type_sort]" class="chosen-select" style="width: 180px">
                                            <option <?php if ($user['type_sort'] == 'added') { echo 'selected'; } ?> value="added">По дате добавления</option>
                                            <option <?php if ($user['type_sort'] == 'changed') { echo 'selected'; } ?> value="changed">По дате изменения</option>
                                        </select>
                                    </td>
                                    <td style="color: #757575">По дате добавления</td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td>Страна по умолчанию <i class="fa fa-info-circle"></td>
                                    <td style="overflow: visible">
                                        <select id="country" name="settings[country]" class="chosen-select" style="width: 100%">
                                            <option value="">Все</option>
                <?
                $countries = $db->query("SELECT `id`, `name`, `code` FROM `countries`");
                while ($country = $countries->fetch_assoc()) {
                ?>
                                            <option data-img-src="/img/countries/<?php echo strtolower($country['code']) ?>.png" value="<?php echo $country['id']; ?>"<? echo ($user['country'] == $country['id'] ? ' selected' : '') ?>><?php echo protection($country['name'] . ' (' . $country['code'] . ')', 'display'); ?></option>
                <?
                }
                ?>
                                        </select>
                                    </td>
                                    <td style="color: #757575">Все</td>
                                </tr>
                                <tr>
                                    <td>5</td>
                                    <td>Дубли заявок через API <i class="fa fa-info-circle"></td>
                                    <td style="overflow: visible">
                                        <select id="doubles" name="settings[doubles]" class="chosen-select" style="width: 180px">
                                            <option <?php if ($user['doubles'] == 'no') { echo 'selected'; } ?> value="no">Запрещены</option>
                                            <option <?php if ($user['doubles'] == 'yes') { echo 'selected'; } ?> value="yes">Разрешены</option>
                                        </select>
                                    </td>
                                    <td style="color: #757575">Запрещены</td>
                                </tr>
                                <tr>
                                    <td>6</td>
                                    <td>Часовой пояс заказов <i class="fa fa-info-circle" title="Часовой пояс смещения времени"></td>
                                    <td style="overflow: visible">
                                        <select id="time-zone" name="settings[time_zone]" class="chosen-select" style="width: 100%">
                                            <?php echo TimeZone::getTimeZoneSelect($user['timezone']); ?>
                                        </select>
                                    </td>
                                    <td style="color: #757575">Europe/Kiev</td>
                                </tr>
<?
$date = new DateTime();
?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 15px 0px; line-height: 1.5">
                                        <b>Часовой пояс сервера:</b> <?php echo $date->format('(P \U\T\C)') . ' ' . $date->getTimezone()->getName() . ' - ' . $date->format('Y-m-d H:i:s') ?><br>
                                        <b>Часовой пояс Вашей CRM:</b> <?php echo $date->format('(P \U\T\C)') . ' ' . $date->getTimezone()->getName() . ' - ' . $date->format('Y-m-d H:i:s') ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <input type="submit" style="display: none">
                    </form>
                </div>
<?
$hide_pagination = true;
include_once 'system/core/footer.php';