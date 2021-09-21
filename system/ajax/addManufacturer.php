<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $type = isset($_POST['type']) ? abs(intval($_POST['type'])) : null;
    $description = isset($_POST['description']) ? protection($_POST['description'], 'base') : null;

    if (empty($name)) {
        $error = 'Введите название производителя!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 30) {
        $error = 'Название производителя должно быть в пределах от 2 до 30 символов!';
    }

    if (!isset($type) or $type > 1) $type = 0;

    if (!empty($description)) {
        if (mb_strlen($description, 'UTF-8') > 200) {
            $error = 'Описание должно быть в пределе 200 символов!';
        }
    }

    if (!isset($error)) {
        $sort = $db->query("SELECT COALESCE(MAX(`sort`), 0) AS `max` FROM `manufacturers` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $count = $db->query("SELECT `manufacturers` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['manufacturers'] + 1;
        if ($result = $db->query("SELECT COUNT(*) FROM `manufacturers` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            if ($db->query("INSERT INTO `manufacturers` (`id`, `id_item`, `client_id`, `name`, `type`, `description`, `date_added`, `sort`) VALUES (null, '" . $id_item . "', '" . $chief['id'] . "', '" . $name . "', '" . $type . "', '" . $description . "', '" . $data['time'] . "', '" . ($sort['max'] + 1) . "')")) {
                $success = 1;
                $db->query("UPDATE `id_counters` SET `manufacturers` = (`manufacturers` + 1) WHERE `client_id` = '" . $chief['id'] . "'");
            } else {
                $error = 'Не удалось добавить производителя!';
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
        let form = $('#add-manufacturer'),
            btn = form.find('#button-add-manufacturer');

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
                    url: "system/ajax/addManufacturer.php?action=submit",
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
        <form id="add-manufacturer" method="post" autocomplete="off">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="manufacturer-name" type="text" name="name" placeholder="Введите название">
                </div>
                <div class="modal-window-content__value">
                    <span>Тип</span> <i class="fa fa-code-fork"></i> <select name="type" id="manufacturer-type" class="chosen-select">
                        <option value="">- Не указано -</option>
                        <option value="0">Бренд</option></option>
                        <option value="1">Страна производитель</option>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Описание</span> <i class="fa fa-comment"></i> <textarea name="description" id="manufacturer-description"></textarea>
                </div>
                <div class="buttons">
                    <button id="button-add-manufacturer" class="disabled">Добавить</button>
                </div>
            </div>
            <input type="submit" style="display: none">
        </form>