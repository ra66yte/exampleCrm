<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;

    $name =             isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $country =          isset($_POST['country']) ? protection($_POST['country'], 'int') : null;
    $person =           isset($_POST['contact-person']) ? protection($_POST['contact-person'], 'base') : null;
    $phone =            isset($_POST['phone']) ? protection($_POST['phone'], 'base') : null;
    $email =            isset($_POST['email']) ? protection($_POST['email'], 'base') : null;
    $skype =            isset($_POST['skype']) ? protection($_POST['skype'], 'base') : null;
    $code =             isset($_POST['code']) ? protection($_POST['code'], 'base') : null;
    $checking_account = isset($_POST['checking-account']) ? protection($_POST['checking-account'], 'base') : null;
    $card =             isset($_POST['bank_card']) ? protection($_POST['bank_card'], 'base') : null;
    $comment =          isset($_POST['comment']) ? protection($_POST['comment'], 'base') : null;

    if (!empty($comment)) {
        if (mb_strlen($comment, 'UTF-8') > 200) {
            $error = 'Комментарий не должен превышать 200 символов!';
        }
    }

    if (!empty($card)) {
        if ($card == 0 or !is_numeric($card)) {
            $error = 'Некоректное значение номера карты!';
        } elseif (mb_strlen($card, 'UTF-8') > 30) {
            $error = 'Слишком длинный номер карты!';
        }
    }

    if (!empty($checking_account)) {
        if ($checking_account == 0 or !is_numeric($checking_account)) {
            $error = 'Некоректное значение расчетного счета!';
        } elseif (mb_strlen($checking_account, 'UTF-8') > 30) {
            $error = 'Слишком длинный номер расчетного счета!';
        }
    }

    if (!empty($code)) {
        if ($code == 0 or !is_numeric($code)) {
            $error = 'Некоректное значение ЄДРПОУ!';
        } elseif (mb_strlen($code, 'UTF-8') != 8) {
            $error = 'Номер ЄДРПОУ должен состоять из 8 цифр!';
        }
    }

    if (!empty($skype)) {
        if (mb_strlen($skype, 'UTF-8') < 3 or mb_strlen($skype, 'UTF-8') > 30) {
            $error = 'Skype должен быть в пределах от 3 до 30 символов!';
        }
    }

    if (!empty($email)) {
        if (mb_strlen($email) < 6 or mb_strlen($email) > 60) {
            $error = 'E-mail адрес должен быть в пределах от 6 до 60 символов!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'E-mail адрес указан неверно!';
        }
    }

    if (empty($phone)) {
        $error = 'Укажите номер телефона!';
    }

    if (empty($person)) {
        $error = 'Укажите контактное лицо!';
    } elseif (mb_strlen($person, 'UTF-8') < 2 or mb_strlen($person, 'UTF-8') > 30) {
        $error = 'Поле "Контактное лицо" должно быть в пределах от 2 до 30 символов!';
    }

    if ($country <> 0) {
        if ($result = $db->query("SELECT COUNT(*) FROM `countries` WHERE `id` = '" . $country . "'")->fetch_row() and $result[0] == 0) {
            $error = 'Страна не найдена!';
        }
    }

    if (empty($name)) {
        $error = 'Укажите название организации!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 30) {
        $error = 'Название организации должно быть в пределах от 2 до 30 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `suppliers` WHERE `name` = '" . $name . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Поставщик с таким названием уже есть!';
    }

    if (!isset($error)) {
        $count = $db->query("SELECT `suppliers` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['suppliers'] + 1;

        if ($result = $db->query("SELECT COUNT(*) FROM `suppliers` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            $sql = "INSERT INTO `suppliers` (`id`, `id_item`, `client_id`, `name`, `country`, `contact_person`, `phone`, `email`, `skype`, `comment`, `code`, `bank_card`, `checking_account`, `date_added`) VALUES (null, '" . $id_item . "', '" . $chief['id'] . "', '" . $name . "', '" . $country . "', '" . $person . "', '" . $phone . "', '" . $email . "', '" . $skype . "', '" . $comment . "', '" . $code . "', '" . $card . "', '" . $checking_account . "', '" . $data['time'] . "')";
            if ($db->query($sql)) {
                $success = 1;
                $db->query("UPDATE `id_counters` SET `suppliers` = (`suppliers` + 1) WHERE `client_id` = '" . $chief['id'] . "'");
            } else {
                $error = 'Не удалось добавить поставщика!';
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
        let form = $('#add-supplier'),
            btn = form.find('#button-add-supplier');
        form.find('#supplier-name').focus();

        function checkFields() {
            let error;

            let comment = form.find('#supplier-comment').val().trim();
            if (comment != '') {
                if (comment.length > 200) {
                    error = 'Комментарий не должен превышать 200 символов!';
                }
            }

            let bankСard = form.find('#supplier-bank-card').val().trim();
            if (bankСard != '') {
                if (isNaN(bankСard)) {
                    error = 'Некоректное значение номера карты!';
                }
            }

            let checkingAccount = form.find('#supplier-checking-account').val().trim();
            if (checkingAccount != '') {
                if (isNaN(checkingAccount)) {
                    error = 'Некоректное значение расчетного счета!';
                }
            }

            let code = form.find('#supplier-code').val().trim();
            if (code != '') {
                if (isNaN(code)) {
                    error = 'Некоректное значение ЄДРПОУ!';
                } else if (code.length != 8) {
                    error = 'Номер ЄДРПОУ должен состоять из 8 цифр!';
                }
            }

            let skype = form.find('#supplier-skype').val().trim();
            if (skype != '') {
                if (skype.length < 3 || skype.length > 30) {
                    error = 'Skype должен быть в пределах от 3 до 30 символов!';
                }
            }

            let email = form.find('#supplier-email').val().trim();
            if (email != '') {
                if (email.length < 6 || email.length > 60) {
                    error = 'E-mail должен быть в пределах от 6 до 60 символов!';
                }
            }

            let phone = form.find('#supplier-phone').val().trim();
            if (phone == '') {
                error = 'Укажите номер телефона!';
            }

            let contactPerson = form.find('#supplier-contact-person').val().trim();
            if (contactPerson == '') {
                error = 'Укажите контактное лицо!';
            } else if (contactPerson.length < 2) {
                error = 'Поле "Контактное лицо" не может содержать меньше 2 символов!';
            } else if (contactPerson.length > 30) {
                error = 'Поле "Контактное лицо" должно быть в пределах 30 символов!';
            }

            let country = form.find('#supplier-country').val().trim();
            if (isNaN(country)) {
                error = 'Некоректное значение страны!';
            }
 
            let name = form.find('#supplier-name').val().trim();
            if (name == '') {
                error = 'Укажите название организации!';
            } else if (name.length < 2) {
                error = 'Название организации не может содержать меньше 2 символов!';
            } else if (name.length > 30) {
                error = 'Название организации должно быть в пределах 20 символов!';
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
                    url: "/system/ajax/addSupplier.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            loadSuppliers();
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
        <form id="add-supplier" method="post" autocomplete="off">
            <div class="modal-window-content__row">
                <div class="modal-window-content__item">
                    <div class="modal-window-content__title">Конфигурация</div>
                    <div class="modal-window-content__value">
                        <span>Организация</span> <i class="fa fa-building"></i> <input id="supplier-name" type="text" name="name" placeholder="Введите название">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Страна</span> <i class="fa fa-globe"></i> <select id="supplier-country" name="country" class="chosen-select">
                            <option value="">- Не указано -</option>
<?
$countries = $db->query("SELECT `id`, `name`, `code` FROM `countries`");
while ($country = $countries->fetch_assoc()) {
?>
                            <option data-id="<?=$country['id']?>" data-img-src="/img/countries/<?=strtolower($country['code'])?>.png"<?=($chief['country'] == $country['id'] ? ' selected' : '')?> value="<?=$country['id']?>"><?=protection($country['name'] . ' (' . $country['code'] . ')', 'display')?></option>
<?
}
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>Контактное лицо</span> <i class="fa fa-handshake-o"></i> <input id="supplier-contact-person" type="text" name="contact-person">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Телефон</span> <i class="fa fa-phone"></i> <input id="supplier-phone" name="phone" type="text">
                    </div>
                    <div class="modal-window-content__value">
                        <span>E-mail</span> <i class="fa fa-envelope"></i> <input id="supplier-email" type="text" name="email">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Skype</span> <i class="fa fa-skype"></i> <input id="supplier-skype" type="text" name="skype">
                    </div>
                </div>

                <div class="modal-window-content__item">
                    <div class="modal-window-content__title">Дополнительно</div>
                    <div class="modal-window-content__value">
                        <span>ЄДРПОУ</span> <i class="fa fa-id-card"></i> <input id="supplier-code" type="text" name="code">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Расчетный счет</span> <i class="fa fa-vcard"></i> <input id="supplier-checking-account" type="text" name="checking-account">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Банковская карта</span> <i class="fa fa-bank"></i> <input id="supplier-bank-card" type="text" name="bank_card">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Комментарий</span> <i class="fa fa-comment"></i> <textarea name="comment" id="supplier-comment" style="height: 200px"></textarea>
                    </div>
                </div>

            </div>
            <div class="buttons">
                <button id="button-add-supplier" class="disabled">Добавить</button>
            </div>
            <input type="submit" style="display: none">
        </form>