<?php

class shopBagcalculatorPlugin extends shopPlugin
{
    public function routingHandler($route)
    {
        if (wa()->getEnv() === 'frontend') {
            return include dirname(__FILE__).'/../config/routing.php';
        }
    }
}
