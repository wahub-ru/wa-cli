<?php

$model = new waModel();
$model->query("
create table if not exists shop_ip_custom_city (
	id int(11) not null AUTO_INCREMENT,
	`country_iso3` varchar(3) not null,
	`region_code` varchar(8) not null,
	`name` varchar(255) not null,
	primary key (id)
)
collate=utf8_general_ci
engine=MyISAM
;
");
