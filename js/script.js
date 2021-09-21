'use strict';

$(function () {
    if (getCookie('is_logined')) {
        playSound('SOUND-LOGON');
        deleteCookie('is_logined');
    }

    $(".chosen-select").chosen({
        disable_search_threshold: 10
    });

    let detect = new MobileDetect(window.navigator.userAgent); // UserAgent
    if (detect.mobile()) {
        $.each($('tr.table-row-search input'), function(e) {
            $(this).attr('tabindex', '-1'); // На мобильных отключаем переключение полей ввода
        });
    }
        // Ебаный в рот! Она тебя сожрет
        $('.table').multiSelect({
            actcls: 'table__active',
            selector: 'tbody tr.table__item',
            callback: function(e) {
                // Сколько выбрано
                if ($('.table__active').length > 0) {
                    $('.status-panel button#button-selected').removeAttr('disabled');
                    $('.status-panel div.status-panel__count').html('<i class="fa fa-info-circle"></i> Выделено: ' + $('.table__active').length).show();
                } else {
                    $('.status-panel button#button-selected').attr('disabled', true);
                }
                
                // Если ничего не осталось
                if (!$('tr.table__active').length) {
                    $('.status-panel__count').text('').hide();
                }

                if ($('tr.table__item').length == $('tr.table__active').length && $('tr.table__item').length !== 0) { // Если заказы вообще есть и выделены
                    $('button#button-select-all').attr('style', 'color: #AE0000'); // Если выбраны все заказы, отмечаем
                }
                
                
            }
        });
        
    
    $('body').on('click', 'input[type="checkbox"], input[type="radio"]', function(e) {
        $('#SOUND-BUTTON-SWITCH')[0].play();
    });

    MenuDetect();

    // Меню
    $(document).on('click', '.menu div > a', function (e) {
        if ($(this).hasClass('menu-div-active')) {
            $(this).removeClass('menu-div-active');
            $(this).closest('div').find('.caret').attr('class', 'fa fa-angle-right caret');
            $(this).closest('div').next('ul').slideUp('fast');
            e.preventDefault();
            e.stopPropagation();
        } else if (!$('.menu').hasClass('menu-mini')) {
            $('.menu div a').removeClass('menu-div-active');
            $('.menu div .caret').attr('class', 'fa fa-angle-right caret');
            $('.menu ul').slideUp('fast');
            $(this).addClass('menu-div-active');
            $(this).closest('div').find('.caret').attr('class', 'fa fa-chevron-down caret');
            $(this).closest('div').next('ul').slideDown('fast');
        }
    });

    $('.menu').on('click', '.menu__link', function(e) {
        if ($('.menu').hasClass('menu-mini')) {
            $('.menu').removeClass('.menu-mini');
            MenuShow();
        }
    });

    if (window.innerWidth < 991 || getCookie('menu') == 'mini'){
        MenuHide();
    }
    
    // Скрываем по клику на body
    $('body').on('click', function(e) {
        
        if ($('.user-info-header-box').is(':visible') && !$('.substrate').length) {
            if (!$('a.user-header-info').is(e.target) && !$('.user-info-header-box').is(e.target) && $('.user-info-header-box').has(e.target).length === 0) { // Если клик не по нашему блоку и не по его дочерним элементам
                $('a.user-header-info').attr('onclick', 'ShowUserInfo();');
                $('.user-info-header-box').hide();
            }
        }
        if ($('.status-panel__options').is(':visible') && !$('.substrate').length) {
            if (!$('a.status-panel__link').is(e.target) && !$('.status-panel__options').is(e.target) && $('.status-panel__options').has(e.target).length === 0 && (!$('.modal-window-wrapper').is(e.target) && $('.modal-window-wrapper').has(e.target).length === 0)) { // Если клик не по нашему блоку и не по его дочерним элементам
                $('button#button-selected').attr('onclick', 'showOptions();');
                $('.status-panel__options').hide();
                $('button#button-selected').attr('style', 'font-size: 14px')
                                           .find('i').removeClass('fa-spin');
            
                $.each($('tr.table__item'), function(e){
                    if ($(this).hasClass('static')) {
                        $(this).removeClass('static');
                    }
                });
            }
        }
        
    });
    
    // Выбираем все заказы на странице
    $('button#button-select-all').on('click', function(e) {
        if ($(this).attr('style')) {
            $(this).removeAttr('style');
        } else if (!$('tr.table__active').length) {
            $(this).attr('style', 'color: #AE0000');
        }
        

        if ($('tr.table__active').length) {
            if ($('.static').length) {
                $('tr.table__active').removeClass('static'); 
            }
            $('tr.table__active').removeClass('table__active'); 
        } else {

            $.each($('tr.table__item'), function(e) {
                if (!$(this).hasClass('disabled')) $(this).addClass('table__active static');
            })
        }

    });

    
    
    $('.status-panel').on('click', '#button-selected', function(e) {
        if ($(this).attr('style') == 'color: #8A5A00; font-size: 14px') {
            $(this).attr('style', 'font-size: 14px')
                   .find('i').removeClass('fa-spin');
        } else {
            $(this).attr('style', 'color: #8A5A00; font-size: 14px')
                   .find('i').addClass('fa-spin');
        }

        // Блокируем кнопки
        if ($('tr.table__active').length > 1) {
            $('#button-copy').addClass('disabled');
            $('#button-copy').prop('disabled', true);

            $('#button-edit').addClass('disabled');
            $('#button-edit').prop('disabled', true);

            $('#button-change-statuses').removeClass('disabled');
            $('#button-change-statuses').prop('disabled', false);
        } else {

            $('#button-edit').removeClass('disabled');
            $('#button-edit').prop('disabled', false);

            $('#button-copy').removeClass('disabled');
            $('#button-copy').prop('disabled', false);

            $('#button-change-statuses').addClass('disabled');
            $('#button-change-statuses').prop('disabled', true);

        }

        $.each($('tr.table__active'), function(e){
            if (!$(this).hasClass('static')) {
                $(this).addClass('static');
            }
        })
    });
    
    /*
    $('body').on('click', '.substrate', function(e) { 
        // Скрываем модальное окно и подложку при клике вне окна
        if ((!$('.modal-window-wrapper').is(e.target) && $('.modal-window-wrapper').has(e.target).length === 0) || $('.disable').is(e.target)) {
            let last_modal = $('.modal-window-wrapper').last().attr('data-id');
            $('.modal-window-wrapper[data-id=' + last_modal + ']').remove();
        }

        $('.modal-window-wrapper').last().find('.modal-window-content').children('.disable').remove();

        if ($('.modal-window-wrapper').length == 0) { // Если окон больше нет
            if ($('tr.table__item').hasClass('blocked-row')) {
                var last_active_order = $('tr.table__active').last().attr('data-id');
                $('tr.table__item[data-id=' + (last_active_order) + ']').removeClass('blocked-row');
            }

            $('.substrate').fadeOut(150, function() {
                $('.substrate').remove();
            });
        }
    });
    */

    $('body').on('click', '.modal-window-content', function(e) {
        if ($('body div').hasClass('error')) {
            if (!$('.error').is(e.target) && !$('.form__button').is(e.target) && !$('button').is(e.target)) { // cyka
                $('.error').remove();
            }
        }
    });

    $('body').on('click', '.modal-window-content button.btn-cancel, .modal-window-content button.close-modal', function(e) {
        let last_modal = $('.modal-window-wrapper').last().attr('data-id');
        if (last_modal > 0) closeModalWindow(last_modal);
        hideOptions(true); // cyka
        e.stopPropagation();
    });

    // test
    let scroll_pos = 0;
    $(".content__overflow").scroll(function () {
        scroll_pos = $(this).scrollTop();
        if (scroll_pos > 30) {
            $(".table:not(.has-tabs) tr").first().find('th').css('background-color', '#eee');
        } else {
            $(".table tr").first().find('th').css('background-color', '#fff');
        }
    });
        
/* end $(function) */
});

