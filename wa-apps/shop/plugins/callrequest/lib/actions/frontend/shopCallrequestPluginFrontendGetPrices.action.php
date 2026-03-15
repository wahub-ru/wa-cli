<?php

class shopCallrequestPluginFrontendGetPricesAction extends waViewAction
{
    public function execute()
    {
        try {
            $price_model = new shopCallrequestPriceModel();
            $prices = $price_model->getAll();

            // Устанавливаем заголовок для JSON
            $this->getResponse()->addHeader('Content-type', 'application/json');

            // Выводим JSON
            echo json_encode([
                'status' => 'ok',
                'prices' => $prices,
                'count' => count($prices)
            ], JSON_UNESCAPED_UNICODE);

            exit;

        } catch (Exception $e) {
            $this->getResponse()->addHeader('Content-type', 'application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}