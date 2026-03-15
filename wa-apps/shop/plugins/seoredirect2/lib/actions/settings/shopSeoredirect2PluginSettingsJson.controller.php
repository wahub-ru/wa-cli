<?php

class shopSeoredirect2PluginSettingsJsonController extends waJsonController
{
	public function execute()
	{
		$method = waRequest::get('method');
		$method = mb_strtolower($method);
		if ($method == 'get')
		{
			$this->get();
		}
		elseif ($method == 'getdomains')
		{
			$this->getDomains();
		}
		elseif ($method == 'getredirectdomains')
		{
			$this->getRedirectDomains();
		}
		elseif ($method == 'geterrordomains')
		{
			$this->getErrorDomains();
		}
		elseif ($method == 'save')
		{
			$this->save();
		}

	}

	public function get()
	{
		$plugin = shopSeoredirect2Plugin::getInstance();
		$this->response = $plugin->getSettings();
	}

	public function getDomains()
	{
		$routing = new shopSeoredirect2WaRouting();
		$domains = $routing->getDomains();
		foreach ($domains as &$domain){
            if(function_exists('idn_to_utf8')) {
                $domain = idn_to_utf8($domain, 0, INTL_IDNA_VARIANT_UTS46);
            }
        }
		$this->response = $domains;
	}

	public function getRedirectDomains(){
        $redirect_model = new shopSeoredirect2RedirectModel();
        $result = $redirect_model->select('DISTINCT(domain)')->fetchAll();

        $domains = array();
        foreach($result as $d){
            if($d['domain'] !== 'general') {
                $d['domain'] = $d['domain'] === "" ? 'none' : $d['domain'];
                if (function_exists('idn_to_utf8')) {
                    $domains[] = idn_to_utf8($d['domain'], 0, INTL_IDNA_VARIANT_UTS46);
                } else {
                    $domains[] = $d['domain'];
                }
            }
        }

        $this->response = $domains;

    }

    public function getErrorDomains(){
        $redirect_model = new shopSeoredirect2ErrorsModel();
        $result = $redirect_model->select('DISTINCT(domain)')->fetchAll();
        $domains = array();
        foreach($result as $d){
            if($d['domain'] !== 'general') {
                if (function_exists('idn_to_utf8')) {
                    $domains[] = idn_to_utf8($d['domain'], 0, INTL_IDNA_VARIANT_UTS46);
                } else {
                    $domains[] = $d['domain'];
                }
            }
        }

        $this->response = $domains;

    }

	public function save()
	{
		$plugin = shopSeoredirect2Plugin::getInstance();
		$plugin->saveSettings(waRequest::post('settings', array()));
	}
}