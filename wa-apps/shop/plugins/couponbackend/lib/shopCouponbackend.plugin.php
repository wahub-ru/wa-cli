<?php

class shopCouponbackendPlugin extends shopPlugin
{
	public function orderCalculateDiscount($params) {
		if (wa()->getEnv() === 'backend') {
			return shopCouponbackendPluginDiscount::backendCoupon($params['order'], $params['contact'], $params['apply']);
		}
	}

	public function backendOrderEdit($order) {
		$edit 			= $this->getSettings('hide_edit', 0)? 1 : 0;
		$codeEnable = (empty($order['id']) or !$edit)? 1 : 0;
		if (empty($order['id']) or !$edit) {
			$discount = $this->getSettings("discount_disabled", 0)? 1 : 0;
			$pathJs 	= wa()->getAppStaticUrl('shop') . 'plugins/couponbackend/js/promocode.js';
			$lang 		= array(
				'loading' 		=> _wp('Loading promocode'),
				'apply' 			=> _wp('Apply'),
				'placeholder' => _wp('Enter coupon code'),
			);
return <<<HTML
<style>
table.zebra .no_edit_discount {
	display: 		none!important;
	visibility: hidden!important;
	width: 			0px!important;
	min-width: 	0px!important;
	height: 		0px!important;
	min-height: 0px!important;
	padding: 		0px!important;
	margin: 		0px!important;
}
</style>
<script>
	var codeEnable = "{$codeEnable}";
	var discountHide = "{$discount}";
	var langCouponbackend = {load: "{$lang['loading']}", apply: "{$lang['apply']}", placeholder: "{$lang['placeholder']}"}
</script>
<script src="{$pathJs}"></script>
HTML;
		}
	}
}
