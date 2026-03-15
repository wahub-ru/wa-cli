<?php


try {
    $model = new waModel();

    $model->exec("CREATE TABLE IF NOT EXISTS `shop_seoredirect2_errors_data` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `hash` varchar(32) NOT NULL,
      `error_id` int(11) NOT NULL,
      `ip` varchar(32) NOT NULL,
      `user_agent` varchar(2083) NOT NULL,
      `browser` varchar(255) NOT NULL,
      `os` varchar(255) NOT NULL,   
      `create_datetime` datetime NOT NULL,   
      PRIMARY KEY (`id`),
      INDEX `hash` (`hash`),
      INDEX `error_id` (`error_id`),
      INDEX `ip` (`ip`),  
      INDEX `os` (`os`),
      INDEX `browser` (`browser`)
    )
    COLLATE='utf8_general_ci'
    ENGINE=MyISAM;
    ");

} catch (Exception $e) {

}