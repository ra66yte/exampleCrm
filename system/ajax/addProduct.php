<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;

    $image_name = null;
    $path = $_SERVER['DOCUMENT_ROOT'] . '/system/images/product/' . $chief['id'] . '/';

    $product_name =      isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $model =             isset($_POST['model']) ? protection($_POST['model'], 'base') : null;
    $vendor =            isset($_POST['vendor_code']) ? protection($_POST['vendor_code'], 'base') : null;
    $color =             isset($_POST['color']) ? abs(intval($_POST['color'])) : 0;
    $manufacturer =      isset($_POST['manufacturer']) ? abs(intval($_POST['manufacturer'])) : 0;
    $category =          isset($_POST['category']) ? abs(intval($_POST['category'])) : 0;
    $direction =         isset($_POST['direction']) ? abs(intval($_POST['direction'])) : 0;
    $description =       isset($_POST['description']) ? protection($_POST['description'], 'base') : null;
    $currency =          isset($_POST['currency'])? abs(intval($_POST['currency'])) : 0;
    $purchase_price =    isset($_POST['purchase-price']) ? abs(floatval($_POST['purchase-price'])) : 0;
    $base_price =        isset($_POST['base-price']) ? abs(floatval($_POST['base-price'])) : 0;
    $discount_price =    isset($_POST['discount-price']) ? abs(floatval($_POST['discount-price'])) : 0;
    $office =            isset($_POST['office']) ? abs(intval($_POST['office'])) : 0;
    $site =              isset($_POST['site']) ? abs(intval($_POST['site'])) : 0;
    $sub_ids =           isset($_POST['sub-id']) ? $_POST['sub-id'] : 0;
    $cargo_description = isset($_POST['cargo_description']) ? protection($_POST['cargo_description'], 'base') : null;
    $depth =             isset($_POST['depth']) ? abs(intval($_POST['depth'])) : 0;
    $width =             isset($_POST['width']) ? abs(intval($_POST['width'])) : 0;
    $height =            isset($_POST['height']) ? abs(intval($_POST['height'])) : 0;
    $weight =            isset($_POST['weight']) ? abs(floatval($_POST['weight'])) : 0;

    if (empty($weight)) {
        $error = 'Укажите вес товара!';
    } elseif (!is_numeric($weight)) { 
        $error = 'Указан некорректный вес!';
    }

    if (!empty($height)) {
        if (!is_numeric($height)) {
            $error = 'Указана некорректная высота!';
        }
    }

    if (!empty($width)) {
        if (!is_numeric($width)) {
            $error = 'Указана некорректная ширина!';
        }
    }

    if (!empty($depth)) {
        if (!is_numeric($depth)) {
            $error = 'Указана некорректная глубина!';
        }
    }


    if (empty($cargo_description)) {
        $error = 'Укажите описание груза!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `np_cargo_description_list` WHERE `ref` = '" . $cargo_description . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Описание груза не найдено!';
    }

    if (is_array($sub_ids) and $sub_ids) {
        foreach ($sub_ids as $key => $value) {
            if (!is_numeric($value)) {
                $error = 'Некорректное значение Sub-ID товара!';
            } elseif ($result = $db->query("SELECT COUNT(*) FROM `attribute_categories` WHERE `id_item` = '" . abs(intval($value)) . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
                $error = 'Произошла ошибка при выборе Sub-ID товара!';
            }
        }
    }

    if (!empty($site)) {
        if (!is_numeric($site)) {
            $error = 'Указан некорректный сайт!';
        }
    }

    if (empty($office)) {
        $error = 'Укажите офис!';
    } elseif (!is_numeric($office) or $result = $db->query("SELECT COUNT(*) FROM `offices` WHERE `id_item` = '" . $office . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Указан некорректный офис!';
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
                $limitBytes  = 1024 * 1024; // 1 mb
                $limitWidth  = 2048;
                $limitHeight = 2048;
                // Проверим нужные параметры
                if (filesize($fileTmpName) > $limitBytes) {
                    $error = 'Размер изображения не должен превышать 1 Mb!';
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
				    $image_name = $parts['filename'] . $prefix . '.' . $parts['extension'];
 
				    
                }
            }
        }

    } else {
        $image_name = 'no_photo.png';
    }

    
    if (!empty($discount_price)) {
        if (!is_numeric($discount_price)) {
            $error = 'Указана некорректная акционная цена!';
        }
    }

    if (empty($base_price)) {
        $error = 'Укажите цену закупки!';
    } elseif (!is_numeric($base_price)) {
        $error = 'Указана некорректная цена закупки!';
    }

    if (empty($purchase_price)) {
        $error = 'Укажите цену продажи!';
    } elseif (!is_numeric($purchase_price)) {
        $error = 'Указана некорректная цена продажи!';
    }

    if (empty($currency)) {
        $error = 'Укажите валюту!';
    } elseif (!is_numeric($currency) or $result = $db->query("SELECT COUNT(*) FROM `currencies` WHERE `id_item` = '" . $currency . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Указана некорректная валюта!';
    }

    if (!empty($description)) {
        if (mb_strlen($description, 'UTF-8') > 200) {
            $error = 'Описание товара должно быть в пределе 200 символов!';
        }
    }

    if (!empty($direction)) {
        if (!is_numeric($direction) or $result = $db->query("SELECT COUNT(*) FROM `countries` WHERE `id` = '" . $direction . "'")->fetch_row() and $result[0] == 0) {
            $error = 'Указано некорректное направление!';
        }
    }

    if (empty($category)) {
        $error = 'Укажите категорию товара!';
    } elseif (!is_numeric($category) or $result = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `id_item` = '" . $category . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Указана некорректная категория!';
    }

    if (!empty($manufacturer)) {
        if (!is_numeric($manufacturer) or $result = $db->query("SELECT COUNT(*) FROM `manufacturers` WHERE `id_item` = '" . $manufacturer . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            $error = 'Указан некорректный производитель!';
        }
    }

    if (!empty($color)) {
        if (!is_numeric($color) or $result = $db->query("SELECT COUNT(*) FROM `colors` WHERE `id` = '" . $color . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Цвет не найден!';
        }
    }

    if (!empty($vendor)) {
        if (mb_strlen($vendor, 'UTF-8') < 1 or mb_strlen($vendor, 'UTF-8') > 25) {
            $error = 'Артикул товара должен быть в пределах от 1 до 25 символов!';
        }
    }

    if (!empty($model)) {
        if (mb_strlen($model, 'UTF-8') > 25) {
            $error = 'Модель товара должна быть в пределах от 1 до 25 символов!';
        }
    }

    if (empty($product_name)) {
        $error = 'Укажите название товара!';
    } elseif (mb_strlen($product_name, 'UTF-8') < 3 or mb_strlen($product_name, 'UTF-8') > 30) {
        $error = 'Название товара должно быть в пределах от 3 до 30 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `products` WHERE `name` = '" . $product_name . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] <> 0) {
        $error = 'Товар с таким названием уже есть!';
    }

    if (!isset($error)) {
        $count = $db->query("SELECT `products` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['products'] + 1;

        if ($result = $db->query("SELECT COUNT(*) FROM `products` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            if ($db->query("INSERT INTO `products` (`id`, `id_item`, `client_id`, `image`, `name`, `model`, `vendor_code`, `color`, `manufacturer`, `category`, `direction`, `description`, `date_added`, `purchase_price`, `base_price`, `discount_price`, `currency`, `cargo_description`, `depth`, `width`, `height`, `weight`, `office`, `site`) VALUES (null, '" . $id_item . "', '" . $chief['id'] . "', '" . protection($image_name, 'base') . "', '" . $product_name . "', '" . $model . "', '" . $vendor . "', '" . $color . "', '" . $manufacturer . "', '" . $category . "', '" . $direction . "', '" . $description . "', '" . time() . "', '" . $purchase_price . "', '" . $base_price . "', '" . $discount_price . "', '" . $currency . "', '" . $cargo_description . "', '" . $depth . "', '" . $width . "', '" . $height . "', '" . $weight . "', '" . $office . "', '" . $site . "')")) {
                $success = 1;
    
                // Перемещаем файл в директорию.
                if ($image_name != 'no_photo.png' and move_uploaded_file($_FILES['image']['tmp_name'], $path . $image_name)) {
                    // $success = 1;
                } else {
                    $error = 'Не удалось загрузить файл!';
                }
                $sql = 'null';
                if (is_array($sub_ids) and count($sub_ids) > 0) {
                    $sql = "INSERT INTO `products_sub-id` (`id`, `client_id`, `product_id`, `attribute_category_id`) VALUES";
                    foreach ($sub_ids as $key => $value) {
                        $sql .= " (null, '" . $chief['id'] . "', '" . $id_item . "', '" . abs(intval($value)) . "'),";
                    }
                    $sql = rtrim($sql, ',');
                    $db->query($sql);
                }

                $db->query("UPDATE `id_counters` SET `products` = (`products` + 1) WHERE `client_id` = '" . $chief['id'] . "'");

            } else {
                $error = 'Не удалось добавить товар!';
            }
        }
        
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

function build_tree_select($categories, $parent_id, $level) {
    global $db, $chief;
    if (is_array($categories) and isset($categories[$parent_id])) { //Если категория с таким parent_id существует
        foreach ($categories[$parent_id] as $category) { // Обходим
            $count_subs = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `parent_id` = '" . $category['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
            /**
             * Выводим категорию 
             *  $level * 20 - отступ, $level - хранит текущий уровень вложености (0, 1, 2..)
             */

?>
            <option value="<?=$category['id_item']?>" style="text-align: left; padding-left: <?=($level == 0 ? '5' : $level * 20)?>px">
                <?php echo protection($category['name'], 'display'); if ($count_subs[0] <> 0) echo ' (' . $count_subs[0] . ') ▼'; ?>
            </option>
<? 

            $level = $level + 1; // Увеличиваем уровень вложености
            // Рекурсивно вызываем эту же функцию, но с новым $parent_id и $level
            build_tree_select($categories, $category['id_item'], $level);
            $level = $level - 1; // Уменьшаем уровень вложености
        }
    }
}
?>
<style>
    #product-image-block {
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
    .price-item {
        display: inline-block;
        width: 85px;
        text-align: center;
        margin-left: 5px;
    }
    .price-item input {
        text-align: center;
    }
</style>
<script>
    $(function(){
        let form = $('#form-add-product'),
            btn = $('#button-add-product');

        form.find('#product-name').focus();

        function checkFields() {
            let error;

            let weight = form.find('#product-weight').val();
            if (weight == '') {
                error = 'Укажите вес товара!';
            } else if (isNaN(weight)) {
                error = 'Вес товара должен быть числом!';
            } else if (weight <= 0) {
                error = 'Укажите вес правильно!';
            }

            let height = form.find('#product-height').val();
            if (height != '') {
                if (isNaN(height)) {
                    error = 'Высота должна быть числом!';
                } else if (height <= 0) {
                    error = 'Укажите высоту правильно!';
                }
            }

            let width = form.find('#product-width').val();
            if (width != '') {
                if (isNaN(width)) {
                    error = 'Ширина должна быть числом!';
                } else if (width <= 0) {
                    error = 'Укажите ширину правильно!';
                }
            }

            let depth = form.find('#product-depth').val();
            if (depth != '') {
                if (isNaN(depth)) {
                    error = 'Длина должна быть числом!';
                } else if (depth <= 0) {
                    error = 'Укажите длину правильно!';
                }
            }

            let cargo = form.find('#product-cargo-description');
            if (cargo.val() == '') {
                error = 'Укажите описание груза!';
            }

            let site = form.find('#product-site');
            if (site.val() != '') {
                if (isNaN(site.val())) {
                    error = 'Сайт указан неправильно!';
                }
            }
            

            let office = form.find('#product-office');
            
            if (office.val() == '') {
                error = 'Укажите офис!';
            } else if (isNaN(office.val())) {
                error = 'Офис указан неправильно!';
            }
            

            let uploadFile = form.find('#product-image');
            if (uploadFile.val() != '') {
                let maxFileSize = 1; // mb
                if (!validateSize(uploadFile[0], maxFileSize)){
                    error = 'Размер файла превышает ' + maxFileSize + ' MB';
                }
            }

            let discountPrice = form.find('#product-discount-price');
            if (discountPrice.val() != '') {
                if (isNaN(discountPrice.val())) {
                    error = 'Акционная цена должна быть числом!';
                }
            }

            let basePrice = form.find('#product-base-price');
            if (basePrice.val() == '') {
                error = 'Укажите цену закупки!';
            } else if (isNaN(basePrice.val())) {
                error = 'Цена закупки должна быть числом!';
            }

            let purchasePrice = form.find('#product-purchase-price');
            if (purchasePrice.val() == '') {
                error = 'Укажите цену продажи!';
            } else if (isNaN(purchasePrice.val())) {
                error = 'Цена продажи должна быть числом!';
            }

            let currency = form.find('#product-currency').val();
            if (currency == '') {
                error = 'Укажите валюту!';
            } else if (isNaN(currency)) {
                error = 'Указана некорректная валюта!';
            }

            let description = form.find('#product-description').val().trim();
            if (description != '') {
                if (description.length > 200) {
                    error = 'Описание товара должно быть в пределах 200 символов!';
                }
            }

            let direction = form.find('#product-direction').val();
            if (direction != '') {
                if (isNaN(direction)) {
                    error = 'Направление указано неправильно!';
                }
            }

            let category = form.find('#product-category').val();
            if (category == '') {
                error = 'Укажите категорию товара!';
            } else if (isNaN(category)) {
                error = 'Указана некорректная категория!';
            }

            let manufacturer = form.find('#product-manufacturer').val();
            if (manufacturer != '') {
                if (isNaN(manufacturer)) {
                    error = 'Указан некорректный производитель!';
                }
            }

            let color = form.find('#product-color').val();
            if (color != '') {
                if (isNaN(color)) {
                    error = 'Указан некорректный цвет!';
                }
            }

            let vendor = form.find('#product-vendor_code').val().trim();
            if (vendor != '') {
                if (vendor.length > 25) {
                    error = 'Артикул товара должен быть в пределах 25 символов!';
                }
            }

            let model = form.find('#product-model').val().trim();
            if (model != '') {
                if (model.length > 30) {
                    error = 'Модель товара должна быть в пределах 30 символов!';
                }
            }

            let name = form.find('#product-name').val().trim();
            if (name == '') {
                error = 'Укажите название товара!';
            } else if (name.length < 3) {
                error = 'Название товара должно содержать не меньше 3 символов!';
            } else if (name.length > 25) {
                error = 'Название товара должно содержать не больше 25 символов!';
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

        $('#product-image').on('change', function() {
            let error;
            let uploadFile = $(this);
            if (uploadFile.val() == '') {
                error = 'Выберите изображение товара!';
            }
            if (error) {
                btn.addClass('disabled');
            }
        });
        

        form.on('submit', function(e){
            let error = checkFields();
            
            if (error) {
                if (!$('.modal-window-content div').is('.error')) {
                    $('.modal-window-content').prepend('<div class="error"></div>');
                    $('.error').text(error).show();
                }
            } else {
                let data = new FormData($(this).get(0));
                let count_modal = $('.modal-window-wrapper').length;
                
                $.ajax({
                    type: "POST",
                    url: "/system/ajax/addProduct.php?action=submit",
                    data: data,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        var jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            loadProducts();
                            closeModalWindow(count_modal);
                        } else if (jsonData.error) {
                            if (!$('.modal-window-content div').is('.error')) {
                                $('.modal-window-content').prepend('<div class="error"></div>');
                                $('.error').text(jsonData.error).show();
                            } else {
                                $('.error').text(jsonData.error).show();
                            }
                        }
                        
                    }
                });
                
            }

            return false;
        });

    });

    function clearImage() {
        $('#clear-image').hide();
        $('#product-image').val('');
        $('#product-image-block').css('background-image', 'url(/system/images/product/no_photo.png)');
        $('#info-image-name').hide();
        $('#button-image-product').show();
    }

    function readFile(input) {
        if (input.files && input.files[0]) {
            
            var size0 = input.files[0].size;
            var maxSize = 1024 * 1024;
            if (size0 > maxSize) {
                var error = 'Максимальный размер загружаемого изображения 1 Mb';
            }

            var type = input.files[0].type;
            if (type === 'image/png' || type === 'image/jpg' || type === 'image/jpeg') {
                
            } else {
                var error = 'Разрешены только изображения в формате png, jpg и jpeg!';
            }

            if (error) {
                if (!$('.modal-window-content div').is('.error')) {
                    $('.modal-window-content').prepend('<div class="error"></div>');
                    $('.error').text(error).show();
                }
                $('#button-add-product').addClass('disabled');
            } else {

                var reader = new FileReader(input.files[0]);

                reader.onload = function(e) {
                    $('#product-image-block').css('background-image', 'url(' + e.target.result + ')');
                    $('#button-image-product').hide();
                    $('#clear-image').show();
                    $('#info-image-name').text(input.files[0].name).show();
                };

                reader.readAsDataURL(input.files[0]);
                
            }

        }
    }
</script>
        <form id="form-add-product" method="post" autocomplete="off">
        <div class="modal-window-content__row">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="product-name" type="text" name="name">
                </div>
                <div class="modal-window-content__value">
                    <span>Модель</span> <i class="fa fa-registered"></i> <input id="product-model" type="text" name="model">
                </div>
                <div class="modal-window-content__value">
                    <span>Артикул</span> <i class="fa fa-sticky-note-o"></i> <input id="product-vendor_code" type="text" name="vendor_code">
                </div>
                <div class="modal-window-content__value">
                    <span>Цвет</span> <i class="fa fa-eyedropper"></i> <select id="product-color" name="color" class="chosen-select">
                        <option value="">- Не указано -</option>
<?php
$colors = $db->query("SELECT * FROM `colors` WHERE `client_id` = '" . $chief['id'] . "'");
while ($color = $colors->fetch_assoc()) {
?>
                        <option value="<?=$color['id_item']?>"><?=protection($color['name'], 'display')?></option>
<?
}
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Производитель</span> <i class="fa fa-trademark"> </i> <select id="product-manufacturer" name="manufacturer" class="chosen-select">
                        <option value="">- Не указано -</option>
<?php
$manufacturers = $db->query("SELECT `id_item`, `name` FROM `manufacturers` WHERE `client_id` = '" . $chief['id'] . "'");
while ($manufacturer = $manufacturers->fetch_assoc()) {
?>
                        <option value="<?=$manufacturer['id_item']?>"><?=protection($manufacturer['name'], 'display')?></option>
<?
}
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Категория</span> <i class="fa fa-sitemap"></i> <select id="product-category" name="category" class="chosen-select">
                        <option value="">- Не указано -</option>
<?php
$query = $db->query("SELECT `id_item`, `name`, `parent_id` FROM `product_categories` WHERE `status` = 'on' AND `client_id` = '" . $chief['id'] . "' ORDER BY `id` ASC");
$categories = array();
while ($category = $query->fetch_assoc()) {
    $categories[$category['parent_id']][] = $category;
}
echo build_tree_select($categories, 0, 0);
?>
                    </select>
                </div>
                <div class="modal-window-content__value drop-up">
                    <span>Направление</span> <i class="fa fa-globe"></i> <select id="product-direction" name="direction" class="chosen-select">
                        <option value="">Все</option>
<?
$countries = $db->query("SELECT `countries`.`id`, `countries`.`name`, `countries`.`code` FROM `countries` INNER JOIN `countries_list` ON (`countries`.`id` = `countries_list`.`country_id`) WHERE `countries_list`.`client_id` = '" . $chief['id'] . "' ORDER BY `id`");
while ($country = $countries->fetch_assoc()) {
?>
                            <option data-img-src="/img/countries/<?=strtolower($country['code'])?>.png" value="<?=$country['id']?>"><?=protection($country['name'] . ' (' . $country['code'] . ')', 'display')?></option>
<?
}
?>
                    </select>
                </div>

                
                <div class="modal-window-content__value">
                    <span>Описание</span> <i class="fa fa-flag-checkered"></i> <textarea name="description" id="product-description" style="height: 250px"></textarea>
                </div>
                
            </div>


            <div class="modal-window-content__item">
            <div class="modal-window-content__title">Ценовая политика</div>
                <div class="modal-window-content__value">
                    <span>Валюта</span> <i class="fa fa-money"></i> <select id="product-currency" name="currency" class="chosen-select">
                        <option value="">- Не указано</option>
<?
$currencies = $db->query("SELECT `id_item`, `name`, `symbol` FROM `currencies` WHERE `client_id` = '" . $chief['id'] . "'");
while ($currency = $currencies->fetch_assoc()) {
?>
                        <option value="<?=$currency['id_item']?>"<?=($currency['id_item'] == 1 ? ' selected' : ''); ?>><?=protection($currency['name'] . ' (' . $currency['symbol']. ')', 'display')?></option>
<?
}
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <div class="price-item">
                        <span>Цена продажи</span> <i class="fa fa-shopping-bag"></i> <input id="product-purchase-price" class="small" type="text" name="purchase-price" placeholder="0.00" style="min-width: 40px; width: 60px">
                    </div>
                    <div class="price-item">
                        <span>Цена закупки</span> <i class="fa fa-shopping-basket"></i> <input id="product-base-price" type="text" class="small" name="base-price" placeholder="0.00" style="min-width: 40px; width: 60px">
                    </div>
                    <div class="price-item">
                        <span style="color: #c60">Акционная цена</span> <i class="fa fa-percent"></i> <input id="product-discount-price" class="small" type="text" name="discount-price" placeholder="0.00" style="min-width: 40px; width: 60px">
                    </div>
                </div>
            
                <div class="modal-window-content__title">Изображение</div>
                <div class="modal-window-content__value" style="text-align: center">
                <div id="product-image-block" style="background-image: url('/system/images/product/no_photo.png');">

                    <span id="clear-image" onclick="clearImage();" title="Удалить" style="display: none;">×</span>
                    <div id="info-image-name" title="" style="display: none;"></div>
                </div>
                    <div id="upload-file-container">
                        <input id="product-image" type="file" name="image" class="inputfile-link" onchange="readFile(this);">
                        <label for="product-image" id="button-image-product"><i class="fa fa-image"></i> <span>Добавить изображение</span></label>
                    </div>
                </div>
                

                
            </div>

            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Дополнительно</div>
                <div class="modal-window-content__value">
                    <span>Офис</span> <i class="fa fa-building"></i> <select id="product-office" name="office" class="chosen-select">
                        <option value="">- Не указано -</option>
<?php
$offices = $db->query("SELECT `id_item`, `name` FROM `offices` WHERE `client_id` = '" . $chief['id'] . "'");
while ($office = $offices->fetch_assoc()) {
?>
                        <option value="<?=$office['id_item']?>"><?=protection($office['name'], 'display')?></option>
<?
}
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Сайт</span> <i class="fa fa-flag-checkered"></i> <select id="product-site" name="site" class="chosen-select">
                        <option value="">- Не указано -</option>
<?php
$sites = $db->query("SELECT `id_item`, `name`, `url` FROM `sites` WHERE `client_id` = '" . $chief['id'] . "'");
while ($site = $sites->fetch_assoc()) {
?>
                        <option value="<?=$site['id_item']?>"><?=protection($site['url'], 'display')?></option>
<?
}
?>
                    </select>
                </div>

                <div class="modal-window-content__title">Sub-ID</div>
                <div class="modal-window-content__value">
                    <span>Sub-ID</span> <i class="fa fa-flask"></i> <select id="product-sub-id" name="sub-id[]" class="chosen-select" multiple="true">
                    <?=(($result = $db->query("SELECT COUNT(*) FROM `attribute_categories` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) ? '<option value="">- Не указано -</option>' : '')?>
<?php
$attribute_categories = $db->query("SELECT `id_item`, `name`, `status` FROM `attribute_categories` WHERE `client_id` = '" . $chief['id'] . "'");
while ($attribute_category = $attribute_categories->fetch_assoc()) {
?>
                        <option value="<?=$attribute_category['id_item'];?>"<?=($attribute_category['status'] == 'off' ? ' disabled' : '')?>><?=protection($attribute_category['name'], 'display')?></option>
<?
}
?>
                    </select>
                </div>

                <div class="modal-window-content__title">Новая Почта</div>
                <div class="modal-window-content__value drop-up"
                    <span>Опис. груза</span> <img src="/system/images/delivery/nova_poshta.svg" alt="*"> <select id="product-cargo-description" name="cargo_description" class="chosen-select">
                        <option value="">- Не указано -</option>
<?php
$cargo_descriptions = $db->query("SELECT `desc_ua`, `ref` FROM `np_cargo_description_list`");
while ($cargo_description = $cargo_descriptions->fetch_assoc()) {
?>
                        <option value="<?=protection($cargo_description['ref'], 'display')?>"><?=protection($cargo_description['desc_ua'], 'display')?></option>
<?
}
?>
                    </select>
                </div>

                <div class="modal-window-content__value">

                    <div class="price-item">
                        <span>Длина</span> <input id="product-depth" type="text" class="small" name="depth" placeholder="0" style="min-width: 40px; width: 60px"> см.
                    </div>
                    <div class="price-item">
                        <span>Ширина</span> <input id="product-width" type="text" class="small" name="width" placeholder="0" style="min-width: 40px; width: 60px"> см.
                    </div>
                    <div class="price-item">
                        <span>Высота</span> <input id="product-height" type="text" class="small" name="height" placeholder="0" style="min-width: 40px; width: 60px"> см.
                    </div>
                    
                </div>
                <div class="modal-window-content__value">
                    <span>Вес</span> <i class="fa fa-balance-scale"></i> <div class="modal-window-content__value-block"><input id="product-weight" class="small" type="text" name="weight" placeholder="0.00" style="min-width: 40px; width: 60px; text-align: center"> кг.</div> 
                </div>

            </div>
        </div>

            <div class="buttons">
                <button id="button-add-product" name="save-changes" class="disabled">Добавить</button>
            </div>
            <input type="submit" style="display: none">
        </form>