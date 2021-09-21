<?php
include_once '../core/begin.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;

    $login = isset($_POST['login']) ? protection($_POST['login'], 'base') : null;
    $pass = isset($_POST['pass']) ? protection($_POST['pass'], 'base') : null;

    if (empty($pass)) {
        $error = 'Укажите пароль!';
    }

    if (empty($login)) {
        $error = 'Укажите имя пользователя!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `user` WHERE `login` = '" . protection($_POST['login'], 'base') . "'")->fetch_row() and $result[0] == 0) {
        $error = isset($error) ? $error : 'Неправильное имя пользователя или пароль!';
    }
    
    if (!isset($error)) {
        $user = $db->query("SELECT `id`, `password` FROM `user` WHERE `login` = '" . protection($_POST['login'], 'base') . "'")->fetch_assoc();
        if (password_verify($pass, $user['password'])) {
            // Авторизуем посетителя
            setcookie('user_id', $user['id'], time() + 86400, '/');
            setcookie('hash', $user['password'], time() + 86400, '/');
            $_SESSION['id_user'] = $user['id'];
            $success = 1;
        } else {
            $error = 'Неправильное имя пользователя или пароль!';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