function MenuHide() {
    setCookie('menu', 'mini');
    $('.menu, .header__logo').animate({ 'width': '40px', 'max-width': '40px' }, 'fast', function() {
        $('.header__menu i').attr('class', 'fa fa-chevron-right');
    });


    $('.header__menu').attr('onclick', 'MenuShow();');
    //$('.header__logo-image').hide();
    $('.menu div .caret').hide();

    $('.menu__overflow').css('overflow', 'hidden');

    $('.menu div a, .menu div a.menu-div-active').find('span').css({
        textIndent: '100%',
        whiteSpace: 'nowrap',
        overflow: 'hidden'
    });

    $('.menu div').next('ul').hide();
    $('.menu').addClass('menu-mini');

}

function MenuShow() {
    setCookie('menu', 'full');
    $('.menu, .header__logo').animate({ 'width': '200px', 'max-width': '200px' }, 'fast', function() {
        MenuWidth();
    });
    
}

function MenuWidth() {
    $('.header__menu').attr('onclick', 'MenuHide();');
    $('.header__menu i').attr('class', 'fa fa-chevron-left');
    //$('.header__logo-image').show();
    $('.menu div .caret').show();

    $('.menu div a, .menu div a.menu-div-active').find('span').css({
        textIndent: '0',
        whiteSpace: 'none',
        overflow: 'auto'
    });
    $('.menu__overflow span').css('overflow', 'hidden');
    $('.menu__overflow').css('overflow', 'auto');
    $('.menu').removeClass('menu-mini');
    MenuDetect();
}

