<?php

$model = new waModel();

$sql_for_redirect_sort = "ALTER TABLE `shop_seoredirect2_redirect` ADD `sort` INT(11) NOT NULL DEFAULT 0 AFTER `status`";
$sql_for_redirect_type = "ALTER TABLE `shop_seoredirect2_redirect` ADD `type` INT(11) NOT NULL DEFAULT 0 AFTER `url_to`";
$sql_for_redirect_param = "ALTER TABLE `shop_seoredirect2_redirect` ADD `param` INT(11) NOT NULL DEFAULT 0 AFTER `url_to`";

try
{
	$model->exec($sql_for_redirect_sort);
}
catch (waException $e){}
try
{
	$model->exec($sql_for_redirect_type);
}
catch (waException $e){}
try
{
	$model->exec($sql_for_redirect_param);
}
catch (waException $e){}