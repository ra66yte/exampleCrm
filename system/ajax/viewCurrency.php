<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $id = isset($_POST['currency_id']) ? abs(intval($_POST['currency_id'])) : null;
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null ;
    $symbol = isset($_POST['symbol']) ? protection($_POST['symbol'], 'base') : null;

    if (empty($name)) {
        $error = 'Укажите название!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 30) {
        $error = 'Название должно быть в пределах от 2 до 30 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `currencies` WHERE `name` = '" . $name . "' AND `id_item` != '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Валюта с таким названием уже есть!';
    }

    if (empty($symbol)) {
        $error = 'Укажите символ!';
    } elseif (mb_strlen($symbol, 'UTF-8') != 1) {
        $error = 'Символ должен состоять из одного символа!';
    }

    if (empty($id)) {
        $error = 'Валюта не выбрана!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `currencies` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Валюта не найдена!';
    }

    if (!isset($error)) {
        if ($db->query("UPDATE `currencies` SET `name` = '" . $name . "', `symbol` = '" . $symbol . "' WHERE `id` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = 'Не удалось обновить данные о валюте!';
        }
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['currency_id']) and is_numeric($_GET['currency_id'])) {
    $currency_id = abs(intval($_GET['currency_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `currencies` WHERE `id_item` = '" . $currency_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $currency = $db->query("SELECT `name` FROM `currencies` WHERE `id_item` = '" . $currency_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['currency_name' => protection($currency['name'], 'display')];
        } else {
            $error = 'Неизвестная валюта!';
            $title = ['currency_name' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `currencies` WHERE `id_item` = '" . $currency_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $currency = $db->query("SELECT `id_item`, `name`, `symbol`, `date_added` FROM `currencies` WHERE `id_item` = '" . $currency_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>
<script>
    $(function(){
        let form = $('#change-currency'),
            btn = form.find('#button-change-currency');
        
        function checkFields() {
            let error;
        
            let symbol  = form.find('#currency-symbol').val();
            if (symbol == '') {
                error = 'Укажите символ!';
            } else if (symbol.length != 1) {
                error = 'Символ должен состоять из одного символа!';
            }
        
            let name = form.find('#currency-name').val();
            if (name == '') {
                error = 'Укажите название!';
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
                    url: "/system/ajax/viewCurrency.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            loadCurrencies();
                            hideOptions(true);
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
        <form id="change-currency" method="post" spellcheck="false" autocomplete="off">
            <input type="hidden" name="currency_id" value="<?=protection($currency['id_item'], 'int')?>">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="currency-name" type="text" name="name" value="<?=protection($currency['name'], 'display')?>" placeholder="Введите название">
                </div>
                <div class="modal-window-content__value">
                    <span>Символ</span> <i class="fa fa-dollar"></i> <input id="currency-symbol" type="text" name="symbol" value="<?=protection($currency['symbol'], 'display')?>">
                </div>
                <div class="modal-window-content__value">
                    <span>Добавлена</span> <i class="fa fa-calendar-plus-o"></i> <input type="text" name="date_added" disabled value="<?=passed_time($currency['date_added'])?>">
                </div>
                <div class="buttons">
                    <button id="button-change-currency" name="save-changes">Сохранить и закрыть</button>
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
