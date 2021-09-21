<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $id = isset($_POST['attribute_category_id']) ? abs(intval($_POST['attribute_category_id'])) : null;
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;

    if (empty($name)) {
        $error = 'Укажите название!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 25) {
        $error = 'Название должно быть в пределах от 2 до 25 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `attribute_categories` WHERE `name` = '" . $name . "' AND `id_item` != '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Категория атрибутов с таким названием уже есть!';
    }

    if (empty($id)) {
        $error = 'Категория не выбрана!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `attribute_categories` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Категория не найдена!';
    }

    if (!isset($error)) {
        if ($db->query("UPDATE `attribute_categories` SET `name` = '" . $name . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = 'Не удалось обновить данные категории атрибутов!';
        }
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['attribute_category_id']) and is_numeric($_GET['attribute_category_id'])) {
    $attribute_category_id = abs(intval($_GET['attribute_category_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `attribute_categories` WHERE `id_item` = '" . $attribute_category_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $attribute_category = $db->query("SELECT `name` FROM `attribute_categories` WHERE `id_item` = '" . $attribute_category_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['attribute_category_name' => protection($attribute_category['name'], 'display')];
        } else {
            $error = 'Неизвестная категория атрибутов!';
            $title = ['attribute_category_name' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `attribute_categories` WHERE `id_item` = '" . $attribute_category_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $attribute_category = $db->query("SELECT `id_item`, `name`, `status`, `date_added` FROM `attribute_categories` WHERE `id_item` = '" . $attribute_category_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>
<script>
    $(function(){
        let form = $('#change-attribute-category'),
            btn = form.find('#button-change-attribute-category');
        
        function checkFields() {
            let error;
        
            let name = form.find('#attribute-category-name').val();
            if (name == '') {
                error = 'Укажите название!';
            } else if (name.length < 2) {
                error = 'Название не может содержать меньше 2 символов!';
            } else if (name.length > 25) {
                error = 'Название должно быть в пределах 25 символов!';
            }
        
            if (error) {
                btn.addClass('disabled');
                return error;
            } else {
                btn.removeClass('disabled');
                return false
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
                    url: "/system/ajax/viewAttributeCategory.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            loadAttributeCategories();
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
        <form id="change-attribute-category" method="post" autocomplete="off">
            <input type="hidden" name="attribute_category_id" value="<?=protection($attribute_category['id_item'], 'int')?>">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="attribute-category-name" type="text" name="name" value="<?=protection($attribute_category['name'], 'display')?>" placeholder="Введите название">
                </div>
                <div class="modal-window-content__title">Дополнительно</div>
                <div class="modal-window-content__value">
                    <span>Статус</span> <i class="fa fa-eye-slash"></i>
                    <select name="status" class="chosen-select" disabled>
                        <option value="">Не выбрано</option>
                        <option value="on"<?=($attribute_category['status'] == 'on' ? ' selected' : '')?>>On</option>
                        <option value="off"<?=($attribute_category['status'] == 'off' ? ' selected' : '')?>>Off</option>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Добавлена</span> <i class="fa fa-calendar-plus-o"></i> <input type="text" name="date_added" disabled value="<?=passed_time($attribute_category['date_added'])?>">
                </div>
                <div class="buttons">
                    <button id="button-change-attribute-category" name="save-changes">Сохранить и закрыть</button>
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
