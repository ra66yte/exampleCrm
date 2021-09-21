<?php
include_once '../core/begin.php';

if (!checkAccess('attribute_categories')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;

    $ids = array();
    foreach($_POST['attribute_categories'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);
    if ($db->query("DELETE FROM `attribute_categories` WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")) {
        $success = 1;
    } else {
        $error = 'Не удалось удалить категорию(и) атрибутов!';
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countAttributeCategories = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-attribute-categories', function(e) {
            let arrayAttributeCategories = new Array();
            $.each($('tr.table__active'), function(e) {
                arrayAttributeCategories.push($(this).attr('data-id'));
            });

            $.ajax({
                type: "POST",
                url: "/system/ajax/deleteAttributeCategories.php?delete=true",
                data: { 'attribute_categories': arrayAttributeCategories },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadAttributeCategories();
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
        <div>Вы действительно хотите удалить <b><?=($countAttributeCategories == 1 ? 'категорию' : plural_form($countAttributeCategories, array('категорию', 'категории', 'категорий')))?> атрибутов</b>?</div>
        <div class="buttons">
            <button id="delete-attribute-categories">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}