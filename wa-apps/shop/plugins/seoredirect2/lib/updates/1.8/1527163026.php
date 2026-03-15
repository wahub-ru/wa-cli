<?php

try
{
	$model = new waModel();
	$model->exec("
alter table shop_seoredirect2_shop_urls
	change column type type tinyint not null after id,
	change column url url varchar(255) not null after type,
	add index id (id, type, url, parent_id);
");
}
catch (Exception $ignored)
{

}