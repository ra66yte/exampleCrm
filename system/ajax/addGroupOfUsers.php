<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;
    $description = isset($_POST['description']) ? protection($_POST['description'], 'base') : null;

    if (empty($name)) {
        $error = 'Введите название!';
    } elseif (mb_strlen($name, 'UTF-8') < 2 or mb_strlen($name, 'UTF-8') > 25) {
        $error = 'Название группы должно быть в пределах от 2 до 25 символов!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `groups_of_users` WHERE `name` = '" . $name . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Группа пользователей с таким названием уже есть!';
    }


    if (empty($description)) {
        $error = 'Введите описание!';
    } elseif (mb_strlen($description, 'UTF-8') < 10 or mb_strlen($description, 'UTF-8') > 200) {
        $error = 'Описание должно быть в пределах от 10 до 200 символов!';
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
        $sort = $db->query("SELECT COALESCE(MAX(`sort`), 0) AS `max` FROM `groups_of_users` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $count = $db->query("SELECT `groups_of_users` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['groups_of_users'] + 1;

        if ($result = $db->query("SELECT COUNT(*) FROM `groups_of_users` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            if ($db->query("INSERT INTO `groups_of_users` (`id`, `id_item`, `client_id`, `name`, `description`, `sort`) VALUES (null, '" . $id_item . "', '" . $chief['id'] . "', '" . $name . "', '" . $description . "', '" . ($sort['max'] + 1) . "')")) {
            
                if (count($menu) > 0 or count($statuses) > 0) {
                    $chunk = "INSERT INTO `group_rights` (`id`, `client_id`, `group_id`, `access_right`, `value`) VALUES";
    
                    $right_id = getAccessID('statuses');
                    foreach ($statuses as $key => $value) {
                        if ($result = $db->query("SELECT COUNT(*) FROM `status_order` WHERE `id_item` = '" . abs(intval($value)) . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
                            $chunk .= " (null, '" . $chief['id'] . "', '" . $id_item . "', '" . $right_id . "', '" . abs(intval($value)) . "'),";
                        }
                    }
    
                    foreach ($menu as $key => $value) {
                        $right_id = getAccessID($value);
                        if (!empty($right_id)) {
                            $chunk .= " (null, '" . $chief['id'] . "', '" . $id_item . "', '" . $right_id . "', '0'),";
                        }
                    }
    
                    $chunk = rtrim($chunk, ',');
                    $db->query($chunk);
                }
    
                $db->query("UPDATE `id_counters` SET `groups_of_users` = (`groups_of_users` + 1) WHERE `client_id` = '" . $chief['id'] . "'");
                $success = 1;
            } else {
                $error = 'Не удалось добавить группу пользователей!';
            }
        } else {
            $error = 'Не удалось добавить группу пользователей! Попробуйте еще раз.';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
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
            let countMenu = $('ul#scroll-menu li').length,
                countMenuActive = $('ul#scroll-menu li').not('.off').length;
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
            let countStatuses = $('ul#scroll-statuses li').length,
                countStatusesActive = $('ul#scroll-statuses li').not('.off').length;

            if ((countStatuses - countStatusesActive) != 0) {
                $('#select-all-statuses').prop('checked', false);
            } else {
                $('#select-all-statuses').prop('checked', true);
            }
        });


        let form = $('#add-group-of-users'),
            btn = form.find('#button-add-group-of-users');

        form.find('#group-name').focus();
        $('.chosen-select').chosen('destroy');
        $('.chosen-select').chosen({
            'disable_search': true
        });

        function checkFields() {
            let error;

            let description  = form.find('#group-description').val().trim();
            if (description == '') {
                error = 'Введите описание!';
            } else if (description.length < 10) {
                error = 'Описание не может содержать меньше 10 символов!';
            } else if (description.length > 200) {
                error = 'Описание должно быть в пределах 200 символов!';
            }

            let name = form.find('#group-name').val().trim();
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
                    url: "system/ajax/addGroupOfUsers.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        // console.log(jsonData);
                        if (jsonData.success == 1) {
                            loadGroupsOfUsers();
                            closeModalWindow(count_modal);
                        } else {
                            if (!$('.modal-window-content div').is('.error')) {
                                $('.modal-window-content').prepend('<div class="error"></div>');
                            }
                            $('.error').text(jsonData.error).show();
                        }
                    }
                });
            }
            return false;
        });
    });
