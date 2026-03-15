<?php

$model = new waModel();
$sql = 'ALTER TABLE shop_seoredirect2_redirect DROP COLUMN `execute`';

try
{
	$model->query($sql);
}
catch (waException $e)
{

}