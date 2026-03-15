<?php

$model = new waModel();
try {
    $sql = 'SELECT `thumbpage` FROM `blog_page` WHERE 0';
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = 'ALTER TABLE  `blog_page` ADD  `thumbpage` varchar(255) DEFAULT NULL ';
    $model->query($sql);
}