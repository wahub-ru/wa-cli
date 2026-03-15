<?php

class shopPagevisitorcounterPluginFrontendTrackAction extends waViewAction
{
    public function execute()
    {
        // Этот action должен вызываться только через AJAX (POST)
        if (!waRequest::isXMLHttpRequest() || waRequest::method() != 'post') {
            throw new waException('Forbidden', 403);
        }

        $pageId = waRequest::post('page_id', 0, 'int');
        $visitorHash = waRequest::post('visitor_hash', '', 'string');

        if ($pageId && $visitorHash) {
            // Создаем хэш для дополнительного обезличивания и экономии места
            $visitorHashToStore = md5($visitorHash . waRequest::getUserAgent());

            $model = new shopPagevisitorcounterModel();
            $model->trackView($pageId, $visitorHashToStore);
        }

        // Возвращаем JSON-ответ
        $this->getResponse()->setContentType('application/json');
        $this->getView()->assign('status', 'ok');
        $this->setTemplate('Track.html'); // Можно использовать простой JSON-шаблон
    }
}