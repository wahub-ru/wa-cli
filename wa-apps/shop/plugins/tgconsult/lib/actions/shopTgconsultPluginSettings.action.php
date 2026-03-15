<?php
class shopTgconsultPluginSettingsAction extends waViewAction
{
    public function execute()
    {


        $plugin_id = 'tgconsult';
        /** @var shopPlugin $plugin */
        $plugin = waSystem::getInstance()->getPlugin($plugin_id, true);

        $namespace = wa()->getApp().'_'.$plugin_id;
        $settings  = (array) $plugin->getSettings();
        $defaults = [
            'welcome'               => 'Здравствуйте! Чем помочь?',
            'manager_name'          => 'Менеджер',
            'manager_name_mode'     => 'settings',
            'icon_color'            => '#0D6EFD',
            'widget_position'       => 'right',
            'widget_offset_side'    => 22,
            'widget_offset_bottom'  => 70,
            'working_hours_enabled' => 0,
            'working_timezone'      => date_default_timezone_get() ?: 'UTC',
            'offhours_autoreply'    => 'Сейчас мы вне графика работы. Оставьте, пожалуйста, ваши контакты для связи, и мы ответим в рабочее время.',
            'work_mon_enabled'      => 1,
            'work_mon_start'        => '09:00',
            'work_mon_end'          => '18:00',
            'work_tue_enabled'      => 1,
            'work_tue_start'        => '09:00',
            'work_tue_end'          => '18:00',
            'work_wed_enabled'      => 1,
            'work_wed_start'        => '09:00',
            'work_wed_end'          => '18:00',
            'work_thu_enabled'      => 1,
            'work_thu_start'        => '09:00',
            'work_thu_end'          => '18:00',
            'work_fri_enabled'      => 1,
            'work_fri_start'        => '09:00',
            'work_fri_end'          => '18:00',
            'work_sat_enabled'      => 0,
            'work_sat_start'        => '10:00',
            'work_sat_end'          => '16:00',
            'work_sun_enabled'      => 0,
            'work_sun_start'        => '10:00',
            'work_sun_end'          => '16:00',
        ];
        $settings = array_merge($defaults, $settings);
        if (!isset($settings['widget_position']) && isset($settings['position'])) {
            $settings['widget_position'] = $settings['position'];
        }
        if (!isset($settings['widget_offset_side']) && isset($settings['offset_side'])) {
            $settings['widget_offset_side'] = $settings['offset_side'];
        }
        if (!isset($settings['widget_offset_bottom']) && isset($settings['offset_bottom'])) {
            $settings['widget_offset_bottom'] = $settings['offset_bottom'];
        }
        $settings['widget_position'] = shopTgconsultPlugin::normalizeWidgetPosition(
            ifset($settings, 'widget_position', 'right')
        );

        $timezone_list = DateTimeZone::listIdentifiers();
        sort($timezone_list, SORT_STRING);
        $app_url   = wa()->getAppUrl('shop');
        $root_abs  = rtrim(wa()->getRootUrl(true), '/');
        $tgc_webhook_auto = $root_abs.'/tgconsult/webhook/';

        $this->getResponse()->setTitle(_w(sprintf('Plugin %s settings', $plugin->getName())));
        $this->view->assign(compact('namespace','settings','app_url','tgc_webhook_auto','plugin_id','timezone_list'));
        $this->setTemplate(wa()->getAppPath('plugins/tgconsult/templates/actions/settings/settings.html', 'shop'));
    }
}
