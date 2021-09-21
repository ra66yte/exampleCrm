<?php
include_once '../core/begin.php';

if (isset($_GET['ga_id']) and is_numeric($_GET['ga_id'])) {
    $ga_id = abs(intval($_GET['ga_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `arrival_of_goods` WHERE `id_item` = '" . $ga_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $ga = $db->query("SELECT `incoming_order`, `date_added` FROM `arrival_of_goods` WHERE `id_item` = '" . $ga_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['ga_number' => protection($ga['incoming_order'], 'display'), 'date' => ' от ' . view_time($ga['date_added'])];
        } else {
            $error = 'Неизвестный приход товаров!';
            $title = ['ga_number' => 'UNDEFINED', 'date' => ''];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `arrival_of_goods` WHERE `id_item` = '" . $ga_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $ga = $db->query("SELECT * FROM `arrival_of_goods` WHERE `id_item` = '" . $ga_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>
<script>
    $(function(){
        let form = $('#view-ga'),
            btn = form.find('#button-close-ga');

            let lastItem = $('#ga-products').find('tbody tr').last();

            if (lastItem.attr('data-role') == 'child') {
                let prevParentItems = lastItem.prevAll('tr[data-role="parent"]'),
                i = 1;
                $.each(prevParentItems, function() {
                    if (i == 1) {
                        $(this).children('td:nth-child(1), td:nth-child(4), td:nth-child(5), td:nth-child(6), td:nth-child(7), td:nth-child(8)').css('border-bottom', 'none');
                        if ($(this).prev().attr('data-role') == 'child') {
                            let siblingPrevParentItems = $(this).prevAll('tr[data-role="parent"]');
                            $.each(siblingPrevParentItems, function() {
                                $(this).children('td:nth-child(1), td:nth-child(4), td:nth-child(5), td:nth-child(6), td:nth-child(7), td:nth-child(8)').css('border-bottom', '1px solid #eee');
                            });
                        }
                    } else {
                        return false;
                    }
                    i++;
                });
            }
        
        form.on('submit', function(e){
            let count_modal = $('.modal-window-wrapper').length;
            loadGA();
            closeModalWindow(count_modal);
            return false;
        });
    });

    function viewProductInfo(id){
        if (!id) return false;
        showModalWindow('Информация о товаре', '/system/ajax/viewProductInfo.php?product_id=' + id);
    }
</script>
        <form id="view-ga" method="post">
        <div class="modal-window-content__row">
                <div class="modal-window-content__item">
                    <div class="modal-window-content__title">Конфигурация</div>
                    <div class="modal-window-content__value">
                        <span>Поставщик</span> <i class="fa fa-truck"></i> <select id="ga-supplier" name="supplier" class="chosen-select">
<?
$suppliers = $db->query("SELECT COUNT(*) FROM `suppliers` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($suppliers[0] == 0) {
?>
                                            <option value="">- Нет поставщиков -</option>
<?
} else {
    $suppliers = $db->query("SELECT `id_item`, `name` FROM `suppliers` WHERE `client_id` = '" . $chief['id'] . "'");
?>
                                            <option value="">- Не указано -</option>
<?
    while ($supplier = $suppliers->fetch_assoc()) {
?>
                                            <option value="<?=$supplier['id_item']?>"<?=($supplier['id_item'] == $ga['supplier_id'] ? ' selected' : '')?>><?=protection($supplier['name'], 'display')?></option>
<?
    }
}
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>Описание</span> <i class="fa fa-comment"></i> <textarea id="ga-comment" name="comment" style="height: 190px"><?=protection($ga['comment'], 'display')?></textarea>
                    </div>
                </div>
            
                <div class="modal-window-content__item" style="width: auto">
                    <div class="modal-window-content__title">Товар</div>
                    <div class="modal-window-content__table">
                        <table id="ga-products" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>id</th>
                                    <th>sub_id</th>
                                    <th>sub_name</th>
                                    <th>Товар</th>
                                    <th>Цена</th>
                                    <th>Кол-во</th>
                                    <th>Итого</th>
                                </tr>
                            </thead>
                            <tbody>
<?
$products = $db->query("SELECT `products`.`id_item`, `products`.`name`, `products`.`model`, `arrival_of_goods-products`.`count`, `arrival_of_goods-products`.`id` AS `arrival_products_id`, `arrival_of_goods-products`.`price`, `arrival_of_goods-products`.`amount` FROM `products` INNER JOIN `arrival_of_goods-products` ON (`products`.`id_item` = `arrival_of_goods-products`.`product_id`) WHERE `arrival_of_goods-products`.`arrival_id` = '" . $ga['id_item'] . "' AND `products`.`client_id` = '" . $chief['id'] . "' AND `arrival_of_goods-products`.`client_id` = '" . $chief['id'] . "'");
$count = $amount = 0;
while ($product = $products->fetch_assoc()) {
    $count += abs(intval($product['count']));
    $amount += abs(floatval($product['amount']));

    if ($attrs = $db->query("SELECT COUNT(*) FROM `arrival_of_goods-attributes` WHERE `arrival_id` = '" . $ga['id_item'] . "' AND `arrival_products_id` = '" . $product['arrival_products_id'] . "' AND `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $attrs[0] > 0) {
        $attributes = $db->query("SELECT `attribute_id` FROM `arrival_of_goods-attributes` WHERE `arrival_id` = '" . $ga['id_item'] . "' AND `arrival_products_id` = '" . $product['arrival_products_id'] . "' AND `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'");
        $step['i'] = 1;
        while ($attribute = $attributes->fetch_assoc()) {
            $attribute = $db->query("SELECT `id_item`, `name`, `category_id` FROM `attributes` WHERE `id_item` = '" . $attribute['attribute_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $category = $db->query("SELECT `id_item`, `name` FROM `attribute_categories` WHERE `id_item` = '" . $attribute['category_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            if ($step['i'] == 1) {
?>
                                <tr data-id="<?=$product['id_item']?>" data-role="parent" data-count="<?=$attrs[0]?>">
                                    <td rowspan="<?=$attrs[0]?>"><?=$product['id_item']?></td>
                                    <td title="<?=protection($category['name'], 'display')?>"><?=$category['id_item']?></td>
                                    <td><?=protection($attribute['name'], 'display')?></td>
                                    <td rowspan="<?=$attrs[0]?>" title="<?=protection($product['name'] . ' ' . $product['model'], 'display')?>"><a id="view_product" href="javascript:void(0);" onclick="viewProductInfo(<?=$product['id_item']?>);"><?=protection($product['name'] . ' ' . $product['model'], 'display')?></a></td>
                                    <td rowspan="<?=$attrs[0]?>"><?=number_format(abs(floatval($product['price'])), 2, '.', '')?></td>
                                    <td rowspan="<?=$attrs[0]?>"><?=abs(intval($product['count']))?></td>
                                    <td rowspan="<?=$attrs[0]?>"><?=number_format(abs(floatval($product['amount'])), 2, '.', '')?></td>
                                </tr>
<?
            } else {
?>
                                <tr data-id="<?=$product['id_item']?>" data-role="child">
                                    <td title="<?=protection($category['name'], 'display')?>"><?=$category['id_item']?></td>
                                    <td style="border-right: 1px solid #eee"><?=protection($attribute['name'], 'display')?></td>
                                </tr>
<?
            }
            $step['i']++;
        }
    } else {

?>
                                <tr data-id="<?=$product['id_item']?>" data-role="parent" data-count="1">
                                    <td><?=$product['id_item']?></td>
                                    <td></td>
                                    <td></td>
                                    <td title="<?=protection($product['name'] . ' ' . $product['model'], 'display')?>"><a id="view_product" href="javascript:void(0);" onclick="viewProductInfo(<?=$product['id_item']?>);"><?=protection($product['name'] . ' ' . $product['model'], 'display')?></a></td>
                                    <td><?=number_format(abs(floatval($product['price'])), 2, '.', '')?></td>
                                    <td><?=abs(intval($product['count']))?></td>
                                    <td><?=number_format(abs(floatval($product['amount'])), 2, '.', '')?></td>
                                </tr>
<?
    }
}
?>
                            
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td align="left" colspan="5"><span class="f-r">Всего:</span</td>
                                    <td align="right" id="count"><span><?=abs(intval($count))?></span></td>
                                    <td id="amount" style="border-right: none;"><span style="color: #900; font-size: 14px"><?=number_format($amount, 2, '.', '')?></span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            
            </div>

            <div class="buttons">
                <button id="button-close-ga" class="form__button">Закрыть</button>
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
