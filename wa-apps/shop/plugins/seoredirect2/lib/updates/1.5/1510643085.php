<?php

$model = new waModel();
$model->query("
create table if not exists shop_seoredirect2_errors_exclude (
	error_id int(11) not null,
	primary key (error_id)
)
engine=MyISAM;
");