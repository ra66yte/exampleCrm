<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'change') {
    $success = $error = null;
    $ids = array();
    $status = abs(intval($_POST['status']));
    $departure_date = empty($_POST['departure_date']) ? null : $_POST['departure_date'];

    foreach($_POST['orders'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);

    if ($status == 0) {
        $error = 'Укажите новый статус для выделенных заказов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `status_order` WHERE `id_item` = '" . $status . "' AND `status` = 'on' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Статус заказов не найден!!';
    }

    if (isset($departure_date)) {
        $date = date_create_from_format('d-m-Y H:i:s', $departure_date);
        $date = date_format($date, 'Y-m-d H:i:s');
        $departure_date = strtotime($date);
    }
    
    if (!isset($error)) {
        if ($db->query("UPDATE `orders` SET `status` = '" . $status . "'" . (isset($departure_date) ? ", `departure_date` = '" . $departure_date . "'" : "") . ", `updated` = '" . $data['time'] . "' WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = 'Произошла ошибка при выполнении операции!';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
?>
<style>
    .ui-datepicker {
        z-index: 1020 !important;
    }
    .hidden {
        visibility: hidden;
    }
</style>
<script>
    $(function(){
        let arrayItems = new Array(),
            arrayOrders = new Array(),
            wsData;
        $.each($('tr.table__active'), function(e) {
            arrayItems.push($(this).attr('data-id'));
            arrayOrders.push($(this).attr('data-order-id'));

            wsData = {
                action: 'lock item',
                data: {
                    itemId: $(this).attr('data-id'),
                    location: 'orders'
                }
            }

            sendMessage(ws, JSON.stringify(wsData));

            $(this).addClass('blocked-row');
        });

        $('#orders-ids').find('small').text(arrayOrders.join(', '));
        $('#orders-count').text(arrayOrders.length);

        function checkFields() {
            let error;
            let newStatus = $('#new-status').val();
            if (newStatus == '') {
                error = 'Укажите новый статус для выделенных заказов!';
            } else if (isNaN(newStatus)) {
                error = 'Указан некорректный статус!';
            }

            if (error) {
                $('#change-statuses').addClass('disabled');
                return error;
            } else {
                $('#change-statuses').removeClass('disabled');
                return false;
            }
        }

        $('#new-status').on('change', function() {
            checkFields();
            let currentValue = $(this).val();
            if (currentValue == 3) {
                addDepartureDate();
            } else {
                let dateField = $('#departure-date'),
                    dateText = $('#add-text'),
                    dateLink = $('#add-link');
                dateLink.show();
                dateText.hide();
                dateField.prop('disabled', true).val('').addClass('hidden');
            }
        });

        $('.modal-window-content').on('click', 'button#change-statuses', function(e) {
            let error = checkFields();
            if (error) {
                if (!$('.modal-window-content').last().is('.error')) {
                    $('.modal-window-content').last().prepend('<div class="error"></div>');
                }
                $('.error').last().text(error).show();
            } else {
                let ordersStatus = $('#new-status').val(),
                    departureDate = $('#departure-date').val();
                $.ajax({
                    type: "POST",
                    url: "/system/ajax/changeOrdersStatuses.php?action=change",
                    data: { 'orders': arrayItems, 'status': ordersStatus, 'departure_date': departureDate },
                    success: function(response) {
                        let jsonData = JSON.parse(response),
                            count_modal = $('.modal-window-wrapper').length;
                        if (jsonData.success) {
                            let status = getParameterByName('status');

                            wsData = {
                                action: 'remove item',
                                data: {
                                    itemsId: arrayOrders, // ITEMS
                                    location: 'orders'
                                }
                            }

                            sendMessage(ws, JSON.stringify(wsData));

                            $.each($('tr.table__active'), function(e) {
                                wsData = {
                                    action: 'add item',
                                    data: {
                                        itemId: $(this).attr('data-id'),
                                        location: 'orders'
                                    }
                                }

                                sendMessage(ws, JSON.stringify(wsData));
                                
                            });
        
                            TabStatus(status);
                            hideOptions(true);
                            $('.status-panel__count').hide();
                            closeModalWindow(count_modal);
                        } else {
                            if (!$('.modal-window-content').last().is('.error')) {
                                $('.modal-window-content').last().prepend('<div class="error"></div>');
                            }
                            $('.error').last().text(jsonData.error).show();
                        }
                        
                    }
                }); 
            }   
        });

        $('#departure-date').datetimepicker({
            dateFormat: "dd-mm-yy",
            timeFormat: "HH:mm:ss",
            showSecond: true,
            beforeShow: function(input) {
                $(input).prop('readonly', true);
            }
        });
    });
    
    function addDepartureDate(){
        let dateField = $('#departure-date'),
            dateText = $('#add-text'),
            dateLink = $('#add-link'),
            d = new Date(),
            H = (d.getHours() < 10 ? '0' : '') + d.getHours(),
            i = (d.getMinutes() < 10 ? '0' : '') + d.getMinutes(),
            s = (d.getSeconds() < 10 ? '0' : '') + d.getSeconds();

        dateLink.hide();
        dateText.show();
        dateField.prop('disabled', false).val('<?=date('d-m-Y')?> ' + H + ':' + i + ':' + s).removeClass('hidden');
    }
</script>
    <div class="modal-window-content__item">
        <div class="modal-window-content__value">
            <span>Новый статус будет</span> <i class="fa fa-magic"></i> <select name="status" id="new-status" class="chosen-select">
                <option value="">- Не указано -</option>
                <option disabled>----------------------------------------</option>
<?
    $statuses = $db->query("SELECT `id_item`, `name`, `color`, `status` FROM `status_order` WHERE `status` = 'on' AND `client_id` = '" . $chief['id'] . "'");
    while ($status = $statuses->fetch_assoc()) {
?>
                <option data-id="<?=$status['id_item']?>" data-img-src="/getImage/?color=<?=str_replace('#', '', $status['color'])?>" value="<?=$status['id_item']?>"<?=($status['status'] == 'off' ? ' disabled' : '')?>><?=protection($status['name'], 'display')?></option>
<?
    }
?>
            </select>
        </div>
        <div class="modal-window-content__value">
            <a href="javascript:void(0);" id="add-link" onclick="addDepartureDate();">Добавить дату отправки</a>
            <span id="add-text" style="display: none">Дата отправки</span> <i class="fa fa-calendar-check-o"></i>
            <input id="departure-date" class="pickerdate hidden" type="text" name="departure_date" spellcheck="false" autocomplete="false" disabled>
        </div>
        <br>
        <div class="modal-window-content__value" style="text-align: left"><b style="color: #000; font-size: 16px">ID выделенных заказов:</b></div>
        <div id="orders-ids" class="modal-window-content__value" style="text-align: left"><small></small></div>
        <br>
        <div class="modal-window-content__value" style="color: red; text-align: left; font-size: 12px; max-width: 350px">* Красным обозначены те заказы, которые редактируються в данный момент. Статус таких заказов не изменится.</div>
        <br>
        <div class="modal-window-content__value" style="color: #000; text-align: left">Выбрано заказов: <b id="orders-count">0</b></div>
    </div>
    <div class="buttons">
        <button id="change-statuses" class="disabled">Сохранить</button>
    </div>  
<?
