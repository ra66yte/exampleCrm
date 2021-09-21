<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $id = isset($_POST['attribute_id']) ? abs(intval($_POST['attribute_id'])) : null;
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $category = isset($_POST['category']) ? abs(intval($_POST['category'])) : null;

    if (isset($category)) {
        if (empty($category) or !is_numeric($category)) {
            $error = 'Укажите категорию!';
        } elseif ($result = $db->query("SELECT COUNT(*) FROM `attribute_categories` WHERE `id_item` = '" . $category . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            $error = 'Категория не найдена!';
        }
    } else {
        $this_attribute = $db->query("SELECT `category_id` FROM `attributes` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $category = $this_attribute['category_id'];
    }

    if (empty($name)) {
        $error = 'Укажите название!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 25) {
        $error = 'Название должно быть в пределах от 2 до 25 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `attributes` WHERE `name` = '" . $name . "' AND `id_item` != '" . $id . "' AND `category_id` = '" . $category . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Атрибут с таким названием в этой категории уже есть!'; // ToDo: одинаковые названия в разных категориях
    }

    if (empty($id) or !is_numeric($category)) {
        $error = 'Атрибут не выбран!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `attributes` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Атрибут не найден!';
    }

    if (!isset($error)) {
        if ($db->query("UPDATE `attributes` SET `name` = '" . $name . "', `category_id` = '" . $category . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = 'Не удалось обновить данные атрибута!';
        }
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['attribute_id']) and is_numeric($_GET['attribute_id'])) {
    $attribute_id = abs(intval($_GET['attribute_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `attributes` WHERE `id_item` = '" . $attribute_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $attribute = $db->query("SELECT `name` FROM `attributes` WHERE `id_item` = '" . $attribute_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['attribute_name' => protection($attribute['name'], 'display')];
        } else {
            $error = 'Неизвестный атрибут!';
            $title = ['attribute_name' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `attributes` WHERE `id_item` = '" . $attribute_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $attribute = $db->query("SELECT `id_item`, `name`, `status`, `category_id`, `date_added` FROM `attributes` WHERE `id_item` = '" . $attribute_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>
<script>
    $(function(){
        let form = $('#change-attribute'),
            btn = form.find('#button-change-attribute');
    
        function checkFields() {
            let error;

            let category = form.find('#attribute-category').val();
            if (category == '') {
                error = 'Укажите категорию!';
            } else if (isNaN(category)) {
                error = 'Укажите корректную категорию!';
            }
        
            let name = form.find('#attribute-name').val();
            if (name == '') {
                error = 'Укажите название!';
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
                    url: "/system/ajax/viewAttribute.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            loadAttributes();
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
        <form id="change-attribute" method="post" autocomplete="off">
            <input type="hidden" name="attribute_id" value="<?=protection($attribute['id_item'], 'int')?>">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="attribute-name" type="text" name="name" value="<?=protection($attribute['name'], 'display')?>" placeholder="Введите название">
                </div>
                <div class="modal-window-content__value">
                    <span>Категория</span> <i class="fa fa-sitemap"></i> <select id="attribute-category" name="category" class="chosen-select">
                        <option value="">- Не указано -</option>
<?php
$query = $db->query("SELECT `id_item`, `name`, `status` FROM `attribute_categories` WHERE `client_id` = '" . $chief['id'] . "' ORDER BY `id` ASC");
$categories = array();
while ($category = $query->fetch_assoc()) {
?>
                        <option value="<?=$category['id_item']?>"<?=($category['status'] == 'off' ? ' disabled' : '')?><?=($attribute['category_id'] == $category['id_item'] ? ' selected' : '')?>><?=protection($category['name'], 'display')?></option>
<?
}
?>
                    </select>
                </div>
                <div class="modal-window-content__title" style="margin-top: 20px">Дополнительно</div>
                <div class="modal-window-content__value">
                    <span>Статус</span> <i class="fa fa-eye-slash"></i>
                    <select name="status" class="chosen-select" disabled>
                        <option value="">Не выбрано</option>
                        <option value="on"<?=($attribute['status'] == 'on' ? ' selected' : '')?>>On</option>
                        <option value="off"<?=($attribute['status'] == 'off' ? ' selected' : '')?>>Off</option>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Добавлен</span> <i class="fa fa-calendar-plus-o"></i> <input type="text" name="date_added" disabled value="<?=passed_time($attribute['date_added'])?>">
                </div>
                <div class="buttons">
                    <button id="button-change-attribute" name="save-changes">Сохранить и закрыть</button>
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
