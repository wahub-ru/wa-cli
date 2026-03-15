<?php

class shopParsingPluginBackendImportController extends waJsonController
{
    private static $mysql;
    private static $category_model;
    private static $category_product_model;
    private static $shopTypeFeaturesModel;
    private static $feature_model;
    private $plugin_id = 'parsing';
    
    public function execute()
    {
        try {
            stream_context_set_default( array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ),
            ));
            
            $plugin = wa('shop')->getPlugin('parsing');
            $profile_helper = new shopImportexportHelper($this->plugin_id);
            
            $profile = $profile_helper->getConfig(waRequest::get('profile_id'));
            $settings = $profile['config'];
            
            self::$mysql = new waModel();
            self::$category_model = new shopCategoryModel();
			self::$shopTypeFeaturesModel = new shopTypeFeaturesModel();
            self::$category_product_model = new shopCategoryProductsModel();
            self::$feature_model = new shopFeatureModel();
            $urls = array();
            
            if( isset($settings['shop_proxy']) AND !empty($settings['shop_proxy']) ){
                $proxy = explode("\r\n", $settings['shop_proxy']);
            }
            
            if( isset($settings['shop_step']) AND !empty($settings['shop_step']) ){
                $step = $settings['shop_step'];
            }else{
                $step = 10;
            }
            
            $sitemap = self::$mysql->query("SELECT * FROM shop_parsing_plugin_sitemap WHERE parsing = 0 AND profile_id = '".self::$mysql->escape(waRequest::get('profile_id'))."' ORDER BY RAND() LIMIT ".self::$mysql->escape($step));
            $i = 0;
            
            if($sitemap->count()){
                foreach($sitemap as $s){
                    $url = $s['url'];
                    $data = "";
                    $result = 0;
					
					if( isset($settings['shop_force_skip']) AND $settings['shop_force_skip'] ){
						self::$mysql->query("UPDATE shop_parsing_plugin_sitemap SET parsing = 1, status = 0 WHERE id = '".self::$mysql->escape($s['id'])."'");
					}
                    
                    if(empty($url))
                        continue;					
					
                    $code = @get_headers($url);
                    
					
                    if( preg_match("/301/", $code[0]) ){
                        $url = preg_replace("/Location: /", "", $code[5]);
                        $code = @get_headers($url);
                    }
                   
                    
                    if( preg_match("/200/", $code[0]) AND empty($proxy) ){
                        $data = file_get_contents($url);
                        
                        if(!empty($data)){
                            $result = self::add_product($data, $settings, $s['id']);
                        }
                        
                        if($result){
                            $i++;
                        }
                    }elseif(empty($proxy)){
						self::$mysql->query("UPDATE shop_parsing_plugin_sitemap SET parsing = 1, status = 0 WHERE id = '".self::$mysql->escape($s['id'])."'");
					}
                    
                    if( preg_match("/200|429/", $code[0]) AND !empty($proxy) ){
                        $urls[$s['id']] = $url;
                    }
                }
                
				if(isset($urls) AND !empty($urls))
					$i = self::get_proxy_mult($urls, $proxy, $settings);
            }
            
