$(function(){
	if (discountHide == '1') {
		$("#discount").removeClass("short").addClass("no_edit_discount");
		$("#discount").before('<span id="dis-sale"></span>');
		$(".js-order-discount, #edit-discount, #update-discount").addClass("no_edit_discount");
		setInterval(function(){
			$("#dis-sale").html($("#discount").val());
		}, 300);
	}
	if (codeEnable == '1') {
		$("#s-orders-add-row").after('<tr class="white align-right" id="loading_promocode"><td colspan="5"><i class="icon16 loading"></i> '+langCouponbackend.load+'...</td></tr>');
		$.post("?plugin=couponbackend&action=apply", {type:"clear"}, function(res){
			$("#loading_promocode").remove();
			$("#s-orders-add-row").after('<tr class="white align-right"><td colspan="5"><br/><i style="margin-top:8px;display:none;" class="icon16 loading" id="loading_promocode_apply"></i> <input style="border:1px solid #ccc;padding:8px 15px;margin-right:10px;" type="text" placeholder="'+langCouponbackend.placeholder+'" name="promo_code" id="promo_code" ><button class="button blue" id="apply_promo_code">'+langCouponbackend.apply+'</button></td></tr>');
			$("#apply_promo_code").click(function(){
				$("#loading_promocode_apply").show();
				$.post("?plugin=couponbackend&action=apply", {type:"apply", code:$("#promo_code").val()}, function(res) {
					$("#loading_promocode_apply").hide();
					if (res.status == "ok") {
						if (res.data.valid) {
							$("#promo_code").css({border: "1px solid #ccc", color:"black"});
						}
						else {
							$("#promo_code").css({border: "solid 1px red", color:"red"});
						}
					}
					else {
						alert(res.errors);
					}
					$.order_edit.updateTotal();
					$("#update-discount").trigger("click");
				});
				return false;
			});
		});
	}
});
