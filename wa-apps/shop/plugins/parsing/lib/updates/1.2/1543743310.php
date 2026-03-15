<?php

$mysql = new waModel();

// Delete old files

$files = array(
    wa()->getAppPath("plugins/parsing/templates/actions/settings/Settings.html", "shop"),
	wa()->getAppPath("plugins/parsing/templates/actions/settings", "shop"),
	wa()->getAppPath("plugins/parsing/lib/config/settings.php", "shop"),
);

try {
    foreach ($files as $file) {
        if (file_exists($file)) {
            waFiles::delete($file, true);
        }
    }
	
	$mysql->query("ALTER TABLE `shop_parsing_plugin_sitemap` ADD `profile_id` INT(10) NOT NULL DEFAULT '0'");
	$mysql->query("ADD INDEX `profile_id` (`profile_id`)");
	
} catch (Exception $e) {
    
}