<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $id = isset($_POST['site_id']) ? abs(intval($_POST['site_id'])) : null;
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $url = isset($_POST['url']) ? protection($_POST['url'], 'base') : null;

    if (empty($url)) {
        $error = 'Введите адрес!';
    } elseif (!preg_match("/^(https?:\/\/)?([\w\.]+)\.([a-z]{2,6}\.?)(\/[\w\.]*)*\/?$/", $url)) {
        $error = 'Укажите корректный адрес!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `sites` WHERE `url` = '" . $url . "' AND `id_item` != '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Сайт с таким адресом уже добавлен!';
    }

    if (empty($name)) {
        $error = 'Укажите название!';
    } elseif (mb_strlen($name, 'UTF-8') < 3 or mb_strlen($name, 'UTF-8') > 30) {
        $error = 'Название должно быть в пределах от 2 до 30 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `sites` WHERE `name` = '" . $name . "' AND `id_item` != '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Сайт с таким названием уже добавлен!';
    }

    if (empty($id)) {
        $error = 'Сайт не выбран!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `sites` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Сайт не найден!';
    }

    if (!isset($error)) {
        if ($db->query("UPDATE `sites` SET `name` = '" . $name . "', `url` = '" . $url . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = 'Не удалось обновить данные о сайте!';
        }
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['site_id']) and is_numeric($_GET['site_id'])) {
    $site_id = abs(intval($_GET['site_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `sites` WHERE `id_item` = '" . $site_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $site = $db->query("SELECT `name`, `url` FROM `sites` WHERE `id_item` = '" . $site_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['site_name' => protection($site['name'], 'display'), 'site_url' => protection($site['url'], 'display')];
        } else {
            $error = 'Неизвестный сайт!';
            $title = ['site_name' => 'UNDEFINED', 'site_url' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `sites` WHERE `id_item` = '" . $site_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $site = $db->query("SELECT `id_item`, `name`, `url`, `date_added` FROM `sites` WHERE `id_item` = '" . $site_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>
<script>
    $(function(){
        let form = $('#change-site'),
            btn = form.find('#button-change-site');
        
        function isValidURL(myURL) { 
            return /^(https?:\/\/)?([\w\.]+)\.([a-z]{2,6}\.?)(\/[\w\.]*)*\/?$/.test(myURL); 
        } 

        function checkFields() {
            let error;

            let url = form.find('#site-url').val();
            if (url == '') {
                error = 'Укажите адрес!';
            } else if (!isValidURL(url)){
                error = 'Укажите корректный адрес!';
            }
 
            let name = form.find('#site-name').val();
            if (name == '') {
                error = 'Укажите название!';
            } else if (name.length < 2) {
                error = 'Название не может содержать меньше 2 символов!';
            } else if (name.length > 30) {
                error = 'Название должно быть в пределах 30 символов!';
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
                    url: "/system/ajax/viewSite.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            loadSites();
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
        <form id="change-site" method="post" autocomplete="off">
            <input type="hidden" name="site_id" value="<?=protection($site['id_item'], 'int')?>">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="site-name" type="text" name="name" value="<?=protection($site['name'], 'display')?>" placeholder="Введите название">
                </div>
                <div class="modal-window-content__value">
                    <span>Адрес</span> <i class="fa fa-globe"></i> <input id="site-url" type="text" name="url" value="<?=protection($site['url'], 'display')?>" placeholder="Введите адрес">
                </div>
                <div class="modal-window-content__title" style="margin-top: 20px">Дополнительно</div>
                <div class="modal-window-content__value">
                    <span>Добавлен</span> <i class="fa fa-calendar-plus-o"></i> <input type="text" name="date_added" disabled value="<?=passed_time($site['date_added'])?>">
                </div>
                <div class="buttons">
                    <button id="button-change-site" name="save-changes">Сохранить и закрыть</button>
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
