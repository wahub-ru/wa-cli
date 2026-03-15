<?php
return array(
	'shop_ip_custom_city' => array(
		'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
		'country_iso3' => array('varchar', 3, 'null' => 0),
		'region_code' => array('varchar', 8, 'null' => 0),
		'name' => array('varchar', 255, 'null' => 0),
		':keys' => array(
			'PRIMARY' => 'id',
		),
	),
	'shop_ip_kladr_api_city' => array(
		'id' => array('varchar', 13, 'null' => 0),
		'region_id' => array('varchar', 13, 'null' => 0),
		'name' => array('varchar', 255),
		'zip' => array('varchar', 255),
		'type' => array('varchar', 255),
		'type_short' => array('varchar', 255),
		'okato' => array('varchar', 255),
		'content_type' => array('varchar', 255),
		':keys' => array(
			'PRIMARY' => 'id',
		),
	),
	'shop_ip_kladr_api_region' => array(
		'id' => array('varchar', 13, 'null' => 0),
		'name' => array('varchar', 255),
		'zip' => array('varchar', 255),
		'type' => array('varchar', 255),
		'type_short' => array('varchar', 255),
		'okato' => array('varchar', 255),
		'content_type' => array('varchar', 255),
		':keys' => array(
			'PRIMARY' => 'id',
		),
	),
);
