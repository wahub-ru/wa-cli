<?php

return array(
    'name' => 'Персональная скидка покупателю',
    'description' => 'Плагин позволяет устанавливать индивидуальную скидку покупателю',
    'img' => 'img/icon.png',
    'vendor' => 1005778,
    'version' => '1.0.0',
    'handlers' => array(
  		'backend_settings_discounts' => 'backend_settings_discounts',
  		'order_calculate_discount' => 'order_calculate_discount',
  		'backend_order_edit' => 'backend_order_edit',
      'backend_customer' => 'backendCustomer',
  	)
);
