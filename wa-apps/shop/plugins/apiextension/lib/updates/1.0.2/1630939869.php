<?php

/**
 * UPDATE 1.0.2
 *
 * @author Steemy, created by 25.08.2021
 */

$model = new waModel();
try {
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `shop_apiextension_reviews_vote` (
  `review_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `vote_like` int(1) NOT NULL DEFAULT 0,
  `vote_dislike` int(1) NOT NULL DEFAULT 0,
  UNIQUE KEY `shop_apiextension_reviews_vote_reviews_id_contact_id` (`review_id`,`contact_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8
SQL;
    $model->exec($sql);

} catch (waDbException $e) {

}

try {
    $model->query('SELECT apiextension_votes FROM shop_product_reviews WHERE 0');
} catch(waDbException $e) {
    $model->query('ALTER TABLE `shop_product_reviews` ADD `apiextension_votes` VARCHAR(50) NULL AFTER `text`');
}

// Delete old files
$files = array(
    wa()->getAppPath("plugins/apiextension/models/shopApiextensionPlugin.model.php", "shop"),
    wa()->getAppPath("plugins/apiextension/classes/shopApiextensionPluginHelper.class.php", "shop"),
);

try {
    foreach ($files as $file) {
        if (file_exists($file)) {
            waFiles::delete($file, true);
        }
    }
} catch (Exception $e) {

}