<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $name =    isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $address = isset($_POST['address']) ? protection($_POST['address'], 'base') : null;
    $email =   isset($_POST['email']) ? protection($_POST['email'], 'base') : null;

    if (empty($email)) {
        $error = 'Укажите E-mail!';
    } elseif (mb_strlen($email, 'UTF-8') < 6 or mb_strlen($email, 'UTF-8') > 64) {
        $error = 'E-mail должен быть в пределах от 6 до 64 символов!';
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
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `offices` WHERE `name` = '" . $name . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Отдел с таким названием уже есть!';
    }

    if (!isset($error)) {
        $count = $db->query("SELECT `offices` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['offices'] + 1;
        if ($result = $db->query("SELECT COUNT(*) FROM `offices` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            if ($db->query("INSERT INTO `offices` (`id`, `id_item`, `client_id`, `name`, `address`, `email`, `date_added`) VALUES (null, '" . $id_item . "', '" . $chief['id'] . "', '" . $name . "', '" . $address . "', '" . $email . "', '" . $data['time'] . "')")) {
                $success = 1;
                $db->query("UPDATE `id_counters` SET `offices` = `offices` + 1 WHERE `client_id` = '" . $chief['id'] . "'");
            } else {
                $error = 'Не удалось добавить отдел!';
            }
        } else {
            $error = 'Произошла ошибка! Попробуйте еще раз.';
        }
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
?>

<script>
    $(function(){
        let form = $('#add-office'),
            btn = form.find('#button-add-office');

        form.find('#office-name').focus();

        function checkFields() {
            let error,
                email = form.find('#office-email').val().trim(),
                address = form.find('#office-address').val().trim(),
                name = form.find('#office-name').val().trim();

            if (email == '') {
                error = 'Укажите E-mail!';
            } else if (email.length < 6) {
                error = 'E-mail не может содержать меньше 6 символов!';
            } else if (email.length > 60) {
                error = 'Адрес должен быть в пределах 60 символов!';
            }

            if (address == '') {
                error = 'Укажите адрес!';
            } else if (address.length < 2) {
                error = 'Адрес не может содержать меньше 2 символов!';
            } else if (address.length > 60) {
                error = 'Адрес должен быть в пределах 60 символов!';
            }
 
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
                    url: "system/ajax/addOffice.php?action=submit",
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
                            }
                        }
                    }
                });
            }
            return false;
        });
    });
</script>
        <form id="add-office" method="post" autocomplete="off" spellcheck="false">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="office-name" type="text" name="name" placeholder="Введите название">
                </div>
                <div class="modal-window-content__value">
                    <span>Адрес</span> <i class="fa fa-globe"></i> <input id="office-address" type="text" name="address">
                </div>
                <div class="modal-window-content__value">
                    <span>E-mail</span> <i class="fa fa-envelope"></i> <input id="office-email" type="text" name="email">
                </div>
                <div class="buttons">
                    <button id="button-add-office" class="disabled">Добавить</button>
                </div>
            </div>
            <input type="submit" style="display: none">
        </form>