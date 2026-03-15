<?php

/**
 * UPDATE 1.0.3
 *
 * @author Steemy, created by 08.11.2021
 */

// Delete old files
$files = array(
    wa()->getAppPath("plugins/apiextension/classes/shopApiextensionPluginReviewsHelper.class.php", "shop"),
    wa()->getAppPath("plugins/apiextension/product/shopApiextensionPluginProductHelper.class.php", "shop"),
    wa()->getAppPath("plugins/apiextension/marketing/promo/shopApiextensionPluginMarketingPromoRuleHelper.class.php", "shop"),
    wa()->getAppPath("plugins/apiextension/customer/shopApiextensionPluginCustomerHelper.class.php", "shop"),
    wa()->getAppPath("plugins/apiextension/category/shopApiextensionPluginCategoryHelper.class.php", "shop"),
);

try {
    foreach ($files as $file) {
        if (file_exists($file)) {
            waFiles::delete($file, true);
        }
    }
} catch (Exception $e) {

}

// Create table shop_apiextension_reviews_affiliate
$model = new waModel();
try {
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `shop_apiextension_reviews_affiliate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL DEFAULT 0,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `affiliate` int(11) NOT NULL,
  `create_datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `state` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `shop_apiextension_review_affiliate_review_id` (`review_id`),
  KEY `shop_apiextension_review_affiliate_contact_id_product_id_state` (`contact_id`,`product_id`,`state`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8
SQL;
    $model->exec($sql);

} catch (waDbException $e) {

}