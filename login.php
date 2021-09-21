<?php
include_once __DIR__ . '/system/core/begin.php';
if (isset($user)) header('Location: /?registered');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>CRM v. <?php echo $data['CRM_v'] ?></title>
    <link rel="stylesheet" href="css/no_auth.css">
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
    <script src="/js/spectrum/spectrum.min.js"></script>
    <script src="/js/multiselect.js"></script>
    <script src="/js/script.js"></script>
</head>
<body>
    <script type="text/javascript">
        $(function () {
            /* Скрываем ошибку по клику на body */
            $('body').on('click', function(e) {
                if ($('body div').hasClass('error')) {
                    if (!$('.error').is(e.target) && !$('.form__button').is(e.target)) {
                        $('.error').remove();
                    }
                }
            });

            $('.form').submit(function(e){
                if ($('.error').is('.swing')) {
                    $('audio').remove();
                    $('.error').removeClass('swing');
                }
                $.ajax({
                    type: "POST",
                    url: "system/ajax/login.php",
                    data: $(this).serialize(),
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            setCookie('is_logined', '1');
                            let preloader = '<div class="preloader" style="background: #fff">' +
                                                '<div class="cssload-loader">' +
                                                    '<div class="cssload-inner cssload-one"></div>' +
                                                    '<div class="cssload-inner cssload-two"></div>' +
                                                    '<div class="cssload-inner cssload-three"></div>' +
                                                '</div>' +
                                            '</div>';
                            $('body').prepend(preloader);
                                
                            setTimeout(() => {
                                location.href = '/index.php';
                            }, 1500);
                                
                                
                        } else {
                            $('.login').before('<audio id="ERROR" src="/source/sound/ERROR.mp3" type="audio/mp3" preload="auto"></audio>');
                            $('#ERROR')[0].play();
                
                            if (!$('.login__column div').is('.error')) {
                                $('.login__title').after('<div class="error"></div>');
                            }
                            if (jsonData.error) {
                                $('.error').text(jsonData.error).show();
                            }
                            $('.error').addClass('swing');
                            // setTimeout(function() { $('.error').hide('fast'); }, 3000);
                        }
                    }
                });
                return false;
            });
        });
    </script>
    <div class="login">
        <div class="container">
            <div class="login__column">
                <div class="login__title">CRMbyte v. <? echo $data['CRM_v'] ?></div>
                <form action="/login.php?action=sign" class="form" method="post">
                    <input type="hidden" name="_token" value="<?=$token?>">
                    <div class="form__login">
                        Введите логин: <input type="text" name="login">    
                    </div>
                    <div class="form__password">
                        Введите пароль: <input type="password" name="pass">
                    </div>
                    <div class="form__forget">
                        <a href="#">Забыли пароль?</a>
                    </div>
                    <button class="form__button">Войти</button>
                </form>
                <div class="login__footer">
                    CRM<span>(rab)</span>byte 2020 &copy; All rights reserved
                </div>
            </div>
        </div>
    </div>
</body>
</html>
