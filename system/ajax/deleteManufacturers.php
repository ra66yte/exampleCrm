<?php
include_once '../core/begin.php';

if (!checkAccess('manufacturers')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;

    $ids = array();
    foreach($_POST['manufacturers'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);
    if ($db->query("DELETE FROM `manufacturers` WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")) {
        $success = 1;
    } else {
        $error = 'Не удалось удалить производителя(ей)!';
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countManufacturers = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-manufacturers', function(e) {
            let arrayManufacturers = new Array();
            $.each($('tr.table__active'), function(e) {
                arrayManufacturers.push($(this).attr('data-id'));
            });

            $.ajax({
                type: "POST",
                url: "/ajax_deleteManufacturers?delete=true",
                data: { 'manufacturers': arrayManufacturers },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadManufacturers();
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
        <div>Вы действительно хотите удалить <b><?php echo ($countManufacturers == 1 ? 'производителя' : plural_form($countManufacturers, array('производителя', 'производителей', 'производителей'))) ?></b>?</div>
        <div class="buttons">
            <button id="delete-manufacturers">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}