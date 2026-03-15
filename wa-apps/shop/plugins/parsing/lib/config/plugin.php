<?php

return array(
    'name' => 'Парсинг',
    'description' => 'Парсинг сайтов',
    'vendor' => '1037432',
    'version' => '1.4.1',
    'shop_settings' => false,
    'importexport'   => 'profiles',
    'export_profile' => true,
    'img' => 'img/parsing.png',
    'frontend' => false,
    'handlers' => array(
        'backend_product' => 'backendProduct',
		'backend_product_edit' => 'backendProductEdit',
		'product_save' => 'productSave',
    ),
);
