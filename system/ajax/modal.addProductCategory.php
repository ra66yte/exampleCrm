<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit') {
    $success = $error = null;

    $name =   isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $type =   isset($_POST['type']) ? abs(intval($_POST['type'])) : 0;
    $parent = isset($_POST['sub']) ? abs(intval($_POST['sub'])) : 0;

    if ($type == 2) {
        if (empty($parent)) {
            $error = 'Укажите родительскую категорию!';
        } elseif ($result = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `id_item` = '" . $parent . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            $error = 'Родительская категория не найдена!';
        }
    }

    if (empty($name)) {
        $error = 'Укажите название категории!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 30) {
        $error = 'Название должно быть в пределах от 2 до 30 символов';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `name` = '" . $name . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Категория товаров с таким названием уже есть!';
    }

    if (!isset($error)) {
        $count = $db->query("SELECT `product_categories` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['product_categories'] + 1;

        if ($result = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            $db->query("INSERT INTO `product_categories` (`id`, `id_item`, `client_id`, `parent_id`, `name`, `date_added`) VALUES (null, '" . $id_item . "', '" . $chief['id'] . "', '" . $parent . "', '" . $name . "', '" . $data['time'] . "')");

            $db->query("UPDATE `id_counters` SET `product_categories` = (`product_categories` + 1) WHERE `client_id` = '" . $chief['id'] . "'");
            $success = 1;
        } else {
            $error = 'Не удалось добавить категорию товаров! Попробуйте еще рвз.';
        }
    }
    
    echo json_encode(array('success' => $success, 'error' => $error));
    die;
}
function build_tree_select($categories, $parent_id, $level) {
    global $db, $chief;
    if (is_array($categories) and isset($categories[$parent_id])) { //Если категория с таким parent_id существует
        foreach ($categories[$parent_id] as $category) { // Обходим
            $count_subs = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `parent_id` = '" . $category['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
            /**
             * Выводим категорию 
             *  $level * 20 - отступ, $level - хранит текущий уровень вложености (0, 1, 2..)
             */

?>
            <option value="<?=$category['id_item']?>" style="text-align: left; padding-left: <?=($level == 0 ? '5' : $level * 20)?>px">
                <?php echo protection($category['name'], 'display'); if ($count_subs[0] <> 0) echo ' (' . $count_subs[0] . ') ▼'; ?>
            </option>
<? 

            $level = $level + 1; // Увеличиваем уровень вложености
            // Рекурсивно вызываем эту же функцию, но с новым $parent_id и $level
            build_tree_select($categories, $category['id_item'], $level);
            $level = $level - 1; // Уменьшаем уровень вложености
        }
    }
}
?>
<script>
    $(function(){
        let form = $('#add-product-category'),
            btn = form.find('#button-add-product-category');

        form.find('#category-name').focus();
            // Функция проверки полей формы
        
        function checkFields() {
            let name = form.find('#category-name').val().trim(),
                error;
            
            if (name == '') {
                error = 'Введите название категории!';
            } else if (name.length > 30) {
                error = 'Название должно быть в пределах 30 символов!';
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
                    url: "system/ajax/modal.addProductCategory.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        
                        if (jsonData.success) {
                            loadCategories();
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

    function checkState(event) {
        if ($(event).val() == 2) {
            $('select#sub-category').removeAttr('disabled');
            $(".chosen-select").trigger('chosen:updated');
        } else {
            $('select#sub-category').find('option[value=""]').prop('selected', true);
            $('select#sub-category').attr('disabled', 'true');
            $(".chosen-select").trigger('chosen:updated');
        }
    }
</script>
        <form id="add-product-category" method="post" autocomplete="off">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"> </i> <input id="category-name" type="text"  name="name" placeholder="Введите название">
                </div>
                <div class="modal-window-content__value">
                    <span>Подчиняется</span> <i class="fa fa-sitemap"></i>
                    <select id="sub-category" name="sub" class="chosen-select" disabled>
                        <option value="">- Не указано -</option>
<?
$count = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($count[0] > 0) {
    $items = $db->query("SELECT `id_item`, `parent_id`, `name` FROM `product_categories` WHERE `status` = 'on' AND `client_id` = '" . $chief['id'] . "' ORDER BY `id` ASC");
    $categories = array();
    while ($category = $items->fetch_assoc()) {
        $categories[$category['parent_id']][] = $category;
    }
}
echo build_tree_select($categories, 0, 0);
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Тип</span> <i class="fa fa-code-fork"></i>
                    <select name="type" class="chosen-select" onchange="checkState(this)">
                        <option value="">- Не выбрано -</option>
                        <option value="1" selected>Родительская</option>
                        <option value="2">Подчиняемая</option>
                    </select>
                </div>
                <br>
                <div class="modal-window-content__title" style="margin-top: 20px">Дополнительно</div>
                <div class="modal-window-content__value">
                    <span>Статус</span> <i class="fa fa-eye-slash"></i>
                    <select name="category-status" class="chosen-select" disabled>
                        <option value="">Не выбрано</option>
                        <option value="1">Off</option>
                        <option value="2" selected>On</option>
                    </select>
                </div>
                <br>
                <div class="buttons">
                    <button id="button-add-product-category" class="disabled" name="save-changes">Добавить</button>
                    <input type="submit" style="display: none">
                </div>
            </div>
        </form>