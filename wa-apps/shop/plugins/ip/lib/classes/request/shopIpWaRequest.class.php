<?php


class shopIpWaRequest implements shopIpRequest
{
	public function getIp()
	{
		$ips = preg_split('/[^0-9A-Fa-f\:\.]+/', waRequest::getIp());
		
		return reset($ips);
	}
}