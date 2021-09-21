<?php
include_once 'system/core/begin.php';

if (!checkAccess('clients')) redirect('/denied');

if (!isset($_GET['type']) or (isset($_GET['type']) and ($_GET['type'] <> 'all' and !is_numeric($_GET['type'])))) { // Если вообще нет типа или параметр пустой
    header('Location: ?type=all');
    exit;
}

$data['title'] = 'Клиенты';
include_once 'system/core/header.php';
?>
<script>
    $(function() {
        // Поиск по таблице
        let form = $('#form-clients'),
            table = $('#clients-table');

        form.find(".chosen-select").chosen()
            .change(function(e){
                form.trigger('submit');
            });

        form.on('submit', function(e){
            let type = getParameterByName('type') ? getParameterByName('type') : 'all',
                page = getParameterByName('page'),
                data = $(this).serializeArray();

            if ($('.status-panel__search').length) {
                $('.status-panel__search div').remove();
            }
            $.each(data, function(e){
                if (this.value != '') {
                    let thisName = this.name,
                        thisValue = this.value,
                        date_word = '';
                        
                    if (thisName == 'date_added_start' || thisName == 'date_added_end') {
                        thisName = 'date_added';
                        if (this.name == 'date_added_start') date_word = 'с';
                        else date_word = 'по';
                    }
                    let field_name = table.find('th[data-name=' + thisName + ']').text();
                    $('.status-panel__search').append('<div data-field="' + this.name + '" class="data-field"><span></span> <i class="fa fa-remove" onclick="RemoveSearchField(\'' + this.name + '\');"></i></div>');
                    $('.status-panel__search div').last().find('span').text(field_name + ': ' + (date_word != '' ? date_word + ' ' : '') + this.value);


                    let detect = new MobileDetect(window.navigator.userAgent);
                    if (detect.mobile()) {
                        $.each($('tr.table-row-search select[name=' + this.name + ']'), function(e){
                            let value_select = $(this).find('option[value=' + thisValue  + ']').text();
                            $('.status-panel__search div').last().find('span').text(field_name + ': ' + value_select);
                        });
                    } else {
                        $.each($('tr.table-row-search select[name=' + this.name + ']'), function(e){
                            let value_select = $(this).next().find('a.chosen-single span').text();
                            $('.status-panel__search div').last().find('span').text(field_name + ': ' + value_select);
                        });
                    }
                    
                }
            });
            
            $.ajax({ 
                url: '/system/ajax/clients-row-search.php?type=' + type + '&page=' + (page ? page : 1),
                method: 'POST',
                cache: false,
                data: data,
                beforeSend: function(){
                    startPreloader();
                },
                success: function(response){
                    let jsonData = JSON.parse(response),
                        template_orders_table,
                        pagination;

                    $('#clients-table tbody').html('');
                    
                    // Items
                    template_orders_table = renderRows(jsonData.rows, true);
                    $('#clients-table tbody').html(template_orders_table);
                    
                    
                    // Навигация
                    pagination = renderPagination(jsonData.pagination);
                    $('.pagination__info').html(pagination);
                    // Скролл в начало
                    $('.content__overflow').scrollTop(0);
                },
                complete: function (){
                    stopPreloader();
                }
            });
            return false; 
        });

        $('button#button-search').on('click', function(e) {
            form.trigger('submit');
        });

        $('#form-clients').on('dblclick', 'tr.table__item', function(e) {
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
                let client_id = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/ajax_viewClient?client_id=" + client_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        showModalWindow('Клиент - <b>' + jsonData.title.client_name + '</b>', '/ajax_viewClient?client_id=' + client_id);
                    }    
                });
            }
        });

        $('.status-panel').on('click', '#button-edit', function(e) {
            if (!$(this).hasClass('disabled')) {
                let client_id = $('tr.table__active').attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/ajax_viewClient?client_id=" + client_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        showModalWindow('Клиент - <b>' + jsonData.title.client_name + '</b>', '/ajax_viewClient?client_id=' + client_id);
                    }    
                });
            }
        });

        // Меняем таб статуса на активный и наоборот
        $(document).on('click', 'ul.status-list__item li a', function (e) {
            if (!$(this).hasClass('noactive')) {
                $('ul.status-list__item li a').removeClass('tab-status-active');
                $(this).addClass('tab-status-active');
            } else {
                return false;
            }
        });

        // buttons arrow right
        $(document).on('click', '#button-arrow-right-tabs', function (e) {
            let value = $('.status-list').scrollLeft() + 250;
            $('.status-list').animate({ scrollLeft: value }, "slow");
        });
        // buttons arrow left
        $(document).on('click', '#button-arrow-left-tabs', function (e) {
            let value = $('.status-list').scrollLeft() - 250;
            $('.status-list').animate({ scrollLeft: value }, "slow");
        });

        // Открываем таб
        if (!getParameterByName('type')) {
            TabClients('all');
        } else {
            TabClients(getParameterByName('type'));
        }

    });

    function RemoveSearchField(field) {
        $.each($('tr input, tr select'), function(e) {
            if ($(this).attr('name') == field) {
                $('.status-panel__search div[data-field=' + field + ']').remove();
                $(this).val('');
                $('#form-clients').trigger('submit');
            }
        });
        $('.chosen-select').trigger('chosen:updated');
    }

    function renderRows(data, search = false){
        let template_orders_table = '',
            countTd = $('#clients-table thead').find('tr td').length;

        if (Array.isArray(data) && data.length) {
                $.each(data, function(i, item){
                    template_orders_table += ('<tr data-id="' + item.id_item + '" class="table__item">' +
                                                '<td><input type="checkbox" name="item[' + item.id_item + ']">' + item.id_item + '</td>' +
                                                '<td>' + item.name + '</td>' +
                                                '<td class="center"><img src="/img/countries/' + item.country_code + '.png" alt="*"> ' + item.country_name + '</td>' +
                                                '<td class="center">' + item.phone + '</td>' +
                                                '<td class="center">' + item.group + '</td>' +
                                                '<td class="center">' + item.email + '</td>' +
                                                '<td>' + item.comment + '</td>' +
                                                '<td class="center">' + item.date_added + '</td>' +
                                                '<td class="center">' + item.site + '</td>' +
                                                '<td class="center">' + item.ip + '</td>' +
                                            '</tr>');
                });
        } else {
            
            if (data == 'empty' && getParameterByName('type') != 'all') {
                window.location.href = '/clients?status=all';
            }
            
            template_orders_table = ('<tr class="no-result">' +
                                        '<td colspan="' + countTd + '">' + ((search && $('.status-panel__search div').length)? 'Ничего не найдено!' : 'Здесь ничего нет.') + '</td>' +
                                    '</tr>');   
        }
        return template_orders_table;
    }
    // TabClients()
    function TabClients(clients, pageLocation = false) {
        let page;
        if (arguments.length == 0) {
            clients = "all";
        } else {
            if (clients == 0) clients = "all";
        }
        if (pageLocation) {
            page = pageLocation;
        } else if (getParameterByName('page')) {
            page = getParameterByName('page');
        }
        
        if (page && getParameterByName('type') == clients){
            stateObj = { foo: "clients" };
            history.pushState(stateObj, "clients", '?type=' + clients + '&page=' + page);
        } else {
            stateObj = { foo: "clients" };
            history.pushState(stateObj, "clients", '?type=' + clients);   
            page = null; // Ошибка пре переключении таба 
            
        }
        
        // Обновляем счетчики клиентов
        updateClientsCount();
        // Подгружаем контент с клиентами

        if ($('.status-panel__search div').length) {
            $('#form-clients').trigger('submit');
        } else {
            $.ajax({ 
                url: '/system/ajax/clients.php?type=' + clients + '&page=' + (page ? page : 1),
                method: 'POST',
                cache: false,
                data : {},
                beforeSend: function(){
                    startPreloader();
                },
                success: function(response){
                    let jsonData = JSON.parse(response),
                        template_orders_table,
                        pagination;

                    $('#clients-table tbody').html('');
                    
                    // Items
                    template_orders_table = renderRows(jsonData.rows);
                    $('#clients-table tbody').html(template_orders_table);
                    
                    
                    
                    // Навигация
                    pagination = renderPagination(jsonData.pagination);
                    $('.pagination__info').html(pagination);
                    // Скролл в начало
                    $('.content__overflow').scrollTop(0);
                },
                complete: function (){
                    stopPreloader();
                }
            });
        }
    }

    // Обновление счетчика заказов во всех статусах
    function updateClientsCount () {
        $.ajax({
            url: window.location.protocol + "//" + window.location.hostname + "/ajax_updateClientsCount",
            method: 'POST',
            data: { clients : true }, // Пустые параметры нельзя передавать
            success: function(response) {
                var data = JSON.parse(response);
                $('.status-list ul li').each(function(){
                    var attr_id = $(this).attr('id');
                    var arr = attr_id.split('-');
                    var id = arr[2];                  
                    $('#tab-clients-' + id).find('b').text(data.count_clients[id]);
                    if (getParameterByName('type') == id) {
                        $('.status-list ul li a').removeClass('tab-status-active');
                        $('#tab-clients-' + id + ' a').addClass('tab-status-active');
                    }
                    if (data.count_clients[id] < 1 && id != 0){
                        $('#tab-clients-' + id).removeAttr('onclick');
                        $('#tab-clients-' + id).css('opacity', '0.5');
                        $('#tab-clients-' + id + ' a').addClass('noactive');
                    } else {
                        $('#tab-clients-0').attr('onclick', 'TabClients(\'all\');');
                        $('#tab-clients-' + id).attr('onclick', 'TabClients(\'' + id + '\');');
                        $('#tab-clients-' + id).css('opacity', '1.0');
                    }
                });
            }
        });
    }
    
    function addClient() {
        showModalWindow('Добавление клиента', '/system/ajax/addClient.php');
    }

    function deleteClients() {
        let countClients = $('tr.table__active').length;
        if (countClients < 1) {
            console.log('Ничего не выбрано..');
        } else {
            let name = 'клиента';
            if (countClients > 1) name = 'клиентов';
            showModalWindow('Удаление ' + name, '/ajax_deleteClients?count=' + countClients, 'confirm');
        }
    }

