<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $id = isset($_POST['color_id']) ? abs(intval($_POST['color_id'])) : null;
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;

    if (empty($name)) {
        $error = 'Укажите название!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 25) {
        $error = 'Название должно быть в пределах от 2 до 25 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `colors` WHERE `name` = '" . $name . "' AND `id_item` != '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Цвет с таким названием уже есть!';
    }

    if (empty($id)) {
        $error = 'Цвет не выбран!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `colors` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Цвет не найден!';
    }

    if (!isset($error)) {
        if ($db->query("UPDATE `colors` SET `name` = '" . $name . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = 'Не удалось обновить данные о цвете!';
        }
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['color_id']) and is_numeric($_GET['color_id'])) {
    $color_id = abs(intval($_GET['color_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `colors` WHERE `id_item` = '" . $color_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $color = $db->query("SELECT `name` FROM `colors` WHERE `id_item` = '" . $color_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['color_name' => protection($color['name'], 'display')];
        } else {
            $error = 'Неизвестный цвет!';
            $title = ['color_name' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `colors` WHERE `id_item` = '" . $color_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $color = $db->query("SELECT `id_item`, `name`, `date_added` FROM `colors` WHERE `id_item` = '" . $color_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>
<script>
    $(function(){
        let form = $('#change-color'),
            btn = form.find('#button-change-color');
        
        function checkFields() {
            let error;
        
            let name = form.find('#color-name').val();
            if (name == '') {
                error = 'Укажите название!';
            } else if (name.length < 2) {
                error = 'Название не может содержать меньше 2 символов!';
            } else if (name.length > 25) {
                error = 'Название должно быть в пределах 25 символов!';
            }
        
            if (error) {
                btn.addClass('disabled'); return error;
            } else {
                btn.removeClass('disabled'); return false;
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
                    url: "/system/ajax/viewcolor.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);

                        if (jsonData.success == 1) {
                            loadColors();
                            closeModalWindow(count_modal);
                        } else {
                            if (!$('.modal-window-content div').is('.error')) {
                                $('.modal-window-content').prepend('<div class="error"></div>');
                                $('.error').text(jsonData.error).show();
                            }
                        }
                        hideOptions(true);
                    }
                });
            }
            return false;
        });
    });
</script>
        <form id="change-color" method="post" autocomplete="off">
            <input type="hidden" name="color_id" value="<?=protection($color['id_item'], 'int')?>">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="color-name" type="text" name="name" value="<?=protection($color['name'], 'display')?>" placeholder="Введите название">
                </div>
                <div class="modal-window-content__title">Дополнительно</div>
                <div class="modal-window-content__value">
                    <span>Статус</span> <i class="fa fa-eye-slash"></i>
                    <select name="status" class="chosen-select" disabled>
                        <option value="">Не выбрано</option>
                        <option value="on"<?=($color['status'] == 'on' ? ' selected' : '')?>>On</option>
                        <option value="off"<?=($color['status'] == 'off' ? ' selected' : '')?>>Off</option>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Добавлен</span> <i class="fa fa-calendar-plus-o"></i> <input type="text" name="date_added" disabled value="<?=passed_time($color['date_added'])?>">
                </div>
                <div class="buttons">
                    <button id="button-change-color" name="save-changes">Сохранить и закрыть</button>
                </div>
            </div>
            <input type="submit" style="display: none">
        </form>
<?
    } else {
?>
        Произошла ошибка при выполнении операции!
<?
    }
}
