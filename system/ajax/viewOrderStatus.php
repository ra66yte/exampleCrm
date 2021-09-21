<?php
include_once '../core/begin.php';

if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $id = abs(intval($_POST['status_id']));
    $name = protection($_POST['name'], 'base');
    $color = protection($_POST['color'], 'base');
    $warehouse = protection($_POST['warehouse'], 'base');
    $block = protection($_POST['block'], 'base');

    if ($block == '') $block = 'off';
    
    if (empty($color)) {
        $error = 'Укажите цвет!';
    } elseif (!preg_match("/#[a-zA-Z0-9]{6}/i", $color)) {
        $error = 'Укажите корректный цвет!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `status_order` WHERE `color` = '" . $color . "' AND `id_item` != '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Статус заказов с таким цветом уже есть!';
    }

    if ($warehouse == '') $warehouse = 'none';

    if (empty($name)) {
        $error = 'Укажите название!';
    } elseif (mb_strlen($name) < 3 or mb_strlen($name) > 25) {
        $error = 'Название должно быть в пределах от 3 до 25 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `status_order` WHERE `name` = '" . $name . "' AND `id_item` != '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Статус заказов с таким названием уже есть!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `status_order` WHERE `id_item` = '" . $id . "' AND `permanent` = '1' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Этот статус заказов редактировать нельзя!';
    }
    if (!isset($error)) {
        if ($db->query("UPDATE `status_order` SET `name` = '" . $name . "', `color` = '" . $color . "', `warehouse` = '" . $warehouse . "', `block` = '" . $block . "' WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` = '" . $id . "'")) {
            $success = 1;
        } else {
            $error = "Не удалось обновить статус!";
        }
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['status_id']) and is_numeric($_GET['status_id'])) {
    $status_id = abs(intval($_GET['status_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `status_order` WHERE `id_item` = '" . $status_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $status = $db->query("SELECT `name` FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` = '" . $status_id . "'")->fetch_assoc();
            $title = ['status_name' => protection($status['name'], 'display')];
        } else {
            $error = 'Неизвестный статус заказа!';
            $title = ['status_name' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` = '" . $status_id . "'")->fetch_row() and $result[0] > 0) {
        $status = $db->query("SELECT * FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` = '" . $status_id . "'")->fetch_assoc();
?>

<script>

$(function() {
    let form = $('#change-order-status'),
        btn = form.find('#button-change-order-status');

    form.find('#status-color').spectrum({
        color: '<?php echo protection($status['color'], 'display') ?>',
        preferredFormat: 'hex',
        type: 'text',
        showInput: true,
        showAlpha: false,
        locale: 'ru',
        showPaletteOnly: true,
        togglePaletteOnly: true
    });

    function checkFields() {
        let error;

        let color =  form.find('#status-color').val();
        if (color == '') {
            error = 'Укажите цвет!';
        } else if (!color.match(/#[a-f0-9]{6}\b/gi)) {
            error = 'Введите корректный цвет!';
        }

        let name = form.find('#status-name').val();
            
        if (name == '') {
            error = 'Введите название статуса!';
        } else if (name.length < 3) {
            error = 'Название не может содержать меньше 3 символов!';
        } else if (name.length > 25) {
            error = 'Название должно быть в пределах 25 символов!';
        }


        if (error) {
            btn.addClass('disabled');
            return error;
        } else {
            btn.removeClass('disabled');
            return false;
        }
    }

    form.on('keyup change', function() {
        checkFields();
    });

    form.on('submit', function(e){
        let error = checkFields();
        if (error) {
            if (!$('.modal-window-content div').is('.error')) {
                $('.modal-window-content').prepend('<div class="error"></div>');
                $('.error').text(error).show();
            }
        } else {
            let data = $(this).serializeArray(),
                count_modal = $('.modal-window-wrapper').length;
            $.ajax({
                type: "POST",
                url: "system/ajax/viewOrderStatus.php?action=submit",
                data: data,
                success: function(response) {
                    let jsonData = JSON.parse(response);
                    if (jsonData.success == 1) {
                        loadStatuses();
                        hideOptions(true);
                        closeModalWindow(count_modal);
                    } else {

                        if (!$('.modal-window-content div').is('.error')) {
                            $('.modal-window-content').prepend('<div class="error"></div>');
                            $('.error').text(jsonData.error).show();
                        }
                    }
                }
            });
        }
        return false;
    });
})
</script>
        <form id="change-order-status" method="post" autocomplete="off">
            <input type="hidden" name="status_id" value="<?=protection($status['id_item'], 'int')?>">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"> </i> <input id="status-name" type="text" name="name" placeholder="Введите название" value="<?=protection($status['name'], 'display')?>">
                </div>
                <div class="modal-window-content__value">
                    <span>Цвет</span> <i id="circle-color" class="fa fa-eyedropper"></i> <input id="status-color" type="text" name="color" placeholder="Например, #ffffff" value="<?=protection($status['color'], 'display')?>">
                </div>
                <div class="modal-window-content__value">
                    <span>Склад</span> <i class="fa fa-database"> </i> <select name="warehouse" id="status-warehouse" class="chosen-select">
                        <option value=""<?=($status['warehouse'] == 'none' ? ' selected' : '')?>>- Не указано -</option>
                        <option value="in"<?=($status['warehouse'] == 'in' ? ' selected' : '')?>>На склад</option>
                        <option value="out"<?=($status['warehouse'] == 'out' ? ' selected' : '')?>>Со склада</option>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Направление</span> <i class="fa fa-globe"> </i> <select name="direction" id="status-direction" class="chosen-select" disabled>
                        <option value="">- Не указано -</option>
                        <option value="in"<?=($status['country'] == '0' ? ' selected' : '')?>>Все</option>
                    </select>
                </div>
                <div class="modal-window-content__title" style="margin-top: 20px">Дополнительно</div>
                <div class="modal-window-content__value">
                    <span>Блок</span> <i class="fa fa-shield"></i>
                    <select name="block" class="chosen-select">
                        <option value="">Не выбрано</option>
                        <option value="off"<?=($status['block'] == 'off' ? ' selected' : '')?>>Off</option>
                        <option value="on"<?=($status['block'] == 'on' ? ' selected' : '')?>>On</option>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Статус</span> <i class="fa fa-eye-slash"></i>
                    <select name="status" class="chosen-select" disabled>
                        <option value="">Не выбрано</option>
                        <option value="on"<?=($status['status'] == 'on' ? ' selected' : '')?>>On</option>
                        <option value="off"<?=($status['status'] == 'off' ? ' selected' : '')?>>Off</option>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Добавлен</span> <i class="fa fa-calendar-plus-o"></i> <input type="text" name="date_added" disabled value="<?=passed_time($status['date_added'])?>">
                </div>
<?
$count = $db->query("SELECT COUNT(*) FROM `orders` WHERE `status` = '" . $status['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row(); 
?>
                <div class="modal-window-content__value">
                    <p><i class="fa fa-info-circle"></i> Заказов с этим статусом: <?=$count[0]?> шт.</p>
                </div>
                <div class="buttons">
                    <button id="button-change-order-status" name="save-changes">Сохранить и закрыть</button>
                </div>
            </div>
            <input type="submit" style="display: none">
        </form>
<?
    } else {
?>
    Информация по заданному статусу заказов отсутсвует.
<?
    }
}