<?php
include_once '../core/begin.php';

if (!checkAccess('delivery_methods')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;
    $path = $_SERVER['DOCUMENT_ROOT'] . '/system/images/delivery/' . $chief['id'] . '/';
    $ids = array();
    foreach($_POST['deliveries'] as $value) {
        $ids[] = "'" . protection($value, 'int') . "'";
    }
    $matches = implode(',', $ids);
    if ($result = $db->query("SELECT COUNT(*) FROM `delivery_methods` WHERE `permanent` = 'on' AND `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Не удалось произвести операцию!';
    } else {
        $deliveries = $db->query("SELECT `icon` FROM `delivery_methods` WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'");
        while ($delivery = $deliveries->fetch_assoc()) {
            if (is_file($path . $delivery['icon'])) {
                unlink($path . $delivery['icon']);
            }
        }
        if ($db->query("DELETE FROM `delivery_methods` WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = 'Не удалось произвести операцию!';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countDeliveries = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-deliveries', function(e) {
            let arrayDeliveries = new Array();
            $.each($('tr.table__active'), function(e) {
                arrayDeliveries.push($(this).attr('data-id'));
            });
            $.ajax({
                type: "POST",
                url: "/system/ajax/deleteDelivery.php?delete=true",
                data: { 'deliveries': arrayDeliveries },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadDeliveryMethods();
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
        <div>Вы действительно хотите удалить <b><?=($countDeliveries == 1 ? 'способ доставки' : plural_form($countDeliveries, array('способ доставки', 'способа доставки', 'способов доставки')))?></b>?</div>
        <div class="buttons">
            <button id="delete-deliveries">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}
