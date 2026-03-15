<?php

$model = new waModel();
try {
    $model->exec("ALTER TABLE `blog_post` DROP `viewed`");
} catch (waDbException $e) {
    
}