function MenuDetect(){ // доработать
    var url = window.location.pathname;
    $.each($(".menu__overflow ul li a"), function(){ 
        if (this.href.split('?')[0] === 'http://' + location.hostname + url){
            $(this).addClass('menu-li-active');
            $(this).closest('ul').show();
            $(this).closest('ul').prev('div').find('a').addClass('menu-div-active');
            $(this).closest('ul').prev('div').find('.caret').attr('class','fa fa-chevron-down caret');           
        } else { $(this).removeClass('menu-li-active'); }
    });
}

// Стартуем прелоадер
function startPreloader(content) {
    if (!content) content = $('.content');
    let preloader = '<div class="preloader">' +
                        '<div class="cssload-loader">' +
                            '<div class="cssload-inner cssload-one"></div>' +
                            '<div class="cssload-inner cssload-two"></div>' +
                            '<div class="cssload-inner cssload-three"></div>' +
                        '</div>' +
                    '</div>';
    $(content).prepend(preloader);
}
// Убираем прелоадер
function stopPreloader(content) {
    if (!content) content = '.content';
    if ($(content + ' div').hasClass('preloader')) {
        $('.preloader').delay(200).fadeOut('fast', function() {
            $(content + ' div.preloader').remove();
        });
    }

    if ($('.status-panel__count').is(':visible')) $('.status-panel__count').text('').hide(); // Эксперимент
    if ($('tr.table__active').length == 1) $('button#button-select-all').attr('style', '');

}

// PreloaderModal
function startPreloaderModal(id) {
    let preloader = '<div class="preloader"><i class="fa fa-spinner fa-spin"></i></div>';
    $('.modal-window-wrapper[data-id=' + id + ']').prepend(preloader);
}
function stopPreloaderModal(id) {
    if ($('.modal-window-wrapper[data-id=' + id + '] div').hasClass('preloader')) {
        $('.modal-window-wrapper[data-id=' + id + '] .preloader').delay(200).fadeOut('fast', function() {
           $('.modal-window-wrapper[data-id=' + id + '] .preloader').remove();  
        });
    }

}

