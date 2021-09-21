<?php
include_once '../core/begin.php';
// error_reporting(E_ALL);
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;

    $payment_name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;

    // Получаем нужные элементы массива "image"
    $fileTmpName = $_FILES['ico']['tmp_name'];
    $errorCode = $_FILES['ico']['error'];
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
            $limitBytes  = 1024 * 1024; // 1 mb
            $limitWidth  = 32;
            $limitHeight = 32;
            // Проверим нужные параметры
            
            if ($image[1] > $limitHeight) {
                $error = 'Высота изображения не должна превышать ' . $limitHeight . 'px!';
            }
            if ($image[0] > $limitWidth) {
                $error = 'Ширина изображения не должна превышать ' . $limitWidth . 'px!';
            }

            if (filesize($fileTmpName) > $limitBytes) {
                $error = 'Размер изображения не должен превышать 1 Mb!';
            }

            if (!pathinfo($_FILES['ico']['name'], PATHINFO_EXTENSION)) {
                $error = 'Неверный формат файла!';
            }

            if (!$error) {
                $path = $_SERVER['DOCUMENT_ROOT'] . '/system/images/payment/' . $chief['id'] . '/';
                if (!is_dir($path)) {
                    mkdir($path, 0777, true);
                }

                // Оставляем в имени файла только буквы, цифры и некоторые символы.
			    $pattern = "[^a-zа-яё0-9,~!@#%^-_\$\?\(\)\{\}\[\]\.]";
			    $name = mb_eregi_replace($pattern, '-', $_FILES['ico']['name']);
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
 
            }
        }
    }

    if (empty($payment_name)) {
        $error = 'Укажите название!';
    } elseif (mb_strlen($payment_name, 'UTF-8') < 3 or mb_strlen($payment_name, 'UTF-8') > 25) {
        $error = 'Название должно быть в пределах от 3 до 25 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `payment_methods` WHERE `name` = '" . $payment_name . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Способ оплаты с таким названием уже есть!';
    }
    
    if (!isset($error)) {
        // Перемещаем файл в директорию.
        if (move_uploaded_file($_FILES['ico']['tmp_name'], $path . $name)) {
            $success = 1;
            $count = $db->query("SELECT `payment_methods` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $id_item = $count['payment_methods'] + 1;

            if ($result = $db->query("SELECT COUNT(*) FROM `payment_methods` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
                $db->query("INSERT INTO `payment_methods` (`id`, `id_item`, `client_id`, `name`, `icon`) VALUES (null, '" . $id_item . "', '" . $chief['id'] . "', '" . $payment_name . "', '" . protection($name, 'base') . "')");
            } else {
                $error = 'Не удалось добавить способ оплаты!';
            }
        } else {
            $error = 'Не удалось загрузить иконку способа оплаты!';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
?>

<script>
    $(function(){
        var form = $('#add-payment-method'),
            btn = $('#button-add-payment-method');

        form.find('#payment-name').focus();
        
        $('#payment-ico').on('change', function(e){
            let error;
            
            let fileName = '';
            let maxFileSize = 1; // mb
            if (!validateSize(this, maxFileSize)){
                error = 'Размер файла превышает ' + maxFileSize + ' MB';
                if (!$('.modal-window-content div').is('.error')) {
                    $('.modal-window-content').prepend('<div class="error"></div>');
                    $('.error').text(error).show();
                }
            }
            //console.log($('#payment-ico')[0].files[0])
            
            if (this.files) {
                fileName = $(this).val().replace(/.*\\/, "");
                if (fileName == '') fileName = 'Выберите файл';
            }
            $('label').find('b').text(fileName);

            let uploadFile = $(this);
            if (uploadFile.val() == '') {
                error = 'Выберите иконку способа оплаты!';
            }
            
            // if ($(this).val() != '') $('#payment-file').append('<button>X</button>');

            if (error) {
                btn.addClass('disabled');
            } else {
                btn.removeClass('disabled');
            }
        });
        

        function checkFields() {
            let error;

            let uploadFile = form.find('#payment-ico');

            if (uploadFile.val() == '') {
                error = 'Выберите иконку способа оплаты!';
            } else {
                let maxFileSize = 1; // mb
                if (!validateSize(uploadFile[0], maxFileSize)){
                    error = 'Размер файла превышает ' + maxFileSize + ' MB';
                }
            }

            var name = form.find('#payment-name').val().trim();
            
            if (name == '') {
                error = 'Введите название способа оплаты!';
            } else if (name.length < 3) {
                error = 'Название должно содержать не меньше 3 символов!';
            } else if (name.length > 25) {
                error = 'Название должно быть в пределах 25 символов!';
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
                let data = new FormData($(this).get(0)),
                    count_modal = $('.modal-window-wrapper').length;
                $.ajax({
                    type: "POST",
                    url: "/system/ajax/addPaymentMethod.php?action=submit",
                    data: data,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        //console.log(response);
                        if (jsonData.success == 1) {
                            loadPaymentMethods();
                            closeModalWindow(count_modal);
                        } else if (jsonData.error) {
                            if (!$('.modal-window-content div').is('.error')) {
                                $('.modal-window-content').prepend('<div class="error"></div>');
                            }
                            $('.error').text(jsonData.error).show();
                        }
                    }
                });
            }
            return false;
        });
    });

</script>
    <div class="modal-window-title">Добавление нового способа оплаты <button class="modal-window-close" onclick="closeModalWindow();">×</button></div>
    <div class="modal-window-content">
        <form id="add-payment-method" method="post" autocomplete="off">
            <div class="modal-window-content__item">
            <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="payment-name" type="text" name="name" placeholder="Введите название">
                </div>
                <div class="modal-window-content__value">
                    <span>Иконка</span> <i class="fa fa-picture-o"></i> <div id="payment-file" class="modal-window-content__value-block" style="padding-left: 0">
                        <input id="payment-ico" type="file" name="ico" class="inputfile"><label for="payment-ico"><i class="fa fa-upload"></i>  <b>Выберите файл</b></label>
                    </div>
                </div>
                <div class="buttons">
                    <button id="button-add-payment-method" class="disabled" name="save-changes">Добавить</button>
                    <input type="submit" style="display: none">
                </div>
            </div>
        </form>
    </div>