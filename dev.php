<?php
include_once 'system/core/begin.php';
$data['title'] = 'Страница в разработке';
include_once 'system/core/header.php';
?>
            <!-- Content -->
            <section class="content">
                <h1>CRM v. <? echo $data['CRM_v']; ?> :: Страница в разработке</h1>
                <div class="content__overflow" style="text-align: center; heigth: 250px; padding: 50px 0; font-size: 30px">
                    Эта страница в разработке :-)
                </div>
                <!-- put div here without scroll -->
            </section>
<? 
include_once 'system/core/footer.php';

