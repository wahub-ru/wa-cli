<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version 1.3.0
 * @copyright Serge Rodovnichenko, 2017
 * @license MIT
 */
return array(
    'product_date' => array(
        'title'        => _wp('Product dates'),
        'description'  => _wp('Show date of creation and editing of product'),
        'control_type' => waHtmlControl::SELECT,
        'value'        => 'date',
        'options'      => array('no' => _wp('do not show'), 'date' => _wp('date'), 'datetime' => _wp('date and time'))
    ),
    'edit_history' => array(
        'title'        => _wp('Edit history'),
        'description'  => _wp('Show a product editing history in the separate tab at product view page in backend'),
        'control_type' => waHtmlControl::CHECKBOX,
        'value'        => '0'
    ),
    'add2cart'     => array(
        'title'        => 'URL добавления в корзину для Яндекс.Турбо',
        'description'  => 'Включить поддержку добавления в корзину для <a href="https://yandex.ru/support/webmaster/turbo/market.html" target="_blank">выгрузки YML в Яндекс.Турбо <i class=\"icon16 new-window\"</a>',
        'control_type' => waHtmlControl::CHECKBOX,
        'value'        => '0'
    )
);
