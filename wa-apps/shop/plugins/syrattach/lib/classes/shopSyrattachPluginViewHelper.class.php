<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2021
 * @license Webasyst
 */
declare(strict_types=1);

/**
 * Class shopSyrattachPluginViewHelper
 */
class shopSyrattachPluginViewHelper extends waPluginViewHelper
{
    /**
     * @param int|string $product_id
     * @param bool|int $force_on_empty
     * @param string|null $no_template
     * @return string
     */
    public function render($product_id, $force_on_empty = false, ?string $no_template = null): string
    {
        $product_id = (int)$product_id;
        $force_on_empty = (bool)$force_on_empty;

        $attachments = $this->getList($product_id);
        if (!$attachments && !$force_on_empty) return '';

        if (($template_file = $this->getTemplate($no_template)) === null) return '';

        try {
            $view = wa('shop')->getView();
        } catch (waException $e) {
            waLog::log('Exception loading getView: ' . $e->getMessage());
            return '';
        }

        $view->assign('attachments', $attachments);
        waSystem::pushActivePlugin('syrattach');
        try {
            $result = $view->fetch($template_file);
        } catch (SmartyException $e) {
            waLog::log('Smarty exception on rendering attachments template: ' . $e->getMessage());
            $result = "";
        } catch (waException $e) {
            waLog::log('Webasyst system exception on rendering attachments template: ' . $e->getMessage());
            $result = "";
        }
        waSystem::popActivePlugin();

        return $result;
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
     */
    public function getList($product_id): array
    {
        if (!($product_id = (int)$product_id)) return [];
        if (!($files = (new shopSyrattachFileModel())
            ->select("`id`,`name`, `ext`, `description`, `size`")
            ->where('product_id=i:id', ['id' => $product_id])
            ->order('`sort` ASC')
            ->fetchAll())) return [];

        array_walk($files, function (&$file) use ($product_id) {
            try {
                $file['url'] = shopSyrattachPlugin::getFileUrl($file + ['product_id' => $product_id]);
            } catch (waException $e) {
                waLog::log("Exception when processing list of files: " . $e->getMessage());
                $file = null;
            }
        });
        $files = array_filter($files);

        return array_values($files);
    }

    /**
     * @param string|null $no_template
     * @return string|null
     */
    protected function getTemplate(?string $no_template): ?string
    {
        if ($no_template === null) $no_template = $this->plugin->getSettings('no_template');
        $file = null;

        try {
            if (wa()->getEnv() === 'frontend' && ($theme = waRequest::getTheme())) {
                $theme = new waTheme($theme);
                if ($theme->getFile('plugin.syrattach.attachments.html')) {
                    $file = $theme->getPath() . "/plugin.syrattach.attachments.html";
                }
            }
        } catch (waException $e) {
            waLog::log('Exception when loading theme template: ' . $e->getMessage());
            $file = null;
        }

        if ($file || ($no_template === 'off'))
            return $file;

        $template_path = 'plugins/syrattach/templates/frontend_product.html';

        try {
            $original_template = wa()->getAppPath($template_path, 'shop');
        } catch (waException $e) {
            waLog::log('Exception reading original template');
            return null;
        }

        try {
            $modified_template = wa()->getDataPath($template_path, false, 'shop', false);
        } catch (waException $e) {
            waLog::log('Exception reading old custom template');
            $modified_template = null;
        }

        if ($modified_template && file_exists($modified_template)) return $modified_template;

        return $original_template;
    }
}
