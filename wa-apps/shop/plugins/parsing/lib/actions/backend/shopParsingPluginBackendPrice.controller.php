<?php

class shopParsingPluginBackendPriceController extends waJsonController
{
    private static $mysql;
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
            
            $profile = $profile_helper->getConfig(waRequest::request('profile_id'));
            $settings = $profile['config'];
            
            self::$mysql = new waModel();
            $urls = array();
            
            if( isset($settings['shop_proxy']) AND !empty($settings['shop_proxy']) ){
                $proxy = explode("\r\n", $settings['shop_proxy']);
            }
            
            if( isset($settings['shop_step']) AND !empty($settings['shop_step']) ){
                $step = $settings['shop_step'];
            }else{
                $step = 10;
            }
            
            if( isset($settings['shop_day_update']) AND !empty($settings['shop_day_update']) ){
                $days = $settings['shop_day_update'];
            }else{
                $days = 3;
            }
            
            $sitemap = self::$mysql->query("SELECT * FROM shop_parsing_plugin_sitemap WHERE  profile_id = '".self::$mysql->escape(waRequest::request('profile_id'))."' AND status = 1 AND product_id IS NOT NULL AND datetime < NOW() - INTERVAL ".self::$mysql->escape($days)." DAY LIMIT ".self::$mysql->escape($step));
            $i = 0;

            if($sitemap->count()){
                foreach($sitemap as $s){
                    $url = $s['url'];
                    $data = "";
                    $result = 0;
                    
                    if(empty($url))
                        continue;                    
                    
                    $code = @get_headers($url);
                    
                    if( preg_match("/301/", $code[0]) ){
                        $url = preg_replace("/Location: /", "", $code[5]);
                    }
                   
                    $code = @get_headers($url);
                    
                    if( preg_match("/200/", $code[0]) AND empty($proxy) ){
                        
                        $data = file_get_contents($url);
                        
                        if(!empty($data)){
                            $result = self::update_price($data, $settings, $s['id'], $s);
                        }
                        
                        if($result){
                            $i++;
                        }
                    }
                    
                    if( preg_match("/200|429/", $code[0]) AND !empty($proxy) ){
                        $urls[$s['id']] = $url;
                    }
                }
                
				if(!empty($proxy)){
					$i = self::get_proxy_mult($urls, $proxy, $settings);
				}
            }
            
            $this->response = "Обновлено для $i товаров";
            
        } catch(waException $e) {
            self::logging($e->getMessage());
        }
    }
    
    public function update_price($data, $settings, $id, $s){
   	    $name = ""; $price = ""; $result = 0; $check_product = "";
        
        $doc = new DOMDocument();
        $source = mb_convert_encoding($data, 'HTML-ENTITIES', 'utf-8');
        @$doc->loadHTML( $source );
        

        $check_product = self::$mysql->query("SELECT id FROM shop_product WHERE id = '".self::$mysql->escape($s['product_id'])."'")->fetchAssoc();            
		
		if(empty($check_product))
		{
			self::$mysql->query("UPDATE shop_parsing_plugin_sitemap SET status = 0 WHERE id = '".self::$mysql->escape($id)."'");
			return 0;
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
            
            if( !empty($price) AND isset($settings['shop_price_add']) AND !empty($settings['shop_price_add']) ){
                $price = $price*(100+$settings['shop_price_add'])/100;
            }
        }
		
        if(!empty($price)){            

            self::$mysql->query("UPDATE shop_product SET price = '".self::$mysql->escape($price)."' WHERE id = '".self::$mysql->escape($check_product['id'])."'");
            
            self::$mysql->query("UPDATE shop_product_skus SET price = '".self::$mysql->escape($price)."', primary_price = '".self::$mysql->escape($price)."' WHERE product_id = '".self::$mysql->escape($check_product['id'])."'");
            
            self::$mysql->query("UPDATE `shop_product` SET 
                                    min_price = (SELECT MIN(price) FROM `shop_product_skus` WHERE `shop_product`.`id` = `shop_product_skus`.`product_id` AND `shop_product_skus`.`available` = 1 LIMIT 1), 
                                    max_price = (SELECT MAX(price) FROM `shop_product_skus` WHERE `shop_product`.`id` = `shop_product_skus`.`product_id` AND `shop_product_skus`.`available` = 1 LIMIT 1)
                                    WHERE `shop_product`.id = '".self::$mysql->escape($check_product['id'])."'");
                                    
            self::$mysql->query("UPDATE shop_parsing_plugin_sitemap SET datetime = NOW() WHERE id = '".self::$mysql->escape($id)."'");
            $result = 1;
        }
        
        return $result;
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
                            
                            $result = self::update_price($data, $settings, $id);
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
}