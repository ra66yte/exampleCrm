<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $description = isset($_POST['description']) ? protection($_POST['description'], 'base') : null;

    if (empty($description)) {
        $error = 'Введите описание!';
    } elseif (mb_strlen($description, 'UTF-8') < 10 or mb_strlen($description, 'UTF-8') > 200) {
        $error = 'Описание должно быть в пределах от 10 до 200 символов!';
    }

    if (empty($name)) {
        $error = 'Введите название группы!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 25) {
        $error = 'Название группы должно быть в пределах от 2 до 25 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `groups_of_clients` WHERE `name` = '" . $name . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Группа клиентов с таким названием уже есть!';
    }
    
    if (!isset($error)) {
        $sort = $db->query("SELECT COALESCE(MAX(`sort`), 0) AS `max` FROM `groups_of_clients` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $count = $db->query("SELECT `groups_of_clients` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['groups_of_clients'] + 1;

        if ($result = $db->query("SELECT COUNT(*) FROM `groups_of_clients` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            if ($db->query("INSERT INTO `groups_of_clients` (`id`, `id_item`, `client_id`, `name`, `description`) VALUES (null, '" . $id_item . "', '" . $chief['id'] . "', '" . $name . "', '" . $description . "')")) {
                $success = 1;
                $db->query("UPDATE `id_counters` SET `groups_of_clients` = (`groups_of_clients` + 1) WHERE `client_id` = '" . $chief['id'] . "'");
            } else {
                $error = 'Не удалось добавить группу клиентов!';
            }
        } else {
            $error = 'Не удалось добавить группу клиентов! Попробуйте еще раз.';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
?>

<script>
    $(function(){
        let form = $('#add-group-of-clients'),
            btn = form.find('#button-add-group-of-clients');
        form.find('#group-name').focus();
        $('.chosen-select').chosen('destroy');
        $('.chosen-select').chosen({
            'disable_search': true
        });

        function checkFields() {
            let error;

            let description  = form.find('#group-description').val().trim();
            if (description == '') {
                error = 'Введите описание!';
            } else if (description.length < 10) {
                error = 'Описание не может содержать меньше 10 символов!';
            } else if (description.length > 200) {
                error = 'Описание должно быть в пределах 200 символов!';
            }

            var name = form.find('#group-name').val().trim();
            if (name == '') {
                error = 'Введите название группы!';
            } else if (name.length < 2) {
                error = 'Название не может содержать меньше 2 символов!';
            } else if (name.length > 25) {
                error = 'Название должно быть в пределах 25 символов!';
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
                let data = $(this).serializeArray();
                $.ajax({
                    type: "POST",
                    url: "system/ajax/addGroupOfClients.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response),
                            count_modal = $('.modal-window-wrapper').length;
                        if (jsonData.success == 1) {
                            loadGroupsOfClients();
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
        <form id="add-group-of-clients" method="post" autocomplete="off" spellcheck="off">
            <div class="modal-window-content__row">
                <div class="modal-window-content__item">
                    <div class="modal-window-content__title">Конфигурация</div>
                    <div class="modal-window-content__value">
                        <span>Название</span> <i class="fa fa-tag"></i> <input id="group-name" type="text" name="name" placeholder="Введите название">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Описание</span> <i class="fa fa-comment"></i> <textarea name="description" id="group-description"></textarea>
                    </div>
                    
                </div>
            </div>
            <div class="buttons">
                <button id="button-add-group-of-clients" class="disabled">Добавить</button>
            </div>
            <input type="submit" style="display: none">
        </form>