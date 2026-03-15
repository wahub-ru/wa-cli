<?php

class shopParsingPluginBackendCollectController extends waJsonController
{
    private $plugin_id = 'parsing';
    public function execute()
    {
        
        try {
            $plugin = wa('shop')->getPlugin('parsing');
            $profile_helper = new shopImportexportHelper($this->plugin_id);
            
            $profile = $profile_helper->getConfig(waRequest::get('profile_id'));
            $settings = $profile['config'];
            
            $mysql = new waModel();
            $dom = new DOMDocument;
            $sitemap_url = $settings['shop_sitemap_url'];
            stream_context_set_default( array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ),
            ) );
            $mess = '';
            
            if( isset($sitemap_url) AND !empty($sitemap_url) ){
                $code = get_headers($sitemap_url);
                
                if( preg_match("/200/", $code[0]) ){
                    
                    self::parse_sitemap($sitemap_url, $settings, $mysql);
                    
                }else{
                    $mess = "Ссылка Sitemap не доступна";
                }
            }else{
                $mess = "Необходимо заполнить поле Sitemap URL";
            }
            
            $this->response = $mess;
            
        } catch(waException $e) {
            $this->setError($e->getMessage());
        }
    }
    
    public function parse_sitemap($sitemap_url, $settings, $mysql){
        if( isset($sitemap_url) AND !empty($sitemap_url) ){
            $code = @get_headers($sitemap_url);
            $more_sitemaps = array();
            
            if(!empty($code)){
                if( preg_match("/301/", $code[0]) ){
                    $sitemap_url = preg_replace("/Location: /", "", $code[5]);
                    
                    self::parse_sitemap($sitemap_url, $settings, $mysql);
                }
                
                if( preg_match("/200/", $code[0]) ){
                    $reader = new XMLReader();
                    $reader->open($sitemap_url);
                    
                    while($reader->read()) {
                        if($reader->nodeType == XMLReader::ELEMENT) {
                            // если находим элемент <card>
                            if($reader->localName == 'loc') {
                                $reader->read();
                                
                                if($reader->nodeType == XMLReader::TEXT) {
                                    if( preg_match("@\.xml$@", $reader->value) ){
                                        $more_sitemaps[] = $reader->value;
                                    }else{
                                        if( isset($settings['shop_product_url_target']) AND !empty($settings['shop_product_url_target']) ){
                                            if( preg_match("@$settings[shop_product_url_target]@", $reader->value) ){                            
                                                $mysql->query("INSERT IGNORE INTO shop_parsing_plugin_sitemap (url, profile_id) VALUES ('".$mysql->escape($reader->value)."', '$settings[id]')");
                                            }
                                        }else{
                                            $mysql->query("INSERT IGNORE INTO shop_parsing_plugin_sitemap (url, profile_id) VALUES ('".$mysql->escape($reader->value)."', '$settings[id]')");
                                        }
                                        
                                    }
                                }
                            }
                        }
                    }
                    
                    $reader->close();
                }
            }
            
            if(!empty($more_sitemaps)){
                foreach($more_sitemaps as $more){
                    self::parse_sitemap($more, $settings, $mysql);
                }
            }
        }
    }
}