function showModalWindow(title, source, type = 'default', content = null) {
    let preloader = '<div class="preloader"><i class="fa fa-spinner fa-spin"></i></div>',
        count_modal = ($('.modal-window-wrapper').length + 1), // Если нет, то первое
        melody;

            // Затемняем фон
    if (!$('.substrate').length) {
        $('body').append('<div class="substrate"></div>');
        $('.substrate').animate({ opacity: 1 }, 150);
    }

    $('.substrate').append('<div data-id="' + count_modal + '" class="modal-window-wrapper"><div class="modal-window-body"></div></div>');
    $('.modal-window-wrapper').fadeIn(200);

        // Предыдущие окна
        $('.modal-window-wrapper').each(function() {
            let modal_id = $(this).attr('data-id');
            if (modal_id != count_modal && !$(this).find('.modal-window-content').children('.disable').length) { // Если были окна, то отключаем их
                $(this).find('.modal-window-content').prepend('<div class="disable"></div>');
                $(this).find('.modal-window-close').prop('disabled', true);
            }
        });
    
        $('.modal-window-wrapper').draggable({
            cursor: 'move',
            handle: '.modal-window-title',
            containment: 'window',
            distance: '10'
        });

    switch (type) {
        case 'error':
            title = '<i class="fa fa-exclamation-circle" style="color: #AE0000"></i> ' + ((title === null) ? 'Ошибка!' : title);
            melody = 'SOUND-ERROR';
            break;
        case 'success':
            title = '<i class="fa fa-info-circle" style="color: green"></i> ' + ((title === null) ? 'Успешно!' : title);
            melody = 'SOUND-INFO';
            break;
        case 'confirm':
            title = '<i class="fa fa-exclamation-circle" style="color: green"></i> ' + ((title === null) ? 'Подтверждение действия!' : title);
            melody = 'SOUND-CONFIRM';
            break;
        case 'default':
            title = (title === null ? 'Модальное окно' : title);
            melody = '';
            break;
        default:
            title = 'Системное оповещение';
            break;
            
    }
   
    $('.modal-window-wrapper[data-id=' + count_modal + ']').children('.modal-window-body').prepend('<div class="modal-window-title"><span>' + title + '</span><button class="modal-window-close" onclick="closeModalWindow();">×</button></div>')
    $('.modal-window-wrapper[data-id=' + count_modal + ']').children('.modal-window-body').append('<div class="modal-window-content">' + preloader + '</div>');
    if (type == 'default' || source !== null) {
        setTimeout(() => {
            $('.modal-window-wrapper[data-id=' + count_modal + ']').children('.modal-window-body').find('.modal-window-content').load(source, function(e) {
                // $(".chosen-select").chosen("destroy");
                $('.chosen-select').chosen({
                    disable_search_threshold: 10
                });
                // Была шибка при открытии окна и keypress enter
                $(':focus').trigger('blur');
            });
        }, 100);
    } else {
        setTimeout(() => {
            $('.modal-window-wrapper[data-id=' + count_modal + ']').children('.modal-window-body').find('.modal-window-content').html('<center>' + content + '</center>');
            $('.modal-window-wrapper[data-id=' + count_modal + ']').children('.modal-window-body').find('.modal-window-content').append('<div class="buttons"><button class="close-modal">Закрыть</button></div>');
        }, 100);
    }

    if (melody !== '') playSound(melody);
}

// closeModalWindow
function closeModalWindow(id = null) {
    let location = ($('section.content')[0].hasAttribute('data-location') ? $('section.content').attr('data-location') : null),
        countModals = 0,
        wsData;

    if (id) { // Если закрываем определенное окно
        $('.modal-window-wrapper[data-id=' + id + ']').remove();
        countModals = $('.modal-window-wrapper').length;
    } else {  
        // Если закрываем по клику на кнопку закрытия
        $('.modal-window-wrapper').on('click', '.modal-window-close', function(e) {
            $(this).closest('.modal-window-wrapper').remove();
            
        });
        countModals = ($('.modal-window-wrapper').length - 1);
    }

    if (countModals > 0) {
        $('.modal-window-wrapper[data-id="' + countModals + '"]').find('.modal-window-content').children('.disable').remove();
        $('.modal-window-wrapper[data-id="' + countModals + '"]').find('.modal-window-close').prop('disabled', false);
    } else { // Если окон больше нет
        // Убираем лоадер строки заказа
        if ($('tr.table__active').length) {
            $.each($('tr.table__active'), function(e) {
                wsData = {
                    action: 'unlock item',
                    data: {
                        itemId: $(this).attr('data-id'),
                        location: location
                    }
                }
                sendMessage(ws, JSON.stringify(wsData));

                $(this).removeClass('blocked-row');
            });

        }
        $('.substrate').fadeOut(150, function() {
            $('.substrate').remove();
        });
    }
}

