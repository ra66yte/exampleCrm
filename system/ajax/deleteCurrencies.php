<?php
include_once '../core/begin.php';

if (!checkAccess('currencies')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;

    $ids = array();
    foreach($_POST['currencies'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);
    if ($db->query("DELETE FROM `currencies` WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")) {
        $success = 1;
    } else {
        $error = 'Не удалось удалить валюту!';
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countCurrencies = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-currencies', function(e) {
            let arrayCurrencies = new Array();
            $.each($('tr.table__active'), function(e) {
                arrayCurrencies.push($(this).attr('data-id'));
            });

            $.ajax({
                type: "POST",
                url: "/system/ajax/deleteCurrencies.php?delete=true",
                data: { 'currencies': arrayCurrencies },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadCurrencies();
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
        <div>Вы действительно хотите удалить <b><?=($countCurrencies == 1 ? 'валюту' : plural_form($countCurrencies, array('валюту', 'валюты', 'валют')))?></b>?</div>
        <div class="buttons">
            <button id="delete-currencies">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}