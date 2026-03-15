<?php
// Delete old files
$files = array(
    wa()->getAppPath("plugins/parsing/lib/actions/backend/shopParsingPluginBackendImporthh.controller.php", "shop"),
);

try {
    foreach ($files as $file) {
        if (file_exists($file)) {
            waFiles::delete($file, true);
        }
    }
} catch (Exception $e) {
    
}