</script>
                <!-- Content -->
                <section class="content">
                <div class="border"></div>
                <div class="status-panel" style="padding-bottom: 1px">
                    <div class="status-panel__row">
                        <div class="status-panel__count"></div>
                        <div class="status-panel__search"></div>
                    </div>
                    <button id="button-search" style="font-size: 14px"><i class="fa fa-search"></i></button>
                    <div style="position: relative">
                        <button id="button-selected" style="font-size: 14px" onclick="showOptions();" disabled><i class="fa fa-cog"></i></button>
                        <div class="status-panel__options">
                            <ul>
                                <li id="print-table"><i class="fa fa-print"></i> Печать таблицы</li>
                                <li class="hr"></li>
                                <li id="button-edit" style="color: #8A5A00"><i class="fa fa-edit"></i> Редактировать</li>
                                <li class="hr"></li>
                                <li id="button-delete" onclick="deleteClients();" style="color: #AE0000"><i class="fa fa-trash-o"></i> Удалить</li>
                            </ul>
                        </div>
                    </div>
                    <button id="button-add" style="color: green" onclick="addClient();"><i class="fa fa-plus-square"></i> Добавить</button>
                    <button id="button-select-all" title="Выбрать все"><i class="fa fa-file-text"></i></button>
                </div>

                <div class="status-list">
                    <button class="tabs-arrow" id="button-arrow-left-tabs">◄</button>
                    <ul class="status-list__item">
                        <li id="tab-clients-0">
