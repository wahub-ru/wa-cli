<?php

/**
 * Class photosLinkPlugin
 */
class photosLinkPlugin extends photosPlugin
{
    /**
     * @var waView $view
     */
    private static $view;

    /**
     * @var photosLinkPlugin $plugin
     */
    private static $plugin;

    /**
     * @return photosLinkPlugin|waPlugin
     * @throws waException
     */
    private static function getPlugin()
    {
        if (!isset(self::$plugin)) {
            self::$plugin = wa('photos')->getPlugin('link');
        }
        return self::$plugin;
    }

    /**
     * @return waSmarty3View|waView
     * @throws waException
     */
    private static function getView()
    {
        if (!isset(self::$view)) {
            self::$view = waSystem::getInstance()->getView();
        }
        return self::$view;
    }

    /**
     * @return string
     */
    public function getPluginPath() {
        return $this->path;
    }

    /**
     * @param $photo_id
     * @return array
     * @throws waException
     */
    public function backendPhoto($photo_id)
    {
        $link_model = new photosLinkPluginLinkModel();
        $link = $link_model->getById($photo_id);
        $view = self::getView();
        $plugin = self::getPlugin();

        $view->assign('link', $link);
        $view->assign('photo_id', $photo_id);

        return array(
            'bottom' => $view->fetch($plugin->getPluginPath() . '/templates/hooks/backend_photo.bottom.html'),
        );
    }

    /**
     * @param $photo_id
     */
    public function photoDelete($photo_id)
    {
        $link_model = new photosLinkPluginLinkModel();
        $link_model->deleteById($photo_id);
    }
    
    /**
     * @return string
     * @throws waException
     */
    public static function getFeedbackControl() {
        $plugin = self::getPlugin();
        $view = self::getView();
        return $view->fetch($plugin->getPluginPath() . '/templates/controls/feedback.html');
    }
}
