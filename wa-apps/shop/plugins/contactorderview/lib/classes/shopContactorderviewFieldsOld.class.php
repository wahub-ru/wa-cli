<?php

class shopContactorderviewFieldsOld extends waContactNameField {
  public static function getContactFields() {
		$checkoutFields = self::getCheckoutFields();
		$contactFields = waContactFields::getAll();
		$outFields = array();
		if (is_array($checkoutFields)) {
			foreach($checkoutFields as $fieldID => $value) {
				if ($fieldID != 'address' and  $fieldID != 'address.shipping') {
					$outFields[$fieldID] = $value;
					if (empty($outFields[$fieldID]['localized_names']) and isset($contactFields[$fieldID]->name)) {
						$name = (isset($contactFields[$fieldID]->name['ru_RU']))? $contactFields[$fieldID]->name['ru_RU'] : ((isset($contactFields[$fieldID]->name['en_US']))? $contactFields[$fieldID]->name['en_US'] : '');
						$outFields[$fieldID]['localized_names'] = $name;
					}
				}
			}
		}
		return $outFields;
	}

	protected static function getCheckoutFields() {
		$checkoutFields = wa('shop')->getConfig()->getCheckoutSettings();
		if (!isset($checkoutFields['contactinfo'])) {
			$checkoutFields = wa('shop')->getConfig()->getCheckoutSettings(true);
		}
		return $checkoutFields['contactinfo']['fields'];
	}
}
