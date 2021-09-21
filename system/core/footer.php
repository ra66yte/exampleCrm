<?php
if (isset($user)) {
    if (!isset($hide_pagination)) {
        $rows = array(10, 25, 50, 100, 200, 300, 400, 500);
        ?>      
                        <!-- put div here without scroll -->
                        <div class="content__end">
                            <div class="pagination">
                                <div class="pagination__info">
                                </div>
<?
        if (!isset($hide_rows_list)) {
?>
                            <div class="pagination__select">
                                Отображать по: <select id="select-rows" onchange="ChangeShowMaxRows(this);">
<?
        foreach ($rows as $value) {
?>
                                    <option<?=(($user['max_rows'] == $value) ? ' selected' : '')?> value="<?=$value?>"><?=$value?></option> 
<?
        }
?> 
                                </select>
                            </div>
<?
        }
?>
                            </div>
                        </div>
<?
    }
?>  
                    </section>
                </div>
                <!-- Footer -->
                <footer class="footer">
                    <div class="footer__copyright">CRM v. <?php echo $data['CRM_v'] ?> by rabbyte (2020)</div>
                    <span id="ws" class="websocket">Ws: <s><i class="fa fa-spinner fa-spin"></i></s></span>
                </footer>
            </div>
        
<? 
} else {
    // something for guest
}
?>
    <script src="/js/ws/index.js"></script>
</body>
</html>
<?
exit;
