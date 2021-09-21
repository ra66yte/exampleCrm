<?php
include_once '../core/begin.php';

if (isset($_GET['woog_id']) and is_numeric($_GET['woog_id'])) {
    $woog_id = abs(intval($_GET['woog_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `write_off_of_goods` WHERE `id_item` = '" . $woog_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $woog = $db->query("SELECT `id_item`, `date_added` FROM `write_off_of_goods` WHERE `id_item` = '" . $woog_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['id_item' => protection($woog['id_item'], 'display'), 'date_added' => view_time($woog['date_added'])];
        } else {
            $error = 'Неизвестное списание!';
            $title = ['id_item' => 'UNDEFINED', 'date_added' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `write_off_of_goods` WHERE `id_item` = '" . $woog_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $woog = $db->query("SELECT `id_item`, `employee_id`, `comment`, `date_added` FROM `write_off_of_goods` WHERE `id_item` = '" . $woog_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>
<script>
    $(function(){
        let form = $('#view-woog'),
            btn = form.find('#button-view-woog');
        
        form.on('submit', function(e){
            let count_modal = $('.modal-window-wrapper').length;
            loadWOOG();
            closeModalWindow(count_modal);
            return false;
        });
    });
</script>
        <form id="view-woog" method="post">
            <input type="hidden" name="woog_id" value="<?=protection($woog['id_item'], 'int')?>">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Информация</div>
                <div class="modal-window-content__value">
                    <span>Товар</span> <i class="fa fa-info"></i> <div class="modal-window-content__value-block">
<?
        $products = $db->query("SELECT `products`.`id_item`, `products`.`name`, `products`.`model`, `write_off_of_goods-products`.`count`, `write_off_of_goods-products`.`id` AS `woog_product_id` FROM `products` INNER JOIN `write_off_of_goods-products` ON (`products`.`id_item` = `write_off_of_goods-products`.`product_id`) WHERE `write_off_of_goods-products`.`woog_id` = '" . $woog['id_item'] . "' AND `write_off_of_goods-products`.`client_id` = '" . $chief['id'] . "' AND `products`.`client_id` = '" . $chief['id'] . "'");

        while ($product = $products->fetch_assoc()) {
            $attrs = $db->query("SELECT `name` FROM `attributes` WHERE `id_item` IN (SELECT `attribute_id` FROM `write_off_of_goods-attributes` WHERE `woog_product_id` = '" . $product['woog_product_id'] . "' AND `client_id` = '" . $chief['id'] . "') AND `client_id` = '" . $chief['id'] . "'");
            $attributes = '';
            while ($attribute = $attrs->fetch_assoc()) {
                $attributes .= $attribute['name'] . ', ';
            }
?>
                    <?=$product['id_item'] . ' - ' . protection($product['name'] . ' ' . $product['model'] . ' (' . $product['count'] . ' шт.)', 'display') . ' <span style="color: red; font-style: italic">' . protection(rtrim($attributes, ', '), 'display') . '</span>'?><br>
<?
        }
?>
                    </div>
                </div>
                <div class="modal-window-content__value">
<?
        $employee = $db->query("SELECT `name` FROM `user` WHERE `id_item` = '" . $woog['employee_id'] . "' AND (`chief_id` = 0 OR `chief_id` = '" . $chief['id'] . "')")->fetch_assoc();
?>
                    <span>Сотрудник</span> <i class="fa fa-user"></i> <div class="modal-window-content__value-block"><?=protection($employee['name'], 'display')?></div>
                </div>
                <div class="modal-window-content__value">
                    <span>Описание</span> <i class="fa fa-comment"></i> <textarea><?=protection($woog['comment'], 'display')?></textarea>
                </div>
                <div class="modal-window-content__title">Дополнительно</div>
                <div class="modal-window-content__value">
                    <span>Дата</span> <i class="fa fa-calendar"></i> <input type="text" name="date_added" disabled value="<?=passed_time($woog['date_added'])?>">
                </div>
                <div class="buttons">
                    <button id="button-view-woog" name="woog-close">Закрыть</button>
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
