<?php

$model = new waModel();
try {
    $sql = "SELECT `viewed` FROM `blog_post` WHERE 0";
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = "ALTER TABLE `blog_post` ADD `viewed` INT NOT NULL DEFAULT '0'";
    $model->query($sql);
}