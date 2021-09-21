<?php
include_once '../core/begin.php';

function subcategories($selected) {
    global $db, $user, $chief;
    $result = $db->query("SELECT `id_item`, `parent_id` FROM `product_categories` WHERE `client_id` = '" . $chief['id'] . "'");
    $arr = array(); // результирующий массив
    $keys = array(); // здесь будет массив ключей
    $keys[] = $selected; // добавляем первый ключ в массив
    while ($i = $result->fetch_assoc()) {
        // Проверяем наличие ID категории в массиве ключей
        if (in_array($i['parent_id'], $keys)) {
            $arr[$i['parent_id']][] = $i['id_item'];
            $keys[] = $i['id_item']; // расширяем массив
        }
    }
    return $keys;
}

function build_tree_select($categories, $parent_id, $level, $selected, $parent_selected) {
    global $db, $chief;
    if (is_array($categories) and isset($categories[$parent_id])) { //Если категория с таким parent_id существует
        foreach ($categories[$parent_id] as $category) { // Обходим
            $count_subs = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `parent_id` = '" . $category['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
            $parent = $db->query("SELECT `status` FROM `product_categories` WHERE `id_item` = '" . $category['parent_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            /**
             * Выводим категорию 
             *  $level * 20 - отступ, $level - хранит текущий уровень вложености (0, 1, 2..)
             */
            $subcategories = subcategories($selected);
?>
            <option value="<?=$category['id_item']?>" style="text-align: left; padding-left: <?php echo ($level == 0 ? '5' : $level * 20); ?>px"<?php echo ($parent_selected == $category['id_item']) ? ' selected' : ''; ?> <?php echo ($parent['status'] == 'off' or $category['status'] == 'off' or in_array($category['id_item'], $subcategories)) ? ' disabled' : ''; ?>><?php echo protection($category['name'], 'display'); if ($count_subs[0] > 0) echo ' (' . $count_subs[0] . ') ▼'; ?>
            </option>
<? 

            $level = $level + 1; // Увеличиваем уровень вложености
            // Рекурсивно вызываем эту же функцию, но с новым $parent_id и $level
            build_tree_select($categories, $category['id_item'], $level, $selected, $parent_selected);
            $level = $level - 1; // Уменьшаем уровень вложености
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_GET['action']) and $_GET['action'] == 'submit') {
    $success = $error = null;

    $id =     isset($_POST['category_id']) ? abs(intval($_POST['category_id'])) : 0;
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
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `name` = '" . $name . "' AND `id_item` != '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Категория товаров с таким названием уже есть!';
    }

    if (!isset($error)) {
        if ($db->query("UPDATE `product_categories` SET `parent_id` = '" . $parent . "', `name` = '" . $name . "' WHERE  `id_item` = '" . $id  . "' AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['category_id'])) {
    $category_id = abs(intval($_GET['category_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `id_item` = '" . $category_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $category = $db->query("SELECT `name` FROM `product_categories` WHERE `id_item` = '" . $category_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['product_category_name' => protection($category['name'], 'display')];
        } else {
            $error = 'Неизвестная категория товаров!';
            $title = ['product_category_name' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `id_item` = '" . $category_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $category = $db->query("SELECT `id_item`, `parent_id`, `name`, `status`, `date_added` FROM `product_categories` WHERE `id_item` = '" . $category_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>

<script>
    $(function(){
        let form = $('#change-product-category'),
            btn = form.find('#button-change-product-category');
        
        function checkFields() {
            let name = form.find('#category-name').val(),
                error;
            
            if (name == '') {
                error = 'Введите название категории!';
            } else if (name.length > 25) {
                error = 'Название должно быть в пределах 25 символов!';
            }

            if (error) return error;
            else return false;

        }
        
        form.on('keyup change', function() {
            checkFields();
        });

        $('#change-product-category').on('submit', function(e){
            let error = checkFields();
            if (error) {
                if (!$('.modal-window-content div').is('.error')) {
                    $('.modal-window-content').prepend('<div class="error"></div>');
                    $('.error').text(error).show();
                }
            } else {
                var data = $(this).serializeArray();
                var count_modal = $('.modal-window-wrapper').length;
                $.ajax({
                    type: "POST",
                    url: "/ajax_viewProductCategory?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        // console.log(jsonData);
                        if (jsonData.success) {
                            hideOptions(true);
                            setTimeout(loadCategories, 100); // Иногда не обновлялось древо категорий
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
        <form id="change-product-category" method="post" autocomplete="off" spellcheck="false">
            <input type="hidden" name="category_id" value="<?=$category['id_item']?>">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input type="text" id="category-name" name="name" value="<?=protection($category['name'], 'display')?>">
                </div>

                <div class="modal-window-content__value">
                    <span>Подчиняется</span> <i class="fa fa-sitemap"></i>
                    <select id="sub-category" name="sub" class="chosen-select" <?php if ($category['parent_id'] == 0) { echo 'disabled'; } ?>>
                        <option value="">- Не указано -</option>
<?
$count = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($count[0] > 0) {
    $items = $db->query("SELECT `id_item`, `parent_id`, `name`, `status` FROM `product_categories` WHERE `client_id` = '" . $chief['id'] . "' ORDER BY `id` ASC");
    $categories = array();
    while ($single_category = $items->fetch_assoc()) {
        $categories[$single_category['parent_id']][] = $single_category;
    }
}
echo build_tree_select($categories, 0, 0, $category['id_item'], $category['parent_id']);
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Тип</span> <i class="fa fa-code-fork"></i>
                    <select name="type" class="chosen-select" onchange="checkState(this);">
                        <option value="">- Не указано -</option>
                        <option value="1" <?php if ($category['parent_id'] == 0) { echo 'selected="true"'; } ?>>Родительская</option>
                        <option value="2" <?php if ($category['parent_id'] <> 0) { echo 'selected="true"'; } ?>>Подчиняемая</option>
                    </select>
                </div>
               
                <br>
                <div class="modal-window-content__title">Дополнительно</div>
                
                <div class="modal-window-content__value">
                    <span>Статус</span> <i class="fa fa-eye-slash"></i>
                    <select name="status" class="chosen-select" disabled>
                        <option value="">Не выбрано</option>
                        <option value="1" <?php if ($category['status'] == 'off') { echo 'selected'; } ?>>Off</option>
                        <option value="2" <?php if ($category['status'] == 'on') { echo 'selected'; } ?>>On</option>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Добавлена</span> <i class="fa fa-calendar"></i> <input type="text" name="category-date-added" value="<?php echo passed_time($category['date_added']); ?>" disabled>
                </div>
                <br>
                <div class="buttons">
                    <button id="button-change-product-category" name="save-changes">Сохранить и закрыть</button>
                    <input type="submit" style="display: none">
                </div>
            </div>
        </form>
<?
    }
}