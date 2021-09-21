<?php
include_once '../core/begin.php';

if (isset($_GET['mog_id']) and is_numeric($_GET['mog_id'])) {
    $mog_id = abs(intval($_GET['mog_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `movement_of_goods` WHERE `id` = '" . $mog_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $mog = $db->query("SELECT `id`, `date_added` FROM `movement_of_goods` WHERE `id` = '" . $mog_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['mog_id' => protection($mog['id'], 'display'), 'mog_date_added' => protection(view_time($mog['date_added']), 'display')];
        } else {
            $error = 'Неизвестное движкение товаров!';
            $title = ['mog_id' => 'UNDEFINED', 'mog_date_added' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `movement_of_goods` WHERE `id` = '" . $mog_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $mog = $db->query("SELECT * FROM `movement_of_goods` WHERE `id` = '" . $mog_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>
<script>
    $(function(){
        let form = $('#view-woog'),
            btn = form.find('#button-view-woog');
        
        form.on('submit', function(e){
            let count_modal = $('.modal-window-wrapper').length;
            loadWOOG();
            closeModalWindow(count_modal);
            return false;
        });
    });
</script>
        <form id="view-woog" method="post">
            <input type="hidden" name="woog_id" value="<?=$mog['id'], 'int')?>">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Информация</div>
                <div class="buttons">
                    <button id="button-view-woog" name="woog-close">Закрыть</button>
                </div>
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
