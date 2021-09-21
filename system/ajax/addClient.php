<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;

    $name =    isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $country = isset($_POST['country']) ? protection($_POST['country'], 'int') : null;
    $group =   isset($_POST['group']) ? protection($_POST['group'], 'int') : null;
    $phone =   isset($_POST['phone']) ? protection($_POST['phone'], 'base') : null;
    $email =   isset($_POST['email']) ? protection($_POST['email'], 'base') : null;
    $comment = isset($_POST['comment']) ? protection($_POST['comment'], 'base') : null;

    if (!empty($comment)) {
        if (mb_strlen($comment, 'UTF-8') > 200) {
            $error = 'Описание не должно превышать 200 символов!';
        }
    }

    if (!empty($email)) {
        if (mb_strlen($email, 'UTF-8') < 6 or mb_strlen($email, 'UTF-8') > 60) {
            $error = 'E-mail адрес должен быть в пределах от 6 до 60 символов!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'E-mail адрес указан неверно!';
        } elseif ($result = $db->query("SELECT COUNT(*) FROM `clients` WHERE `email` = '" . $email . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $error = 'Клиент с таким E-mail уже есть!';
        }
    }

    if (empty($phone)) {
        $error = 'Укажите номер телефона клиента!';
    } else {
        $phone = mb_substr($phone, -10);
        if (mb_strlen($phone, 'UTF-8') == 10 and $result = $db->query("SELECT COUNT(*) FROM `clients` WHERE `phone` LIKE '%" . $phone . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $error = 'Клиент с таким номером телефона уже есть!';
        }
    }

    if (empty($group)) {
        $error = 'Укажите группу клиента!';
    } elseif (!is_numeric($group)) {
        $error = 'Некорректное значение группы клиента!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `groups_of_clients` WHERE `id_item` = '" . $group . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Группа клиентов не найдена!';
    }

    if (empty($country)) {
        $error = 'Укажите страну клиента!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `countries` WHERE `id` = '" . $country . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Страна не найдена!';
    }
    
    if (!isset($error)) {
        $count = $db->query("SELECT `clients` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['clients'] + 1;

        if ($result = $db->query("SELECT COUNT(*) FROM `clients` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            $sql = "INSERT INTO `clients` (`id`, `id_item`, `client_id`, `name`, `phone`, `email`, `comment`, `country`, `group_id`, `date_added`) VALUES (null, '" . $id_item . "', '" . $chief['id'] . "', '" . $name . "', '" . $phone . "', '" . $email . "', '" . $comment . "', '" . $country . "', '" . $group . "', '" . $data['time'] . "')";
            if ($db->query($sql)) {
                $success = 1;
                $db->query("UPDATE `id_counters` SET `clients` = (`clients` + 1) WHERE `client_id` = '" . $chief['id'] . "'");
            } else {
                $error = 'Не удалось добавить клиента!';
            }
        } else {
            $error = 'Не удалось добавить клиента! Попробуйте еще раз.';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
?>

<script>
    $(function(){
        let form = $('#add-client'),
            btn = form.find('#button-add-client');
        form.find('#client-name').focus();
        $('.chosen-select').chosen('destroy');
        $('.chosen-select').chosen({
            'disable_search': false
        });

        function checkFields() {
            let error;

            let comment = form.find('#client-comment').val().trim();
            if (comment != '') {
                if (comment.length > 200) {
                error = 'Комментарий не должен превышать 200 символов!';
                }
            }

            let email = form.find('#client-email').val().trim();
            if (email != '') {
                if (email.length < 6 || email.length > 60) {
                error = 'E-mail должен быть в пределах от 6 до 60 символов!';
                }
            }

            let phone = form.find('#client-phone').val().trim();
            if (phone == '') {
                error = 'Укажите номер телефона клиента!';
            }

            var group = form.find('#client-group').val();
            if (group == '') {
                error = 'Укажите группу!';
            } else if (isNaN(group) || group == 0) {
                error = 'Некорректное значение группы клиента!';
            }

            var country = form.find('#client-country').val().trim();
            if (country == '') {
                error = 'Укажите страну клиента!';
            } else if (isNaN(country) || country == 0) {
                error = 'Некорректное значение страны!';
            }

            var name = form.find('#client-name').val().trim();
            if (name == '') {
                error = 'Укажите имя!';
            } else if (name.length < 2) {
                error = 'Имя не может содержать меньше 2 символов!';
            } else if (name.length > 40) {
                error = 'Имя должно быть в пределах 40 символов!';
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
                    url: "/system/ajax/addClient.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response),
                            type = getParameterByName('type');
                        if (jsonData.success == 1) {
                            TabClients(type);
                            closeModalWindow(count_modal);
                        } else {
                            if (!$('.modal-window-content div').is('.error')) {
                                $('.modal-window-content').prepend('<div class="error"></div>');
                            }
                            $('.error').text(jsonData.error);
                        }
                    }
                });
            }
            return false;
        });
    });
</script>
        <form id="add-client" method="post" autocomplete="off">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Ф.И.О</span> <i class="fa fa-user"></i> <input id="client-name" type="text" name="name" placeholder="Введите имя">
                </div>
                <div class="modal-window-content__value">
                    <span>Страна</span> <i class="fa fa-globe"></i> <select name="country" id="client-country" class="chosen-select">
                        <option value="">- Не указано -</option>
<?
$countries = $db->query("SELECT `id`, `name`, `code` FROM `countries`");
while ($country = $countries->fetch_assoc()) {
?>
                        <option data-img-src="/img/countries/<?=strtolower($country['code'])?>.png"<?=($chief['country'] == $country['id'] ? ' selected' : '')?> value="<?php echo $country['id']; ?>"><?php echo protection($country['name'] . ' (' . $country['code'] . ')', 'display'); ?></option>
<?
}
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Группа</span> <i class="fa fa-users"></i> <select name="group" id="client-group" class="chosen-select">
                        <option value="">- Не указано -</option>
<?
$client_groups = $db->query("SELECT `id_item`, `name` FROM `groups_of_clients` WHERE `client_id` = '" . $chief['id'] . "'");
while ($group = $client_groups->fetch_assoc()) {
?>
                        <option value="<?php echo $group['id_item']; ?>" <?php echo ($_SESSION['clients_type'] == $group['id_item'] ? 'selected' : ''); ?>><?php echo protection($group['name'], 'display'); ?></option>
<?
}
unset($_SESSION['clients_type']);
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Телефон</span> <i class="fa fa-phone"></i> <input id="client-phone" type="text" name="phone">
                </div>
                <div class="modal-window-content__value">
                    <span>E-mail</span> <i class="fa fa-envelope"></i> <input id="client-email" type="text" name="email">
                </div>
                <div class="modal-window-content__value">
                    <span>Комментарий</span> <i class="fa fa-comment"></i> <textarea name="comment" id="client-comment"></textarea>
                </div>
                <div class="buttons">
                    <button id="button-add-client" class="form__button disabled">Добавить</button>
                </div>
            </div>
            <input type="submit" style="display: none">
        </form>