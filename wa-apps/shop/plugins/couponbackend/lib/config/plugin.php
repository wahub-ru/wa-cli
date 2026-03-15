<?php

return array(
  'name'        => _wp('Application of coupons in the backend'),
  'description' => _wp('The plugin allows you to apply discount coupons in the backend of the store when creating an order'),
  'img'         => 'img/icon.png',
  'vendor'      => '1005778',
  'version'     => '1.0.0',
  'handlers'    => array(
    'order_calculate_discount' => 'orderCalculateDiscount',
    'backend_order_edit'       => 'backendOrderEdit',
  )
);
