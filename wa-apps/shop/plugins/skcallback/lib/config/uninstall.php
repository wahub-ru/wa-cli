<?php

/*Удаляем таблицы плагина*/
$model = new waModel();
$model->query('DROP TABLE IF EXISTS `shop_skcallback_defines`');
$model->query('DROP TABLE IF EXISTS `shop_skcallback_controls_type`');
$model->query('DROP TABLE IF EXISTS `shop_skcallback_controls`');
$model->query('DROP TABLE IF EXISTS `shop_skcallback_requests`');
$model->query('DROP TABLE IF EXISTS `shop_skcallback_values`');
$model->query('DROP TABLE IF EXISTS `shop_skcallback_cart`');
$model->query('DROP TABLE IF EXISTS `shop_skcallback_status`');