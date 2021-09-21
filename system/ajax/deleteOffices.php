<?php
include_once '../core/begin.php';

if (!checkAccess('offices')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;

    $ids = array();
    foreach($_POST['offices'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);
    if ($db->query("DELETE FROM `offices` WHERE `client_id` = '" . $chief['id'] . "' AND `id` IN ($matches)")) {
        $success = 1;
    } else {
        $error = 'Не удалось удалить отдел(ы)!';
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countOffices = abs(intval($_GET['count']));
?>
<script>
    $(function(){
        $('.modal-window-content').on('click', 'button#delete-offices', function(e) {
            let arrayOffices = new Array();
            $.each($('tr.table__active'), function(e) {
                 arrayOffices.push($(this).attr('data-id'));
            });

            $.ajax({
                type: "POST",
                url: "/system/ajax/deleteOffices.php?delete=true",
                data: { 'offices': arrayOffices },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadOffices();
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
        <div>Вы действительно хотите удалить <b><?php echo ($countOffices == 1 ? 'отдел' : plural_form($countOffices, array('отдел', 'отдела', 'отделов'))) ?></b>?</div>
        <div class="buttons">
            <button id="delete-offices">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}