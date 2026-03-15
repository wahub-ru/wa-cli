<?php

class shopCategoryinorderPlugin extends shopPlugin
{
	public function backend_order($params) {
		$plugin = wa()->getPlugin('categoryinorder');
		$link = $plugin->getSettings('link');
		$categoryArray = array();
		$productInCategory = array();
		$outCategory = '<h3 class="gray">Количество товаров по категориям</h3>';
		if (count($params['items'])) {
			$model = new waModel();
			foreach($params['items'] as &$p) {
				$category = $model->query("SELECT `C`.`id`, `C`.`name`, `P`.`id` AS `product_id` FROM `shop_product` AS `P` LEFT JOIN `shop_category` AS `C` ON `P`.`category_id` = `C`.`id` WHERE `P`.`id` = i:id LIMIT 1", array(
					'id' => $p['product_id']
				))->fetch();
				if (isset($productInCategory[$category['id']])) {
					$productInCategory[$category['id']] += $p['quantity'];
				}
				else {
					$productInCategory[$category['id']] = $p['quantity'];
				}
				$categoryArray[$category['id']] = $category['name'];
			}
			$categoryArrUl = array();
			foreach($productInCategory as $cid => $quantity) {
				$categoryArrUl[] = '<li style="list-style:none;margin-bottom:10px;">
	        <span class="counters">
	            <span class="count" style="color:black;font-size:14px;">'.$quantity.' шт.</span>
	        </span>
        	<i class="icon16 folder"></i><'.(($cid == '' or $link)? 'span' : 'a target="_blank" href="?action=products#/products/category_id='.$cid.'"').' class="name">'.(($cid == '')? 'Без категории' : $categoryArray[$cid]).'</'.(($cid == '' or $link)? 'span' : 'a').'>
        </li>';
			}
			if (count($categoryArrUl) > 5) {
				$ul2 = array_chunk($categoryArrUl, ceil(count($categoryArrUl)/2));
				$outCategory .= '<ul style="padding-right:4.5%;border-right:1px solid #ccc;display:inline-block;max-width: 45%;width:100%;padding-left: 0px;">'.implode('',$ul2[0]).'</ul>';
				$outCategory .= '<ul style="margin-left:4.5%;display:inline-block;max-width: 45%;width:100%;padding-left: 0px;">'.implode('',$ul2[1]).'</ul>';
			}
			else {
				$outCategory .= '<ul style="max-width: 45%;width:100%;padding-left: 0px;">'.implode('',$categoryArrUl).'</ul>';
			}
		}
		return array(
			'info_section' => $outCategory
		);
	}
}
