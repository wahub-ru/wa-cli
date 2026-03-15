<?php

$model = new waModel();
$model->query("
create table if not exists shop_ip_kladr_api_city (
	id varchar(13) not null,
	region_id varchar(13) not null,
	name varchar(255) null default null,
	zip varchar(255) null default null,
	type varchar(255) null default null,
	type_short varchar(255) null default null,
	okato varchar(255) null default null,
	content_type varchar(255) null default null,
	primary key (id)
)
collate=utf8_general_ci
engine=MyISAM
;
");
$model->query("
create table if not exists shop_ip_kladr_api_region (
	id varchar(13) not null,
	name varchar(255) null default null,
	zip varchar(255) null default null,
	type varchar(255) null default null,
	type_short varchar(255) null default null,
	okato varchar(255) null default null,
	content_type varchar(255) null default null,
	primary key (id)
)
collate=utf8_general_ci
engine=MyISAM
row_format=dynamic
;
");