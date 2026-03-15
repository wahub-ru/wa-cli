<?php

class blogThumbpagePlugin extends blogPlugin
{

    const PLUGIN_ID = 'thumbpage';

    public function pageEdit($data)
    {
        if (self::getPluginSettings('status')) {
            $view = wa()->getView();
            $view->assign('page', $data['page']);
            $html = $view->fetch($this->path . '/templates/actions/backend/BackendPageEdit.html');
            return $html;
        }
        return '';
    }

    public static function getImgUrl($post_id)
    {
        $pageMmodel = new blogPageModel();
        $page = $pageMmodel->getById($post_id);
        if ($page['thumbpage']) {
            return wa()->getDataUrl('plugins/thumbpage/images/' . $page['thumbpage'], true, 'blog');
        }
    }

    public static function getPluginSettings($name = NULL, $pluginId = '')
    {
        static $settings = array();

        if (empty($pluginId)) {
            $pluginId = self::PLUGIN_ID;
        }

        if (!array_key_exists($pluginId, $settings)) {
            $settings[$pluginId] = self::plugin($pluginId)->getSettings();
        }

        if (empty($name)) {
            return $settings[$pluginId];
        }

        if (array_key_exists($name, $settings[$pluginId])) {
            return $settings[$pluginId][$name];
        } else {
            return NULL;
        }
    }

    public static function plugin($pluginId = '')
    {
        static $plugin = array();

        if (empty($pluginId)) {
            $pluginId = self::PLUGIN_ID;
        }

        if (!array_key_exists($pluginId, $plugin)) {
            $plugin[$pluginId] = wa('blog')->getPlugin($pluginId);
        }

        return $plugin[$pluginId];
    }

}
