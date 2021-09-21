<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $id = abs(intval($_POST['office_id']));
    $name = protection($_POST['name'], 'base');
    $address = protection($_POST['address'], 'base');
    $email = protection($_POST['email'], 'base');

    if (empty($email)) {
        $error = 'Укажите E-mail!';
    } elseif (mb_strlen($email, 'UTF-8') < 6 or mb_strlen($email, 'UTF-8') > 60) {
        $error = 'E-mail должен быть в пределах от 6 до 60 символов!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'E-mail адрес указан неверно!';
    }

    if (empty($address)) {
        $error = 'Укажите адрес!';
    } elseif (mb_strlen($address, 'UTF-8') < 2 or mb_strlen($address, 'UTF-8') > 60) {
        $error = 'Адрес должен быть в пределах от 2 до 60 символов!';
    }

    if (empty($name)) {
        $error = 'Укажите название!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 20) {
        $error = 'Название должно быть в пределах от 2 до 20 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `offices` WHERE `name` = '" . $name . "' AND id_item` != '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Отдел с таким названием уже есть!';
    }

    if (empty($id)) {
        $error = 'Отдел не выбран!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `offices` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Отдел не найден!';
    }

    if (!isset($error)) {
        if ($db->query("UPDATE `offices` SET `name` = '" . $name . "', `address` = '" . $address . "', `email` = '" . $email . "' WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` = '" . $id . "'")) {
            $success = 1;
        } else {
            $error = 'Не удалось обновить данные!';
        }
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['office_id']) and is_numeric($_GET['office_id'])) {
    $office_id = abs(intval($_GET['office_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `offices` WHERE `id_item` = '" . $office_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $office = $db->query("SELECT `name` FROM `offices` WHERE `id_item` = '" . $office_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['office_name' => protection($office['name'], 'display')];
        } else {
            $error = 'Неизвестный отдел!';
            $title = ['office_name' => 'undefined'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `offices` WHERE `id_item` = '" . $office_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $office = $db->query("SELECT `id_item`, `name`, `address`, `email` FROM `offices` WHERE `id_item` = '" . $office_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>
<script>
    $(function(){
        let form = $('#change-office'),
            btn = form.find('#button-save-changes');

        function checkFields() {
            let error;

            let email = form.find('#office-email').val().trim();
            if (email == '') {
                error = 'Укажите E-mail!';
            } else if (email.length < 6) {
                error = 'E-mail не может содержать меньше 6 символов!';
            } else if (email.length > 60) {
                error = 'Адрес должен быть в пределах 60 символов!';
            }

            let address = form.find('#office-address').val().trim();
            if (address == '') {
                error = 'Укажите адрес!';
            } else if (address.length < 2) {
                error = 'Адрес не может содержать меньше 2 символов!';
            } else if (address.length > 60) {
                error = 'Адрес должен быть в пределах 60 символов!';
            }
 
            let name = form.find('#office-name').val().trim();
            if (name == '') {
                error = 'Укажите название!';
            } else if (name.length < 2) {
                error = 'Название не может содержать меньше 2 символов!';
            } else if (name.length > 20) {
                error = 'Название должно быть в пределах 20 символов!';
            }

            if (error) {
                btn.addClass('disabled');
            } else {
                btn.removeClass('disabled');
            }

            if (error) return error;
            else return false;
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
                    url: "system/ajax/viewOffice.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            loadOffices();
                            closeModalWindow(count_modal);
                        } else {
                            if (!$('.modal-window-content div').is('.error')) {
                                $('.modal-window-content').prepend('<div class="error"></div>');
                                $('.error').text(jsonData.error).show();
                            } else {
                                $('.error').text(jsonData.error);
                            }
                        }
                    }
                });
            }
            return false;
        });
    });
</script>
        <form id="change-office" method="post" autocomplete="off">
            <input type="hidden" name="office_id" value="<?php echo protection($office['id_item'], 'int') ?>">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="office-name" type="text" name="name" placeholder="Введите название" value="<?=protection($office['name'], 'display')?>">
                </div>
                <div class="modal-window-content__value">
                    <span>Адрес</span> <i class="fa fa-globe"></i> <input id="office-address" type="text" name="address" value="<?=protection($office['address'], 'display')?>">
                </div>
                <div class="modal-window-content__value">
                    <span>E-mail</span> <i class="fa fa-envelope"></i> <input id="office-email" type="text" name="email" value="<?=protection($office['email'], 'display')?>">
                </div>
                <div class="buttons">
                    <button id="button-change-office">Сохранить и закрыть</button>
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