// showOptions
function showOptions() {
    $(document).one('click', 'button#button-selected', function(e) {
        e.preventDefault();
        $(this).attr('onclick', 'hideOptions();');
        $('.status-panel__options').show();
    });
}
function hideOptions(clean = null) {
    $(document).one('click', 'a.status-panel__link', function(e) {
        e.preventDefault();
        $(this).attr('onclick', 'showOptions();');
        $('.status-panel__options').hide();
    });

    if (clean === true) {
        $('tr.table__item').removeClass('static');
        $('.status-panel__options').hide();
        $('button#button-selected').find('i').removeClass('fa-spin');
        $('button#button-selected').attr('onclick', 'showOptions();').css('color', '');
        if (!$('.table__active').length) {
            $('button#button-selected').prop('disabled', true);
        }
    }
}

// User-Info-Header-Box
function ShowUserInfo() {
    $(document).one('click', 'a.user-header-info', function (e) {
        e.preventDefault();
        // e.stopPropagation();
        $('a.user-header-info').attr('onclick', 'HideUserInfo();');
        $('.user-info-header-box').show();
    })
}
function HideUserInfo() {
    $(document).one('click', 'a.user-header-info', function (e) {
        e.preventDefault();
        // e.stopPropagation();
        $('a.user-header-info').attr('onclick', 'ShowUserInfo();');
        $('.user-info-header-box').hide();
    })
}

function getAllUrlParams(url) {
    // извлекаем строку из URL или объекта window
    var queryString = url ? url.split('?')[1] : window.location.search.slice(1);
    // объект для хранения параметров
    var obj = {};
    // если есть строка запроса
    if (queryString) {
        // данные после знака # будут опущены
        queryString = queryString.split('#')[0];
        // разделяем параметры
        var arr = queryString.split('&');
        for (var i = 0; i < arr.length; i++) {
            // разделяем параметр на ключ => значение
            var a = arr[i].split('=');
            // обработка данных вида: list[]=thing1&list[]=thing2
            var paramNum = undefined;
            var paramName = a[0].replace(/\[\d*\]/, function (v) {
                paramNum = v.slice(1, -1);
                return '';
            });
            // передача значения параметра ('true' если значение не задано)
            var paramValue = typeof (a[1]) === 'undefined' ? true : a[1];
            // преобразование регистра
            paramName = paramName.toLowerCase();
            paramValue = paramValue.toLowerCase();
            // если ключ параметра уже задан
            if (obj[paramName]) {
                // преобразуем текущее значение в массив
                if (typeof obj[paramName] === 'string') {
                    obj[paramName] = [obj[paramName]];
                }
                // если не задан индекс...
                if (typeof paramNum === 'undefined') {
                    // помещаем значение в конец массива
                    obj[paramName].push(paramValue);
                }
                // если индекс задан...
                else {
                    // размещаем элемент по заданному индексу
                    obj[paramName][paramNum] = paramValue;
                }
            }
            // если параметр не задан, делаем это вручную
            else {
                obj[paramName] = paramValue;
            }
        }
    }
    return obj;
}

function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

