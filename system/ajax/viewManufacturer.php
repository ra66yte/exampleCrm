<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $id = isset($_POST['manufacturer_id']) ? abs(intval($_POST['manufacturer_id'])) : null;
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $type = isset($_POST['type']) ? abs(intval($_POST['type'])) : null;
    $description = isset($_POST['description']) ? protection($_POST['description'], 'base') : null;

    if (empty($name)) {
        $error = 'Введите название производителя!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 30) {
        $error = 'Название производителя должно быть в пределах от 2 до 30 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `manufacturers` WHERE `name` = '" . $name . "' AND `id_item` != '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Производитель с таким названием уже есть!';
    }

    if (!isset($type) or $type > 1) $type = 0;

    if (!empty($description)) {
        if (mb_strlen($description, 'UTF-8') > 200) {
            $error = 'Описание должно быть в пределе 200 символов!';
        }
    }

    if (empty($id)) {
        $error = 'Производитель не выбран!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `manufacturers` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Производитель не найден!';
    }

    if (!isset($error)) {
        if ($db->query("UPDATE `manufacturers` SET `name` = '" . $name . "', `type` = '" . $type . "', `description` = '" . $description . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = 'Не удалось обновить данные о производителе!';
        }
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['manufacturer_id']) and is_numeric($_GET['manufacturer_id'])) {
    $manufacturer_id = abs(intval($_GET['manufacturer_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `manufacturers` WHERE `id_item` = '" . $manufacturer_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $manufacturer = $db->query("SELECT `name` FROM `manufacturers` WHERE `id_item` = '" . $manufacturer_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['manufacturer_name' => protection($manufacturer['name'], 'display')];
        } else {
            $error = 'Неизвестный производитель!';
            $title = ['manufacturer_name' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `manufacturers` WHERE `id_item` = '" . $manufacturer_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $manufacturer = $db->query("SELECT `id_item`, `name`, `type`, `description`, `date_added` FROM `manufacturers` WHERE `id_item` = '" . $manufacturer_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>
<script>
    $(function(){
        let form = $('#change-manufacturer'),
            btn = form.find('#button-change-manufacturer');
        
        function checkFields() {
            let error;
        
            let description  = form.find('#manufacturer-description').val();
            if (description != '') {
                if (description.length > 200) {
                    error = 'Описание должно быть в пределах 200 символов!';
                }
            }  
        
            let type = form.find('#manufacturer-type').val();
            if (type == '') {
                error = 'Выберите тип!';
            } else if (isNaN(type)) {
                error = 'Неправильный тип';
            }
        
            let name = form.find('#manufacturer-name').val();
            if (name == '') {
                error = 'Введите название производителя!';
            } else if (name.length < 2) {
                error = 'Название не может содержать меньше 2 символов!';
            } else if (name.length > 30) {
                error = 'Название должно быть в пределах 30 символов!';
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
                    url: "/ajax_viewManufacturer?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            loadManufacturers();
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
    });
</script>
        <form id="change-manufacturer" method="post" autocomplete="off">
            <input type="hidden" name="manufacturer_id" value="<?=protection($manufacturer['id_item'], 'int')?>">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="manufacturer-name" type="text" name="name" value="<?=protection($manufacturer['name'], 'display')?>" placeholder="Введите название">
                </div>
                <div class="modal-window-content__value">
                    <span>Тип</span> <i class="fa fa-code-fork"></i> <select name="type" id="manufacturer-type" class="chosen-select">
                        <option value="">- Не указано -</option>
                        <option value="0" <?=($manufacturer['type'] == '0' ? 'selected' : '')?>>Бренд</option></option>
                        <option value="1" <?=($manufacturer['type'] == '1' ? 'selected' : '')?>>Страна производитель</option>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Описание</span> <i class="fa fa-comment"></i> <textarea name="description" id="manufacturer-description"><?=protection($manufacturer['description'], 'display')?></textarea>
                </div>
                <div class="modal-window-content__value">
                    <span>Добавлен</span> <i class="fa fa-calendar-plus-o"></i> <input type="text" name="date_added" disabled value="<?=passed_time($manufacturer['date_added'])?>">
                </div>
                <div class="buttons">
                    <button id="button-change-manufacturer" name="save-changes">Сохранить и закрыть</button>
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
