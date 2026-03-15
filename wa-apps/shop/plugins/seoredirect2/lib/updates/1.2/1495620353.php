<?php

$redirect_model = new shopSeoredirect2RedirectModel();
$redirects = $redirect_model->getAll();
try
{
	foreach ($redirects as $key => $redirect)
	{
		$redirect_model->updateById($redirect['id'], array('sort' => $key));
	}
}
catch (waException $e)
{
}
