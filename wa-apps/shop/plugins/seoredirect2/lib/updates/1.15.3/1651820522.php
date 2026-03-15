<?php

try
{
   $model = new waModel();
   $model->exec("INSERT INTO wa_app_settings (app_id, name, value) VALUES ('shop.seoredirect2', 'clean_errors', '30');");
   $model->exec("INSERT INTO wa_app_settings (app_id, name, value) VALUES ('shop.seoredirect2', 'clean_errors_data', '7');");
}
catch (Exception $e)
{
}
