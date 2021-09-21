<?php
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>CRM: <?php echo $data['title'] ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/chosen.css">
    <link rel="stylesheet" href="/css/modals.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/redmond/jquery-ui.css">
    <link rel="stylesheet" href="/css/jquery-ui-timepicker-addon.min.css">
    <link rel="stylesheet" href="/font/css/font-awesome.min.css">
    <link rel="stylesheet" href="/js/spectrum/spectrum.min.css">

    <script src="/js/jquery-3.4.1.min.js"></script>
    <script src="/js/jquery-ui-1.12.min.js"></script>
    <script src="/js/jquery-ui-timepicker-addon.min.js"></script>
    <script src="/js/dplocalization.js"></script>
    <script src="/js/mobile-detect.min.js"></script>
    <script src="/js/chosen.jquery.min.js"></script>
    <script src="/js/chosenImage.js"></script>
    <script src="/js/spectrum/spectrum.min.js"></script>
    <script src="/js/multiselect.js"></script>
    <script src="/js/script.js"></script>
    
<?
if ($_SERVER['PHP_SELF'] == '/orders.php') { // Страница с заказами
?>
    <script src="/js/orders.js"></script>
<?
}
?>
    
</head>
<body>
    <audio volume="1" id="SOUND-LOGON" src="/source/sound/LogON.mp3" type="audio/mp3" preload="auto"></audio>
    <audio volume="1" id="SOUND-LOGOFF" src="/source/sound/LogOFF.mp3" type="audio/mp3" preload="auto"></audio>
    <audio volume="1" id="SOUND-INFO" src="/source/sound/INFO.mp3" type="audio/mp3" preload="auto"></audio>
    <audio volume="1" id="SOUND-CONFIRM" src="/source/sound/CONFIRM.mp3" type="audio/mp3" preload="auto"></audio>
    <audio volume="1" id="SOUND-BUTTON-SWITCH" src="/source/sound/BUTTON_SWITCH.mp3" type="audio/mp3" preload="auto"></audio>
    <audio volume="1" id="SOUND-ERROR" src="/source/sound/ERROR.mp3" type="audio/mp3" preload="auto"></audio>
<?

