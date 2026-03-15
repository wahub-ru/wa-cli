<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2021
 * @license Webasyst
 */
declare(strict_types=1);

/**
 * Class shopSyrattachPluginSettingsDeleteCustomTemplateController
 */
class shopSyrattachPluginSettingsDeleteCustomTemplateController extends waJsonController
{
    /**
     * @throws waException
     */
    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings'))
            throw new waException(_w('Access denied'), 403);

        $template_path = 'plugins/syrattach/templates/frontend_product.html';
        $modified_template = wa()->getDataPath($template_path, false, 'shop', false);
        if (file_exists($modified_template)) waFiles::delete($modified_template);
    }
}