            $this->response = "Импортировано $i товаров";
            
        } catch(waException $e) {
            self::logging( $e->getMessage() );
        }
    }
    
   	public function add_product($data, $settings, $id){
   	    $name = ""; $price = ""; $categories = array(); $parent_cat_id = 0; $image_urls = array(); $sku = ""; $features = array(); $result = 0; $product_features = array(); $desc = '';
        
        $doc = new DOMDocument();
        $source = mb_convert_encoding($data, 'HTML-ENTITIES', 'utf-8');
        @$doc->loadHTML( $source );
        $doc->normalize();
        $doc->normalizeDocument();
        
		//Ссылки
		if( isset($settings['shop_collect_href']) AND $settings['shop_collect_href'] ){
			$url_arr = parse_url($settings['shop_sitemap_url']);
			
			foreach($doc->getElementsByTagName("a") as $a)
			{ 
                $href = $a->getAttribute('href'); 
				
                if( $href == '/' OR preg_match("/\?/", $href) ){
                    continue;
                }
                
				if(!preg_match("/http/", $href))
				{
					$href = $url_arr['scheme']."://".$url_arr['host'].$href;
				}else{
					if(!preg_match("@".$url_arr['host']."@", $href))
					{
						continue;
					}
				}
                
				self::$mysql->query("INSERT IGNORE INTO shop_parsing_plugin_sitemap (url, profile_id) VALUES ('".self::$mysql->escape($href)."', '$settings[id]')");
            }
		}
		
        // Наименование
        if( isset($settings['shop_name_tag']) AND !empty($settings['shop_name_tag']) ){
            foreach($doc->getElementsByTagName($settings['shop_name_tag']) as $tag) { 
                $name = trim($tag->nodeValue); 
            }
            
            if(!empty($name)){
                $p_url = shopHelper::transliterate($name);
            
				if($settings['shop_stop_duplicate']){
					$check_product = self::$mysql->query("SELECT id FROM shop_product WHERE name = '".self::$mysql->escape($name)."'")->fetchAssoc();
					
					if(!empty($check_product)){
						return false;
					}
				}
            
            }
        }
		
		// Описание
        if( isset($settings['shop_desc_tag']) AND !empty($settings['shop_desc_tag']) ){
            foreach($doc->getElementsByTagName($settings['shop_desc_tag']) as $tag) {
                
                if( isset($settings['shop_desc_attr']) AND !empty($settings['shop_desc_attr']) ){
                    $attr = explode("=", $settings['shop_desc_attr']);
                    if(count($attr) == 2){
                        $attr_element = $tag->getAttribute($attr[0]);
                        
                        if($attr_element == $attr[1]){
                            
                            $desc = trim($this->get_inner_html($tag));
                        }
                    }
                }else{
					$desc = trim($this->get_inner_html($tag));
                }
            }
        }
		
        // Артикул
        if( isset($settings['shop_sku_tag']) AND !empty($settings['shop_sku_tag']) ){
            foreach($doc->getElementsByTagName($settings['shop_sku_tag']) as $tag) {
                
                if( isset($settings['shop_sku_attr']) AND !empty($settings['shop_sku_attr']) ){
                    $attr = explode("=", $settings['shop_sku_attr']);
                    if(count($attr) == 2){
                        $attr_element = $tag->getAttribute($attr[0]);
                        
                        if($attr_element == $attr[1]){
                            
                            if( isset($settings['shop_sku_attr_sku']) AND !empty($settings['shop_sku_attr_sku']) ){
                                $sku = $tag->getAttribute($settings['shop_sku_attr_sku']);
                            }else{
                                $sku = $tag->nodeValue;
                            }
                        }
                    }
                }else{
                    if( isset($settings['shop_sku_attr_sku']) AND !empty($settings['shop_sku_attr_sku']) ){
                        $sku = $tag->getAttribute($settings['shop_sku_attr_sku']);
                    }else{
                        $sku = $tag->nodeValue;
                    }
                }
            }
			
			$sku = trim(preg_replace(array("/Артикул/", "/:/"), array("",""), $sku));
        }
        
        // Цена
        if( isset($settings['shop_price_tag']) AND !empty($settings['shop_price_tag']) ){
            foreach($doc->getElementsByTagName($settings['shop_price_tag']) as $tag) {
                
                if( isset($settings['shop_price_attr']) AND !empty($settings['shop_price_attr']) ){
                    $attr = explode("=", $settings['shop_price_attr']);
                    if(count($attr) == 2){
                        $attr_element = $tag->getAttribute($attr[0]);
                        
                        if($attr_element == $attr[1]){
                            
                            if( isset($settings['shop_price_attr_price']) AND !empty($settings['shop_price_attr_price']) ){
                                $price = $tag->getAttribute($settings['shop_price_attr_price']);
                            }else{
                                $price = $tag->nodeValue;
                            }
                        }
                    }
                }else{
                    if( isset($settings['shop_price_attr_price']) AND !empty($settings['shop_price_attr_price']) ){
                        $price = $tag->getAttribute($settings['shop_price_attr_price']);
                    }else{
                        $price = $tag->nodeValue;
                    }
                }
            }
            
            if(!empty($price)){
                $price = preg_replace("/[^\d\.+]/", "", $price);
            }
            
            if( !empty($price) AND isset($settings['shop_price_add']) AND !empty($settings['shop_price_add']) ){
                $price = $price*(100+$settings['shop_price_add'])/100;
            }
        }

        //breadcrumb category_tag
        if( isset($settings['shop_category_tag']) AND !empty($settings['shop_category_tag']) ){
            foreach($doc->getElementsByTagName($settings['shop_category_tag']) as $tag) {
                
                $attr = explode("=", $settings['shop_category_attr']);
                $attr_element = $tag->getAttribute($attr[0]);
                
                if($attr_element == $attr[1]){
                    foreach($tag->getElementsByTagName("a") as $a) {
                        $category = trim($a->nodeValue);
                        if(in_array($category, array("Главная", "Каталог")) OR empty($category) OR strlen($category) < 3 OR trim($name) == trim($category))
                            continue;
                            
                        $categories[] = preg_replace("@\/@", "", $category);
                    }
                }
            }
            
            //Проверка Категорий по наименованию
            if( isset($settings['shop_category_name']) AND !empty($settings['shop_category_name'])){
                $allow_categories = explode("\r\n",$settings['shop_category_name']);
                
                if(!empty($allow_categories)){
                    $allow = false;
                    foreach($allow_categories as $a_c){
                        if( in_array($a_c, $categories) )
                            $allow = true;
                    }
                    
                    if(!$allow){
                        self::$mysql->query("UPDATE shop_parsing_plugin_sitemap SET parsing = 1, status = 0 WHERE id = '".self::$mysql->escape($id)."'");
                        return false;
                    }
                }
            }
            
            if(!empty($categories)){
                foreach($categories as $cat){
					$exist_cat = self::$mysql->query("SELECT * FROM shop_category WHERE name = '".self::$mysql->escape($cat)."' AND parent_id = $parent_cat_id")->fetchAssoc();
                    
                    if(!empty($exist_cat)){
                        $parent_cat_id = $exist_cat['id'];
                    }else{
                        try {
                            $parent_cat_id = self::$category_model->add( array( "name" => $cat, "url" => shopHelper::transliterate($cat), ), $parent_cat_id );
                        } catch(waException $e) {
                            self::logging($e);
                        }
                    }
                }
            }  
        }
        
        // Картинка
        if( isset($settings['shop_img_tag']) AND !empty($settings['shop_img_tag']) ){
            foreach($doc->getElementsByTagName($settings['shop_img_tag']) as $tag) {
                
                if( isset($settings['shop_img_attr']) AND !empty($settings['shop_img_attr']) ){
                    $attr = explode("=", $settings['shop_img_attr']);
                    if(count($attr) == 2){
                        $attr_element = $tag->getAttribute($attr[0]);
                        
                        if($attr_element == $attr[1]){
                            
                            if( isset($settings['shop_img_attr_img']) AND !empty($settings['shop_img_attr_img']) ){
                                $image_urls[] = $tag->getAttribute($settings['shop_img_attr_img']);
                            }else{
                                $image_urls[] = $tag->nodeValue;
                            }
                        }
                    }
                }else{
                    if( isset($settings['shop_img_attr_img']) AND !empty($settings['shop_img_attr_img']) ){
                        $image_urls[] = $tag->getAttribute($settings['shop_img_attr_img']);
                    }else{
                        $image_urls[] = $tag->nodeValue;
                    }
                }
            }
            
            if(  isset($settings['shop_img_prefix']) AND !empty($settings['shop_img_prefix']) ){
				foreach($image_urls as $key => $image_url){
					$image_urls[$key] = $settings['shop_img_prefix'].$image_url;
				}
            }
        }
        // Характеристики
        if( isset($settings['shop_all_features_tag']) AND !empty($settings['shop_all_features_tag']) ){
            
            foreach($doc->getElementsByTagName($settings['shop_all_features_tag']) as $tag) {
                
                $attr = explode("=", $settings['shop_all_features_name_attr']);
                if(count($attr) == 2){
                    $attr_element = $tag->getAttribute($attr[0]);
                    
                    if($attr_element == $attr[1]){
                        if( isset($settings['shop_features_name_tag']) AND !empty($settings['shop_features_name_tag']) ){
                            foreach($tag->getElementsByTagName($settings['shop_features_name_tag']) as $tag_name) {
                                
                                if( isset($settings['shop_features_name_attr']) AND !empty($settings['shop_features_name_attr']) ){
                                    $attr_name = explode("=", $settings['shop_features_name_attr']);
                                    if(count($attr_name) == 2){
                                        $attr_name_element = $tag_name->getAttribute($attr_name[0]);
                                        
                                        if($attr_name_element == $attr_name[1]){
                                            @$name_feature[] = $this->remove_spaces($tag_name->nodeValue);
                                        }
                                    }
                                }else{
                                    @$name_feature[] = $this->remove_spaces($tag_name->nodeValue);
                                }
                            }
                        }
                        
                        if( isset($settings['shop_features_value_tag']) AND !empty($settings['shop_features_value_tag']) ){
                            $i=0;
                            foreach($tag->getElementsByTagName($settings['shop_features_value_tag']) as $tag_value) {
                                
                                if( isset($name_feature) AND isset($settings['shop_features_value_attr']) AND !empty($settings['shop_features_value_attr']) ){
                                    $attr_value = explode("=", $settings['shop_features_value_attr']);
                                    if(count($attr_value) == 2){
                                        $attr_value_element = $tag_value->getAttribute($attr_value[0]);
                                        
                                        if($attr_value_element == $attr_value[1]){
											$node_value = $settings['shop_features_value_tag'] != 'meta' ? $tag_value->nodeValue : $tag_value->getAttribute('content');
                                            $features[ preg_replace("/:/", "", $name_feature[$i]) ] = $this->remove_spaces($node_value);
											$i++;
                                        }
                                    }
                                }else{
									
                                    $features[ preg_replace("/:/", "", $name_feature[$i]) ] = $this->remove_spaces($tag_value->nodeValue);
									$i++;
                                }
                            }
                        }
                    }
                }
            }
			
			if( !empty($features) AND $settings['shop_features_value_tag'] == $settings['shop_features_name_tag'] AND empty(@$settings['shop_features_value_attr']) AND empty(@$settings['shop_features_name_attr']) )
			{
				$features = array();
				
				foreach($name_feature as $key => $name_f)
				{
					if($key & 1){
						$features[$n_f] = $name_f;
					}else{
						$n_f = $name_f;
					}
				}
			}
			
            if(!empty($features)){
                foreach($features as $f_name => $f_value){
                    $code = shopHelper::transliterate($f_name);
                    $code = preg_replace("/\-/", "_", $code);
                    $exist_feature = self::$feature_model->getByCode($code);
                    
                    if( empty($exist_feature) ){
                        
                        $data = array(
                            'name'       => $f_name,
                            'code'       => $code,
                            'selectable' => 0,
                            'multiple'   => 0,
                        );
                        
                        $feature_id = self::$feature_model->save($data);
						
						self::$shopTypeFeaturesModel->insert( array(
							'type_id' => $settings['shop_type_id'],
							'feature_id' => $feature_id,
						) );
                    }
                    
                    $product_features[$code] = $f_value;
                }
            }
        }

        if(!empty($name)){
            $new_prod = new shopProduct();
            $product = array(
                'name' => $name,
                'url' => $p_url,
				'description' => $desc,
                'price' => $price,
                'type_id' => $settings['shop_type_id'],
                'min_price' => $price,
                'category_id' => $parent_cat_id,
                'max_price' => $price,
                'features' => $product_features,
                'skus' => array(
                    -1 => array( 
                        'sku' => $sku,
                        'price' => $price,
                        'available' => 1,
                        'primary_price' => $price,
                    ), 
                ),
            );
            
            $result = $new_prod->save($product);
            self::$category_product_model->add( array($new_prod['id']), array($parent_cat_id) );
            
            if($result){
                self::$mysql->query("UPDATE shop_parsing_plugin_sitemap SET product_id = '".self::$mysql->escape($new_prod['id'])."', parsing = 1, status = 1 WHERE id = '".self::$mysql->escape($id)."'");
            }else{
                self::$mysql->query("UPDATE shop_parsing_plugin_sitemap SET parsing = 1, status = 0 WHERE id = '".self::$mysql->escape($id)."'");
            }
            
            if($result AND !empty($image_urls)){
				foreach($image_urls as $image_url){
					$image_result = $this->import_image($new_prod['id'], $image_url, $name);
				}
            }
            
        }
        
        return $result;
   	}
    
    public function import_image($product_id, $path, $name){
            $file = wa()->getCachePath('plugins/parsing/' . shopHelper::transliterate($name));
            
            if(!waFiles::upload($path, $file)) {
				$this->logging($path." - Image not found");
    			return;
    		}
            if ($file && file_exists($file)) {
    			if ($image = waImage::factory($file)) {
    				$image_changed = true;
    
    				/**
    				 * Extend upload proccess
    				 * Make extra workup
    				 * @event image_upload
    				 */
    				$event = wa()->event('image_upload', $image);
    				if ($event) {
    					foreach ($event as $plugin_id => $result) {
    						if ($result) {
    							$image_changed = true;
    						}
    					}
    				}
    
    				$model = new shopProductImagesModel();
    
    				$data = array(
    					'product_id'        => $product_id,
    					'upload_datetime'   => date('Y-m-d H:i:s'),
    					'width'             => $image->width,
                        'filename'          => '',
    					'height'            => $image->height,
    					'size'              => filesize($file),
    					'ext'               => $image->getExt(),
    				);
    
    				
    				$image_id = $data['id'] = $model->add($data);
    				
    				if (!$image_id) {
    					throw new waException("Database error");
    				}
    
    				/**
    				 * @var shopConfig $config
    				 */
    				$config = wa()->getConfig()->getAppConfig('shop');
    
    				$image_path = shopImage::getPath($data);
    				if ((file_exists($image_path) && !is_writable($image_path)) || (!file_exists($image_path) && !waFiles::create($image_path))) {
    					$model->deleteById($image_id);
    					throw new waException(
    						sprintf("The insufficient file write permissions for the %s folder.",
    							substr($image_path, strlen($config->getRootPath()))
    					));
    				}
    
    				if ($image_changed) {
    					$image->resize(800, 800, "AUTO", false)->save($image_path);
    
    					// save original
    					$original_file = shopImage::getOriginalPath($data);
    					if ($config->getOption('image_save_original') && $original_file) {
    						waFiles::copy($file, $original_file);
    					}
    
    				} else {
    					waFiles::copy($file, $image_path);
    				}
    				unlink($file);        // free variable
                    
    				//shopImage::generateThumbs($data, $config->getImageSizes());
                    
    			}
    		}   
    }
    
    
    public function get_proxy_mult($urls, $proxy, $settings) {
        if (!extension_loaded("curl")) self::logging("Бибилиотека cURL недоступна на сервере");
        
        $i = 0;
        $cmh = curl_multi_init();
         
        // массив заданий для мультикурла
        $tasks = array();
        
        foreach ($urls as $id => $url) {
            shuffle($proxy);
        	// инициализируем отдельное соединение (поток)
        	$ch = curl_init($url);
        	// если будет редирект - перейти по нему
        	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        	// возвращать результат
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        	// не возвращать http-заголовок
        	curl_setopt($ch, CURLOPT_HEADER, 0);
        	// таймаут ожидания
        	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_PROXY, $proxy[0]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        	// добавляем дескриптор потока в массив заданий
        	$tasks[$id] = $ch;
        	// добавляем дескриптор потока в мультикурл
        	curl_multi_add_handle($cmh, $ch);
        }
         
        // количество активных потоков
        $active = null;
        // запускаем выполнение потоков
        do {
        	$mrc = curl_multi_exec($cmh, $active);
        } 
        while ($mrc == CURLM_CALL_MULTI_PERFORM);
         
        // выполняем, пока есть активные потоки
        while ($active && ($mrc == CURLM_OK)) {
        	// если какой-либо поток готов к действиям
        	if (curl_multi_select($cmh) != -1) {
        		// ждем, пока что-нибудь изменится
        		do {
        			$mrc = curl_multi_exec($cmh, $active);
        			// получаем информацию о потоке
        			$info = curl_multi_info_read($cmh);
        			// если поток завершился
        			if ($info['msg'] == CURLMSG_DONE) {
        				$ch = $info['handle'];
        				// ищем урл страницы по дескриптору потока в массиве заданий
        				$id = array_search($ch, $tasks);
                        
                        $error = curl_error($ch);
        				// забираем содержимое
        				$data = curl_multi_getcontent($ch);
                        
                        if(!empty($error)){
                            self::logging($error);
                        }
                        
                        if(!empty($data)){
                            
                            $result = self::add_product($data, $settings, $id);
                            if($result)
                                $i++;
                        }
        				// удаляем поток из мультикурла
        				curl_multi_remove_handle($cmh, $ch);
        				// закрываем отдельное соединение (поток)
        				curl_close($ch);
        			}
        		}
        		while ($mrc == CURLM_CALL_MULTI_PERFORM);
        	}
        }
         
        // закрываем мультикурл
        curl_multi_close($cmh);
        
        return $i;
    }
    
    //Функция логирования
	public function logging($message)
    {
        $path = wa()->getConfig()->getPath('log');
        waFiles::create($path.'/shop/parsing.log');
        waLog::log($message, 'shop/parsing.log');
    }
	
	public function get_inner_html($node) 
    { 
        $innerHTML= ''; 
        $children = $node->childNodes; 
        foreach ($children as $child) { 
            $innerHTML .= $child->ownerDocument->saveXML( $child ); 
        } 
    
        return $innerHTML;  
    }
	
	public function remove_spaces($content){
		$string = htmlentities($content, null, 'utf-8');
		$content = str_replace("&nbsp;", " ", $string);
		$content = html_entity_decode($content);
		$content = preg_replace("/\s/", ' ', $content);
		return trim($content);
	}
}