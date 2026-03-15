<?php

class shopContactorderviewPlugin extends shopPlugin
{
	private $print = false;

	public function backend_order($params) {
		$fields = (class_exists("shopBackendCustomerForm"))? $this->getFieldsNewWebasyst($params['id']) : $this->getFieldsOldWebasyst($params['contact_id']);

		$require = ($this->getSettings('view_rq') == 1)? true : false;
		$hideNoValue = ($this->getSettings('hide_no_value') == 1)? true : false;
		$hideDefaultFields = ($this->getSettings('hide_default_fields') == 1)? true : false;
		$showDefaultFields = ($this->getSettings('show_default_fields') == 1)? true : false;
		$defaultFields = array('jobtitle','company','url','birthday','title','sex','locale','timezone','company_contact_id','socialnetwork','about');
		$defaultFieldsHide = array('firstname','lastname','phone','email','name','middlename','im');
		if (is_array($fields)) {
			$htmlTitle = '<h3><span class="gray">Данные покупателя</span>' . ((!$this->print)? ' <a href="?action=plugins#/contactorderview/" title="Перейти к настройке"><i class="icon10 settings"></i></a>' : '') . '</h3>';
			$html = '';
			foreach($fields as $fid => $field) {
				if (($require and $field['required']) or !$require) {
					if (($hideDefaultFields and array_search($fid, $defaultFields) === false) || !$hideDefaultFields) {
						if ($showDefaultFields or array_search($fid, $defaultFieldsHide) === false) {
							if (is_array($field['value'])) {
								$group = array();
								foreach($field['value'] as $v) {
									if (isset($v['value'])) {
										$group[] = $v['value'];
									}
								}
								$val = implode(', ', $group);
							}
							else {
								$val = $field['value'];
							}
							if (($hideNoValue and $val != "") or !$hideNoValue) {
								$html .= '<div style="margin-bottom:6px;"><span style="color:#787878">'.$field['name'].':</span> '.(($val != "")? htmlentities($val) : '—').'</div>';
							}
						}
					}
				}
			}
		}
$script = <<<HTML
<script>
	$(function(){
		$(".field_ds").remove();
		$("#s-order .details").after('<div class="field_ds"></div>');
		$(".field_ds").html($(".dop_show_fields"));
		$(".dop_show_fields").css({marginTop:'10px'});
	});
</script>
HTML;
		return array('info_section' => ($html != '')? '<div class="dop_show_fields" style="margin-bottom:10px;">'.$htmlTitle.$html.'</div>'.(($this->print)? $script : '') : '');
	}

	public function backendOrderPrint($params) {
		$hidePrint = ($this->getSettings('hide_print') == 1)? true : false;
		if (!$hidePrint) {
			$this->print = true;
		  return $this->backend_order($params);
		}
		return array();
	}

	protected function getFieldsNewWebasyst($orderID) {
		$form = new shopContactorderviewFields();
		$form->setAddressDisplayType('first');
		$order = new shopOrder($orderID, array(
				'customer_form' => $form
		));
		return $order->customerForm()->getData();
	}

	protected function getFieldsOldWebasyst($contactID) {
		$contact = new waContact($contactID);
		$fields = shopContactorderviewFieldsOld::getContactFields();
		$outFields = array();
		if (is_array($fields)) {
			foreach($fields as $fid => $field) {
				$val = $contact->get($fid);
				if (is_array($val)) {
					if (isset($val['value'])) {
						$val = $val['value'];
					}
					else {
						$group = array();
						foreach($val as $v) {
							if (isset($v['value'])) {
								$group[] = $v['value'];
							}
						}
						$val = implode(', ', $group);
					}
				}
				$outFields[$fid] = array(
					'required' => $field['required'],
					'name' => $field['localized_names'],
					'value' => $val,
				);
			}
		}
		return $outFields;
	}
}
