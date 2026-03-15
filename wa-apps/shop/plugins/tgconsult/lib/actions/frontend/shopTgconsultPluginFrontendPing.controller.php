<?php
class shopTgconsultPluginFrontendPingController extends waJsonController
{
    public function execute()
    {
        $this->response = ['ok' => true, 'ts' => time()];
    }
}
