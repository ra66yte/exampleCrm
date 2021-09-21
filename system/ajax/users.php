<?php
include_once '../core/begin.php';

$items_on_page = isset($user['max_rows']) ? $user['max_rows'] : $data['orders_on_page'];
$rows = array();

if (isset($_GET['module']) and $_GET['module'] == 'search') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        $count = $db->query("SELECT COUNT(*) FROM `user` WHERE (`id` = '" . $chief['id'] . "' AND `chief_id` = '0') OR `chief_id` = '" . $chief['id'] . "'")->fetch_row();
    
        if ($count[0] == 0) {
            $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
            echo json_encode(array('rows' => null, 'pagination' => $pagination));
            exit;
        } else {
            $search_keys = array();
            $search_values = array();
            foreach ($_POST as $key => $value) {
                if ($key != null && $value != null) {
                    if ($key == 'id') $key = 'id_item';
                    $search_keys[] = $key;
                    $search_values[] = $value;
                } else {
                    unset($_POST[$key]);
                }
            }
    
            if (count($search_values) != 0) {
                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }
                
                $count = $db->query("SELECT COUNT(*) FROM `user` WHERE ((`id` = '" . $chief['id'] . "' AND `chief_id` = '0') OR `chief_id` = '" . $chief['id'] . "') AND " . $chunk . "")->fetch_row();

                if ($count[0] == 0) {
                    $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
                    echo json_encode(array('rows' => null, 'pagination' => $pagination));
                    exit;
                } else {
                    $countPages = k_page($count[0], $items_on_page);
                    $currentPage = page($countPages);
                    $start = ($currentPage * $items_on_page) - $items_on_page;

                    $items = $db->query("SELECT `id_item`, `name`, `group_id`, `avatar` FROM `user` WHERE ((`id` = '" . $chief['id'] . "' AND `chief_id` = '0') OR `chief_id` = '" . $chief['id'] . "') AND " . $chunk . " ORDER by `id` ASC LIMIT $start, $items_on_page");

                    while ($us = $items->fetch_assoc()) {
                        $group = $db->query("SELECT `name`, `type` FROM `groups_of_users` WHERE `id_item` = '" . $us['group_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                        if (!isset($group)) $group = array('name' => 'Без группы', 'type' => null);
            
                        $rows[] = [
                            'id_item' => $us['id_item'],
                            'name' => $us['name'],
                            'avatar' => $us['avatar'],
                            'group_name' => $group['name'],
                            'group_type' => $group['type'],
                            'chief_id' => $chief['id']
                        ];
                    }
                }

    
            } else {
                $countPages = k_page($count[0], $items_on_page);
                $currentPage = page($countPages);
                $start = ($currentPage * $items_on_page) - $items_on_page;
        
                $items = $db->query("SELECT `id_item`, `name`, `group_id`, `avatar` FROM `user` WHERE (`id` = '" . $chief['id'] . "' AND `chief_id` = '0') OR `chief_id` = '" . $chief['id'] . "' ORDER by `id` ASC LIMIT $start, $items_on_page");
        
                while ($us = $items->fetch_assoc()) {
                    $group = $db->query("SELECT `name`, `type` FROM `groups_of_users` WHERE `id_item` = '" . $us['group_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                    if (!isset($group)) $group = array('name' => 'Без группы', 'type' => null);
        
                    $rows[] = [
                        'id_item' => $us['id_item'],
                        'name' => $us['name'],
                        'avatar' => $us['avatar'],
                        'group_name' => $group['name'],
                        'group_type' => $group['type'],
                        'chief_id' => $chief['id']
                    ];
                }
                
            }

            $pagination = array('countPages' => $countPages, 'currentPage' => $currentPage, 'totalRows' => $count[0], 'maxRows' => $items_on_page);
            echo json_encode(array('rows' => $rows, 'pagination' => $pagination));
            exit;
        }
        
    } else {
        die('Something went wrong...');
    }
}

if (isset($_GET['show']) and $_GET['show'] == 'true') {
    
    $count = $db->query("SELECT COUNT(*) FROM `user` WHERE (`id` = '" . $chief['id'] . "' AND `chief_id` = '0') OR `chief_id` = '" . $chief['id'] . "'")->fetch_row();
    if ($count[0] > 0) {
        $countPages = k_page($count[0], $items_on_page);
        $currentPage = page($countPages);
        $start = ($currentPage * $items_on_page) - $items_on_page;
        $users = $db->query("SELECT `id_item`, `name`, `group_id`, `avatar` FROM `user` WHERE (`id` = '" . $chief['id'] . "' AND `chief_id` = '0') OR `chief_id` = '" . $chief['id'] . "' ORDER by `id` ASC LIMIT $start, $items_on_page");
        $i = 1;
        while ($us = $users->fetch_assoc()) {
            $group = $db->query("SELECT `name`, `type` FROM `groups_of_users` WHERE `id_item` = '" . $us['group_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            if (!isset($group)) $group = array('name' => 'Без группы', 'type' => null);

            $rows[] = [
                'id_item' => $us['id_item'],
                'name' => $us['name'],
                'avatar' => $us['avatar'],
                'group_name' => $group['name'],
                'group_type' => $group['type'],
                'chief_id' => $chief['id']
            ];
            /*
?>
            <tr data-id="<?php echo $us['id']; ?>" class="table__item <?php echo (($us_group['type'] == 'administrator' and $us['chief_id'] == 0) ? 'disabled' : '') ?>">
                <td><?php echo $us['id']; ?></td>
                <td><div class="table__item-avatar" style="background-image: url('/system/images/photo/<?php echo ($us['avatar'] == 'no_photo.png') ? 'no_photo.png' : $chief['id'] . '/' . $us['avatar'] ?>');"></div></td>
                <td style="text-align: left"><?php echo protection($us['name'], 'display'); ?></td>
                <td align="center"><?php echo protection($group['name'], 'display'); ?></td>
                <td style="text-align: center"><?php echo $i; ?></td>
            </tr>
<?
            $i++;
            */
        }
        $pagination = array('countPages' => $countPages, 'currentPage' => $currentPage, 'totalRows' => $count[0], 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => $rows, 'pagination' => $pagination));
        exit;

    } else {

        $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => null, 'pagination' => $pagination));
        exit;

    }
}