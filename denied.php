<?php
if (isset($_GET['error']) and $_GET['error'] == 'access_denied') {
    echo 'Доступ ограничен!';
} else {
    echo 'Произошла ошибка!';
}
