<?php
include_once '../core/begin.php';

$items_on_page = isset($user['max_rows']) ? $user['max_rows'] : $data['orders_on_page'];

if (isset($_GET['disable_category']) and $_GET['disable_category'] == true) {
    $success = $disable = null;
    $id = abs(intval($_POST['id_item']));
    $category = $db->query("SELECT `status`, `parent_id` FROM `product_categories` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
    if ($category['parent_id'] != 0) {
        $parent = $db->query("SELECT `status` FROM `product_categories` WHERE `id_item` = '" . $category['parent_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        if ($parent['status'] == 'off') {
            $success =  $disable = 1;
        }
    }
    echo json_encode(array('success' => $success, 'disable' => $disable));
    exit;
}
/*
// Древо
function build_tree($categories, $parent_id, $level) {
    global $db, $chief;
    if (is_array($categories) and isset($categories[$parent_id])) { //Если категория с таким parent_id существует
        $folder = '';
        foreach ($categories[$parent_id] as $category) { // Обходим
            $count_subs = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `parent_id` = '" . $category['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
            $folder = ($count_subs[0] > 0) ? '<i class="fa fa-folder-open" style="color: #3A6DC2"></i>' : '<i class="fa fa-folder"></i>';

?>
        <tr data-id="<?php echo $category['id_item']; ?>" class="table__item <?php echo $category['status'] == 'off' ? 'disabled' : ''; ?>">
            <td><?php echo $category['id_item'] ?></td>
            <td style="text-align: left; padding-left: <?php echo ($level == 0 ? '5' : $level * 20); ?>px"><?php echo ($level == 0 ? '<b style="font-size: 14px">' : '') . $folder . ' ' . protection($category['name'], 'display'); if ($count_subs[0] <> 0) { echo ' (' . $count_subs[0] . ')'; } echo ($level == 1 ? '</b>' : ''); ?></td>
            <td align="center">
                <label class="toggle">
                    <input type="checkbox" <?php echo ($category['status'] == 'on') ? 'checked' : ''; ?> onclick="changeStatus('product_categories', '<?php echo $category['status']; ?>', '<?php echo $category['id_item']; ?>');" class="toggle__input">
                    <div class="toggle__control"></div>
                </label>
            </td>
            <td align="center"><i class="fa fa-calendar-check-o"></i> <?=passed_time($category['date_added'])?></td>
        </tr>
<? 

            $level = $level + 1; // Увеличиваем уровень вложености
            // Рекурсивно вызываем эту же функцию, но с новым $parent_id и $level
            build_tree($categories, $category['id_item'], $level);
            $level = $level - 1; // Уменьшаем уровень вложености
        }
    }
}
*/

if (isset($_GET['show']) and $_GET['show'] == 'true') {
    $count = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
    if ($count[0] > 0) {
        $countPages = k_page($count[0], $items_on_page);
        $currentPage = page($countPages);
        $start = ($currentPage * $items_on_page) - $items_on_page;

        $items = $db->query("SELECT `id_item`, `parent_id`, `name`, `status`, `date_added` FROM `product_categories` WHERE `client_id` = '" . $chief['id'] . "' ORDER BY `id` ASC");
        $categories = array();
        while ($category = $items->fetch_assoc()) {
            $count_subs = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `parent_id` = '" . $category['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
            $category = $category + ['count_subs' => $count_subs[0]];
            $category['date_added'] = passed_time($category['date_added']);
            $categories[$category['parent_id']][] = $category;
        }

        $pagination = array('countPages' => $countPages, 'currentPage' => $currentPage, 'totalRows' => $count[0], 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => $categories, 'pagination' => $pagination));
        exit;
    } else {
        $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => null, 'pagination' => $pagination));
        exit;
    }
}
