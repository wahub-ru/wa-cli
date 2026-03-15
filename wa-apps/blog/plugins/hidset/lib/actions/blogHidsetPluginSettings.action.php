<?php
class blogHidsetPluginSettingsAction extends waViewAction{
    public function execute(){
        $plugin = wa()->getPlugin('hidset');
        $hsets = $plugin->hsets;
        ksort($hsets);
        $this->view->assign('hsets', $hsets);
        $this->view->assign('allsets', wa('blog')-> getConfig() -> getOption(null));
        
    }
}