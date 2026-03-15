<?php
/**
 * @package Syrattach
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright (c) 2014-2021, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */

/**
 * Main plugin class
 */
class shopSyrattachPlugin extends shopPlugin
{

    const SYRATTACH_ATTACHMENTS_FOLDER = "attachments";

    const LOG = 'shop/plugins/syrattach.log';

    /** @var shopSyrattachFileModel */
    private $Attachments;

    public function __construct($info)
    {
        parent::__construct($info);
        $this->Attachments = new shopSyrattachFileModel();
    }

    /**
     * @param $product_id
     * @return string
     */
    public static function getDirectory($product_id): string
    {
        return shopProduct::getPath($product_id, self::SYRATTACH_ATTACHMENTS_FOLDER, true);
    }

    /**
     * Template editor
     *
     * Renders the template with custom form control
     *
     * @param string $param
     * @param array $settings
     * @return string
     */
    public static function templateControl(string $param, array $settings): string
    {
        try {
            $control_template_path = 'plugins/syrattach/templates/settings/template_control.html';
            $control_template = waSystem::getInstance()->getAppPath($control_template_path, 'shop');
            $view = waSystem::getInstance()->getView();
            $template_path = 'plugins/syrattach/templates/frontend_product.html';
            $original_template = waSystem::getInstance()->getAppPath($template_path, 'shop');
            $modified_template_path = waSystem::getInstance()->getDataPath($template_path, false, 'shop', false);
        } catch (waException $exception) {
            waLog::log($exception->getMessage(), self::LOG);
            return '';
        }

        $original_template = file_get_contents($original_template);
        $modified_template = null;
        $template_modified = false;

        if (file_exists($modified_template_path)) {
            $modified_template = file_get_contents($modified_template_path);
            $template_modified = true;
        }

        $view->assign(compact('settings', 'modified_template', 'original_template', 'template_modified'));

        try {
            return $view->fetch($control_template);
        } catch (Exception $e) {
            waLog::log($e->getMessage(), self::LOG);
            return '';
        }
    }

    /**
     * @param array $route
     * @return array|mixed|string[]
     * @throws waException
     */
    public function routing($route = array())
    {
        if (wa()->getEnv() === 'backend') {
            return ['products/<id>/attachments/?' => 'backend/attachments'];
        }
        return parent::routing($route);
    }

    /**
     * Hook 'backend_product'
     *
     * @param array|shopProduct $product
     * @return array
     * @throws SmartyException
     * @throws waException
     */
    public function backendProduct($product): array
    {
        $template = $this->path . '/templates/backend_product.html';
        $view = waSystem::getInstance()->getView();
        $count = $this->Attachments->countByField('product_id', $product['id']);
        $shop_version = wa('shop')->getVersion();
        $hints_allowed = (bool)version_compare($shop_version, '7.5', '>=');

        $view->assign(compact('count', 'product', 'hints_allowed'));
        $html = $view->fetch($template);

        return array('edit_section_li' => $html);
    }

    /**
     * @param $params
     * @return array
     * @throws waException
     */
    public function handlerBackendProd(&$params): array
    {
        $wa_app_url = wa()->getAppUrl('shop', true);
        $id = (int)$params['product']->getId();

        if (!$id) {
            $id = 'new';
            $total = 0;
        } else {
            $total = (new shopSyrattachFileModel())->countByField('product_id', $id);
        }

        return [
            'sidebar_item' => "<li id=\"s-syrattach-plugin-menuitem\"><a href='{$wa_app_url}products/$id/attachments/'><span>" .
                _wp('Attached files') .
                "</span>" . ($total ? "<span class=\"count\">$total</span>" : "") . "</a></li>"
        ];
    }

    /**
     * @return string[]
     */
    public function handlerBackendProdLayout(): array
    {
        return ['bottom' => '<script>$(\'#wa-app\').on(\'wa_loaded\', ()=>{$.wa_shop_products.router.routes["/products/\\\\d+/attachments/"]={id:"products", content_selector: ".s-product-page .js-page-content"}});</script>'];
    }

    /**
     * Handler for 'product_custom_fields' hook
     *
     * List of columns in the CSV file
     *
     * @return array
     * @throws waException
     */
    public function productCustomFields(): array
    {
        return ['product' => ['file' => _wp('Attached File')]];
    }

