<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;

    $id =      isset($_POST['client_id']) ? abs(intval($_POST['client_id'])) : 0;
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
        } elseif ($result = $db->query("SELECT COUNT(*) FROM `clients` WHERE `email` = '" . $email . "' AND `id_item` != '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $error = 'Клиент с таким E-mail уже есть!';
        }
    }

    if (empty($phone)) {
        $error = 'Укажите номер телефона клиента!';
    } else {
        $phone = mb_substr($phone, -10);
        if (mb_strlen($phone) == 10 and $result = $db->query("SELECT COUNT(*) FROM `clients` WHERE `phone` LIKE '%" . $phone . "' AND `id_item` != '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $error = 'Клиент с таким номером телефона уже есть!';;
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
        $country = 0;
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `countries` WHERE `id` = '" . $country . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Страна не найдена!';
    }

    if (empty($id)) {
        $error = 'Клиент не выбран!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `clients` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Клиент не найден!';
    }
    
    if (!isset($error)) {
        $sql = "UPDATE `clients` SET `name` = '" . $name . "', `phone` = '" . $phone . "', `email` = '" . $email . "', `comment` = '" . $comment . "', `country` = '" . $country . "', `group_id` = '" . $group . "' WHERE `id_item` = '" . $id . "'";
        if ($db->query($sql)) {
            $success = 1;
        } else {
            $error = 'Не удалось обновить данные клиента!';
        }  
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['client_id']) and is_numeric($_GET['client_id'])) {
    $client_id = abs(intval($_GET['client_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `clients` WHERE `id_item` = '" . $client_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $client = $db->query("SELECT `name` FROM `clients` WHERE `id_item` = '" . $client_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['client_name' => protection($client['name'], 'display')];
        } else {
            $error = 'Неизвестный клиент!';
            $title = ['client_name' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `clients` WHERE `id_item` = '" . $client_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $client = $db->query("SELECT `id_item`, `name`, `phone`, `email`, `comment`, `site`, `ip`, `group_id`, `country`, `date_added` FROM `clients` WHERE `id_item` = '" . $client_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>
<script>
    $(function(){
        let form = $('#change-client'),
            btn = form.find('#button-change-client');

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

            let group = form.find('#client-group').val();
            if (group == '') {
                error = 'Укажите группу!';
            } else if (isNaN(group) || group == 0) {
                error = 'Некорректное значение группы клиента!';
            }

            let country = form.find('#client-country').val().trim();
            if (country != '') {
                if (isNaN(country) || country == 0) {
                    error = 'Некорректное значение страны! ' + country;
                }
            }

            let name = form.find('#client-name').val().trim();
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
                    url: "/ajax_viewClient?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            let type = getParameterByName('type');
                            TabClients(type);
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
        <form id="change-client" method="post" autocomplete="off">
            <input type="hidden" name="client_id" value="<?=$client['id_item'];?>">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Ф.И.О</span> <i class="fa fa-user"></i> <input id="client-name" type="text" name="name" placeholder="Введите имя" value="<?=protection($client['name'], 'display')?>">
                </div>
                <div class="modal-window-content__value">
                    <span>Страна</span> <i class="fa fa-globe"></i> <select name="country" id="client-country" class="chosen-select">
                        <option value="">- Не указано -</option>
<?
$countries = $db->query("SELECT `id`, `name`, `code` FROM `countries`");
while ($country = $countries->fetch_assoc()) {
?>
                        <option data-img-src="/img/countries/<?=strtolower($country['code'])?>.png" value="<?=$country['id']?>"<?=($client['country'] == $country['id'] ? ' selected' : '')?>><?=protection($country['name'] . ' (' . $country['code'] . ')', 'display')?></option>
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
                        <option value="<?=$group['id_item']?>"<?=($client['group_id'] == $group['id_item'] ? ' selected' : '')?>><?=protection($group['name'], 'display')?></option>
<?
}
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Телефон</span> <i class="fa fa-phone"></i> <input id="client-phone" type="text" name="phone" value="<?=protection($client['phone'], 'display')?>">
                </div>
                <div class="modal-window-content__value">
                    <span>E-mail</span> <i class="fa fa-envelope"></i> <input id="client-email" type="text" name="email" value="<?=protection($client['email'], 'display')?>">
                </div>
                <div class="modal-window-content__value">
                    <span>Комментарий</span> <i class="fa fa-comment"></i> <textarea name="comment" id="client-comment"><?=protection($client['comment'], 'display')?></textarea>
                </div>
                <div class="modal-window-content__value">
                    <span>Добавлено</span> <i class="fa fa-calendar"></i> <input id="client-date_added" type="text" name="date_added" value="<?=protection(passed_time($client['date_added']), 'display')?>" disabled>
                </div>
                <div class="buttons">
                    <button id="button-change-client" class="form__button">Сохранить и закрыть</button>
                </div>
            </div>
            <input type="submit" style="display: none">
        </form>
<?
    } else {
?>
    Something went wrong..
<?
    }
}