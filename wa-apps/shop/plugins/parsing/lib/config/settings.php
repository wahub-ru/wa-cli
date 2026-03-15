<?php
return array(
    'name' => array(
        'title' => 'Наименование профиля',
        'class'=>'blog_hh shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'mode' => array(
        'title' => 'Режим обработки',
        'class'=>'shop blog_hh',
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            'shop' => 'Магазин. Товары',
        ),
    ),    
    'shop_sitemap_url' => array(
        'title' => 'Sitemap URL, в формате http://sitename.ru/sitemap.xml',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_product_url_target' => array(
        'title' => 'Постоянная часть URL товара. Например /product/',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
	'shop_collect_href' => array(
        'title' => 'Собирать внутренние ссылки',
        'class'=>'shop',
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            0 => 'Нет',
			1 => 'Да',
        ),
    ),
	'shop_force_skip' => array(
        'title' => 'Продолжить парсинг если сервер не отвечает',
        'class'=>'shop',
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            0 => 'Нет',
			1 => 'Да',
        ),
    ),
	'shop_stop_duplicate' => array(
        'title' => 'Проверять на дубли по наименованию',
        'class'=>'shop',
		'value'=>0,
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            0 => 'Нет',
			1 => 'Да',
        ),
    ),
	'shop_type_id' => array(
        'title' => 'Тип товара по умолчанию',
        'class'=>'shop',
        'control_type' => waHtmlControl::SELECT,
        'options_callback' => array('shopParsingPlugin', 'getShopTypes'),
    ),
    'shop_category_name' => array(
        'title' => 'Наименование необходимых категорий, каждая с новой строки',
        'class'=>'shop',
        'control_type' => waHtmlControl::TEXTAREA,
    ),
    'shop_name_tag' => array(
        'title' => 'Tag Наименования. Например h1',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
	'shop_desc_tag' => array(
        'title' => 'Tag Описания. Например div',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_desc_attr' => array(
        'title' => 'Tag Описания содержит Атрибут=Значение. Например class=description',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_sku_tag' => array(
        'title' => 'Tag Артикула. Например span',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_sku_attr' => array(
        'title' => 'Tag Артикула содержит Атрибут=Значение. Например class=article',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_sku_attr_sku' => array(
        'title' => 'Укажите атрибут если Атрибут=Артикул. Например data-article',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_category_tag' => array(
        'title' => 'Tag Категории. Импорт категорий происходит согласно схеме https://schema.org/BreadcrumbList. Например ul',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_category_attr' => array(
        'title' => 'Tag Категории. Укажите атрибут если Атрибут=Значение. Например itemtype=https://schema.org/BreadcrumbList',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_price_tag' => array(
        'title' => 'Tag Цены. Например div',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_price_attr' => array(
        'title' => 'Tag Цены содержит Атрибут=Значение. Например class=price',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_price_attr_price' => array(
        'title' => 'Укажите атрибут если Атрибут=Цена. Например data-price',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_price_add' => array(
        'title' => 'Наценка, в %',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_img_tag' => array(
        'title' => 'Tag Картинки. Например img',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_img_attr' => array(
        'title' => 'Tag Картинки содержит Атрибут=Значение. Например class=lightbox',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_img_attr_img' => array(
        'title' => 'Укажите атрибут если Атрибут=url картинки. Например src',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_img_prefix' => array(
        'title' => 'Укажите префикс для url картинки если нужно. Например http://sitename.ru',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_all_features_tag' => array(
        'title' => 'Tag в котором находятся все характеристики. Например table',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_all_features_name_attr' => array(
        'title' => 'Атрибут=значение в котором находятся все характеристики. Например class=custom',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_features_name_tag' => array(
        'title' => 'Tag в котором находятся наименования характеристик.',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_features_value_tag' => array(
        'title' => 'Tag в котором находятся значения характеристик.',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_features_name_attr' => array(
        'title' => 'Атрибут=значение в котором находятся наименование характеристики. Например class=names',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_features_value_attr' => array(
        'title' => 'Атрибут=значение в котором находятся значение характеристики. Например class=values',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    'shop_proxy' => array(
        'title' => 'Заполните поле прокси-сервер, если хотите использовать запросы через прокси-сервера. Каждый прокси(ip:порт) с новой строки.',
        'class'=>'shop',
        'control_type' => waHtmlControl::TEXTAREA,
    ),
    'shop_step' => array(
        'title' => 'Количество потоков за 1 запрос',
        'class'=>'shop',
        'control_type' => waHtmlControl::INPUT,
    ),
    /*
	'hide_not_availible_goods' => array(
        'title' => 'Скрывать товары не в наличии',
        
        'value' => false,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    */
);
