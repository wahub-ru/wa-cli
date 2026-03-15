<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version 1.1.0
 * @copyright (c) 2014-2021, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */
return array(
    'name'          => /*_wp*/('Attached Files'),
    'img'           => 'img/syrattach.png',
    'version'       => '2.1.0',
    'vendor'        => '670917',
    'shop_settings' => true,
    'handlers'      =>
        array(
            'backend_product'       => 'backendProduct',
            'frontend_product'      => 'frontendProduct',
            'product_delete'        => 'productDelete',
            'product_custom_fields' => 'productCustomFields',
            'product_save'          => 'productSave',
            'backend_prod'          => 'handlerBackendProd',
            'backend_prod_layout'   => 'handlerBackendProdLayout',
            'routing'               => 'routing'
        ),
);
