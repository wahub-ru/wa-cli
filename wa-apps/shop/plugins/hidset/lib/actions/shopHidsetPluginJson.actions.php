<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2023 waResearchLab
 */

class shopHidsetPluginJsonActions extends waJsonActions
{
    /**
     * @param array $data
     * @param array $fields
     */
    public function checkRequiredFields($data, $fields)
    {
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        foreach ($fields as $f) {
            if (!isset($data[$f]) || ($data[$f] != '0' && !$data[$f])) {
                $this->setError(sprintf(shopHidsetPlugin::ERROR_REQUIRED_PARAMETER, $f));
            }
        }
    }

    public function checkPremium()
    {
        if (!waLicensing::check('shop/plugins/hidset')->isPremium()) {
            $this->setError(shopHidsetPlugin::ERROR_NEED_PREMIUM);
        }
    }

    /**
     * @param string $message
     * @param array $data
     */
    public function setError($message, $data = array())
    {
        if (wa()->getEnv() == 'cli') {
            throw new waException($message);
        }
        if ($data) {
            $this->errors[] = array($message, $data);
        } else {
            $this->errors[] = $message;
        }
    }
}