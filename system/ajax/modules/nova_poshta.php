<?php
include_once '../../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $id = abs(intval($_POST['plugin_id']));

    if (!$error) {
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['plugin_id']) and is_numeric($_GET['plugin_id'])) {
    $plugin_id = abs(intval($_GET['plugin_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
        
        $result = $db->query("SELECT COUNT(*) FROM `plugin` WHERE `id` = '" . $plugin_id . "'")->fetch_row();
        if ($result[0] > 0) {
            $success = 1;
            $plugin = $db->query("SELECT `name` FROM `plugin` WHERE `id` = '" . $plugin_id . "'")->fetch_assoc();
            $title = ['name' => protection($plugin['name'], 'display')];
        } else {
            $error = 'Неизвестный модуль!';
            $title = ['name' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `plugin` WHERE `id` = '" . $plugin_id . "'")->fetch_row() and $result[0] > 0) {
        $plugin = $db->query("SELECT * FROM `colors` WHERE `client_id` = '" . $chief['id'] . "' AND `id` = '" . $plugin_id . "'")->fetch_assoc();
?>
<script>
    $(function(){
        let form = $('#plugin-nova-poshta'),
            btn = form.find('#button-save');
        
        function checkFields() {
            let error;
        
            if (error) {
                btn.addClass('disabled'); return error;
            } else {
                btn.removeClass('disabled'); return false;
            }
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
                    url: "/system/ajax/viewcolor.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            loadPlugins();
                            closeModalWindow(count_modal);
                        } else {
                            if (!$('.modal-window-content div').is('.error')) {
                                $('.modal-window-content').prepend('<div class="error"></div>');
                                $('.error').text(jsonData.error).show();
                            }
                        }
                        hideOptions(true);
                    }
                });
            }
            return false;
        });
    });
</script>
        <form id="plugin-nova-poshta" method="post">
            <input type="hidden" name="color_id" value="<?php echo protection($plugin['id'], 'int') ?>">
            <div class="modal-window-content__item">
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
