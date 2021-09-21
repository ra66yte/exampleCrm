<?php
include_once '../core/begin.php';
function build_tree_select($categories, $parent_id, $level, $selected) {
    global $db, $chief;
    if (is_array($categories) and isset($categories[$parent_id])) { //Если категория с таким parent_id существует
        foreach ($categories[$parent_id] as $category) { // Обходим
            $count_subs = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `parent_id` = '" . $category['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
            /**
             * Выводим категорию 
             *  $level * 20 - отступ, $level - хранит текущий уровень вложености (0, 1, 2..)
             */

?>
            <option value="<?=$category['id_item']?>"<?=($category['id_item'] == $selected ? ' selected' : '')?> style="text-align: left; padding-left: <?=($level == 0 ? '5' : $level * 20)?>px">
                <?=protection($category['name'], 'display'); if ($count_subs[0] <> 0) echo ' (' . $count_subs[0] . ') ▼'?>
            </option>
<? 

            $level = $level + 1; // Увеличиваем уровень вложености
            // Рекурсивно вызываем эту же функцию, но с новым $parent_id и $level
            build_tree_select($categories, $category['id_item'], $level, $selected);
            $level = $level - 1; // Уменьшаем уровень вложености
        }
    }
}

if (isset($_GET['product_id']) and is_numeric($_GET['product_id'])) {
    $product_id = abs(intval($_GET['product_id']));
/*
    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($db->query("SELECT `id` FROM `products` WHERE `client_id` = '" . $chief['id'] . "' AND `id` = '" . $product_id . "'")->num_rows > 0) {
            $success = 1;
            $product = $db->query("SELECT `name`, `model` FROM `products` WHERE `client_id` = '" . $chief['id'] . "' AND `id` = '" . $product_id . "'")->fetch_assoc();
            $title = ['product_name' => protection($product['name'], 'display'), 'product_model' => protection($product['model'], 'display')];
        } else {
            $error = 'Неизвестный товар!';
            $title = ['product_name' => 'UNDEFINED', 'product_model' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }
*/

    if ($result = $db->query("SELECT COUNT(*) FROM `products` WHERE `id_item` = '" . $product_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $product = $db->query("SELECT * FROM `products` WHERE `id_item` = '" . $product_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
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
        let form = $('#form-view-product'),
            btn = $('#button-view-product');

        form.on('submit', function(e){
            let count_modal = $('.modal-window-wrapper').length;
            closeModalWindow(count_modal);
            return false;
        });

    });
</script>
        <form id="form-view-product" method="post">
        <div class="modal-window-content__row">
            <div class="modal-window-content__item" style="position: relative">
                <div class="disable"></div>
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="product-name" type="text" name="name" value="<?=protection($product['name'], 'display')?>">
                </div>
                <div class="modal-window-content__value">
                    <span>Модель</span> <i class="fa fa-registered"></i> <input id="product-model" type="text" name="model" value="<?=protection($product['model'], 'display')?>">
                </div>
                <div class="modal-window-content__value">
                    <span>Артикул</span> <i class="fa fa-sticky-note-o"></i> <input id="product-vendor_code" type="text" name="vendor_code" value="<?=protection($product['vendor_code'], 'display')?>">
                </div>
                <div class="modal-window-content__value">
                    <span>Цвет</span> <i class="fa fa-eyedropper"></i> <select id="product-color" name="color" class="chosen-select">
                        <option value="">- Не указано -</option>
<?php
$colors = $db->query("SELECT `id_item`, `name` FROM `colors` WHERE `client_id` = '" . $chief['id'] . "'");
while ($color = $colors->fetch_assoc()) {
?>
                        <option value="<?=$color['id_item']?>"<?=($color['id_item'] == $product['color'] ? ' selected' : '')?>><?=protection($color['name'], 'display')?></option>
<?
}
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Производитель</span> <i class="fa fa-trademark"></i> <select id="product-manufacturer" name="manufacturer" class="chosen-select">
                        <option value="">- Не указано -</option>
<?php
$manufacturers = $db->query("SELECT `id_item`, `name` FROM `manufacturers` WHERE `client_id` = '" . $chief['id'] . "'");
while ($manufacturer = $manufacturers->fetch_assoc()) {
?>
                        <option value="<?=$manufacturer['id_item']?>"<?=($manufacturer['id_item'] == $product['manufacturer'] ? ' selected' : '')?>><?=protection($manufacturer['name'], 'display')?></option>
<?
}
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Категория</span> <i class="fa fa-sitemap"></i> <select id="product-category" name="category" class="chosen-select">
                        <option value="">- Не указано -</option>
<?php
$query = $db->query("SELECT * FROM `product_categories` WHERE `status` = 'on' AND `client_id` = '" . $chief['id'] . "' ORDER BY `id` ASC");
$categories = array();
while ($category = $query->fetch_assoc()) {
    $categories[$category['parent_id']][] = $category;
}
echo build_tree_select($categories, 0, 0, $product['category']);
?>
                    </select>
                </div>

                
                <div class="modal-window-content__value drop-up">
                    <span>Направление</span> <i class="fa fa-globe"></i> <select id="product-direction" name="direction" class="chosen-select">
                        <option value="">Все</option>
<?
$countries = $db->query("SELECT `id`, `name`, `code` FROM `countries`");
while ($country = $countries->fetch_assoc()) {
?>
                            <option data-img-src="/img/countries/<?=strtolower($country['code'])?>.png" value="<?=$country['id']?>"<?=($product['direction'] == $country['id'] ? ' selected' : '')?>><?=protection($country['name'] . ' (' . $country['code'] . ')', 'display')?></option>
<?
}
?>
                    </select>
                </div>

                
                <div class="modal-window-content__value">
                    <span>Описание</span> <i class="fa fa-flag-checkered"></i> <textarea name="description" id="product-description" style="height: 210px"><?=protection($product['description'], 'display')?></textarea>
                </div>
                
            </div>


            <div class="modal-window-content__item" style="position: relative">
                <div class="disable"></div>
            <div class="modal-window-content__title">Ценовая политика</div>
                <div class="modal-window-content__value">
                    <span>Валюта</span> <i class="fa fa-money"></i> <select id="product-currency" name="currency" class="chosen-select">
                        <option value="">- Не указано</option>
<?
$currencies = $db->query("SELECT * FROM `currencies` WHERE `client_id` = '" . $chief['id'] . "'");
while ($currency = $currencies->fetch_assoc()) {
?>
                        <option value="<?=$currency['id']?>" <?=($currency['id'] == $product['currency'] ? 'selected' : '')?>><?=protection($currency['name'] . ' (' . $currency['symbol']. ')', 'display')?></option>
<?
}
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <div class="price-item">
                        <span>Цена продажи</span> <i class="fa fa-shopping-bag"></i> <input id="product-purchase-price" class="small" type="text" name="purchase-price" value="<?=$product['purchase_price']?>" placeholder="0.00" style="min-width: 40px; width: 60px">
                    </div>
                    <div class="price-item">
                        <span>Цена закупки</span> <i class="fa fa-shopping-basket"></i> <input id="product-base-price" class="small" type="text" name="base-price" value="<?=$product['base_price']?>" placeholder="0.00" style="min-width: 40px; width: 60px">
                    </div>
                    <div class="price-item">
                        <span style="color: #c60">Акционная цена</span> <i class="fa fa-percent"></i> <input id="product-discount-price" class="small" type="text" name="discount-price" value="<?=$product['discount_price']?>" placeholder="0.00" style="min-width: 40px; width: 60px">
                    </div>
                </div>
            
                <div class="modal-window-content__title">Изображение</div>
                <div class="modal-window-content__value" style="text-align: center">
                    <div id="product-image-block" style="background-image: url('/system/images/product/<?=protection($product['image'], 'display')?>');">

                        <span id="clear-image" onclick="clearImage();" title="Удалить" style="display: none;">×</span>
                        <div id="info-image-name" title="" style="display: none;"></div>
                    </div>
                </div>
                

                
            </div>

            <div class="modal-window-content__item" style="position: relative">
                <div class="disable"></div>
                <div class="modal-window-content__title">Дополнительно</div>
                <div class="modal-window-content__value">
                    <span>Офис</span> <i class="fa fa-building"></i> <select id="product-office" name="office" class="chosen-select">
                        <option value="">- Не указано -</option>
<?php
$offices = $db->query("SELECT `id`, `name` FROM `offices` WHERE `client_id` = '" . $chief['id'] . "'");
while ($office = $offices->fetch_assoc()) {
?>
                        <option value="<?=$office['id']?>" <?=($office['id'] == $product['office'] ? 'selected' : '')?>><?=protection($office['name'], 'display')?></option>
<?
}
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Сайт</span> <i class="fa fa-flag-checkered"></i> <select id="product-site" name="site" class="chosen-select">
                        <option value="0">- Не указано -</option>
<?php
$sites = $db->query("SELECT `id`, `name`, `url` FROM `sites` WHERE `client_id` = '" . $chief['id'] . "'");
while ($site = $sites->fetch_assoc()) {
?>
                        <option value="<?=$site['id']?>" <?=($site['id'] == $product['site'] ? 'selected' : '')?>><?=protection($site['url'], 'display')?></option>
<?
}
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Добавлен</span> <i class="fa fa-calendar-plus-o"></i> <input type="text" name="date_added" value="<?=passed_time($product['date_added'])?>">
                </div>

                <div class="modal-window-content__title">Sub-ID</div>
                <div class="modal-window-content__value">
<?
$product_sub_ids = $db->query("SELECT `attribute_category_id` FROM `products_sub-id` WHERE `product_id` = '" . $product['id'] . "' AND `client_id` = '" . $chief['id'] . "'");
if ($product_sub_ids->num_rows == 0) {
?>
                    <center>Нет Sub-ID для этого товара</center>
<?
} else {
?>
                    <span>Sub-ID</span> <i class="fa fa-flask"></i> <select id="product-sub-id" name="sub-id[]" class="chosen-select" multiple="true">
                    <?php echo ($db->query("SELECT `id` FROM `attribute_categories` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows == 0) ? '<option value="">- Не указано -</option>' : ''; ?>
<?
$product_sub_id = array();
while ($row_sub_id = $product_sub_ids->fetch_assoc()) {
 $product_sub_id[] = $row_sub_id['attribute_category_id'];
}
$attribute_categories = $db->query("SELECT `id`, `name`, `status` FROM `attribute_categories` WHERE `client_id` = '" . $chief['id'] . "'");
while ($attribute_category = $attribute_categories->fetch_assoc()) {
?>
                        <option value="<?=$attribute_category['id']?>" <?=($attribute_category['status'] == 'off' ? 'disabled' : '')?> <?=(in_array($attribute_category['id'], $product_sub_id) ? 'selected' : '')?>><?=protection($attribute_category['name'], 'display')?></option>
<?
}
?>
                    </select>
<?
}
?>
                </div>

                <div class="modal-window-content__title">Количество на складе</div>
                
<?
if ($product_sub_ids->num_rows > 0 and $product['count_with_attributes'] > 0) {
?>
                <div class="modal-window-content__value">
                    <div class="modal-window-content__attributes">
<?
    $count_keys = $db->query("SELECT `id` FROM `products_sub-id-keys` WHERE `product_id` = '" . $product['id'] . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows; //  AND `count` != 0
    if ($count_keys > 0) {
        $keys = $db->query("SELECT * FROM `products_sub-id-keys` WHERE `product_id` = '" . $product['id'] . "' AND `client_id` = '" . $chief['id'] . "'");
        while ($key = $keys->fetch_assoc()) {
            $attrs = $db->query("SELECT `sub_id` FROM `products_sub-id-values` WHERE `key_id` = '" . $key['key_id'] . "' AND `product_id` = '" . $product['id'] . "' AND `client_id` = '" . $chief['id'] . "'");
            $attrs_string = '';
            while ($attr = $attrs->fetch_assoc()) {
                $attribute = $db->query("SELECT `name` FROM `attributes` WHERE `id` = '" . $attr['sub_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                $attrs_string .= protection($attribute['name'], 'display') . ', ';
            }
            $attrs_string = mb_ucfirst(mb_strtolower(rtrim($attrs_string, ', '), 'UTF-8'), 'UTF-8');
            // ToDo: надо ли..
            if ($key['count'] > 0) echo '<span title="' . $attrs_string . '">' . (mb_strlen($attrs_string, 'UTF-8') > 25 ? mb_substr($attrs_string, 0, 25, 'UTF-8') . '...' : $attrs_string) . ': <b>' . $key['count'] . '</b> шт.</span>';
        }
    }
?>                  </div>
                </div>
<?
}
?>
                
                <div class="modal-window-content__value">
                    <span><?=(($product_sub_ids->num_rows == 0) ? 'Количество' : 'Общее кол-во')?></span> <i class="fa fa-archive"></i> <div class="modal-window-content__value-block"><b><?=intval($product['count'])?></b> шт.<?=($product['count_with_attributes'] > 0 ? ($product['count_with_attributes'] == $product['count'] ? ' <span style="color: green">[Распределено]</span>' : ' <span style="color: red">[Не распределено: <b>' . ($product['count'] - $product['count_with_attributes']) . '</b> шт.]</span>') : '')?></div>
                </div>

                <div class="modal-window-content__title">Новая Почта</div>
                <div class="modal-window-content__value drop-up">
                    <span>Опис. груза</span> <img src="/system/images/delivery/ico-new-post.ico" alt="*"> <select id="product-cargo-description" name="cargo-description" class="chosen-select">
                        <option value="">- Не указано -</option>
                        <option value="1" selected>OLX</option>
                    </select>
                </div>

                <div class="modal-window-content__value">

                    <div class="price-item">
                        <span>Длина</span> <input id="product-depth" class="small" type="text" name="depth" value="<?=protection($product['depth'], 'int')?>" style="min-width: 40px; width: 60px"> см.
                    </div>
                    <div class="price-item">
                        <span>Ширина</span> <input id="product-width" class="small" type="text" name="width" value="<?=protection($product['width'], 'int')?>" style="min-width: 40px; width: 60px"> см.
                    </div>
                    <div class="price-item">
                        <span>Высота</span> <input id="product-height" class="small" type="text" name="height" value="<?=protection($product['height'], 'int')?>" style="min-width: 40px; width: 60px"> см.
                    </div>
                    
                </div>
                <div class="modal-window-content__value">
                    <span>Вес</span> <i class="fa fa-balance-scale"></i> <div class="modal-window-content__value-block"><input id="product-weight" class="small" type="text" name="weight" value="<?=abs(floatval($product['weight']))?>" style="min-width: 40px; width: 60px; text-align: center"> кг.</div> 
                </div>
                
            </div>
        </div>

            <div class="buttons">
                <button id="button-view-product" name="close">Закрыть</button>
            </div>
            <input type="submit" style="display: none">
        </form>
<?
    } else {
?>
    Информация по заданному товару отсутствует.
<?
    }
}
