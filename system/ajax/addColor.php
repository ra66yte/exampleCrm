<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $name = protection($_POST['name'], 'base');

    if (empty($name)) {
        $error = 'Укажите название!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 25) {
        $error = 'Название должно быть в пределах от 2 до 25 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `colors` WHERE `name` = '" . $name . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Цвет с таким названием уже есть!';
    }

    if (!isset($error)) {
        $sort = $db->query("SELECT COALESCE(MAX(`sort`), 0) AS `max` FROM `colors` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $count = $db->query("SELECT `colors` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['colors'] + 1;

        if ($result = $db->query("SELECT COUNT(*) FROM `colors` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            if ($db->query("INSERT INTO `colors` (`id`, `id_item`, `client_id`, `name`, `date_added`, `sort`) VALUES (null, '" . $id_item . "', '" . $chief['id'] . "', '" . $name . "', '" . $data['time'] . "', '" . ($sort['max'] + 1) . "')")) {
                $success = 1;
                $db->query("UPDATE `id_counters` SET `colors` = (`colors` + 1) WHERE `client_id` = '" . $chief['id'] . "'");
            } else {
                $error = 'Не удалось добавить цвет!';
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
        let form = $('#add-color'),
            btn = form.find('#button-add-color');
        form.find('#color-name').focus();

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
                    url: "system/ajax/addColor.php?action=submit",
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
                    }
                });
            }
            return false;
        });
    });
</script>
        <form id="add-color" method="post" autocomplete="off">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="color-name" type="text" name="name" placeholder="Введите название">
                </div>
                <div class="buttons">
                    <button id="button-add-color" class="disabled">Добавить</button>
                </div>
            </div>
            <input type="submit" style="display: none">
        </form>