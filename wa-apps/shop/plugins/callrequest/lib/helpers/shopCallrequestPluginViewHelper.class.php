<?php

class shopCallrequestPluginViewHelper extends waPluginViewHelper
{
    /**
     * Вывод встроенного калькулятора
     */
    public function calculator()
    {
        return '<div class="cr-calculator-inline" data-callrequest-calculator></div>';
    }
}