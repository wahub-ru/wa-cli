<?php

class shopParsingPluginBackendSetupAction extends waViewAction
{
    private $plugin_id = 'parsing';

    public function execute()
    {
        
        $routing = wa()->getRouting();
        $plugin = wa()->getPlugin('parsing');
        
        $profile_helper = new shopImportexportHelper($this->plugin_id);
        $this->view->assign('profiles', $list = $profile_helper->getList());
        
        $params = array();        
        $this->view->assign('params', array('params' => $params));
        
        $profile = $profile_helper->getConfig();
        
        $profile['config'] += array(
            'name'                           => 'Профиль',
            'mode'                           => 'blog_hh',
        );
        
        $this->view->assign('profile', $profile);

        $mysql = new waModel();
        
        $plugin->saveSettings($profile['config']);
        
        $settings = $plugin->getControls(array(
            'id' => 'parsing',
            'namespace' => 'profile',
            'title_wrapper' => '%s',
            'description_wrapper' => '',
            'control_wrapper' => '<div class="name">%1$s %3$s</div><div class="value">%2$s</div>'
        ));
        
        $count_urls = $mysql->query("SELECT COUNT(*) as count FROM shop_parsing_plugin_sitemap WHERE profile_id = '$profile[id]'")->fetchAssoc();
        $count_parsing = $mysql->query("SELECT COUNT(*) as count FROM shop_parsing_plugin_sitemap WHERE parsing = 1 AND profile_id = '$profile[id]'")->fetchAssoc();
        $count_status = $mysql->query("SELECT COUNT(*) as count FROM shop_parsing_plugin_sitemap WHERE parsing = 1 AND status = 1 AND profile_id = '$profile[id]'")->fetchAssoc();
        $count_product = $mysql->query("SELECT COUNT(*) as count FROM shop_parsing_plugin_sitemap WHERE product_id IS NOT NULL AND status = 1 AND profile_id = '$profile[id]'")->fetchAssoc();
        
        $this->view->assign('count_urls', $count_urls['count']);
        $this->view->assign('count_parsing', $count_parsing['count']);
        $this->view->assign('count_status', $count_status['count']);
        $this->view->assign('count_product', $count_product['count']);
        $this->view->assign('settings', $settings);
        $this->view->assign('root_path', wa()->getConfig()->getRootPath());
        
    }
}