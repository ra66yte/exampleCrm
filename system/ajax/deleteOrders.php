<?php
include_once '../core/begin.php';

if (!checkAccess('orders')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;
    $ids = array();
    foreach($_POST['orders'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);
    // ToDo: сделать проверку на статус и доступ пользователя к нему
    if ($db->query("UPDATE `orders` SET `deleted_at` = '" . $data['time'] . "' WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")) {
        $success = 1;
    } else {
        $error = 'Произошла ошибка при выполнении операции!';
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countOrders = abs(intval($_GET['count']));
?>
    <script>
        $(function(){
            let arrayOrders = new Array(),
                wsData;

            $.each($('tr.table__active'), function(e) {
                arrayOrders.push($(this).attr('data-id'));
                $(this).addClass('blocked-row');
                wsData = {
                    action: 'lock item',
                    data: {
                        itemId: $(this).attr('data-id'),
                        location: 'orders'
                    }
                }
                sendMessage(ws, JSON.stringify(wsData));
            });


            $('.modal-window-content').on('click', 'button#delete-orders', function(e) {
                
                $.ajax({
                    type: "POST",
                    url: "/system/ajax/deleteOrders.php?delete=true",
                    data: { 'orders': arrayOrders },
                    success: function(response) {
                        let jsonData = JSON.parse(response),
                            count_modal = $('.modal-window-wrapper').length;
                        if (jsonData.success) {
                            let status = getParameterByName('status');

                            wsData = {
                                action: 'remove item',
                                data: {
                                    itemsId: arrayOrders,
                                    location: 'orders'
                                }
                            }
                            
                            sendMessage(ws, JSON.stringify(wsData));

                            TabStatus(status);
                            hideOptions(true);
                            $('.status-panel__count').hide();
                        } else {
                            showModalWindow(null, null, 'error', jsonData.error);
                        }
                        closeModalWindow(count_modal);
                    }
                });    
            });
        });
        
    </script>
        <div>Вы действительно хотите удалить <b><?=($countOrders == 1 ? 'заказ' : plural_form($countOrders, array('заказ', 'заказа', 'заказов')))?></b>?</div>
        <div class="buttons">
            <button id="delete-orders">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>  
<?
}