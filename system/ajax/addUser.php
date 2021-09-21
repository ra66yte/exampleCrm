<?php
include_once '../core/begin.php';
// Проверка доступности логина
if (isset($_GET['action']) and $_GET['action'] == 'checklogin' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    $login = protection($_POST['login'], 'base');
    if ($result = $db->query("SELECT COUNT(*) FROM `user` WHERE `login` = '" . $login . "'")->fetch_row() and $result[0] == 0) {
        $success = 1;
    } else {
        $error = 'Логин <b>' . protection($_POST['login'], 'display') . '</b> недоступен! Придумайте другой.';
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;

    $country =  isset($_POST['country']) ? protection($_POST['country'], 'int') : 0;
    $fio =      isset($_POST['fio']) ? protection($_POST['fio'], 'base') : null;
    $offices =  isset($_POST['offices']) ? $_POST['offices'] : null;
    $group =    isset($_POST['group']) ? protection($_POST['group'], 'int') : 0;
    $login =    isset($_POST['login']) ? protection($_POST['login'], 'base') : null;
    $password = isset($_POST['user_password']) ? protection($_POST['user_password'], 'base') : null;
    $phone =    isset($_POST['phone']) ? protection($_POST['phone'], 'base') : null;
    $email =    isset($_POST['email']) ? protection($_POST['email'], 'base') : null;
    $site =     isset($_POST['site']) ? protection($_POST['site'], 'base') : null;
    $comment =  isset($_POST['comment']) ? protection($_POST['comment'], 'base') : null;

    if (!empty($comment)) {
        if (mb_strlen($comment, 'UTF-8') > 200) {
            $error = 'Описание не должно превышать 200 символов!';
        }
    }

    if (!empty($site)) {
        if (mb_strlen($site, 'UTF-8') < 4 or mb_strlen($site, 'UTF-8') > 60) {
            $error = 'Адрес сайта должен быть в пределах от 4 до 60 символов!';
        } elseif (!preg_match("/^(https?:\/\/)?([\w\.]+)\.([a-z]{2,6}\.?)(\/[\w\.]*)*\/?$/", $site)) {
            $error = 'Укажите корректный адрес сайта!';
        }
    }

    if (!empty($email)) {
        if (mb_strlen($email, 'UTF-8') < 6 or mb_strlen($email, 'UTF-8') > 60) {
            $error = 'E-mail адрес должен быть в пределах от 6 до 60 символов!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'E-mail адрес указан неверно!';
        }
    }

    if (empty($phone)) {
        $error = 'Укажите номер телефона!';
    }

    if (empty($password)) {
        $error = 'Укажите пароль!';
    } elseif (mb_strlen($password, 'UTF-8') < 6 or mb_strlen($password, 'UTF-8') > 20) {
        $error = 'Пароль должен быть в пределах от 6 до 20 символов!';
    }

    if (empty($login)) {
        $errorr = 'Укажите логин!';
    } elseif (mb_strlen($login, 'UTF-8') < 2 or mb_strlen($login, 'UTF-8') > 20) {
        $error = 'Логин должен быть в пределах от 2 до 20 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `user` WHERE `login` = '" . $login . "' AND `chief_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Выбранный логин недоступен! Придумайте другой.';
    } elseif (!preg_match('/^[A-Za-z0-9]+(?:[-_\.]?[A-Za-z0-9])*+$/u', $login)) {
        $error = 'Выбранный логин недопустим.<br>Правильный логин может содержать буквы латинского алфавита, цифры, а также символы -,_ и ., но только внутри.<br>[A-Za-z0-9-_.], например <b>ivanov.ivan12</b>';
    }

    if (empty($group)) {
        $error = 'Укажите группу!';
    } elseif (!is_numeric($group)) {
        $error = 'Некорректное значение группы пользователя!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `groups_of_users` WHERE `id_item` = '" . $group . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Группа пользователей не найдена!';
    }

    if (empty($offices)) {
        $error = 'Укажите отдел пользователя!';
    } else {
        foreach ($offices as $key => $value) {
            if (!is_numeric($value)) {
                $error = 'Некоректное значение отдела пользователя!';
            } elseif ($result = $db->query("SELECT COUNT(*) FROM `offices` WHERE `id_item` = '" . abs(intval($value)) . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
                $error = 'Произошла ошибка при выборе отдела(ов)!';
            }
        }
    }

    if (empty($fio)) {
        $error = 'Укажите Ф.И.О пользователя!';
    } elseif (mb_strlen($fio, 'UTF-8') < 5 or mb_strlen($fio, 'UTF-8') > 60) {
        $error = 'Ф.И.О должны быть в пределах от 5 до 60 символов!';
    }
    
    if ($country <> 0) {
        if ($result = $db->query("SELECT COUNT(*) FROM `countries` WHERE `id` = '" . $country . "'")->fetch_row() and $result[0] == 0) {
            $error = 'Направление не найдено!';
        }
    }

    if (isset($_FILES['image']['name']) and $_FILES['image']['name'] != '') { // Если выбирали файл
        // Получаем нужные элементы массива "image"
        $fileTmpName = $_FILES['image']['tmp_name'];
        $errorCode = $_FILES['image']['error'];
        // Проверим на ошибки
        if ($errorCode !== UPLOAD_ERR_OK || !is_uploaded_file($fileTmpName)) {
            // Массив с названиями ошибок
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE   => 'Размер файла превысил значение upload_max_filesize в конфигурации PHP.',
                UPLOAD_ERR_FORM_SIZE  => 'Размер загружаемого файла превысил значение MAX_FILE_SIZE в HTML-форме.',
                UPLOAD_ERR_PARTIAL    => 'Загружаемый файл был получен только частично.',
                UPLOAD_ERR_NO_FILE    => 'Файл не был загружен.',
                UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка.',
                UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск.',
                UPLOAD_ERR_EXTENSION  => 'PHP-расширение остановило загрузку файла.',
            ];
            // Зададим неизвестную ошибку
            $unknownMessage = 'При загрузке файла произошла неизвестная ошибка!';
            // Если в массиве нет кода ошибки, скажем, что ошибка неизвестна
            $error = isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : $unknownMessage;
        } else {
            // Ошибок нет
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            // Получим MIME-тип
            $mime = (string) finfo_file($fi, $fileTmpName);
            // Проверим ключевое слово image (image/jpeg, image/png и т. д.)
            if (strpos($mime, 'image') === false) {
                $error = 'Можно загружать только изображения!';
            } else {
                // Если это действительно изображение
                $image = getimagesize($fileTmpName);
                $limitBytes  = 1024 * 1024 * 5; // 1 mb
                $limitWidth  = 2048;
                $limitHeight = 2048;
                // Проверим нужные параметры
                if (filesize($fileTmpName) > $limitBytes) {
                    $error = 'Размер изображения не должен превышать 5 Mb!';
                }
                if ($image[1] > $limitHeight) {
                    $error = 'Высота изображения не должна превышать ' . $limitHeight . 'px!';
                }
                if ($image[0] > $limitWidth) {
                    $error = 'Ширина изображения не должна превышать ' . $limitWidth . 'px!';
                }

                if (!pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION)) {
                    $error = 'Неверный формат файла!';
                }

                if (!$error) {
                    $path = $_SERVER['DOCUMENT_ROOT'] . '/system/images/photo/' . $chief['id'] . '/';
                    if (!is_dir($path)) {
                        mkdir($path, 0777, true);
                    }

                    // Оставляем в имени файла только буквы, цифры и некоторые символы.
			        $pattern = "[^a-zа-яё0-9,~!@#%^-_\$\?\(\)\{\}\[\]\.]";
			        $name = mb_eregi_replace($pattern, '-', $_FILES['image']['name']);
			        $name = mb_ereg_replace('[-]+', '-', $name);

                    // Т.к. есть проблема с кириллицей в названиях файлов (файлы становятся недоступны).
			        // Сделаем их транслит:
			        $converter = array(
				        'а' => 'a',   'б' => 'b',   'в' => 'v',    'г' => 'g',   'д' => 'd',   'е' => 'e',
				        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',    'и' => 'i',   'й' => 'y',   'к' => 'k',
				        'л' => 'l',   'м' => 'm',   'н' => 'n',    'о' => 'o',   'п' => 'p',   'р' => 'r',
				        'с' => 's',   'т' => 't',   'у' => 'u',    'ф' => 'f',   'х' => 'h',   'ц' => 'c',
				        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',  'ь' => '',    'ы' => 'y',   'ъ' => '',
				        'э' => 'e',   'ю' => 'yu',  'я' => 'ya', 
			
				        'А' => 'A',   'Б' => 'B',   'В' => 'V',    'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
				        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',    'И' => 'I',   'Й' => 'Y',   'К' => 'K',
				        'Л' => 'L',   'М' => 'M',   'Н' => 'N',    'О' => 'O',   'П' => 'P',   'Р' => 'R',
				        'С' => 'S',   'Т' => 'T',   'У' => 'U',    'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
				        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',  'Ь' => '',    'Ы' => 'Y',   'Ъ' => '',
				        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
			        );
 
			        $name = strtr($name, $converter);
                    $file_name = $path . $name;
                    $parts = pathinfo($file_name);
                
                    // Чтобы не затереть файл с таким же названием, добавим префикс.
				    $i = 0;
				    $prefix = '';
				    while (is_file($path . $parts['filename'] . $prefix . '.' . $parts['extension'])) {
		  			    $prefix = '(' . ++$i . ')';
				    }
				    $name = $parts['filename'] . $prefix . '.' . $parts['extension'];
 
				    // Перемещаем файл в директорию.
				    if (move_uploaded_file($_FILES['image']['tmp_name'], $path . $name)) {
                        $image_name = $name;
                    } else {
                        $error = 'Не удалось загрузить файл!';
                    }
                }
            }
        }

    } else {
        $image_name = 'no_photo.png';
    }
    
    // Если нет ошибок
    if (!isset($error)) {

        $count = $db->query("SELECT `users` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['users'] + 1;
        // Добавляем пользователя в таблицу `user`
        $sql = "INSERT INTO `user` (`id`, `id_item`, `login`, `password`, `name`, `avatar`, `group_id`, `chief_id`, `phone`, `email`, `site`, `comment`, `country`) VALUES (null, '" . $id_item . "', '" . $login . "', '" . password_hash($password, PASSWORD_DEFAULT) . "', '" . $fio . "', '" . $image_name . "', '" . $group . "', '" . $chief['id'] . "', '" . $phone . "', '" . $email . "', '" . $site . "', '" . $comment . "', '" . $country . "')";

        if ($result = $db->query("SELECT COUNT(*) FROM `user` WHERE `id_item` = '" . $id_item . "' AND `chief_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            if ($db->query($sql)) {
    
                // Добавляем пользователя в таблицу `staff`
                $sql = "INSERT INTO `staff` (`id`, `chief_id`, `employee_id`, `group_id`) VALUES (null, '" . $chief['id'] . "', '" . $id_item . "', '" . $group . "')";
                if ($db->query($sql)) {
                    // Добавляем пользователю права, если они предоставлены
                    if (isset($_POST['rights']) and count($_POST['rights']) > 0) {
                        $sql = "INSERT INTO `employee_right` (`id`, `client_id`, `employee_id`, `staff_right_id`) VALUES";
                        $need = false; // Надо ли делать запрос
                        foreach ($_POST['rights'] as $right => $value) {
                            $right = protection($right, 'base');
                            if ($result = $db->query("SELECT COUNT(*) FROM `staff_rights` WHERE `code_name` = '" . $right . "'")->fetch_row() and $result[0] > 0) {
                                $need = true;
                                $employee_right = $db->query("SELECT `id` FROM `staff_rights` WHERE `code_name` = '" . $right . "'")->fetch_assoc();
                                $sql .= " (null, '" . $chief['id'] . "', '" . $id_item . "', '" . $employee_right['id'] . "'),";
                            }
                        }
                        $sql = rtrim($sql, ',');
                        if ($need == true) {
                            if ($db->query($sql)) {
    
                            } else {
                                $error = 'Произошла ошибка при выполнении операции! [e:4]';
                            }
                        }
                    }
    
                    // Добавлем отделы пользователя в таблицу `staff_offices`
                    $sql = "INSERT INTO `staff_offices` (`id`, `client_id`, `employee_id`, `office_id`) VALUES";
                    foreach ($offices as $key => $value) {
                        $sql .= " (null, '" . $chief['id'] . "', '" . $id_item . "', '" . abs(intval($value)) . "'),";
                    }
                    $sql = rtrim($sql, ',');
                    if ($db->query($sql)) {
                        // Успех
                        $success = 1;
                    } else {
                        $error = 'Произошла ошибка при выполнении операции! [e:3]';
                    }

                    $db->query("UPDATE `id_counters` SET `users` = (`users` + 1) WHERE `client_id` = '" . $chief['id'] . "'");
                    
                } else {
                    $error = 'Произошла ошибка при выполнении операции! [e:2]';
                }
                
            } else {
                $error = 'Произошла ошибка при выполнении операции!';
            }
        } else {
            $error = 'Произошла ошибка! Попробуйте еще раз.';
        }
        
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
?>
<style>
    #user-image-block {
        position: relative;
        display: block;
        border: 1px dashed #ababab;
        border-radius: 3px;
        padding: 2px;
        height: 150px;
        width: 150px;
        background-repeat: no-repeat;
        background-position: center center;
        background-size: contain;
        background-color: #fff;
        margin: 0 auto;
    }
    #clear-image {
        position: absolute;
        background: #900;
        color: #fff;
        font-size: 16px;
        top: 1px;
        right: 1px;
        padding: 0 5px 1px;
        cursor: pointer;
    }
    #info-image-name {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 2px 0 3px;
        background: rgba(0, 0, 0, 0.5);
        color: #fff;
        text-align: center;
        font-size: 12px;
        white-space: nowrap;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .password-random {
        position: absolute;
        top: 0;
        right: 7px;
        transform: translate(0%, 25%);
    }

    .password-control {
        position: absolute;
        top: 0;
        right: 24px;
        transform: translate(0%, 25%);
    }
</style>
<script>
    $(function(){

        $('ul#scroll-access').on('click', 'li', function(e){
            let checker = $(this).find('input[type="checkbox"]');
            if (checker.prop('checked') == true) {
                checker.prop('checked', false);
                $(this).addClass('off');
            } else {
                checker.prop('checked', true);
                $(this).removeClass('off');
            }

            $('ul#scroll-statuses input[type="checkbox"]').trigger('change');
        });

        $('.password-control').hide();
        $('#password-input').on('keyup', function(){
            let pass = $(this).val();
            if (pass.length > 0) {
                $('.password-control').show();
            } else {
                $('.password-control').hide();
            }
        });

        let form = $('#add-user'),
            btn = form.find('#button-add-user');

        form.find('#user-fio').focus();

        function checkFields() {
            let error;

            let uploadFile = form.find('#user-image');
            if (uploadFile.val() != '') {
                let maxFileSize = 5; // mb
                if (!validateSize(uploadFile[0], maxFileSize)){
                    error = 'Размер файла превышает ' + maxFileSize + ' MB';
                }
            }

            let comment = form.find('#user-comment').val().trim();
            if (comment != '') {
                if (comment.length > 200) {
                error = 'Комментарий не должен превышать 200 символов!';
                }
            }

            let site = form.find('#user-site').val().trim();
            if (site != '') {
                if (site.length < 4 || site.length > 60) {
                error = 'Сайт пользователя должен быть в пределах от 4 до 60 символов!';
                }
            }

            let email = form.find('#user-email').val().trim();
            if (email != '') {
                if (email.length < 6 || email.length > 60) {
                error = 'E-mail должен быть в пределах от 6 до 60 символов!';
                }
            }

            let phone = form.find('#user-phone').val().trim();
            if (phone == '') {
                error = 'Укажите номер телефона пользователя!';
            }

            let pass = form.find('#password-input').val().trim();
            if (pass == '') {
                error = 'Укажите пароль пользователя!';
            } else if (pass.length < 6 || pass.length > 20) {
                error = 'Пароль пользователя должен быть в пределах от 6 до 20 символов!';
            }

            let login = form.find('#user-login').val().trim();
            if (login == '') {
                error = 'Укажите логин пользователя!'
            } else if (login.length < 2 || login.length > 20) {
                error = 'Логин пользователя должен быть в пределах от 2 до 20 символов!';
            }

            let group = form.find('#user-group').val();
            if (group == '') {
                error = 'Укажите группу пользователя!';
            } else if (isNaN(group)) {
                error = 'Некоректное значение группы пользователя!';
            }
            
            let office = form.find('#user-office').val();
            if (office == '') {
                error = 'Укажите отдел пользователя!';
            }
            

            let fio = form.find('#user-fio').val().trim();
            if (fio == '') {
                error = 'Укажите Ф.И.О пользователя!';
            } else if (fio.length < 5 || fio.length > 60) {
                error = 'Ф.И.О должны быть в пределах от 5 до 60 символов!';
            }

            let direction = form.find('#user-direction').val();
            if (isNaN(direction)) {
                error = 'Некоректное значение направления';
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
                let data = new FormData($(this).get(0)),
                    count_modal = $('.modal-window-wrapper').length;
                $.ajax({
                    type: "POST",
                    url: "system/ajax/addUser.php?action=submit",
                    data: data,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        console.log(jsonData)
                        if (jsonData.success == 1) {
                            loadUsers();
                            closeModalWindow(count_modal);
                        } else {
                            if (!$('.modal-window-content div').is('.error')) {
                                $('.modal-window-content').prepend('<div class="error"></div>');
                                $('.error').html(jsonData.error).show();
                                btn.addClass('disabled');
                            }
                        }
                    }
                });
            }
            return false;
        });


    });

    function gen_password(len){
        let password = "",
            symbols = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!№;%:?*()_+=";
        for (i = 0; i < len; i++){
            password += symbols.charAt(Math.floor(Math.random() * symbols.length));     
        }
        return password;
    }

    function random_password(target){
        let input = document.getElementById('password-input');
        input.value = gen_password(6);
        $('.password-control').show();
    }

    function show_hide_password(target){
        let input = document.getElementById('password-input');
        if (input.getAttribute('type') == 'password') {
            target.innerHTML = '<i class="fa fa-eye"></i>';
            input.setAttribute('type', 'text');
        } else {
            target.innerHTML = '<i class="fa fa-eye-slash"></i>';
            input.setAttribute('type', 'password');
        }
        return false;
    }

    function clearImage() {
        $('#clear-image').hide();
        $('#user-image').val('');
        $('#user-image-block').css('background-image', 'url(/system/images/photo/no_photo.png)');
        $('#info-image-name').hide();
        $('#button-image-user').css('visibility', 'visible');
    }

    function readFile(input) {
        if (input.files && input.files[0]) {
            let size0 = input.files[0].size,
                maxSize = 1024 * 1024 * 5,
                error;

            if (size0 > maxSize) {
                error = 'Максимальный размер загружаемого изображения 5 Mb';
            }

            let type = input.files[0].type;
            if (type === 'image/png' || type === 'image/jpg' || type === 'image/jpeg') {
                
            } else {
                error = 'Разрешены только изображения в формате png, jpg и jpeg!';
            }

            if (error) {
                if (!$('.modal-window-content div').is('.error')) {
                    $('.modal-window-content').prepend('<div class="error"></div>');
                    $('.error').text(error).show();
                }
                $('#button-add-user').addClass('disabled');
            } else {

                let reader = new FileReader(input.files[0]);

                reader.onload = function(e) {
                    $('#user-image-block').css('background-image', 'url(' + e.target.result + ')');
                    $('#button-image-user').css('visibility', 'hidden');
                    $('#clear-image').show();
                    $('#info-image-name').text(input.files[0].name).show();
                };

                reader.readAsDataURL(input.files[0]);
                
            }
        }
    }
