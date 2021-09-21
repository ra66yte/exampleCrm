<?php
?>
<script>
    function confirmExit() {
        let count_modal = $('.modal-window-wrapper').length;
        startPreloader('body');
        playSound('SOUND-LOGOFF');
        closeModalWindow(count_modal);
        setTimeout(() => {
            window.location.href = '/logout.php';
        }, 2000);
    }
</script>
        Вы действительно хотите выйти?
        <div class="buttons">
            <a href="javascript:void(0);" class="buttons__link" onclick="confirmExit();">Да, именно</a> <button class="btn-cancel">Нет</button>
        </div>