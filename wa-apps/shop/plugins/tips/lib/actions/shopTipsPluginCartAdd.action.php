<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2018
 * @license Webasyst
 */

class shopTipsPluginCartAddAction extends shopFrontendAction
{
    public function execute()
    {
        $url = wa()->getRouteUrl('shop/frontend/cart');

        $offer_id = waRequest::request('offer_id', waRequest::TYPE_STRING_TRIM);
        if (!$offer_id || !preg_match('/^\d+(s\d+)?$/', $offer_id)) {
            throw new Exception('Not Found');
        }

        $parts = explode('s', $offer_id);
        if ($sku_id = ifempty($parts, 1, null)) {
            $_POST['sku_id'] = $sku_id;
        } else {
            $_POST['product_id'] = $parts[0];
        }

        waRequest::setParam('noredirect', 1);

        $CartAddController = new shopFrontendCartAddController();

        try {
            $CartAddController->execute();

            // waJsonController::getError is not implemented
            // Не, ну а шо я таки могу сделать, если кто-то поленился написать 1 строчку в waJsonController?
            // Значит, будем извращаться
            $ref = new ReflectionClass('shopFrontendCartAddController');
            $refprop = $ref->getProperty('errors');
            $refprop->setAccessible(true);
            $errors = $refprop->getValue($CartAddController);
            if (!empty($errors)) {
                if (!is_string($errors)) {
                    if (is_array($errors)) {
                        $errors = implode('. ', $errors);
                    } elseif (is_object($errors) && method_exists($errors, '__toString') && is_callable($errors, '__toString')) {
                        $errors = (string)$errors;
                    } else {
                        $errors = 'Ошибка добавления в корзину';
                    }
                }
                throw new waException($errors);
            }

            $this->redirect($url, 302);
            return;
        } catch (waException $e) {
            $error = $e->getMessage();
        } catch (ReflectionException $e) {
            $error = 'Ошибка добавления в корзину';
        }

        $this->setLayout(new shopFrontendLayout());
        $this->template = 'string:<h1>Ошибка</h1><p>{$error|escape}</p>';
        $this->view->assign('error', $error);
    }
}