if (isset($user)) {
    ?>
    <div class="wrapper">
        <!-- Header -->
        <header class="header">

                <div class="header__logo">
                    <div class="header__menu" onclick="MenuHide();">
                        <i class="fa fa-chevron-left"></i>
                    </div>
                    <div class="header__logo-image">
                        <a href="/index.php"><img src="/img/logo.png" alt="logo"></a>
                    </div>
                    
                </div>

            
            <div class="header__row">
                <div class="header__info">
                    <?php echo protection($data['title'], 'display'); ?>
                </div>
                <nav class="header__nav">
                    <a href="javascript:void(0);" class="header__link user-header-info" onclick="ShowUserInfo('');"><i class="fa fa-user-circle"></i> <span><?php echo ($user['chief_id'] != 0 ? 'Сотрудник' : 'Администратор'); ?></span></a>
                    <div class="user-info-header-box">
                        <div class="user-info-header-box__content">
                            Логин: <b><?php echo $user['login'] ?></b>
                            <br>
                            <?php
                            $group = $db->query("SELECT `name` FROM `groups_of_users` WHERE `id_item` = '" . $user['group_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
                            ?>
                            Группа: <?=$group[0]?>
                        </div>
                        <div class="user-info-header-box__bottom">
                            <a href="#"><i class="fa fa-cog"></i> Настройки</a>
                            <a href="javascript:void(0);" onclick="showModalWindow('Подтверждение выхода', '/ajax_confirmExit', 'confirm');"><i class="fa fa-power-off"></i> Выход</a>
                        </div>
                    </div>
                    <a href="#" class="header__link" title="Новые заказы"><i class="fa fa-shopping-cart"></i></a>
                    <a href="#" class="header__link" title="Напоминания"><i class="fa fa-bell"></i></a>
                    <a href="javascript:void(0);" class="header__link" title="О системе" onclick="showModalWindow('Информация', '/system/ajax/modal.view.info.php');"><i class="fa fa-info-circle"></i></a>
                </nav>
            </div>
        </header>

        <div class="wrapper__content">
            <!-- Menu -->
            <section class="menu">
                <div class="menu__overflow">
                    <div data-icon="fa-desktop" title="Рабочий стол">
                        <a href="/index.php" class="menu__link"><i class="fa fa-desktop first-icon"></i> <span>Рабочий стол</span></a>
                    </div>
                    <nav>
                        <div data-icon="fa fa-money" title="Заказы">
                            <a href="javascript:void(0);" class="menu__link"><i class="fa fa-money"></i><span>Заказы</span> <i
                                    class="fa fa-angle-right caret"></i></a>
                        </div>
                        <ul>
                            <li><a href="/orders.php?status=1">Перечень заказов</a></li>
                            <li><a href="/order_statuses">Статусы заказов</a></li>
                            <li><a href="/payment_methods">Способы оплаты</a></li>
                            <li><a href="/delivery_methods">Способы доставки</a></li>
                        </ul>

                        <div data-icon="fa fa-truck" title="Отправка товара">
                            <a href="javascript:void(0);" class="menu__link"><i class="fa fa-truck"></i><span>Отправка товара</span>
                                <i class="fa fa-angle-right caret"></i></a>
                        </div>
                        <ul>
                            <li><a href="/list_for_courier">Список для курьера</a></li>
                            <li><a href="/registries">Реестры Новой Почты</a></li>
                        </ul>
                        <div data-icon="fa fa-users" title="Контакты">
                            <a href="javascript:void(0);" class="menu__link"><i class="fa fa-users"></i><span>Контакты</span>
                                <i class="fa fa-angle-right caret"></i></a>
                        </div>
                        <ul>
                            <li><a href="/users">Пользователи</a></li>
                            <li><a href="/groups_of_users">Группы пользователей</a></li>
                            <li><a href="/clients">Клиенты</a></li>
                            <li><a href="/groups_of_clients">Группы клиентов</a></li>
                            <li><a href="/offices">Отделы</a></li>
                        </ul>
                        <div data-icon="fa fa-inbox" title="Каталог">
                            <a href="javascript:void(0);" class="menu__link"><i class="fa fa-inbox"></i><span>Каталог</span> <i
                                    class="fa fa-angle-right caret"></i></a>
                        </div>
                        <ul>
                            <li><a href="/product_categories">Категории товаров</a></li>
                            <li><a href="/products">Товары</a></li>
                            <li><a href="/manufacturers">Производители</a></li>
                            <li><a href="/currency">Валюта</a></li>
                            <li><a href="/sites">Сайты (Landing Pages)</a></li>
                            <li><a href="/attribute_categories">Категории атрибутов</a></li>
                            <li><a href="/attributes">Атрибуты</a></li>
                            <li><a href="/colors_of_goods">Цвета товаров</a></li>
                            <li><a href="/countries">Страны</a></li>
                        </ul>
                        <div data-icon="fa fa-archive" title="Склад">
                            <a href="javascript:void(0);" class="menu__link"><i class="fa fa-archive"></i><span>Склад</span> <i
                                    class="fa fa-angle-right caret"></i></a>
                        </div>
                        <ul>
                            <li><a href="/suppliers">Поставщики</a></li>
                            <li><a href="/goods_arrival">Приход товара</a></li>
                            <li><a href="/movement_of_goods">Движение товаров</a></li>
                            <li><a href="/write_off_of_goods">Списание товаров</a></li>
                        </ul>
                        <div data-icon="fa fa-puzzle-piece" title="Модули">
                            <a href="javascript:void(0);" class="menu__link"><i class="fa fa-puzzle-piece"></i><span>Модули</span> <i class="fa fa-angle-right caret"></i></a>
                        </div>
                        <ul>
                            <li><a href="/plugins">Список модулей</a></li>
                        </ul>
                        <div data-icon="fa fa-line-chart" title="Статистика">
                            <a href="javascript:void(0);" class="menu__link"><i class="fa fa-line-chart"></i><span>Статистика</span>
                                <i class="fa fa-angle-right caret"></i></a>
                        </div>
                        <ul>
                            <li><a href="/statistics">Статистика (Заказы)</a></li>
                        </ul>
                        <div data-icon="fa fa-trash-o" title="Корзина">
                            <a href="javascript:void(0);" class="menu__link"><i class="fa fa-trash-o"></i><span>Корзина</span>
                                <i class="fa fa-angle-right caret"></i></a>
                        </div>
                        <ul>
                            <li><a href="/remote_orders">Заказы (удалённые)</a></li>
                        </ul>
                        <div data-icon="fa fa-cog" title="Настройки">
                            <a href="javascript:void(0);" class="menu__link"><i class="fa fa-cog"></i><span>Настройки</span>
                                <i class="fa fa-angle-right caret"></i></a>
                        </div>
                        <ul>
                            <li><a href="/set_system">Система</a></li>
                            <li><a href="/history">История</a></li>
                            <li><a href="/ban_ip">Блокировка IP</a></li>
                        </ul>
                        <div data-icon="fa fa-info-circle" title="FAQ">
                            <a href="javascript:void(0);" class="menu__link"><i class="fa fa-info-circle"></i><span>FAQ</span>
                                <i class="fa fa-angle-right caret"></i></a>
                        </div>
                        <ul>
                            <li><a href="/answers_and_questions">Вопросы и ответы</a></li>
                            <li><a href="/instruction">Инструкция</a></li>
                            <li><a href="/api_documentation">Документация API</a></li>
                        </ul>
                    </nav>
                </div>
            </section>
    <?
} else {
// something for guest
}
?>