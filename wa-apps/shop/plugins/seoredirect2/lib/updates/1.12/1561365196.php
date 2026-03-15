<?php

$model = new waModel();

try
{
	$model->exec('
ALTER TABLE `shop_seoredirect2_shop_urls`
	ADD INDEX `id_type` (`id`, `type`),
	ADD INDEX `type_parent_id` (`type`, `parent_id`);
');
}
catch (Exception $e)
{
}