/* end */
// Навигация без перезагрузки
function Navigation(page) {
    if (!page) page = 1;
    let place = window.location.pathname;
    switch (place) {
        case "/orders.php": TabStatus(getParameterByName('status'), page); break;
        case "/attribute_categories": loadAttributeCategories(page); break;
        case "/attributes": loadAttributes(page); break;
        case "/clients": TabClients(getParameterByName('type'), page); break;
        case "/colors": loadColors(page); break;
        case "/currency": loadCurrencies(page); break;
        case "/delivery_methods": loadDeliveryMethods(page); break;
        case "/goods_arrival": loadGA(page); break;
        case "/groups_of_clients": loadGroupsOfClients(page); break;
        case "/groups_of_users": loadGroupsOfUsers(page); break;
        case "/manufacturers": loadManufacturers(page); break;
        case "/movement_of_goods": loadMOG(page); break;
        case "/offices": loadOffices(page); break;
        case "/order_statuses": loadStatuses(page); break;
        case "/payment_methods": loadPaymentMethods(page); break;
        case "/product_categories": loadCategories(page); break;
        case "/products": loadProducts(page); break;
        case "/sites": loadSites(page); break;
        case "/suppliers": loadSuppliers(page); break;
        case "/users": loadUsers(page); break;
        case "/write_off_of_goods": loadWOOG(page); break;
        default:
            window.location.href = '?page=' + page;
    }
}

// Максимальное количество строк на странице
function ChangeShowMaxRows(event) {
    $.ajax({
        type: "POST",
        url: "system/ajax/changeShowMaxRows.php",
        data: { 'max_rows' : event.value },
        success: function(response) {    
            let jsonData = JSON.parse(response);
            if (jsonData.success == 1) {
                if (window.location.pathname == "/orders.php") {
                    TabStatus(getParameterByName('status'), 1); // Обновляем содержимое и направляем на первую страницу
                } else {
                    window.location.reload();
                }
            }
        }    
    });
}

function changeStatus(location, status, id) {
    let places = {
        'product_categories': typeof (loadCategories) === 'function' ? loadCategories : null,
        'order_statuses': typeof (loadStatuses) === 'function' ? loadStatuses : null,
        'payment_methods': typeof (loadPaymentMethods) === 'function' ?  loadPaymentMethods : null,
        'delivery_methods': typeof (loadDeliveryMethods) === 'function' ?  loadDeliveryMethods : null,
        'products': typeof (loadProducts) === 'function' ? loadProducts : null,
        'attribute_categories': typeof (loadAttributeCategories) === 'function' ? loadAttributeCategories : null,
        'attributes': typeof (loadAttributes) === 'function' ? loadAttributes : null,
        'colors': typeof (loadColors) === 'function' ? loadColors : null,
        'plugins': typeof (loadPlugins) === 'function' ? loadPlugins : null,
        'countries': typeof (loadCountries) === 'function' ? loadCountries : null,
    },
        page = getParameterByName('page');
    if (Object.keys(places).includes(location)) {
        $.ajax({
            type: "POST",
            url: "system/ajax/changeStatus.php?location=" + location,
            data: { 'id_item': id, 'status': status },
            success: function(response) {    
                let jsonData = JSON.parse(response);
                if (jsonData.success == 1) {
                    places[location](page);
                    
                    if ($('.table__active').length) {
                        $('button#button-selected').prop('disabled', true);
                    }
                } else {
                    showModalWindow(null, null, 'error', jsonData.error);
                }
            }    
        });
    } else {
        return false;
    }
}

// Обновление сортировки
function updateSort(location) {
    let items = $('.table tr.table__item'),
        places = {
            'statuses': typeof (updateStatuses) === 'function' ? updateStatuses : null,
        },
        itemsIds = [];
    $.each(items, function(){
        itemsIds.push($(this).attr('data-id'));
    });
    console.log(itemsIds)
    if (itemsIds.length > 1 && Object.keys(places).includes(location)) {
        $.ajax({
            type: "POST",
            url: "system/ajax/updateSort.php?location=" + location,
            data: { 'ids': itemsIds },
            success: function(response) {    
                let jsonData = JSON.parse(response);
                console.log(jsonData)
                if (jsonData.success == 1) {
                    places[location](jsonData.data);
                } else {
                    showModalWindow(null, null, 'error', jsonData.error);
                }
            }    
        });
    } else {
        return false;
    }
}

