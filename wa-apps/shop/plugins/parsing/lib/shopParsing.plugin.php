<?php

class shopParsingPlugin extends shopPlugin
{
	public function productSave($params)
	{	
		$shopParsingModel = new shopParsingModel();
		$item = $shopParsingModel->getByField('product_id', $params['data']['id']);
		
		if( isset($params['data']['parsing_url']) )
		{
			if(empty($params['data']['parsing_url'])){
				$shopParsingModel->deleteByField('product_id', $params['data']['id']);
			}else{
				
				if(empty($item)){
					$shopParsingModel->insert(array(
						'product_id' => $params['data']['id'],
						'url' => $params['data']['parsing_url'],
						'status' => 1,
						'parsing' => 0,
						'profile_id' => $params['data']['parsing_profile_id']
					), 1);
				}else{
					$shopParsingModel->updateByField('product_id', $params['data']['id'], [
                        'url' => $params['data']['parsing_url'], 
                        'profile_id' => $params['data']['parsing_profile_id']
                    ]);
				}
			}
		}
		
	}
	
	public function backendProductEdit($product)
    {
        $profile_helper = new shopImportexportHelper('parsing');
        $profiles = $profile_helper->getList();                        
		$shopParsingModel = new shopParsingModel();
		$item = $shopParsingModel->getByField('product_id', $product['id']);
        
		if(!empty($item) AND !empty($item['url'])){
			$product_link = $item['url'];
            $selected_profile = $item['profile_id'];                        
		}else{
			$product_link = '';
            $selected_profile = '';            
		}
        
        $select = '<select name="product[parsing_profile_id]"><option value="">Нет</option>';
        foreach($profiles as $p_key => $p_arr){
            if($p_key == $selected_profile){
                $selected = 'selected';
            }else{
                $selected = '';
            }
            $select .= '<option value="'.$p_key.'" '.$selected.'>'.$p_arr['name'].'</option>';
        }
        $select .= '</select>';
		
		$html = '
			<div class="field">
				<div class="name"><b>Ссылка на источник для Парсинга</b></div>
				<div class="value no-shift">
					<input class="long bold s-product-ww-input" type="text" name="product[parsing_url]" value="'.$product_link.'" />
                    '.$select.' 
				</div>
			</div>
		';
		
        return ['basics' => $html];

    }
	
	public static function getShopTypes()
	{
		$shopTypeModel = new shopTypeModel();
		$types = $shopTypeModel->getAll();
		
		if(!empty($types))
		{
			$arr = array();
			
			foreach($types as $t)
			{
				$arr[] = array(
					'value' => $t['id'],
					'title' => $t['name'],
					'description' => ''
				);
			}
			
			return $arr;
		}
	}
	
    public function backendProduct($product){
        $mysql = new waModel();
        $table = $mysql->query("SELECT id, status FROM shop_parsing_plugin_sitemap WHERE product_id = '".$mysql->escape($product['id'])."' ");
        
        if($table->count()){
            $row = $table->fetchAssoc();
            $row['status']? $checked = "checked=''" : $checked = "";
            
            return array( 'edit_basics' => '
                <div class="field">
                    <div class="name">Статус обновления через плагин "Парсинг"</span></div>
                    <div class="value no-shift" data-type="1">
                        <label><input type="checkbox" data-parsing-plugin-id="'.$mysql->escape($row['id']).'" id="checkbox-parsing-plugin" '.$checked.' > Обновлять</label>
                    </div>
                </div>
                <script>
                    $("#checkbox-parsing-plugin").click(function(){
                        $.post("?app=shop&plugin=parsing&action=status&id="+$(this).data("parsing-plugin-id")+"&status="+$("#checkbox-parsing-plugin").is(":checked"), function(response) {
                            console.log(response);
                        });
                    });
                </script>
            ' );
        }
    }
    
    public static function getBlogsOptions(){
        $mysql = new waModel();
        @$arr[0] = array('title' => 'Нет', 'value' => 0);
        $tmp = $mysql->query("SELECT id as value, name as title FROM blog_blog")->fetchAll();
        
        if(!empty($tmp)){
            foreach($tmp as $t){
                $arr[] = $t;
            }
        }
        
        return $arr;
    }
}