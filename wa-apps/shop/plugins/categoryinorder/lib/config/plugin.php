<?php

return array(
    'name' => 'Количество товаров в заказе по категориям',
    'description' => 'Плагин выводит информацию в заказ сколько товаров из каких категорий учасвуют в заказе',
    'img' => 'img/icon.png',
    'vendor' => '1005778',
    'version' => '1.0.0',
    'handlers' => array(
      'backend_order' => 'backend_order',
      'backend_order_print' => 'backend_order'
    )
);