function validateSize(fileInput, size) {
    let fileObj, oSize;
    if ( typeof ActiveXObject == "function" ) { // IE
        fileObj = (new ActiveXObject("Scripting.FileSystemObject")).getFile(fileInput.value);
    } else {
        fileObj = fileInput.files[0];
    }
    oSize = fileObj.size; // Size returned in bytes.
    if (oSize > size * 1024 * 1024){
        return false
    }
    return true;
}

function setCookie(name, value, options = {}) {
    options = {
      'path': '/',
      'max-age': 60*60*24,
      // при необходимости добавьте другие значения по умолчанию
      ...options
    };
  
    if (options.expires instanceof Date) {
      options.expires = options.expires.toUTCString();
    }
  
    let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);
  
    for (let optionKey in options) {
      updatedCookie += "; " + optionKey;
      let optionValue = options[optionKey];
      if (optionValue !== true) {
        updatedCookie += "=" + optionValue;
      }
    }
  
    document.cookie = updatedCookie;
  }

  function getCookie(name) {
    let matches = document.cookie.match(new RegExp(
      "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
  }

  function deleteCookie(name) {
    setCookie(name, "", {
      'max-age': -1
    })
  }

function playSound(ElementId) {
    $('audio').stop();
    let melody = document.getElementById(ElementId);
    melody.play();
}

function renderPagination(data, hideButtons = false) {
    let countPages = data.countPages,
        currentPage = data.currentPage,
        maxRows = data.maxRows,
        totalRows = ((currentPage == countPages) ? data.totalRows : (maxRows * currentPage)),
        minus = ((totalRows == 0) ? 1 : 0),
        pagination = '',
        step;
    if (currentPage < 1) currentPage = 1;
    pagination +=('<div>\r\n' +
                        'Результат: с <span id="pagination-start">' + ((currentPage * maxRows) - (maxRows - 1) - minus) + '</span> по <span id="pagination-now">' + totalRows + '</span> / <b id="pagination-total">' + data.totalRows + '</b>\r\n' +
                   '</div>\r\n' +
                   '<div class="pagination__info-buttons">\r\n');
    if (!hideButtons) {
        if (currentPage != 1) {
            pagination += ('<button onclick="Navigation(\'1\');"><i class="fa fa-fast-backward"></i></button>\r\n' +
                        '<button onclick="Navigation(\'' + (currentPage - 1) + '\');"><i class="fa fa-step-backward"></i></button>\r\n');
        } else {
            pagination += ('<button disabled><i class="fa fa-fast-backward"></i></button>\r\n' +
                        '<button disabled><i class="fa fa-step-backward"></i></button>\r\n');
        }
        pagination += '<div style="display: inline-block; padding: 0 10px;">\r\n';
        for (step = -3; step <= 3; step++) {
            if ((currentPage + step) > 0 && (currentPage + step) <= countPages) {
                if (step == 0) {
                    pagination += '<button disabled>' + (currentPage + step) + '</button>\r\n';
                } else {
                    
                    pagination += '<button class="pagination__pc" onclick="Navigation(\'' + (currentPage + step) + '\');">' + (currentPage + step) + '</button>\r\n';
                }
            }
        }
        pagination += '</div>\r\n';
        if (currentPage != countPages) {
            pagination += ('<button onclick="Navigation(\'' + (currentPage + 1) + '\');"><i class="fa fa-step-forward"></i></button>\r\n' +
                        '<button onclick="Navigation(\'' + countPages + '\');"><i class="fa fa-fast-forward"></i></button>\r\n');
        } else {
            pagination += ('<button disabled><i class="fa fa-step-forward"></i></button>\r\n' +
                        '<button disabled><i class="fa fa-fast-forward"></i></button>\r\n');
        }
    }
    
    return pagination;
}