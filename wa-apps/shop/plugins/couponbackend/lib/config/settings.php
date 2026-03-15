<?php

return array(
	'discount_disabled' => array(
		'title'        => _wp('Disable the ability to edit the "Discount"'),
		'description'  => _wp('This option disables the ability to change the discount value when creating and editing an order.'),
		'control_type' => waHtmlControl::CHECKBOX,
    'value'        => 0,
	),
	'hide_edit' => array(
		'title'        => _wp('Disable coupons when editing'),
		'description'  => _wp('This option disables the plugin when editing an order.'),
		'control_type' => waHtmlControl::CHECKBOX,
    'value'        => 1,
	),
);
