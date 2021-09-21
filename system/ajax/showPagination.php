<?php
include_once '../core/begin.php';
error_reporting(E_ALL);
if (isset($_GET['module'])) {
    $currentPage = isset($_GET['page']) ? abs(intval($_GET['page'])) : 1;
    if ($currentPage == 0) $currentPage = 1;
    if ($_GET['module'] == 'orders') { // пагинация заказов
        $right_id = getAccessID('statuses');
        $count = $db->query("SELECT COUNT(*) FROM `orders` INNER JOIN `status_order` ON (`orders`.`status` = `status_order`.`id`) INNER JOIN `group_rights` ON (`group_rights`.`access_right` = '" . $right_id . "') " . ((isset($_GET['status']) && is_numeric($_GET['status'])) ? "WHERE `orders`.`client_id` = '" . $chief['id'] . "' AND `orders`.`status` = '" . abs(intval($_GET['status'])) . "'" : "WHERE `orders`.`client_id` = '" . $chief['id'] . "'") . " AND `orders`.`deleted` = '0' AND `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`value` = `status_order`.`id` AND `status_order`.`status` = 'on'")->fetch_row();
        $countPages = k_page($count[0], $user['max_rows']);
        $currentPage = page($countPages);
        str("/orders.php?status=" . protection($_GET['status'], 'int') . "&", $countPages, $currentPage, array('count_rows' => $count[0])); // Вывод страниц
    } else if ($_GET['module'] == 'orders_search') {
        $right_id = getAccessID('statuses');
        if (isset($_POST['search'])) {
            $status = abs(intval($_GET['status']));
            if ($status != 'all') {
                $where = " `orders`.`client_id` = '" . $chief['id'] . "' AND `orders`.`status` = '" . $status . "' AND ";
            } else {
                $where = " `orders`.`client_id` = '" . $chief['id'] . "' AND ";
            }

            $search = $_POST['search'];

            if (!empty($search)) {

                $search_keys = array();
                $search_values = array();
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        $search_keys[] = $key;
                        $search_values[] = $value;
                    }
                }

                $chunk = "`orders`.`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `orders`.`" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                $count = $db->query("SELECT COUNT(*) FROM `orders` INNER JOIN `status_order` ON (`orders`.`status` = `status_order`.`id`) INNER JOIN `group_rights` ON (`group_rights`.`access_right` = '" . $right_id . "') WHERE " . $where . $chunk . " AND `orders`.`deleted` = '0' AND `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`value` = `status_order`.`id` AND `status_order`.`status` = 'on'")->fetch_row();
                
            } else { // Если пустой запрос
                $count = $db->query("SELECT COUNT(*) FROM `orders` INNER JOIN `status_order` ON (`orders`.`status` = `status_order`.`id`) INNER JOIN `group_rights` ON (`group_rights`.`access_right` = '" . $right_id . "') " . ((isset($_GET['status']) && is_numeric($_GET['status'])) ? "WHERE `orders`.`client_id` = '" . $chief['id'] . "' AND `orders`.`status` = '" . abs(intval($_GET['status'])) . "'" : "WHERE `orders`.`client_id` = '" . $chief['id'] . "'") . " AND `orders`.`deleted` = '0' AND `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`value` = `status_order`.`id` AND `status_order`.`status` = 'on'")->fetch_row();
            }
            $countPages = k_page($count[0], $user['max_rows']);
            $currentPage = page($countPages);
            str("/orders.php?status=" . protection($_GET['status'], 'int') . "&", $countPages, $currentPage, array('count_rows' => $count[0])); // Вывод страниц
        }
   
    } else if ($_GET['module'] == 'product_categories') {
        $query = $db->query("SELECT `id` FROM `product_categories` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $page = page($countPages);
        $count = $query;
        $minus = ($count == 0) ? 1 : 0;
        echo '<div style="margin-right: 25px">Результат: с ' . (($page * $user['max_rows']) - ($user['max_rows'] - 1) - $minus) . ' по ' . $count . ' / <b>' . $count . '</b></div>';
    } elseif ($_GET['module'] == 'product_categories_search') {
        if (isset($_POST['search'])) {
            $where = "`client_id` = '" . $chief['id'] . "' AND ";
            $search = $_POST['search'];

            if (!empty($search)) {

                $search_keys = array();
                $search_values = array();
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        $search_keys[] = $key;
                        $search_values[] = $value;
                    }
                }

                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                $query = $db->query("SELECT `id` FROM `product_categories` WHERE " . $where . $chunk . "")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/product_categories?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `product_categories` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/product_categories", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
    } else if ($_GET['module'] == 'order_statuses') {
        $query = $db->query("SELECT `id` FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/order_statuses?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } elseif ($_GET['module'] == 'order_statuses_search') {
        if (isset($_POST['search'])) {
            $where = "`client_id` = '" . $chief['id'] . "' AND ";
            $search = $_POST['search'];

            if (!empty($search)) {

                $search_keys = array();
                $search_values = array();
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        $search_keys[] = $key;
                        $search_values[] = $value;
                    }
                }

                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                $query = $db->query("SELECT `id` FROM `status_order` WHERE " . $where . $chunk . "")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/order_statuses?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/order_statuses", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
    } else if ($_GET['module'] == 'payment_methods') {
        $query = $db->query("SELECT `id` FROM `payment_methods` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/payment_methods?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } elseif ($_GET['module'] == 'payment_methods_search') {
        if (isset($_POST['search'])) {
            $where = "`client_id` = '" . $chief['id'] . "' AND ";
            $search = $_POST['search'];

            if (!empty($search)) {

                $search_keys = array();
                $search_values = array();
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        $search_keys[] = $key;
                        $search_values[] = $value;
                    }
                }

                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                $query = $db->query("SELECT `id` FROM `payment_methods` WHERE " . $where . $chunk . "")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/payment_methods?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `payment_methods` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/payment_methods", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
    } else if ($_GET['module'] == 'delivery_methods') {
        $query = $db->query("SELECT `id` FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/delivery_methods?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } elseif ($_GET['module'] == 'delivery_methods_search') {
        if (isset($_POST['search'])) {
            $where = "`client_id` = '" . $chief['id'] . "' AND ";
            $search = $_POST['search'];

            if (!empty($search)) {

                $search_keys = array();
                $search_values = array();
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        $search_keys[] = $key;
                        $search_values[] = $value;
                    }
                }

                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                $query = $db->query("SELECT `id` FROM `delivery_methods` WHERE " . $where . $chunk . "")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/delivery_methods?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/delivery_methods", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
    } else if ($_GET['module'] == 'products') {
        $query = $db->query("SELECT `id` FROM `products` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/products?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } elseif ($_GET['module'] == 'products_search') {
        if (isset($_POST['search'])) {
            $where = "`client_id` = '" . $chief['id'] . "' AND ";
            $search = $_POST['search'];

            if (!empty($search)) {

                $search_keys = array();
                $search_values = array();
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        $search_keys[] = $key;
                        $search_values[] = $value;
                    }
                }

                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                $query = $db->query("SELECT `id` FROM `products` WHERE " . $where . $chunk . "")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/products?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `products` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/products", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
    } else if ($_GET['module'] == 'manufacturers') {
        $query = $db->query("SELECT `id` FROM `manufacturers` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/manufacturers?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } elseif ($_GET['module'] == 'manufacturers_search') {
        if (isset($_POST['search'])) {
            $where = "`client_id` = '" . $chief['id'] . "' AND ";
            $search = $_POST['search'];

            if (!empty($search)) {

                $search_keys = array();
                $search_values = array();
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        if ($key == 'type') {
                            $search_keys[] = $key;
                            if ($value == 1) {
                                $search_values[] = 'Бренд';
                            } else {
                                $search_values[] = 'Страна производитель';
                            }
                        } else {
                            $search_keys[] = $key;
                            $search_values[] = $value;
                        }
                    } else {
                        unset($_POST[$key]);
                    }
                }

                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                $query = $db->query("SELECT `id` FROM `manufacturers` WHERE " . $where . $chunk . "")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/manufacturers?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `manufacturers` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/products", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
    } else if ($_GET['module'] == 'currencies') {
        $query = $db->query("SELECT `id` FROM `currencies` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/currencies?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } else if ($_GET['module'] == 'sites') {
        $query = $db->query("SELECT `id` FROM `sites` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/sites?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } else if ($_GET['module'] == 'attribute_categories') {
        $query = $db->query("SELECT `id` FROM `attribute_categories` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/attribute_categories?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } else if ($_GET['module'] == 'attributes') {
        $query = $db->query("SELECT `id` FROM `attributes` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/attributes?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } else if ($_GET['module'] == 'colors') {
        $query = $db->query("SELECT `id` FROM `colors` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/colors?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } else if ($_GET['module'] == 'groups_of_users') {
        $query = $db->query("SELECT `id` FROM `groups_of_users` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/groups_of_users?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } elseif ($_GET['module'] == 'groups-of-users_search') {
        if (isset($_POST['search'])) {
            $where = "`client_id` = '" . $chief['id'] . "' AND ";
            $search = $_POST['search'];

            if (!empty($search)) {

                $search_keys = array();
                $search_values = array();
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        $search_keys[] = $key;
                        $search_values[] = $value;
                    } else {
                        unset($_POST[$key]);
                    }
                }

                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                $query = $db->query("SELECT `id` FROM `groups_of_users` WHERE " . $where . $chunk . "")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/groups_of_users?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `groups_of_users` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/groups_of_users", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
    } else if ($_GET['module'] == 'groups_of_clients') {
        $query = $db->query("SELECT `id` FROM `groups_of_clients` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/groups_of_clients?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } elseif ($_GET['module'] == 'groups-of-clients_search') {
        if (isset($_POST['search'])) {
            $where = "`client_id` = '" . $chief['id'] . "' AND ";
            $search = $_POST['search'];

            if (!empty($search)) {

                $search_keys = array();
                $search_values = array();
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        $search_keys[] = $key;
                        $search_values[] = $value;
                    } else {
                        unset($_POST[$key]);
                    }
                }

                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                $query = $db->query("SELECT `id` FROM `groups_of_clients` WHERE " . $where . $chunk . "")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/groups_of_clients?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `groups_of_users` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/groups_of_users", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
    } else if ($_GET['module'] == 'users') {
        $query = $db->query("SELECT `id` FROM `user` WHERE `id` = '" . $chief['id'] . "' UNION SELECT `id` FROM `user` WHERE `chief_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/users?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } else if ($_GET['module'] == 'users_search') {
        if (isset($_POST['search'])) {

            $search = $_POST['search'];

            if (!empty($search)) {

                $search_keys = array();
                $search_values = array();
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        $search_keys[] = $key;
                        $search_values[] = $value;
                    }
                }

                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                $query = $db->query("SELECT `id` FROM `user` WHERE `id` = '" . $chief['id'] . "' AND $chunk UNION SELECT `id` FROM `user` WHERE `chief_id` = '" . $chief['id'] . "' AND $chunk")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/users?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `user` WHERE `id` = '" . $chief['id'] . "' UNION SELECT `id` FROM `user` WHERE `chief_id` = '" . $chief['id'] . "'")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/users?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
   
    } else if ($_GET['module'] == 'clients') {
        $query = $db->query("SELECT `id` FROM `clients` WHERE " . ((isset($_GET['type']) && is_numeric($_GET['type'])) ? "`group_id` = '" . abs(intval($_GET['type'])) . "' AND" : "") . " `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/clients?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } else if ($_GET['module'] == 'clients_search') {
        if (isset($_POST['search'])) {
            $type = abs(intval($_GET['type']));
            if ($type != 'all') {
                $where = " `client_id` = '" . $chief['id'] . "' AND `group_id` = '" . $type . "'";
            } else {
                $where = " `client_id` = '" . $chief['id'] . "'";
            }

            $search = $_POST['search'];

            if (!empty($search)) {

                $search_keys = array();
                $search_values = array();
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        $search_keys[] = $key;
                        $search_values[] = $value;
                    }
                }

                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                $query = $db->query("SELECT `id` FROM `clients` WHERE $where AND $chunk")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/clients?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `clients` WHERE $where")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/clients?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
   
    } else if ($_GET['module'] == 'suppliers') {
        $query = $db->query("SELECT `id` FROM `suppliers` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/suppliers?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } else if ($_GET['module'] == 'suppliers_search') {
        if (isset($_POST['search'])) {
            $search = $_POST['search'];

            if (!empty($search)) {
                $search_keys = array();
                $search_values = array();
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        $search_keys[] = $key;
                        $search_values[] = $value;
                    }
                }

                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                $query = $db->query("SELECT `id` FROM `suppliers` WHERE `client_id` = '" . $chief['id'] . "' AND " . $chunk . "")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/suppliers?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `suppliers` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/suppliers?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
   
    } else if ($_GET['module'] == 'goods_arrival') {
        $query = $db->query("SELECT `id` FROM `arrival_of_goods` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/goods_arrival?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } else if ($_GET['module'] == 'goods_arrival_search') {
        if (isset($_POST['search'])) {
            $search = $_POST['search'];

            if (!empty($search)) {
                $search_keys = array();
                $search_values = array();
                $search_products = "";
                $date_time_start = null;
                $date_time_end = null;
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        if ($key == 'product') {
                            $search_products .= "SELECT DISTINCT `arrival_of_goods`.`id` FROM `arrival_of_goods` INNER JOIN `arrival_of_goods-products` ON (`arrival_of_goods`.`id` = `arrival_of_goods-products`.`arrival_id`) WHERE `arrival_of_goods-products`.`product_id` = '" . abs(intval($search['product'])) . "' AND `arrival_of_goods-products`.`client_id` = '" . $chief['id'] . "' AND `arrival_of_goods`.`client_id` = '" . $chief['id'] . "'";
                            $search_keys[] = '';
                            $search_values[] = '';
                        } elseif ($key == 'date_added_start') {
                            $date_start = date_create_from_format('d-m-Y', $value);
                            $date_start =  date_format($date_start, 'Y-m-d');
                            $date_time_start = strtotime($date_start);

                            $search_keys[] = 'date_added_start';
                            $search_values[] = $date_time_start;
                        } elseif ($key == 'date_added_end') {
                            $date_end = date_create_from_format('d-m-Y', $value);
                            $date_end =  date_format($date_end, 'Y-m-d');
                            $date_time_end = strtotime($date_end);

                            $search_keys[] = 'date_added_end';
                            $search_values[] = $date_time_end;
                        } else {
                            $search_keys[] = $key;
                            $search_values[] = $value;
                        }
                    } else {
                        unset($_POST[$key]);
                    }
                }

                $chunk = '';
    
                if (count($search_values) != 0) {

                    if ($search_keys[0] != '') {
                        if ($search_keys[0] == 'date_added_start') {
                            $symbol = ">";
                            $search_keys[0] = 'date_added';
                        } elseif ($search_keys[0] == 'date_added_end') {
                            $symbol = "<";
                            $search_keys[0] = 'date_added';
                        }
                        $chunk = "AND `" . protection($search_keys[0], 'base') . "` " . (isset($symbol) ? $symbol . " '" . protection($search_values[0], 'base') . "'" : "LIKE '%" . protection($search_values[0], 'base') . "%'") . "";
                    }
                    for ($i = 1; $i < count($search_values); $i++) {
                        if ($search_keys[$i] != '') {
                            if ($search_keys[$i] == 'date_added_start') {
                                $symbol = ">";
                                $search_keys[$i] = 'date_added';
                            } elseif ($search_keys[$i] == 'date_added_end') {
                                $symbol = "<";
                                $search_keys[$i] = 'date_added';
                            }
                            $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` " . (isset($symbol) ? $symbol . " '" . protection($search_values[$i], 'base') . "'" : "LIKE '%" . protection($search_values[$i], 'base') . "%'") . "";
                        }
                    }

                    $sql = "" . (($chunk != '') ? "SELECT `arrival_of_goods`.`id` FROM `arrival_of_goods` WHERE `client_id` = '" . $chief['id'] . "' " . $chunk . "": '') . " " . (($chunk == '') ? (($search_products != '') ? "" . $search_products . "" : "AND `arrival_of_goods`.`id` IN (" . $search_products . ")") : (($search_products != '') ? "AND `arrival_of_goods`.`id` IN (" . $search_products . ")" : "" . $search_products . "")) . "";

                    $query = $db->query($sql)->num_rows;
                    $countPages = k_page($query, $user['max_rows']);
                    $currentPage = page($countPages);
                    str("/goods_arrival?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
                }

            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `arrival_of_goods` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/goods_arrival?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
   
    } else if ($_GET['module'] == 'write_off_of_goods') {
        $query = $db->query("SELECT `id` FROM `write_off_of_goods` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/write_off_of_goods?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } else if ($_GET['module'] == 'write_off_of_goods_search') {
        if (isset($_POST['search'])) {
            $search = $_POST['search'];

            if (!empty($search)) {
                $search_keys = array();
                $search_values = array();
                $search_products = "";
                $date_time_start = null;
                $date_time_end = null;
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        if ($key == 'product_id') {
                            $search_products .= "SELECT DISTINCT `write_off_of_goods`.`id` FROM `write_off_of_goods` INNER JOIN `write_off_of_goods-products` ON (`write_off_of_goods`.`id` = `write_off_of_goods-products`.`woog_id`) WHERE `write_off_of_goods-products`.`product_id` = '" . abs(intval($value)) . "' AND  `write_off_of_goods-products`.`client_id` = '" . $chief['id'] . "' AND `write_off_of_goods`.`client_id` = '" . $chief['id'] . "'";
                            $search_keys[] = '';
                            $search_values[] = '';
                        } elseif ($key == 'date_added_start') {
                            $date_start = date_create_from_format('d-m-Y', $value);
                            $date_start =  date_format($date_start, 'Y-m-d');
                            $date_time_start = strtotime($date_start);

                            $search_keys[] = 'date_added_start';
                            $search_values[] = $date_time_start;
                        } elseif ($key == 'date_added_end') {
                            $date_end = date_create_from_format('d-m-Y', $value);
                            $date_end =  date_format($date_end, 'Y-m-d');
                            $date_time_end = strtotime($date_end);

                            $search_keys[] = 'date_added_end';
                            $search_values[] = $date_time_end;
                        } else {
                            $search_keys[] = $key;
                            $search_values[] = $value;
                        }
                    } else {
                        unset($_POST[$key]);
                    }
                }

                $chunk = '';
    
                if (count($search_values) != 0) {

                    if ($search_keys[0] != '') {
                        if ($search_keys[0] == 'date_added_start') {
                            $symbol = ">";
                            $search_keys[0] = 'date_added';
                        } elseif ($search_keys[0] == 'date_added_end') {
                            $symbol = "<";
                            $search_keys[0] = 'date_added';
                        }
                        $chunk = "AND `" . protection($search_keys[0], 'base') . "` " . (isset($symbol) ? $symbol . " '" . protection($search_values[0], 'base') . "'" : "LIKE '%" . protection($search_values[0], 'base') . "%'") . "";
                    }
                    for ($i = 1; $i < count($search_values); $i++) {
                        if ($search_keys[$i] != '') {
                            if ($search_keys[$i] == 'date_added_start') {
                                $symbol = ">";
                                $search_keys[$i] = 'date_added';
                            } elseif ($search_keys[$i] == 'date_added_end') {
                                $symbol = "<";
                                $search_keys[$i] = 'date_added';
                            }
                            $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` " . (isset($symbol) ? $symbol . " '" . protection($search_values[$i], 'base') . "'" : "LIKE '%" . protection($search_values[$i], 'base') . "%'") . "";
                        }
                    }

                    $sql = "" . (($chunk != '') ? "SELECT `write_off_of_goods`.`id` FROM `write_off_of_goods` WHERE `client_id` = '" . $chief['id'] . "' " . $chunk . "": '') . " " . (($chunk == '') ? (($search_products != '') ? "" . $search_products . "" : "AND `write_off_of_goods`.`id` IN (" . $search_products . ")") : (($search_products != '') ? "AND `write_off_of_goods`.`id` IN (" . $search_products . ")" : "" . $search_products . "")) . "";

                    $query = $db->query($sql)->num_rows;
                    $countPages = k_page($query, $user['max_rows']);
                    $currentPage = page($countPages);
                    str("/write_off_of_goods?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
                }

            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `write_off_of_goods` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/write_off_of_goods?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
   
    } else if ($_GET['module'] == 'movement_of_goods') {
        $query = $db->query("SELECT `id` FROM `movement_of_goods` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/movement_of_goods?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } else if ($_GET['module'] == 'movement_of_goods_search') {
        if (isset($_POST['search'])) {
            $search = $_POST['search'];
            if (!empty($search)) {
                $search_keys = array();
                $search_values = array();
                $search_products = "";
                $date_time_start = null;
                $date_time_end = null;
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                            if ($key == 'product') {
                                $search_products .= "SELECT DISTINCT `movement_of_goods`.`id` FROM `movement_of_goods` INNER JOIN `movement_of_goods-products` ON (`movement_of_goods`.`id` = `movement_of_goods-products`.`mog_id`) WHERE `movement_of_goods-products`.`product_id` = '" . abs(intval($value)) . "' AND  `movement_of_goods-products`.`client_id` = '" . $chief['id'] . "' AND `movement_of_goods`.`client_id` = '" . $chief['id'] . "'";
                                $search_keys[] = '';
                                $search_values[] = '';
                            } elseif ($key == 'date_start') {
                                $date_start = date_create_from_format('d-m-Y', $value);
                                $date_start =  date_format($date_start, 'Y-m-d');
                                $date_time_start = strtotime($date_start);

                                $search_keys[] = 'date_start';
                                $search_values[] = $date_time_start;
                            } elseif ($key == 'date_end') {
                                $date_end = date_create_from_format('d-m-Y', $value);
                                $date_end =  date_format($date_end, 'Y-m-d');
                                $date_time_end = strtotime($date_end) + 86400;

                                $search_keys[] = 'date_end';
                                $search_values[] = $date_time_end;
                            } else {
                                $search_keys[] = $key;
                                $search_values[] = $value;
                            }
                    } else {
                        unset($_POST[$key]);
                    }
                }

                $chunk = '';
    
                if (count($search_values) != 0) {
                    if ($search_keys[0] != '') {
                        if ($search_keys[0] == 'date_start') {
                            $symbol = ">";
                            $search_keys[0] = 'date_added';
                        } elseif ($search_keys[0] == 'date_end') {
                            $symbol = "<";
                            $search_keys[0] = 'date_added';
                        }
                        $chunk = "AND `" . protection($search_keys[0], 'base') . "` " . (isset($symbol) ? $symbol . " '" . protection($search_values[0], 'base') . "'" : "LIKE '%" . protection($search_values[0], 'base') . "%'") . "";
                    }
                    for ($i = 1; $i < count($search_values); $i++) {
                        if ($search_keys[$i] != '') {
                            if ($search_keys[$i] == 'date_start') {
                                $symbol = ">";
                                $search_keys[$i] = 'date_added';
                            } elseif ($search_keys[$i] == 'date_end') {
                                $symbol = "<";
                                $search_keys[$i] = 'date_added';
                            }
                            $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` " . (isset($symbol) ? $symbol . " '" . protection($search_values[$i], 'base') . "'" : "LIKE '%" . protection($search_values[$i], 'base') . "%'") . "";
                        }
                    }

                    $sql = "" . (($chunk != '') ? "SELECT `movement_of_goods`.`id` FROM `movement_of_goods` WHERE `client_id` = '" . $chief['id'] . "' " . $chunk . "": '') . " " . (($chunk == '') ? (($search_products != '') ? "" . $search_products . "" : "AND `movement_of_goods`.`id` IN (" . $search_products . ")") : (($search_products != '') ? "AND `movement_of_goods`.`id` IN (" . $search_products . ")" : "" . $search_products . "")) . "";
                    $query = $db->query($sql)->num_rows;
                    $countPages = k_page($query, $user['max_rows']);
                    $currentPage = page($countPages);
                    str("/movement_of_goods?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
                }

            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `movement_of_goods` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/movement_of_goods?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
   
    } else if ($_GET['module'] == 'offices') {
        $query = $db->query("SELECT `id` FROM `offices` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
        $countPages = k_page($query, $user['max_rows']);
        $currentPage = page($countPages);
        str("/offices?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
    } else if ($_GET['module'] == 'offices_search') {
        if (isset($_POST['search'])) {
            $search = $_POST['search'];

            if (!empty($search)) {
                $search_keys = array();
                $search_values = array();
                foreach ($search as $key => $value) {
                    if ($key != null && $value != null) {
                        $search_keys[] = $key;
                        $search_values[] = $value;
                    }
                }

                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }
                $query = $db->query("SELECT `id` FROM `offices` WHERE `client_id` = '" . $chief['id'] . "' AND " . $chunk . "")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/offices?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            } else { // Если пустой запрос
                $query = $db->query("SELECT `id` FROM `offices` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows;
                $countPages = k_page($query, $user['max_rows']);
                $currentPage = page($countPages);
                str("/offices?", $countPages, $currentPage, array('count_rows' => $query)); // Вывод страниц
            }
        }
    } else if ($_GET['module'] == 'plugins') {
        $query = $db->query("SELECT COUNT(*) FROM `plugin`")->fetch_row();
        $countPages = k_page($query[0], $user['max_rows']);
        $currentPage = page($countPages);
        str("/plugins?", $countPages, $currentPage, array('count_rows' => $query[0])); // Вывод страниц
    }
} else {
    die('Something went wrong...');
}
