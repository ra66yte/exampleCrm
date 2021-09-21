<?php
include_once 'system/core/begin.php';

if (!checkAccess('plugins')) redirect('/denied');

$data['title'] = 'Список модулей';
include_once 'system/core/header.php';
?>
<script>
    $(function() {
        let form = $('#form-plugins');
        loadPlugins();

        form.on('dblclick', 'tr.table__item', function(e) {
            if (!$(this).hasClass('disabled')) {
                if ($(this).hasClass('blocked-row')) {
                    // Запрещаем открытие редактируемой строки
                } else {
                    /* Отправляем ajax на обновление блокировки заказа */
                    $('tr.table__active').removeClass('table__active'); // Убираем выделеные у всех выделенных заказов
                    $(this).addClass('table__active static blocked-row'); //  Этот заказ активный
                    $('.status-panel div.status-panel__count').html('<i class="fa fa-info-circle"></i> Выделено: ' + $('.table__active').length).show();
                    $('.status-panel button#button-selected').removeAttr('disabled');
                }
                let plugin_id = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/system/ajax/modules/index.php?plugin_id=" + plugin_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        showModalWindow('Модуль - <b>' + jsonData.title.name + '</b>', '/system/ajax/modules/index.php?plugin_id=' + plugin_id);
                    }    
                });
            }
        });
    });

    function loadPlugins(page) {
        if (!page) {
            page = getParameterByName('page');
        } else {
            let stateObj = { foo: "plugins" };
            history.pushState(stateObj, "plugins", '?page=' + page);
        }
        $('.status-panel__count').text('').hide();
    
        startPreloader();
        $('#form-plugins tbody').load('/system/ajax/plugins.php?show=true&page=' + (page ? page : 1));
        $('.pagination__info').load('/system/ajax/showPagination.php?module=plugins&page=' + (page ? page : 1));
        stopPreloader();
    }

    function setUpPlugin(pluginId) {
        if (isNaN(pluginId)) return false;
        $.ajax({
            type: "POST",
            url: "system/ajax/plugins.php?action=setup_plugin",
            data: { 'id': pluginId },
            success: function(response) {    
                let jsonData = JSON.parse(response),
                    page = getParameterByName('page');
                if (jsonData.success == 1) {
                    loadPlugins(page);
                } else {
                    showModalWindow(null, null, 'error', jsonData.error);
                }
            }    
        });
    }
</script>
                <!-- Content -->
                <section class="content">
                <div class="status-panel">
                    <div class="status-panel__row">
                        <div class="status-panel__count"></div>
                        <div class="status-panel__search"></div>
                    </div>
                    <!-- <button id="button-search" style="font-size: 14px"><i class="fa fa-search"></i></button> -->
                    <div style="position: relative">
                        <button id="button-selected" style="font-size: 14px" onclick="showOptions();" disabled><i class="fa fa-cog"></i></button>
                        <div class="status-panel__options">
                            <ul>
                                <li id="print-table"><i class="fa fa-print"></i> Печать таблицы</li>
                                <li class="hr"></li>
                                <li id="button-edit" style="color: #8A5A00"><i class="fa fa-edit"></i> Настроить</li>
                            </ul>
                        </div>
                    </div>
                    <button id="button-refresh" style="color: green" onclick="loadPlugins();"><i class="fa fa-refresh"></i> Обновить список</button>
                    <button id="button-select-all" title="Выбрать все"><i class="fa fa-file-text"></i></button>
                </div>
                <!-- put div here without scroll -->         
                <div class="content__overflow">
                    <form id="form-plugins" method="post" autocomplete="off">
                        <table id="plugins-table" class="table" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th style="min-width: 30px">id</th>
                                    <th style="min-width: 220px">Название</th>
                                    <th style="min-width: 130px">Логотип</th>
                                    <th style="min-width: 130px">Модуль</th>
                                    <th style="min-width: 200px">Направление</th>
                                    <th style="min-width: 180px">Описание</th>
                                    <th style="min-width: 100px">Статус</th>
                                    <th style="min-width: 100px">Активация</th>
                                </tr>
                                <tr class="table-row-search">
                                    <td style="max-width: 20px"><input type="text" name="id"></td>
                                    <td style="max-width: 200px"><input type="text" name="name"></td>
                                    <td></td>
                                    <td style="max-width: 110px"><input type="text" name="module"></td>
                                    <td style="max-width: 180px">
                                        <select name="country" class="chosen-select">
<?
$countries = $db->query("SELECT `id` FROM `countries`")->num_rows;
if ($countries == 0) {
?>
                                            <option value="">- Не указано -</option>
<?
} else {
    $countries = $db->query("SELECT `id`, `name`, `code` FROM `countries`");
?>
                                            <option value="">Все</option>
<?
    while ($country = $countries->fetch_assoc()) {
?>
                                            <option data-img-src="/img/countries/<?=strtolower($country['code'])?>.png" value="<?php echo $country['id']; ?>"><?php echo protection($country['name'] . ' (' . $country['code'] . ')', 'display'); ?></option>
<?
    }
}
?>
                                        </select>
                                    </td>
                                    <td style="max-width: 160px"><input type="text" name="comment"></td>
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
/*
CREATE TABLE `plugin` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(60) not null,
    `module` varchar(60) not null,
    `logo` varchar(60) null,
    `country` int(11) null default 0,
    `comment` varchar(255) null,
    primary key(`id`)
)

CREATE TABLE `plugins` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) not null,
    `plugin_id` INT(11) not null,
    `installed` enum('0','1') not null default '0',
    `status` enum('0','1') not null default '0',
    primary key(`id`)
)
*/