<?php

class mytextHelper
{
    public function getText()
    {
        $plugin = wa('shop')->getPlugin('mytext');
        $settings = $plugin->getSettings();
        return $settings['custom_text'] ?? '';
    }
}