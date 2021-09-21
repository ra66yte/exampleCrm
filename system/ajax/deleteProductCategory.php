<?php
include_once '../core/begin.php';

if (!checkAccess('product_categories')) redirect('?access_is_denied');

function delete_product_categories($category) { // $category = 1
    global $db, $chief;
    $subcategories = $db->query("SELECT `id` FROM `product_categories` WHERE `client_id` = '" . $chief['id'] . "' AND `parent_id` = '" . abs(intval($category)) . "'");
    while ($subcategory = $subcategories->fetch_assoc()) {
        // Удаляем подкатегории
        delete_product_categories($subcategory['id']);
    }

    // Удаляем главную категорию
    if ($db->query("DELETE FROM `product_categories` WHERE `client_id` = '" . $chief['id'] . "' AND `id` = '" . abs(intval($category)) . "'")) {
        return true;
    } else {
        return false;
    }
}

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;
    $ids = array();
    foreach($_POST['categories'] as $value) {
        $ids[] = $value;
    }
    // $matches = implode(',', $ids);
    foreach ($ids as $id) {
        if (delete_product_categories($id)) {
            $success = 1;
        } else {
            $error = 'Не удалось удалить категорию!';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countCategories = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-categories', function(e) {
            let arrayCategories = new Array();
            $.each($('tr.table__active'), function(e) {
                arrayCategories.push($(this).attr('data-id'));
            });
            $.ajax({
                type: "POST",
                url: "/ajax_deleteCategory?delete=true",
                data: { 'categories': arrayCategories },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadCategories();
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
        <div>Вы действительно хотите удалить <b><?php echo ($countCategories == 1 ? 'категорию' : plural_form($countCategories, array('категорию', 'категории', 'категорий'))) ?></b>? Все вложенные категории будут удалены.</div>
        <div class="buttons">
            <button id="delete-categories">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}