</script>
        <form id="add-group-of-users" method="post">
            <div class="modal-window-content__row">
                <div class="modal-window-content__item">
                    <div class="modal-window-content__title">Конфигурация</div>
                    <div class="modal-window-content__value">
                        <span>Название</span> <i class="fa fa-tag"> </i> <input id="group-name" type="text" name="name" placeholder="Введите название" autocomplete="off">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Описание</span> <i class="fa fa-comment"> </i> <textarea name="description" id="group-description"></textarea>
                    </div>
                    
                </div>

                <div class="modal-window-content__item" style="min-width: 250px">
                    <div class="modal-window-content__title">Доступ к меню</div>
                    <div class="modal-window-content__value">
                        <ul id="scroll-menu" class="scroll-bar">
                            
                            <label for="select-all-menu">
                                <div class="scroll-bar__item">
                                    <input type="checkbox" id="select-all-menu"> Выбрать все
                                </div>
                            </label>
                            <div class="scroll-bar__menu-name"><i class="fa fa-money"></i> Заказы</div>
                            <li class="scroll-bar__item off">
                                Перечень заказов
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-orders" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Статусы заказов
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-order_statuses" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Способы оплаты
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-payment_methods" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Способы доставки
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-delivery_methods" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <div class="scroll-bar__menu-name"><i class="fa fa-truck"></i> Отправка товара</div>
                            <li class="scroll-bar__item off">
                                Список для курьера
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-list_for_courier" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Реестры Новой Почты
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-registries" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <div class="scroll-bar__menu-name"><i class="fa fa-users"></i> Контакты</div>
                            <li class="scroll-bar__item off">
                                Пользователи
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-users" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Группы пользователей
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-groups_of_users" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Клиенты
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-clients" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Группы клиентов
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-groups_of_clients" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>

                            <div class="scroll-bar__menu-name"><i class="fa fa-inbox"></i> Каталог</div>
                            <li class="scroll-bar__item off">
                                Категории товаров
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-product_categories" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Товары
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-products" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Производители
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-manufacturers" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Валюта
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-currencies" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Сайты (Landing Pages)
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-sites" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Категории атрибутов
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-attribute_categories" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Атрибуты
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-attributes" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Цвета товаров
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-colors_of_goods" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>

                            <div class="scroll-bar__menu-name"><i class="fa fa-archive"></i> Склад</div>
                            <li class="scroll-bar__item off">
                                Поставщики
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-suppliers" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Приход товаров
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-goods_arrival" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Движение товаров
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-movement_of_goods" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Списание товаров
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-write_off_of_goods" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>

                            <div class="scroll-bar__menu-name"><i class="fa fa-puzzle-piece"></i> Модули</div>
                            <li class="scroll-bar__item off">
                                Список модулей
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-plugins" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>

                            <div class="scroll-bar__menu-name"><i class="fa fa-line-chart"></i> Статистика</div>
                            <li class="scroll-bar__item off">
                                Статистика (Заказы)
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-statistics" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>

                            <div class="scroll-bar__menu-name"><i class="fa fa-trash-o"></i> Корзина</div>
                            <li class="scroll-bar__item off">
                                Заказы (удаленные)
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-remote_orders" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>

                            <div class="scroll-bar__menu-name"><i class="fa fa-cog"></i> Настройки</div>
                            <li class="scroll-bar__item off">
                                Система
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-set_system" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                История
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-history" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Блокировка IP
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-ban_ip" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>

                            <div class="scroll-bar__menu-name"><i class="fa fa-info-circle"></i> FAQ</div>
                            <li class="scroll-bar__item off">
                                Вопросы и ответы
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-answers_and_questions" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Инструкция
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-instruction" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                            <li class="scroll-bar__item off">
                                Документация API
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="menu-api_documentation" class="toggle__input">
                                        <div class="toggle__control"></div>
                                    </label>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="modal-window-content__item" style="min-width: 250px">
                    <div class="modal-window-content__title">Доступ к статусам</div>
                    <div class="modal-window-content__value">
                        <ul id="scroll-statuses" class="scroll-bar">
                            
                            <label for="select-all-statuses">
                                <div class="scroll-bar__item">
                                    <input type="checkbox" id="select-all-statuses"> Выбрать все
                                </div>
                            </label>
                            
                        
<?
$statuses = $db->query("SELECT `id`, `name`, `color`, `status` FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "'");
while ($status = $statuses->fetch_assoc()) {
?>
                            <li data-id="<?php echo $status['id']; ?>" class="scroll-bar__item off">
                                <img src="/getImage/?color=<?php echo str_replace('#', '', $status['color']); ?>" class="scroll-bar__image" alt="status"> <?php echo protection($status['name'], 'display'); ?>
                                <span>
                                    <label class="toggle">
                                        <input type="checkbox" name="status-<?php echo $status['id']; ?>" class="toggle__input">
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
                <button id="button-add-group-of-users" class="disabled">Добавить</button>
            </div>
            <input type="submit" style="display: none">
        </form>