<?php

$model = new waModel();
try {
    $model->exec("ALTER TABLE `blog_page` DROP `thumbpage`");
} catch (waDbException $e) {

}

