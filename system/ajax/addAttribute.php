<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $category = isset($_POST['category']) ? abs(intval($_POST['category'])) : null;

    if (empty($category) or !is_numeric($category)) {
        $error = 'Укажите категорию!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `attribute_categories` WHERE `id_item` = '" . $category . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Категория не найдена!';
    }

    if (empty($name)) {
        $error = 'Укажите название!';
    } elseif (mb_strlen($name, 'UTF-8') > 25) {
        $error = 'Название должно быть в пределах 25 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `attributes` WHERE `name` = '" . $name . "' AND `category_id` = '" . $category . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Атрибут с таким названием в этой категории уже есть!'; // ToDo: одинаковые названия в разных категориях
    }

    if (!isset($error)) {
        $sort = $db->query("SELECT COALESCE(MAX(`sort`), 0) AS `max` FROM `attributes` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $count = $db->query("SELECT `attributes` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['attributes'] + 1;

        if ($result = $db->query("SELECT COUNT(*) FROM `attributes` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            if ($db->query("INSERT INTO `attributes` (`id`, `id_item`, `category_id`, `client_id`, `name`, `date_added`, `sort`) VALUES (null, '" . $id_item . "', '" . $category . "', '" . $chief['id'] . "', '" . $name . "', '" . $data['time'] . "', '" . ($sort['max'] + 1) . "')")) {
                $success = 1;
                $db->query("UPDATE `id_counters` SET `attributes` = (`attributes` + 1) WHERE `client_id` = '" . $chief['id'] . "'");
            } else {
                $error = 'Не удалось добавить атрибут!';
            }
        } else {
            $error = 'Не удалось выполнить операцию!';
        }
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
?>

<script>
    $(function(){
        let form = $('#add-attribute'),
            btn = form.find('#button-add-attribute');
        form.find('#attribute-name').focus();

        function checkFields() {
            let error;

            let category = form.find('#attribute-category').val();
            if (category == '') {
                error = 'Укажите категорию!';
            } else if (isNaN(category)) {
                error = 'Категория укаазна неправильно!';
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
                    url: "system/ajax/addAttribute.php?action=submit",
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
                    }
                });
            }
            return false;
        });
    });
</script>
        <form id="add-attribute" method="post" autocomplete="off">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="attribute-name" type="text" name="name" placeholder="Введите название">
                </div>
                <div class="modal-window-content__value">
                    <span>Категория</span> <i class="fa fa-sitemap"></i> <select id="attribute-category" name="category" class="chosen-select">
                        <option value="">- Не указано -</option>
<?php
$query = $db->query("SELECT `id_item`, `name`, `status` FROM `attribute_categories` WHERE `client_id` = '" . $chief['id'] . "' ORDER BY `sort`");
$categories = array();
while ($category = $query->fetch_assoc()) {
?>
                        <option value="<?=$category['id_item']?>"<?=($category['status'] == 'off' ? ' disabled' : '')?>><?=protection($category['name'], 'display')?></option>
<?
}
?>
                    </select>
                </div>
                <br>
                <div class="modal-window-content__title" style="margin-top: 20px">Дополнительно</div>
                <br>
                <div class="modal-window-content__value">
                    <span>Статус</span> <i class="fa fa-eye-slash"></i>
                    <select name="category-status" class="chosen-select" disabled>
                        <option value="">Не выбрано</option>
                        <option value="off">Off</option>
                        <option value="on" selected>On</option>
                    </select>
                </div>
                <div class="buttons">
                    <button id="button-add-attribute" class="disabled">Добавить</button>
                </div>
            </div>
            <input type="submit" style="display: none">
        </form>