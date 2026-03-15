<?php

try {
    $adapter = waDbConnector::getConnection();

    $path = wa()->getAppPath('plugins/ip', 'shop') . '/lib/vendor/data/';


    function shopIpKladrApiImportFile($path, $adapter)
    {
        $lines = file($path);
        $templine = '';
        foreach ($lines as $line) {
            if (substr($line, 0, 2) == '--' || $line == '')
                continue;

            $templine .= $line;
            if (substr(trim($line), -1, 1) == ';') {
                $adapter->query($templine);
                $templine = '';
            }
        }
        waLog::log($path . ': file successfully imported', 'ip_install_log.log');
    }

    shopIpKladrApiImportFile($path . 'shop_ip_kladr_api_region.sql', $adapter);
    shopIpKladrApiImportFile($path . 'shop_ip_kladr_api_city.sql', $adapter);

} catch (Exception $exception) {
    waLog::dump($exception);
}