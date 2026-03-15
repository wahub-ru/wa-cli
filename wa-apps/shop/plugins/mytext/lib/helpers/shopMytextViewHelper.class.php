<?php

class shopMytextViewHelper
{
    public static function getText()
    {
        $plugin = wa('shop')->getPlugin('mytext');
        $settings = $plugin->getSettings();
        return htmlspecialchars($settings['custom_text'] ?? '', ENT_QUOTES, 'UTF-8');
    }
}