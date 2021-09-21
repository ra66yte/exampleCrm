<?php
include_once '../core/begin.php';

if (!checkAccess('products')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = null;
    $error = null;
    $path = $_SERVER['DOCUMENT_ROOT'] . '/system/images/product/' . $chief['id'] . '/';
    $ids = array();
    foreach($_POST['products'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);
    
    if ($db->query("UPDATE `products` SET `deleted_at` = '" . $data['time'] . "' WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")) {
        $products = $db->query("SELECT `image` FROM `products` WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'");
        while ($product = $products->fetch_assoc()) {
            if (is_file($path . $product['image']) and $product['image'] != 'no_photo.png') {
                unlink($path . $product['image']);
            }
        }
        $db->query("UPDATE `products` SET `image` = 'no_photo.png' WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'");
        $success = 1;
    } else {
        $error = 'Не удалось выполнить операцию!';
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countProducts = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-products', function(e) {
            let arrayProducts = new Array();
            $.each($('tr.table__active'), function(e) {
                arrayProducts.push($(this).attr('data-id'));
            });
            $.ajax({
                type: "POST",
                url: "/system/ajax/deleteProduct.php?delete=true",
                data: { 'products': arrayProducts },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadProducts();
                        hideOptions(true);
                        $('.status-panel__count').hide();
                    } else {
                        showModalWindow(null, null, 'error', jsonData.error);
                    }
                    closeModalWindow(count_modal);
                }
            }); 
        });
    </script>
        <div>Вы действительно хотите удалить <b><?php echo ($countProducts == 1 ? 'товар' : plural_form($countProducts, array('товар', 'товара', 'товаров'))) ?></b>?</div>
        <div class="buttons">
            <button id="delete-products">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}