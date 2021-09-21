<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $symbol = isset($_POST['symbol']) ? protection($_POST['symbol'], 'base') : null;

    if (empty($symbol)) {
        $error = 'Введите символ!';
    } elseif (mb_strlen($symbol, 'UTF-8') != 1) {
        $error = 'Недопустимый символ!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `currencies` WHERE `symbol` = '" . $symbol . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Валюта с таким символом уже есть!';
    }

    if (empty($name)) {
        $error = 'Укажите название!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 30) {
        $error = 'Название должно быть в пределах от 2 до 30 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `currencies` WHERE `name` = '" . $name . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Валюта с таким названием уже есть!';
    }

    if (!isset($error)) {
        $sort = $db->query("SELECT COALESCE(MAX(`sort`), 0) AS `max` FROM `currencies` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $count = $db->query("SELECT `currencies` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['currencies'] + 1;
        if ($result = $db->query("SELECT COUNT(*) FROM `currencies` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            if ($db->query("INSERT INTO `currencies` (`id`, `id_item`, `client_id`, `name`, `symbol`, `date_added`, `sort`) VALUES (null, '" . $id_item . "', '" . $chief['id'] . "', '" . $name . "', '" . $symbol . "', '" . $data['time'] . "', '" . ($sort['max'] + 1) . "')")) {
                $success = 1;
                $db->query("UPDATE `id_counters` SET `currencies` = (`currencies` + 1) WHERE `client_id` = '" . $chief['id'] . "'");
            } else {
                $error = 'Не удалось добавить валюту!';
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
        let form = $('#add-currency'),
            btn = form.find('#button-add-currency');
        form.find('#currency-name').focus();

        function checkFields() {
            let error;

            let symbol = form.find('#currency-symbol').val();
            if (symbol == '') {
                error = 'Укажите символ!';
            } else if (symbol.length != 1) {
                error = 'Символ должен состоять с одного символа!';
            }

            let name = form.find('#currency-name').val();
            if (name == '') {
                error = 'Введите название валюты!';
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
                    url: "system/ajax/addCurrency.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);

                        if (jsonData.success == 1) {
                            loadCurrencies();
                            closeModalWindow(count_modal);
                        } else {
                            // console.log(jsonData.error);
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
        <form id="add-currency" method="post" spellcheck="false" autocomplete="off">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="currency-name" type="text" name="name" placeholder="Введите название">
                </div>
                <div class="modal-window-content__value">
                    <span>Символ</span> <i class="fa fa-dollar"></i> <input id="currency-symbol" type="text" name="symbol">
                </div>
                <div class="buttons">
                    <button id="button-add-currency" class="disabled">Добавить</button>
                </div>
            </div>
            <input type="submit" style="display: none">
        </form>