</script>
        <form id="add-user" method="post" autocomplete="off">
            <div class="modal-window-content__row">
                <div class="modal-window-content__item">
                    <div class="modal-window-content__title">Конфигурация</div>
                    <div class="modal-window-content__value">
                        <span>Направление</span> <i class="fa fa-globe"></i> <select id="user-direction" name="direction" class="chosen-select">
                            <option value="">Все</option>
<?
$countries = $db->query("SELECT `countries`.`id`, `countries`.`name`, `countries`.`code` FROM `countries` INNER JOIN `countries_list` ON (`countries`.`id` = `countries_list`.`country_id`) WHERE `countries_list`.`client_id` = '" . $chief['id'] . "' ORDER BY `id`");
while ($country = $countries->fetch_assoc()) {
?>
                            <option data-img-src="/img/countries/<?=strtolower($country['code'])?>.png"<?=($chief['country'] == $country['id'] ? ' selected' : '')?> value="<?=$country['id']?>"><?=protection($country['name'] . ' (' . $country['code'] . ')', 'display')?></option>
<?
}
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>Ф.И.О</span> <i class="fa fa-address-book-o"> </i> <input id="user-fio" type="text" name="fio">
                    </div>

                    <div class="modal-window-content__value">
                        <span>Отдел</span> <i class="fa fa-building"></i> <select id="user-office" name="offices[]" class="chosen-select" multiple="true">
                            <?php echo ($db->query("SELECT `id` FROM `offices` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows == 0) ? '<option value="">- Не указано -</option>' : ''; ?>
<?
$offices = $db->query("SELECT `id_item`, `name` FROM `offices` WHERE `client_id` = '" . $chief['id'] . "'");
while ($office = $offices->fetch_assoc()) {
?>
                            <option value="<?$office['id_item']?>"><?php echo protection($office['name'], 'display'); ?></option>
<?
}
?>
                        </select>
                        </div>
                    <div class="modal-window-content__value">
                        <span>Группа</span> <i class="fa fa-users"></i> <select id="user-group" name="group" class="chosen-select">
                            <option value="">- Не указано -</option>
<?
$groups = $db->query("SELECT `id_item`, `type`, `name` FROM `groups_of_users` WHERE `client_id` = '" . $chief['id'] . "'");
while ($group = $groups->fetch_assoc()) {
?>
                            <option value="<?php echo $group['id_item']; ?>"<?=($group['type'] == 'administrator' ? ' disabled' : '')?>><?php echo protection($group['name'], 'display'); ?></option>
<?
}
?>
                        </select>
                    </div>

                    <div class="modal-window-content__title">Данные для входа</div>
                    <div class="modal-window-content__value">
                        <span>Логин</span> <i class="fa fa-user-circle"> </i> <input id="user-login" type="text" name="login">
                    </div>
                    <div class="modal-window-content__value" style="position: relative">
                        <span>Пароль</span> <i class="fa fa-user-secret"> </i> <input id="password-input" type="password" name="user_password">
                        <a href="javascript:void(0);" class="password-control" onclick="return show_hide_password(this);"><i class="fa fa-eye-slash"></i></a>
                        <a href="javascript:void(0);" class="password-random" onclick="return random_password(this);" title="Сгенерировать"><i class="fa fa-random"></i></a>
                    </div>

                    <div class="modal-window-content__title">Дополнительно</div>
                    <div class="modal-window-content__value">
                        <span>Телефон</span> <i class="fa fa-phone"> </i> <input id="user-phone" type="text" name="phone">
                    </div>
                    <div class="modal-window-content__value">
                        <span>E-mail</span> <i class="fa fa-envelope-o"> </i> <input id="user-email" type="text" name="email">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Сайт</span> <i class="fa fa-globe"> </i> <input id="user-site" type="text" name="site">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Комментарий</span> <i class="fa fa-comment"> </i> <textarea id="user-comment" name="comment"></textarea>
                    </div>
                </div>
            
                <div class="modal-window-content__item" style="min-width: 250px">
                    <div class="modal-window-content__title">Фотография</div>
                    <div class="modal-window-content__value" style="text-align: center">
                        <div id="user-image-block" style="background-image: url('/system/images/photo/no_photo.png');">

                            <span id="clear-image" onclick="clearImage();" title="Удалить" style="display: none;">×</span>
                            <div id="info-image-name" title="" style="display: none;"></div>
                        </div>
                        <div id="upload-file-container">
                            <input id="user-image" type="file" name="image" class="inputfile-link" onchange="readFile(this);">
                            <label style="height: 25px" for="user-image" id="button-image-user"><i class="fa fa-image"></i> <span>Добавить фотографию</span></label>
                        </div>
                    </div>

                    <div class="modal-window-content__title">Привилегии</div>
                    <div class="modal-window-content__value">
                        <ul id="scroll-access" class="scroll-bar" style="height: 100px">

                            <li class="scroll-bar__item off">
                                <i class="fa fa-pencil"></i> Редактирование заказов
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="rights[edit_order]" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                <i class="fa fa-envelope-square"></i> Отправка SMS
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="rights[send_sms]" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                <i class="fa fa-phone-square"></i> Скрывать телефон в заказах
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="rights[hide_phone]" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            
            </div>

            <div class="buttons">
                <button id="button-add-user" class="disabled form__button">Добавить</button>
            </div>
            <input type="submit" style="display: none">
        </form>