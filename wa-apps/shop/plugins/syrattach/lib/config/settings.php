<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version 1.0.0
 * @copyright (c) 2014-2021, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */
return array(
    'frontend_product_hook' => array(
        'title'        => _wp('Hook'),
        'description'  => _wp('Hook name to display filelist. Each hook placement depends of theme design. <a href="http://www.webasyst.com/developers/docs/plugins/hooks/shop/frontend_product/" target="_blank">More info about hooks.</a>'),
        'value'        => '0',
        'control_type' => waHtmlControl::SELECT,
        'options'      => [
            ['value' => '0', 'title' => _wp('No. Disable hooks')],
            ['value' => 'block', 'title' => 'frontend_product.block'],
            ['value' => 'block_aux', 'title' => 'frontend_product.block_aux'],
        ]
    ),
    'no_template'           => [
        'title'        => _wp('Storefront without template'),
        'description'  => _wp('If the design theme used in the storefront does not have a template, the plugin can show the default template or switch self off'),
        'control_type' => waHtmlControl::RADIOGROUP,
        'value'        => 'default',
        'options'      => [
            ['value' => 'default', 'title' => _wp('Show default template')],
            ['value' => 'off', 'title' => _wp('Switch off')]
        ]
    ],
    'template'              => array(
        'title'        => _wp('Template'),
        'description'  => _wp("Template to display at the hook position. HTML+Smarty"),
        'control_type' => waHtmlControl::CUSTOM . ' shopSyrattachPlugin::templateControl'
    )
);
