<?php

/**
 * Helper class shopApiextensionPluginCategoryDialog
 *
 * @author Steemy, created by 31.01.2025
 */

class shopApiextensionPluginCategoryDialog
{
    private $settings;

    public function __construct() {
        $pluginSetting = shopApiextensionPluginSettings::getInstance();
        $this->settings = $pluginSetting->getSettings();
    }

    /**
     * Вывод быстрых ссылок в категории
     */
    public function backendProdCategoryDialog($settings)
    {
        if(!$this->settings['additional_links']) {
            return '';
        }

        $version = $this->settings['plugin_info']['version'];
        $additionalLinksJS = wa()->getRootUrl() . "wa-apps/shop/plugins/apiextension/js/backend.category.additional.links.js?v=" . $version;

        if (!empty($settings['category']['id'])) {
            $categoryParamsModel = new shopCategoryParamsModel();
            $params = $categoryParamsModel->get($settings['category']['id']);
        }

        $additionalLinks = [];
        if (!empty($params['apiextension_additional_links'])) {
            try {
                $additionalLinks = json_decode($params['apiextension_additional_links'], true);
            } catch (Exception $e) {
                $additionalLinks = [];
            }
        }

        if (gettype($additionalLinks) != 'array') {
            $additionalLinks = [];
        }

        $view = wa()->getView();
        $view->assign([
            'additionalLinks'   => $additionalLinks,
            'additionalLinksJS' => $additionalLinksJS,
        ]);

        $template = wa()->getAppPath('plugins/apiextension/templates/helpers/category/AdditionalLinks.html', 'shop');
        return $view->fetch($template);
    }

    public function categorySaveHandler($category)
    {
        if(!$this->settings['additional_links']) {
            return '';
        }

        if (!empty($category['id'])) {
            $additionalLinksUI2 = waRequest::post('apiextension_additional_links_ui2', false);

            //check UI2
            if ($additionalLinksUI2) {
                $additionalLinks = waRequest::post('apiextension_additional_links', array());

                $category_id = $category['id'];
                $name = 'apiextension_additional_links';
                $value = $additionalLinks ? json_encode($additionalLinks) : 0;

                $categoryParamsModel = new shopCategoryParamsModel();

                if ($value) {
                    $categoryParamsModel->insert(compact('category_id', 'name', 'value'), 1);
                } else {
                    $categoryParamsModel->deleteByField(compact('category_id', 'name'));
                }
            }
        }
    }
}