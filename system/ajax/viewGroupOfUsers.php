<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $id = isset($_POST['group_id']) ? abs(intval($_POST['group_id'])) : 0;
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $description = isset($_POST['description']) ? protection($_POST['description'], 'base') : null;

    // Попытка редактирования группы супер пользователя или группы другого клиента
    if (($result = $db->query("SELECT COUNT(*) FROM `groups_of_users` WHERE `id_item` = '" . $id . "' AND `type` = 'administrator' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) or ($result = $db->query("SELECT COUNT(*) FROM `groups_of_users` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0)) {
        $error = 'Хорошая попытка! :)';
    }

    if (empty($description)) {
        $error = 'Введите описание!';
    } elseif (mb_strlen($description, 'UTF-8') < 10 or mb_strlen($description, 'UTF-8') > 200) {
        $error = 'Описание должно быть в пределах от 10 до 200 символов!';
    }

    if (empty($name)) {
        $error = 'Введите название группы!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 25) {
        $error = 'Название должно быть в пределах от 2 до 25 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `groups_of_users` WHERE `name` = '" . $name . "' AND `id_item` != '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() AND $result[0] > 0) {
        $error = 'Группа пользователей с таким названием уже есть!';
    }

    if (empty($id)) {
        $error = 'Группа не выбрана!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `groups_of_users` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Группа не найдена!';
    }
    
    $menu = $statuses = array();
    foreach ($_POST as $key => $value) {
        if (preg_match('/menu-[a-z_-]/iu', $key)) {
            $menu_item = stristr($key, '-');
            $menu[] = ltrim($menu_item, '-');
        }

        if (preg_match('/status-\d+/iu', $key, $result)) {
            if (preg_match('/\d+/iu', $result[0], $r)) {
                $statuses[] = $r[0];
            }
        }
    }

    if (!isset($error)) {
        if ($db->query("UPDATE `groups_of_users` SET `name` = '" . $name . "', `description` = '" . $description . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {

            if (count($menu) > 0 or count($statuses) > 0) {
                $all_rights = $db->query("SELECT `access_right`, `value` FROM `group_rights` WHERE `group_id` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'");
                $old_rights = $old_values = array();
                while ($right = $all_rights->fetch_assoc()) {
                    $old_rights[] = $right['access_right'];
                    $old_values[] = $right['value'];
                }

                $chunk = "INSERT INTO `group_rights` (`id`, `client_id`, `group_id`, `access_right`, `value`) VALUES";
                $need = false;

                $new_rights = $new_values = $new_old_rights = $new_old_values = array();

                $right_id = getAccessID('statuses');
                foreach ($statuses as $key => $value) {
                    if ($result = $db->query("SELECT COUNT(*) FROM `status_order` WHERE `id_item` = '" . abs(intval($value)) . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
                        if (!in_array($right_id, $old_rights) or (in_array($right_id, $old_rights) and !in_array($value, $old_values))) {
                            $chunk .= " (null, '" . $chief['id'] . "', '" . $id . "', '" . $right_id . "', '" . abs(intval($value)) . "'),";
                            $need = true;

                            $new_rights[] = $right_id;
                            $new_values[] = $value;
                        } else {
                            $new_old_rights[] = $right_id;
                            $new_old_values[] = $value;
                        }
                    }
                }

                foreach ($menu as $key => $value) {
                    $right_id = getAccessID($value);
                    if (!empty($right_id)) {
                        if (!in_array($right_id, $old_rights)) {
                            $chunk .= " (null, '" . $chief['id'] . "', '" . $id . "', '" . $right_id . "', '0'),";
                            $need = true;

                            $new_rights[] = $right_id;
                            $new_values[] = '0';
                        } else {
                            $new_old_rights[] = $right_id;
                            $new_old_values[] = '0';
                        }
                    }
                }

                $chunk = rtrim($chunk, ',');
                if ($need == true) $db->query($chunk);
                // Все новые привилегии в форме
                $all_new_rights = array_merge($new_rights, $new_old_rights);

                $all_new_values = array_merge($new_values, $new_old_values);

                // Удаляем все кроме старых новых и новых привилегий
                $new_rights_now_chunk = "";
                $i = 0;
                foreach ($all_new_rights as $value) {
                    $new_rights_now_chunk .= "(`access_right` = '" . $value . "' AND `value` = '" . $all_new_values[$i] . "') OR ";
                    $i++;
                }
                $new_rights_now_chunk = rtrim($new_rights_now_chunk, 'OR ');

                $new_rights_now = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $id . "' AND ($new_rights_now_chunk) AND `client_id` = '" . $chief['id'] . "'");
                $new_rights_matches = array();
                while ($new_right_now = $new_rights_now->fetch_assoc()) {
                    $new_rights_matches[] = $new_right_now['id'];
                }

                $delete_matches = implode(',', $new_rights_matches);
               
                $delete_old_rights = "DELETE FROM `group_rights` WHERE `group_id` = '" . $id . "' AND `id` NOT IN ($delete_matches) AND `client_id` = '" . $chief['id'] . "'";
                
                $db->query($delete_old_rights);

            } else {
                $db->query("DELETE FROM `group_rights` WHERE `group_id` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'");
            }
            
            $success = 1;
        } else {
            $error = 'Не удалось обновить группу пользователей!';
        }
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['group_id']) and is_numeric($_GET['group_id'])) {
    $group_id = abs(intval($_GET['group_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `groups_of_users` WHERE `id_item` = '" . $group_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $group = $db->query("SELECT `name` FROM `groups_of_users` WHERE  `id_item` = '" . $group_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['group_name' => protection($group['name'], 'display')];
        } else {
            $error = 'Неизвестная группа пользователей!';
            $title = ['group_name' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `groups_of_users` WHERE `id_item` = '" . $group_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $group = $db->query("SELECT `id_item`, `name`, `description` FROM `groups_of_users` WHERE `id_item` = '" . $group_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>

<script>
    $(function(){

        $('#select-all-menu').on('click', function(e){
            if ($(this).prop('checked') == true) {
                $('ul#scroll-menu').find('li input[type="checkbox"]').prop('checked', true);
                $('ul#scroll-menu').find('li').removeClass('off');
            } else {
                $('ul#scroll-menu').find('li input[type="checkbox"]').prop('checked', false);
                $('ul#scroll-menu').find('li').addClass('off');
            }
    
        });

        $('ul#scroll-menu').on('click', 'li', function(e){
            let checker = $(this).find('input[type="checkbox"]');
            if (checker.prop('checked') == true) {
                checker.prop('checked', false);
                $(this).addClass('off');
            } else {
                checker.prop('checked', true);
                $(this).removeClass('off');
            }

            $('ul#scroll-menu input[type="checkbox"]').trigger('change');
        });

        $('ul#scroll-menu input[type="checkbox"]').on('change', function(e){
            let countMenu = $('ul#scroll-menu li').length;
            let countMenuActive = $('ul#scroll-menu li').not('.off').length;
            if ((countMenu - countMenuActive) != 0) {
                $('#select-all-menu').prop('checked', false);
            } else {
                $('#select-all-menu').prop('checked', true);
            }
        })

        $('#select-all-statuses').on('click', function(e){
            if ($(this).prop('checked') == true) {
                $('ul#scroll-statuses').find('li input[type="checkbox"]').prop('checked', true);
                $('ul#scroll-statuses').find('li').removeClass('off');
            } else {
                $('ul#scroll-statuses').find('li input[type="checkbox"]').prop('checked', false);
                $('ul#scroll-statuses').find('li').addClass('off');
            }
    
        });

        $('ul#scroll-statuses').on('click', 'li', function(e){
            let checker = $(this).find('input[type="checkbox"]');
            if (checker.prop('checked') == true) {
                checker.prop('checked', false);
                $(this).addClass('off');
            } else {
                checker.prop('checked', true);
                $(this).removeClass('off');
            }

            $('ul#scroll-statuses input[type="checkbox"]').trigger('change');
        });

        $('ul#scroll-statuses input[type="checkbox"]').on('change', function(e){
            let countStatuses = $('ul#scroll-statuses li').length;
            let countStatusesActive = $('ul#scroll-statuses li').not('.off').length;
            if ((countStatuses - countStatusesActive) != 0) {
                $('#select-all-statuses').prop('checked', false);
            } else {
                $('#select-all-statuses').prop('checked', true);
            }
        });


        let form = $('#save-group-of-users'),
            btn = form.find('#button-save-group-of-users');

        $('.chosen-select').chosen('destroy');
        $('.chosen-select').chosen({
            'disable_search': true
        });

        function checkFields() {
            let error;

            let description  = form.find('#group-description').val();
            if (description == '') {
                error = 'Введите описание!';
            } else if (description.length < 10) {
                error = 'Описание не может содержать меньше 10 символов!';
            } else if (description.length > 200) {
                error = 'Описание должно быть в пределах 200 символов!';
            }

            var name = form.find('#group-name').val();
            if (name == '') {
                error = 'Введите название группы!';
            } else if (name.length < 2) {
                error = 'Название не может содержать меньше 2 символов!';
            } else if (name.length > 25) {
                error = 'Название должно быть в пределах 25 символов!';
            }

            if (error) {
                btn.addClass('disabled');
            } else {
                btn.removeClass('disabled');
            }

            if (error) return error;
            else return false;
        }

        form.on('keyup change', function() {
            checkFields();
        });

        form.on('submit', function(e){
            let error = checkFields();
            if (error) {
                if (!$('.modal-window-content div').is('.error')) {
                    $('.modal-window-content').prepend('<div class="error"></div>');
                    $('.error').text(error).show();
                }
            } else {
                let data = $(this).serializeArray(),
                    count_modal = $('.modal-window-wrapper').length;
                $.ajax({
                    type: "POST",
                    url: "system/ajax/viewGroupOfUsers.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            loadGroupsOfUsers();
                            closeModalWindow(count_modal);
                        } else {
                            if (!$('.modal-window-content div').is('.error')) {
                                $('.modal-window-content').prepend('<div class="error"></div>');
                                $('.error').text(jsonData.error).show();
                            }
                        }
                    }
                });
            }
            return false;
        });
    });
</script>
        <form id="save-group-of-users" method="post" autocomplete="off">
            <input type="hidden" name="group_id" value="<?=protection($group['id_item'], 'int')?>">
            <div class="modal-window-content__row">
                <div class="modal-window-content__item">
                    <div class="modal-window-content__title">Конфигурация</div>
                    <div class="modal-window-content__value">
                        <span>Название</span> <i class="fa fa-tag"></i> <input id="group-name" type="text" name="name" value="<?=protection($group['name'], 'display')?>" placeholder="Введите название">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Описание</span> <i class="fa fa-comment"></i> <textarea name="description" id="group-description"><?=protection($group['description'], 'display')?></textarea>
                    </div>
                    
                </div>
                <div class="modal-window-content__item" style="min-width: 240px">
                    <div class="modal-window-content__title">Доступ к меню</div>
                    <div class="modal-window-content__value">
                        <ul id="scroll-menu" class="scroll-bar">
                            
                            <label for="select-all-menu">
                                <div class="scroll-bar__item">
                                    <input type="checkbox" id="select-all-menu"> Выбрать все
                                </div>
                            </label>
                            <div class="scroll-bar__menu-name"><i class="fa fa-money"></i> Заказы</div>
<?php
$right = getAccessID('orders');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Перечень заказов
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-orders" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <?php
$right = getAccessID('order_statuses');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Статусы заказов
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-order_statuses" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('payment_methods');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Способы оплаты
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-payment_methods" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('delivery_methods');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Способы доставки
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-delivery_methods" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <div class="scroll-bar__menu-name"><i class="fa fa-truck"></i> Отправка товара</div>
<?php
$right = getAccessID('list_for_courier');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Список для курьера
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-list_for_courier" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('registries');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?> 
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Реестры Новой Почты
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-registries" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <div class="scroll-bar__menu-name"><i class="fa fa-users"></i> Контакты</div>
<?php
$right = getAccessID('users');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?> 
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Пользователи
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-users" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('groups_of_users');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>       
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Группы пользователей
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-groups_of_users" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('clients');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?> 
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Клиенты
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-clients" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('groups_of_clients');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?> 
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Группы клиентов
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-groups_of_clients" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>

                            <div class="scroll-bar__menu-name"><i class="fa fa-inbox"></i> Каталог</div>
<?php
$right = getAccessID('product_categories');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?> 
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Категории товаров
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-product_categories" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('products');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?> 
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Товары
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-products" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('manufacturers');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?> 
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Производители
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-manufacturers" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('currencies');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?> 
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Валюта
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-currencies" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('sites');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?> 
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Сайты (Landing Pages)
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-sites" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('attribute_categories');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?> 
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Категории атрибутов
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-attribute_categories" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('attributes');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?> 
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Атрибуты
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-attributes" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('colors_of_goods');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?> 
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Цвета товаров
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-colors_of_goods" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>

                            <div class="scroll-bar__menu-name"><i class="fa fa-archive"></i> Склад</div>
<?php
$right = getAccessID('suppliers');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?> 
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Поставщики
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-suppliers" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('goods_arrival');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?> 
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Приход товаров
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-goods_arrival" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('movement_of_goods');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Движение товаров
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-movement_of_goods" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('write_off_of_goods');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Списание товаров
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-write_off_of_goods" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>

                            <div class="scroll-bar__menu-name"><i class="fa fa-puzzle-piece"></i> Модули</div>
<?php
$right = getAccessID('plugins');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Список модулей
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-plugins" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>

                            <div class="scroll-bar__menu-name"><i class="fa fa-line-chart"></i> Статистика</div>
<?php
$right = getAccessID('statistics');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Статистика (Заказы)
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-statistics" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>

                            <div class="scroll-bar__menu-name"><i class="fa fa-trash-o"></i> Корзина</div>
<?php
$right = getAccessID('remote_orders');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Заказы (удаленные)
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-remote_orders" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>

                            <div class="scroll-bar__menu-name"><i class="fa fa-cog"></i> Настройки</div>
<?php
$right = getAccessID('set_system');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Система
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-set_system" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('history');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                История
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-history" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('ban_ip');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Блокировка IP
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-ban_ip" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>

                            <div class="scroll-bar__menu-name"><i class="fa fa-info-circle"></i> FAQ</div>
<?php
$right = getAccessID('answers_and_questions');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Вопросы и ответы
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-answers_and_questions" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('instruction');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Инструкция
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-instruction" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?php
$right = getAccessID('api_documentation');
$check_right = $db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows;
?>
                            <li class="scroll-bar__item <?php echo ($check_right > 0 ? '' : 'off'); ?>">
                                Документация API
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-api_documentation" class="toggle__input" <?php echo ($check_right > 0 ? 'checked' : ''); ?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="modal-window-content__item" style="min-width: 240px">
                    <div class="modal-window-content__title">Доступ к статусам</div>
                    <div class="modal-window-content__value">
                        <ul id="scroll-statuses" class="scroll-bar">
                            
                            <label for="select-all-statuses">
                                <div class="scroll-bar__item">
                                    <input type="checkbox" id="select-all-statuses"> Выбрать все
                                </div>
                            </label>
                            
                        
<?
$statuses = $db->query("SELECT `id_item`, `name`, `color`, `status` FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "'");
$right = getAccessID('statuses');
while ($status = $statuses->fetch_assoc()) {
    $count = $db->query("SELECT COUNT(*) FROM `group_rights` WHERE `group_id` = '" . $group['id_item'] . "' AND `access_right` = '" . $right . "' AND `value` = '" . $status['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
?>
                            <li data-id="<?=$status['id_item']?>" class="scroll-bar__item<?=(($count[0] > 0) ? '' : ' off')?>">
                                <img src="/getImage/?color=<?=str_replace('#', '', $status['color'])?>" class="scroll-bar__image" alt="status"> <?=protection($status['name'], 'display')?>
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="status-<?=$status['id']?>" class="toggle__input"<?=(($count > 0) ? ' checked' : '')?>>
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
<?
}
?>
                        </ul>
                    </div>
                </div>
                
            </div>
            <div class="buttons">
                <button id="button-save-group-of-users" class="form__button">Сохранить и закрыть</button>
            </div>
            <input type="submit" style="display: none">
        </form>
<?
    } else {
?>
        Произошла ошибка при выполнении операции!
<?
    }
}