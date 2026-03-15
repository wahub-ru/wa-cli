<?php

class shopPersonalsalePlugin extends shopPlugin
{
	public function backend_settings_discounts() {
		return array(
			array(
				'name'   => 'По покупателю',
				'id'     => 'personalsale_discount',
				'url'    => '?plugin=personalsale&module=backend&action=settings',
				'status' => $this->getSettings('status'),
			),
		);
	}

	public function order_calculate_discount($params) {
		//if ($params['apply']) {
			$model = new waModel();
			$contactID = $params['contact']['id'];
			$sale = $model->query("SELECT * FROM `shop_personalsale_plugin` WHERE `contact_id` = i:id LIMIT 1", array('id' => $contactID))->fetch();
			if (isset($sale['id'])) {
				return array(
					'discount'    => $params['order']['total'] * ($sale['percent'] / 100),
					'description' => 'Индивидуальная скидка покупателя '.$sale['percent'].'%',
				);
			}
		//}
		return false;
	}

	public function backend_order_edit($params)
    {
		$model = new waModel();
		$status = $this->getSettings('status');
		$users = $model->query("SELECT `percent` FROM `shop_personalsale_plugin` WHERE `contact_id` = '{$params['contact_id']}'")->fetch();
		$up = isset($users['percent'])? $users['percent'] : 0;
		$display = ($status)? 'block' : 'none';
$append = <<<HTML
$("#order-edit-form .sidebar").append('<div style="clear:both;display:{$display};" id="pers_sale"><div style="margin-bottom:8px;">Персональная скидка покупателя:</div><input style="padding:5px;margin-right:10px;" value="{$up}" id="percent_user" maxlength="3"> <sapn id="save_percent" class="button green" style="padding: 5px 15px;cursor:pointer;">Сохранить</sapn><div id="sale-form-status" style="display:none;margin-top:10px;"></div><br/><span style="margin-top:8px;color:#ccc;display:inline-block;font-size:14px;">Скидка указывается в процентах 0% - 100%</span></div>');
HTML;
        return <<<HTML
<script>
	var cid = '{$params['contact_id']}';
	var cidPercent = '{$up}';
	var subtotal = 0;
	$(function(){
		{$append}
		$("#save_percent").click(function(){
			$.post("?plugin=personalsale&action=actions&type=save", { contact_id:cid, percent:$("#percent_user").val() }, function(res){
				cidPercent = $("#percent_user").val();
				if (res.status == 'ok') {
					$("#sale-form-status").html('<i style="vertical-align:middle" class="icon16 yes"></i> Сохранено').show();
					$.order_edit.updateTotal();
				}
				else {
					$("#sale-form-status").html('<i style="vertical-align:middle" class="icon16 no"></i> '+res.errors).show();
				}
				setTimeout(function(){
					$("#sale-form-status").hide();
				}, 3000);
			});
			return false;
		});
		var customerID = $("#s-customer-id").val();
		function changeCustomerID() {
			customerID = $("#s-customer-id").val();
			if (customerID > 0) {
				cid = customerID;
				$.post("?plugin=personalsale&action=actions&type=getPercent", { contact_id:customerID }, function(res){
					if (res.data != "") {
						$("#pers_sale").show();
						$("#percent_user").val(res.data);
						cidPercent = res.data;
					}
				});
			}
			return false;
		}
		setInterval(function(){
			if (customerID != $("#s-customer-id").val()) {
				changeCustomerID();
			}
			if (subtotal != $("#subtotal").text() && cid != "" && cid > 0) {
				subtotal = $("#subtotal").text();
				$.order_edit.updateTotal();
				$("#update-discount").click();
			}
		}, 150);
    });
</script>
HTML;
	}

	public function backendCustomer($params) {
			$model = new waModel();
			$status = $this->getSettings('status');
			$users = $model->query("SELECT `percent` FROM `shop_personalsale_plugin` WHERE `contact_id` = '{$params['contact_id']}'")->fetch();
			$up = isset($users['percent'])? $users['percent'] : 0;
			$display = ($status)? 'block' : 'none';
$script = <<<HTML
<script>
			$(function(){
				$("#save_percent").click(function(){
					$.post("?plugin=personalsale&action=actions&type=save", { contact_id:{$params['contact_id']}, percent:$("#percent_user").val() }, function(res){
						if (res.status == 'ok') {
							$("#sale-form-status").html('<i style="vertical-align:middle" class="icon16 yes"></i> Сохранено').show();
						}
						else {
							$("#sale-form-status").html('<i style="vertical-align:middle" class="icon16 no"></i> '+res.errors).show();
						}
						setTimeout(function(){
							$("#sale-form-status").hide();
						}, 3000);
					});
					return false;
				});
			});
</script>
HTML;
	    return array(
	        'header' => '<div style="display:'.$display.';"><h2>Персональная скидка покупателя</h2><div id="pers_sale"><input style="padding:5px;margin-right:10px;" maxlength="3" value="'.$up.'" id="percent_user"> <sapn id="save_percent" class="button green" style="padding: 5px 15px;cursor:pointer;">Сохранить</sapn><div id="sale-form-status" style="display:none;margin-top:10px;"></div><br/><span style="margin-top:8px;color:#ccc;display:inline-block;font-size:14px;">Скидка указывается в процентах 0% - 100%</span></div><br/>
					<br/></div>'.$script,
	    );
	}
}
