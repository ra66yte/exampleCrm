<?php
include_once '../core/begin.php';

if (!checkAccess('suppliers')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;

    $ids = array();
    foreach($_POST['suppliers'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);
    if ($db->query("DELETE FROM `suppliers` WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")) {
        $success = 1;
    } else {
        $error = 'Не удалось удалить поставщика(ов)!';
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countSuppliers = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-suppliers', function(e) {
            let arraySuppliers = new Array();
            $.each($('tr.table__active'), function(e) {
                arraySuppliers.push($(this).attr('data-id'));
            });
            $.ajax({
                type: "POST",
                url: "/system/ajax/deleteSuppliers.php?delete=true",
                data: { 'suppliers': arraySuppliers },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadSuppliers();
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
        <div>Вы действительно хотите удалить <b><?=($countSuppliers == 1 ? 'поставщика' : plural_form($countSuppliers, array('поставщика', 'поставщиков', 'поставщиков')))?></b>?</div>
        <div class="buttons">
            <button id="delete-suppliers">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}
