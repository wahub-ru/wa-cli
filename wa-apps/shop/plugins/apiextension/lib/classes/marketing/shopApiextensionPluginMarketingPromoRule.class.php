<?php

/**
 * Helper class shopApiextensionPluginMarketingPromoRule
 *
 * @author Steemy, created by 25.08.2021
 */

class shopApiextensionPluginMarketingPromoRule
{

    /**
     * Вывод дополнительных полей в маркетинге промо у баннера
     */
    public function showAdditionalFieldsPromoBannerBackend()
    {
        $ruleType = waRequest::post('rule_type', null, waRequest::TYPE_STRING_TRIM);
        if($ruleType != 'banner') return;

        $ruleId = waRequest::post('rule_id', null, waRequest::TYPE_INT);
        $options = waRequest::post('options', [], waRequest::TYPE_ARRAY_TRIM);

        $rule = array();
        $banners = [[]];

        if($ruleId) {
            $promoRulesModel = new shopPromoRulesModel();
            $rule = $promoRulesModel->getByField(['id' => $ruleId, 'rule_type' => 'banner']);

            if($rule && $rule['rule_params']) {
                $banners = ifempty($rule['rule_params']['banners'], [[]]);
            }
        }

        if(!empty($rule['id'])) {
            $ruleName = 'rules[' . $rule['id'] . ']';
        } else {
            $ruleName = isset($options['ident']) ? 'rules[new][' . $options['ident'] . ']' : 'rules[new]';
        }

        $view = wa()->getView();
        $view->assign([
           'banners' => $banners,
           'rule_name' => $ruleName,
        ]);

        $template = wa()->getAppPath('plugins/apiextension/templates/helpers/marketing/promo/AdditionalFieldsPromoBanner.html', 'shop');

        echo $view->fetch($template);
    }
}