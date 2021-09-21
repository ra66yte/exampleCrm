<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $id = isset($_POST['group_id']) ? abs(intval($_POST['group_id'])) : null;
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $description = isset($_POST['description']) ? protection($_POST['description'], 'base') : null;

    // Попытка редактирования группы покупателей или группы другого клиента
    if (($db->query("SELECT COUNT(*) FROM `groups_of_clients` WHERE `id_item` = '" . $id . "' AND `permanent` = 'on' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) or ($result = $db->query("SELECT COUNT(*) FROM `groups_of_clients` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0)) {
        $error = 'Хорошая попытка! :)';
    }

    if (empty($description)) {
        $error = 'Введите описание!';
    } elseif (mb_strlen($description, 'UTF-8') < 10 or mb_strlen($description, 'UTF-8') > 200) {
        $error = 'Описание должно быть в пределах от 10 до 200 символов!';
    }

    if (empty($name)) {
        $error = 'Введите название группы!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 25) {
        $error = 'Название должно быть в пределах от 2 до 25 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `groups_of_clients` WHERE `name` = '" . $name . "' AND `id_item` != '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Группа клиентов с таким названием уже есть!';
    }

    if (empty($id)) {
        $error = 'Группа не выбрана!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `groups_of_clients` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Группа не найдена!';
    }

    if (!isset($error)) {
        if ($db->query("UPDATE `groups_of_clients` SET `name` = '" . $name . "', `description` = '" . $description . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = 'Не удалось обновить группу клиентов!';
        }
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}



if (isset($_GET['group_id']) and is_numeric($_GET['group_id'])) {
    $group_id = abs(intval($_GET['group_id']));
    // Устанавливаем идентификатор сессии открытой группы
    $_SESSION['group_id'] = $group_id;

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `groups_of_clients` WHERE `id_item` = '" . $group_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $group = $db->query("SELECT `name` FROM `groups_of_clients` WHERE `id_item` = '" . $group_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['group_name' => protection($group['name'], 'display')];
        } else {
            $error = 'Неизвестная группа пользователей!';
            $title = ['group_name' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `groups_of_clients` WHERE `id_item` = '" . $group_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $group = $db->query("SELECT `id_item`, `name`, `description`, `sort` FROM `groups_of_clients` WHERE `id_item` = '" . $group_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>

<script>
    $(function(){
        let form = $('#save-group-of-clients'),
            btn = form.find('#button-save-group-of-clients');

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

            let name = form.find('#group-name').val().trim();
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
                let data = $(this).serializeArray(),
                    count_modal = $('.modal-window-wrapper').length;
                $.ajax({
                    type: "POST",
                    url: "system/ajax/viewGroupOfClients.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
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
        <form id="save-group-of-clients" method="post" autocomplete="off" spellcheck="false">
            <input type="hidden" name="group_id" value="<?=protection($group['id_item'], 'int')?>">
            <div class="modal-window-content__row">
                <div class="modal-window-content__item">
                    <div class="modal-window-content__title">Конфигурация</div>
                    <div class="modal-window-content__value">
                        <span>Название</span> <i class="fa fa-tag"></i> <input id="group-name" type="text" name="name" placeholder="Введите название" value="<?=protection($group['name'], 'display')?>">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Описание</span> <i class="fa fa-comment"></i> <textarea name="description" id="group-description"><?=protection($group['description'], 'display')?></textarea>
                    </div>
                </div>
            </div>
            <div class="buttons">
                    <button id="button-save-group-of-clients" class="form__button">Сохранить и закрыть</button>
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