    /**
     * Handler for 'product_delete' hook
     *
     * We don't care about attached files because they will be deleted by
     * Shop-Script with other public files such as images that belongs to
     * products
     *
     * @param array $product_ids
     */
    public function productDelete(array $product_ids)
    {
        $this->Attachments->deleteByField('product_id', $product_ids['ids']);
    }

    /**
     * Handler for 'product_save' hook
     * Copy files from directory on CSV import
     *
     * @param array|mixed $params
     * @throws waException
     */
    public function productSave($params)
    {
        // No data for plugin
        if (!array_key_exists('syrattach_plugin', $params['data'])) {
            return;
        }

        // Нам ID товара нужен позарез
        if (empty($params['data']['id'])) {
            if (wa()->getConfig()->isDebug()) {
                waLog::log(sprintf(_wp('No ID given for product "%s"'), ifset($params['data']['name'], '')) . self::LOG);
            }
            return;
        }

        $data_path = wa()->getDataPath('syrattach', true, 'site', false);
        if (!($files = (array)ifset($params, 'data', 'syrattach_plugin', 'file', []))) return;

        foreach ($files as $file) {
            if ((strpos($file, '/') !== false) || (strpos($file, '\\') !== false)) {
                waLog::log(sprintf(_wp('Wrong file name "%s" for product "%s". File not saved.'), $file, ifset($params['data']['name'])), self::LOG);
                continue;
            }

            $full_path = $data_path . DIRECTORY_SEPARATOR . $file;
            if (!file_exists($full_path) || !is_file($full_path) || !is_readable($full_path)) {
                waLog::log(sprintf(_wp('File named "%s" not exists or it is not a file or file is not readable. File not saved.'), $file), self::LOG);
                continue;
            }

            $this->Attachments->add(
                $params['data']['id'],
                new waRequestFile(
                    array(
                        'name'     => $file,
                        'type'     => 'application/binary',
                        'size'     => filesize($full_path),
                        'tmp_name' => $full_path,
                        'error'    => 0
                    ),
                    true
                ),
                true
            );
        }
    }

    /**
     * Handler for frontend_product hook
     *
     * @param shopProduct $product
     * @return array
     */
    public function frontendProduct(shopProduct $product): array
    {
        $placement = $this->getSettings('frontend_product_hook');
        if (($placement !== 'block') && ($placement !== 'block_aux'))
            return [];

        return [$placement => (new shopSyrattachPluginViewHelper($this, 'syrattach'))->render($product->id)];
    }

    /**
     * Helper method.
     *
     * Returns the rendered template with list of files
     *
     * @param int|string $product_id
     * @param bool|int $force_on_empty If TRUE render template even the list of files is empty
     * @return string
     * @deprecated since 2.0.0 Оставлено для обратной совместимости и совместимости с shop <8.17
     */
    public static function render($product_id, $force_on_empty = false): string
    {
        $product_id = (int)$product_id;
        $force_on_empty = (bool)$force_on_empty;

        try {
            $plugin = wa('shop')->getPlugin('syrattach');
        } catch (waException $e) {
            return "";
        }

        return (new shopSyrattachPluginViewHelper($plugin, 'syrattach'))
            ->render($product_id, $force_on_empty);
    }

    /**
     * Helper method.
     * Returns an array of attached files
     *
     * array(
     *     array(
     *        'id'
     *        'name'
     *        'ext'
     *        'description',
     *        'size',
     *        'url'
     *     )
     * )
     *
     * @param int|string $product_id
     * @return array
     * @deprecated since 2.0.0 Оставлено для обратной совместимости и совместимости с shop <8.17
     */
    public static function getList($product_id): array
    {
        try {
            $plugin = wa('shop')->getPlugin('syrattach');
        } catch (waException $e) {
            return [];
        }

        return (new shopSyrattachPluginViewHelper($plugin, 'syrattach'))
            ->getList($product_id);
    }

    /**
     * @param $attachment
     * @param bool $absolute
     * @return string
     * @throws waException
     */
    public static function getFileUrl($attachment, bool $absolute = false): string
    {
        $path = shopProduct::getFolder($attachment['product_id']) .
            "/" .
            "{$attachment['product_id']}" .
            "/" .
            self::SYRATTACH_ATTACHMENTS_FOLDER .
            "/{$attachment['name']}";

        return waSystem::getInstance()->getDataUrl($path, true, 'shop', $absolute);
    }
}