<?
$total_count =  $db->query("SELECT COUNT(*) FROM `clients` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
?>
                            <a href="javascript:void(0);" data-src="/clients?type=all" <?=(($_GET['type'] == 'all' or abs(intval($_GET['type'])) == 0)  ? 'class="tab-status-active"' : '')?>>Все (<b><?=$total_count[0]?></b>)</a>
                        </li>
<?
$groups_of_clients = $db->query("SELECT `id_item`, `name` FROM `groups_of_clients` WHERE `client_id` = '" . $chief['id'] . "'");
while ($group_of_clients = $groups_of_clients->fetch_assoc()) {
    $count = $db->query("SELECT COUNT(*) FROM `clients` WHERE `group_id` = '" . $group_of_clients['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
?>
                        <li id="tab-clients-<?=$group_of_clients['id_item']?>">
                            <a href="javascript:void(0);" data-src="/clients?type=<?=$group_of_clients['id_item']?>"><?=protection($group_of_clients['name'], 'display')?> (<b><?=$count[0]?></b>)</a>
                        </li>
<?
}
echo "\r\n";
?>
                    </ul>
                    <button class="tabs-arrow" id="button-arrow-right-tabs">►</button> 
                </div>
                <!-- put div here without scroll -->         
                <div class="content__overflow" style="border-top: none">
                    <form id="form-clients" method="post" spellcheck="false" autocomplete="off">
                        <table id="clients-table" class="table has-tabs" cellpadding="0" cellspacing="0" style="border-top: none">
                            <thead>
                                <tr>
                                    <th data-name="id" style="min-width: 30px">id</th>
                                    <th data-name="name" style="min-width: 120px">Клиент</th>
                                    <th data-name="country" style="min-width: 260px">Страна</th>
                                    <th data-name="phone" style="min-width: 120px">Телефон</th>
                                    <th data-name="group_id" style="min-width: 100px">Группа</th>
                                    <th data-name="email" style="min-width: 140px">E-mail</th>
                                    <th data-name="comment" style="min-width: 150px">Комментарий</th>
                                    <th data-name="date_added" style="min-width: 150px">Добавлено</th>
                                    <th data-name="site" style="min-width: 120px">Сайт</th>
                                    <th data-name="ip" style="min-width: 110px">IP</th>
                                </tr>
                                <tr class="table-row-search">
                                    <td style="max-width: 20px"><input type="text" name="id"></td>
                                    <td style="max-width: 120px"><input type="text" name="name"></td>
                                    <td style="max-width: 260px">
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
                                    <td style="max-width: 120px"><input type="text" name="phone"></td>
                                    <td style="max-width: 100px"></td>
                                    <td style="max-width: 140px"><input type="text" name="email"></td>
                                    <td style="max-width: 150px"><input type="text" name="comment"></td>
                                    <td style="max-width: 150px"></td>
                                    <td style="max-width: 120px">
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
                                    <td style="max-width: 110px"><input type="text" name="ip"></td>
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
