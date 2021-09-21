<?php
include_once '../core/begin.php';

if (!checkAccess('sites')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;

    $ids = array();
    foreach($_POST['sites'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);
    if ($db->query("DELETE FROM `sites` WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")) {
        $success = 1;
    } else {
        $error = 'Не удалось удалить сайт(ы)!';
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countSites = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-sites', function(e) {
            let arraySites = new Array();
            $.each($('tr.table__active'), function(e) {
                arraySites.push($(this).attr('data-id'));
            });

            $.ajax({
                type: "POST",
                url: "/system/ajax/deleteSites.php?delete=true",
                data: { 'sites': arraySites },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadSites();
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
        <div>Вы действительно хотите удалить <b><?php echo ($countSites == 1 ? 'сайт' : plural_form($countSites, array('сайт', 'сайта', 'сайтов'))) ?></b>?</div>
        <div class="buttons">
            <button id="delete-sites">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}