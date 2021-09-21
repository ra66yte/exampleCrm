<?php
include_once '../core/begin.php';

if (!checkAccess('attributes')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;

    $ids = array();
    foreach($_POST['attributes'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);
    if ($db->query("DELETE FROM `attributes` WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")) {
        $success = 1;
    } else {
        $error = 'Не удалось удалить атрибут(ы)!';
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countAttributes = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-attributes', function(e) {
            let arrayAttributes = new Array();
            $.each($('tr.table__active'), function(e) {
                arrayAttributes.push($(this).attr('data-id'));
            });

            $.ajax({
                type: "POST",
                url: "/system/ajax/deleteAttributes.php?delete=true",
                data: { 'attributes': arrayAttributes },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadAttributes();
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
        <div>Вы действительно хотите удалить <b><?=($countAttributes == 1 ? 'атрибут' : plural_form($countAttributes, array('атрибут', 'атрибуты', 'атрибутов')))?></b>?</div>
        <div class="buttons">
            <button id="delete-attributes">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}