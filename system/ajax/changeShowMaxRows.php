<?php
include_once '../core/begin.php';
$success = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $max_rows = abs(intval($_POST['max_rows']));
    if ($max_rows != 10 and $max_rows != 25 and $max_rows != 50 and $max_rows != 100 and $max_rows != 200 and $max_rows != 300 and $max_rows != 400 and $max_rows != 500) $max_rows = 10;
    $db->query("UPDATE `user` SET `max_rows` = '" . $max_rows . "' WHERE `id` = '" . $user['id'] . "'");
    $success = 1;
}
echo json_encode(array('success' => $success));
