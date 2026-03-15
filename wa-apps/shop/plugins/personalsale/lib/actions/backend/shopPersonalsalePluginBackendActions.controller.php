<?php

class shopPersonalsalePluginBackendActionsController extends waJsonController
{
    public function execute() {
		  $model = new waModel();
		  $plugin = wa()->getPlugin('personalsale');
      $type = waRequest::get('type');
  		switch($type) {
  			case 'status':
  				$status = ((int) waRequest::get('status') == 1)? 1 : 0;
  				$plugin_id = array('shop', 'personalsale');
  				$app_settings_model = new waAppSettingsModel();
  				$app_settings_model->set($plugin_id, 'status', $status);
  			break;
  			case 'search':
  				$query = waRequest::get('query');
  				$q = $model->escape($query);
  				$users = $model->query("SELECT `P`.`percent`, `C`.`id`, `C`.`name` FROM `wa_contact` AS `C` LEFT JOIN `wa_contact_emails` AS `CE` ON `C`.`id` = `CE`.`contact_id` LEFT JOIN `shop_personalsale_plugin` AS `P` ON `C`.`id` = `P`.`contact_id` WHERE `C`.`name` LIKE '%{$q}%' or `CE`.`email` LIKE '%{$q}%' GROUP BY `C`.`id` LIMIT 15")->fetchAll();
  				$result = array();
  				if (is_array($users)) {
  					foreach ($users as $user) {
  						$result[] = array('id' => $user['id'], 'value' => $user['name'], 'percent' => ((is_numeric($user['percent']))? $user['percent'] : 0));
  					}
  				}
  				$this->response = $result;
  			break;
  			case 'getPercent':
  				$contact_id = waRequest::post('contact_id');
  				if (is_numeric($contact_id)) {
  					$percent = $model->query("SELECT `percent` FROM `shop_personalsale_plugin` WHERE `contact_id` = i:cid", array('cid' => $contact_id))->fetch();
  					if (isset($percent['percent']) and is_numeric($percent['percent'])) {
  						$this->response = $percent['percent'];
  					}
  					else {
  						$this->response = '0';
  					}
  				}
  			break;
  			case 'save':
  				$contact_id = waRequest::post('contact_id');
  				$percent = waRequest::post('percent');
          $percent = (!is_numeric($percent) or $percent > 100 or $percent < 0)? 0 : $percent;
  				if (is_numeric($contact_id)) {
  					if ($percent == 0) {
  						if (!$model->query("DELETE FROM `shop_personalsale_plugin` WHERE `contact_id` = i:cid", array('cid' => $contact_id))) {
  							$this->errors = "Ошибка записи";
  						}
  					}
  					elseif (is_numeric($percent)) {
  						$users = $model->query("SELECT `id` FROM `shop_personalsale_plugin` WHERE `contact_id`=i:id LIMIT 1", array('id' => $contact_id))->fetch();
  						if (!isset($users['id'])) {
  							if (!$model->query("INSERT INTO `shop_personalsale_plugin` (`contact_id`, `percent`) VALUES (i:cid, f:percent)", array('cid' => $contact_id, 'percent' => $percent))) {
  								$this->errors = "Ошибка записи";
  							}
  						}
  						else {
  							if (!$model->query("UPDATE `shop_personalsale_plugin` SET `percent` = f:percent WHERE `contact_id` = i:cid", array('cid' => $contact_id, 'percent' => $percent))) {
  								$this->errors = "Ошибка записи";
  							}
  						}
  					}
  				}
          else {
            $this->errors = "Контакт не найден";
          }
  			break;
  		}
    }
}
