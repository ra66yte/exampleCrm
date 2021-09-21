<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $url = isset($_POST['url']) ? protection($_POST['url'], 'base') : null;

    if (empty($url)) {
        $error = 'Введите адрес!';
    } elseif (!preg_match("/^(https?:\/\/)?([\w\.]+)\.([a-z]{2,6}\.?)(\/[\w\.]*)*\/?$/", $url)) {
        $error = 'Укажите корректный адрес!';
    }

    if (empty($name)) {
        $error = 'Укажите название!';
    } elseif (mb_strlen($name, 'UTF-8') < 3 or mb_strlen($name, 'UTF-8') > 30) {
        $error = 'Название должно быть в пределах от 2 до 30 символов!';
    }

    if (!isset($error)) {
        $sort = $db->query("SELECT COALESCE(MAX(`sort`), 0) AS `max` FROM `sites` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $count = $db->query("SELECT `sites` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['sites'] + 1;

        if ($result = $db->query("SELECT COUNT(*) FROM `sites` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            if ($db->query("INSERT INTO `sites` (`id`, `id_item`, `client_id`, `name`, `url`, `date_added`, `sort`) VALUES (null, '" . $id_item . "', '" . $chief['id'] . "', '" . $name . "', '" . $url . "', '" . $data['time'] . "', '" . ($sort['max'] + 1) . "')")) {
                $success = 1;
                $db->query("UPDATE `id_counters` SET `sites` = (`sites` + 1) WHERE `client_id` = '" . $chief['id'] . "'");
            } else {
                $error = 'Не удалось добавить сайт!';
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
        let form = $('#add-site'),
            btn = form.find('#button-add-site');
        form.find('#site-name').focus();

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
                    url: "system/ajax/addSite.php?action=submit",
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
        <form id="add-site" method="post" autocomplete="off">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="site-name" type="text" name="name" placeholder="Введите название">
                </div>
                <div class="modal-window-content__value">
                    <span>Адрес</span> <i class="fa fa-globe"></i> <input id="site-url" type="text" name="url">
                </div>
                <div class="buttons">
                    <button id="button-add-site" class="disabled">Добавить</button>
                </div>
            </div>
            <input type="submit" style="display: none">
        </form>