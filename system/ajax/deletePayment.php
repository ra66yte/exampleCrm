<?php
include_once '../core/begin.php';

if (!checkAccess('payment_methods')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;

    $path = $_SERVER['DOCUMENT_ROOT'] . '/system/images/payment/' . $chief['id'] . '/';
    $ids = array();
    foreach($_POST['payments'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);
    if ($result = $db->query("SELECT COUNT(*) FROM `payment_methods` WHERE `permanent` = 'on' AND `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Не удалось выполнить операцию! [e:2]';
    }
    
    if (!isset($error)) {
        $payments = $db->query("SELECT `icon` FROM `payment_methods` WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'");
        while ($payment = $payments->fetch_assoc()) {
            if (is_file($path . $payment['icon'])) {
                unlink($path . $payment['icon']);
            }
        }
        
        if ($db->query("DELETE FROM `payment_methods` WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = 'Не удалось выполнить операцию!';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countPayments = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-payments', function(e) {
            let arrayPayments = new Array();
            $.each($('tr.table__active'), function(e) {
                arrayPayments.push($(this).attr('data-id'));
            });
            $.ajax({
                type: "POST",
                url: "/system/ajax/deletePayment.php?delete=true",
                data: { 'payments': arrayPayments },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadPaymentMethods();
                        hideOptions(true);
                        // $('.status-panel__count').hide();
                    } else {
                        showModalWindow(null, null, 'error', jsonData.error);
                    }
                    closeModalWindow(count_modal);
                }
            });
        });
    </script>
    <div class="modal-window-title"><i class="fa fa-exclamation-circle" style="color: #AE0000"></i> Удаление <?=($countPayments == 1 ? 'способа оплаты' : 'способов оплаты')?> <button class="modal-window-close" onclick="closeModalWindow();">×</button></div>
    <div class="modal-window-content">
        <div>Вы действительно хотите удалить <b><?=($countPayments == 1 ? 'способ оплаты' : plural_form($countPayments, array('способ оплаты', 'способа оплаты', 'способов оплаты')))?></b>?</div>
        <div class="buttons">
            <button id="delete-payments">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
    </div>
<?
}