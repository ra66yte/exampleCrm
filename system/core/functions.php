<?php
// Фильтр
function protection($string, $type) {
    global $db;
	if (empty($type)) $type = 'int';
	if ($type == 'int') { // Число
		$string = abs(intval($string));
    } elseif ($type == 'base') { // Запрос в бд
        $string = $db->escape_string(trim($string));
	} elseif ($type == 'display') { // Вывод юзеру
		$string = htmlentities(stripslashes($string), ENT_QUOTES, 'UTF-8');
    } else {
		$string = abs(intval($string));
	}
    return $string;
}
// Склонение
function plural_form($number, $after, $return = true) { // array('1 минуту', '2 минуты', '10 минут')
	$cases = array (2, 0, 1, 1, 1, 2);
	return ($return == true ? $number . ' '  : '') . $after[($number % 100 > 4 and $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}
// Дата просто
function view_time($a) {
	if (empty($a)) return '';
	$tm = date('H:i:s', $a);
    $d = date('d', $a);
    $m = date('m', $a);
	$y = date('Y', $a);
	return $d . '/' . $m . '/' . $y . ' <small>в ' . $tm . '</small>';
}
// Красивая дата
function passed_time($a) {
    $time = time();
    $tm = date('H:i', $a);
    $d = date('d', $a);
    $m = date('m', $a);
    $y = date('Y', $a);
    $last = round(($time - $a) / 60);
	$seconds = round(($time - $a));
	// if ($seconds == 0) $seconds = 1;
    if ($seconds <= 59) return plural_form($seconds, array('секунду', 'секунды', 'секунд')) . " назад";
    if ($last <= 59 ) return plural_form($last, array('минуту', 'минуты', 'минут')) . " назад";
    elseif($d.$m.$y == date('dmY', $time)) return "Сегодня в $tm";
    elseif($d.$m.$y == date('dmY', strtotime('-1 day'))) return "Вчера в $tm";
    elseif($y == date('Y', $time)) return "$d/$m в $tm";
    else return "$d/$m/$y в $tm";
}
// Навигация 
// Выдает текущую страницу
function page($k_page = 1) { 
	$page = 1;
	if (isset($_GET['page']))
	{
		
		if ($_GET['page'] == 'end') {
			$page = intval($k_page);
		} else {
			$page = intval($_GET['page']);
		}
		
	}

	if ($page < 1) $page = 1;

	if ($page > $k_page)
		$page = $k_page;
	return $page;
}

// Высчитывает количество страниц
function k_page($k_post = 0, $k_p_str = 10) { 
	if ($k_post != 0) {
		$v_pages = ceil($k_post / $k_p_str);
		return $v_pages;
	} else return 1;
}

// Вывод номеров страниц (только на первый взгляд кажется сложно ;))
function str($link = '?', $k_page = 1, $page = 1, $datalink = array()) {
	global $user;
	if ($page == $k_page) {
		$orders = $datalink['count_rows'];
	} else $orders =  $user['max_rows'] * $page;
	$minus = ($orders == 0) ? 1 : 0;

	$pagination = '';
	
	$pagination .= '<div>Результат: с <span id="pagination-start">' . (($page * $user['max_rows']) - ($user['max_rows'] - 1) - $minus) . '</span> по <span id="pagination-now">' . $orders . '</span> / <b id="pagination-total">' . $datalink['count_rows'] . '</b></div>';

	if ($page < 1) $page = 1;
	$pagination .= '<div class="pagination__info-buttons">';
	if ($page != 1) $pagination .= '<button onclick="Navigation(\'1\');"><i class="fa fa-fast-backward"></i></button>';
	else $pagination .= '<button disabled><i class="fa fa-fast-backward"></i></button>';

	if ($page != 1)
		$pagination .= '<button onclick="Navigation(\'' . ($page - 1) . '\');"><i class="fa fa-step-backward"></i></button>';
		else $pagination .= '<button disabled><i class="fa fa-step-backward"></i></button>';

		$pagination .= '<div style="display: inline-block; padding: 0 10px;">';

	for ($ot = -3; $ot <= 3; $ot++)
	{
		if ($page + $ot > 0 && $page + $ot <= $k_page)
		{
			if ($ot != 0)
			$pagination .= '<button class="pagination__pc" onclick="Navigation(\'' . ($page + $ot) . '\');">' . ($page + $ot) . '</button>';
			else 
			$pagination .= '<button disabled>' . ($page + $ot) . '</button>';
		}
	}
	
	$pagination .= '</div>';
	if ($page != $k_page)
	$pagination .= '<button onclick="Navigation(\'' . ($page + 1) . '\');"><i class="fa fa-step-forward"></i></button>';
	
	
		if ($page == $k_page) $pagination .= '<button disabled><i class="fa fa-step-forward"></i></button>';
		
	if ($page != $k_page)
	$pagination .= '<button onclick="Navigation(\'' . $k_page . '\');"><i class="fa fa-fast-forward"></i></button>';
		else $pagination .= '<button disabled><i class="fa fa-fast-forward"></i></button>';

		$pagination .= '</div>';
	return $pagination;
}

// Определяем IP
function getIp() {
	$ip = filter_input(INPUT_SERVER, 'HTTP_CLIENT_IP', FILTER_VALIDATE_IP)
    ?: filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_VALIDATE_IP)
    ?: $_SERVER['REMOTE_ADDR']
	?? '0.0.0.0';
	return $ip;
}

// Перенаправление
function redirect($url, $timeout = null) {
	if (empty($url)) $url = '/index.php';
	if (!is_null($timeout) and is_numeric($timeout)) {
		header('Refresh: ' . abs(intval($timeout)) . '; ' . $url);
	} else {
		header('Location: ' . $url);
	}
	exit;
}

function checkAccess($access_name, $value = null) {
	global $db, $user, $chief;
	$access_name = protection($access_name, 'base');
	
	$group = $db->query("SELECT `group_id` FROM `staff` WHERE `employee_id` = '" . $user['id'] . "' AND `chief_id` = '" . $chief['id'] . "'")->fetch_assoc();
	if (!isset($group)) return false;

	$access = $db->query("SELECT `id` FROM `access_rights` WHERE `code_name` = 'access_to_" . $access_name . "'")->fetch_assoc();
	if (!isset($access)) return false;

	if (is_null($value)) {
		if ($db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['group_id'] . "' AND `access_right` = '" . $access['id'] . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows > 0) {
			return true;
		} else {
			return false;
		}
	} else {
		if ($db->query("SELECT `id` FROM `group_rights` WHERE `group_id` = '" . $group['group_id'] . "' AND `access_right` = '" . $access['id'] . "' AND `value` = '" . abs(intval($value)) . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows > 0) {
			return true;
		} else {
			return false;
		}
	}

}

function checkRight($right_name) {
	global $db, $user, $chief;

	if (!$right_name or $db->query("SELECT `id` FROM `staff_rights` WHERE `code_name` = '" . protection($right_name, 'display') . "'")->num_rows == 0) return false;
	$right = $db->query("SELECT `id` FROM `staff_rights` WHERE `code_name` = '" . protection($right_name, 'display') . "'")->fetch_assoc();
	if ($db->query("SELECT `id` FROM `employee_right` WHERE `employee_id` = '" . $user['id'] . "' AND `staff_right_id` = '" . $right['id'] . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows > 0) {
		return true;
	}
	return false;
}

function checkOffice($office) {
	global $db, $user, $chief;
	if (!$office) return false;
	/*
	if ($user['chief_id'] == 0 and $user['group_id'] == 1) { // Главный админ, все отделы видны
		return true;
	} else {
		$
	}
	*/
}

function getAccessID($name) {
	global $db;
	if (empty($name)) return false;
	$result = $db->query("SELECT `id` FROM `access_rights` WHERE `code_name` = 'access_to_" . protection($name, 'base') . "'")->fetch_assoc();
	if ($result) {
		return $result['id'];
	} else {
		return false;
	}
}

function isInstalledPlugin($plugin_id) {
	global $db, $chief;
	if (empty($plugin_id)) return false;
	$query = $db->query("SELECT COUNT(*) FROM `plugins` WHERE `plugin_id` = '" . protection($plugin_id, 'int') . "' AND `installed` = '1' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
	if ($query[0] > 0) {
		return true;
	} else {
		return false;
	}
}

function isActivatedPlugin($plugin_id) {
	global $db, $chief;
	if (empty($plugin_id)) return false;
	$query = $db->query("SELECT COUNT(*) FROM `plugins` WHERE `plugin_id` = '" . protection($plugin_id, 'int') . "' AND `installed` = '1' AND `status` = '1' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
	if ($query[0] > 0) {
		return true;
	} else {
		return false;
	}
}

function isActivatedCountry($country_id) {
	global $db, $chief;
	if (empty($country_id)) return false;
	$query = $db->query("SELECT COUNT(*) FROM `countries_list` WHERE `country_id` = '" . protection($country_id, 'int') . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
	if ($query[0] > 0) {
		return true;
	} else {
		return false;
	}
}

if (!function_exists('mb_ucfirst') && function_exists('mb_substr')) {
    function mb_ucfirst($string, $encoding) {
        $string = mb_strtoupper(mb_substr($string, 0, 1, $encoding), $encoding) . mb_substr($string, 1, null, $encoding);
        return $string;
    }
}
