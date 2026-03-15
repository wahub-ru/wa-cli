<?php

return array(
    'name' => 'Поля покупателя в заказе',
    'description' => 'Плагин позволяет показывать всю информацию о покупателе на странице заказа, без необходимости заходить в редактирование заказа.',
    'img' => 'img/icon.png',
    'vendor' => 1005778,
    'version' => '1.0.3',
    'handlers' => array(
      'backend_order' => 'backend_order',
      'backend_order_print' => 'backendOrderPrint',
    )
);
