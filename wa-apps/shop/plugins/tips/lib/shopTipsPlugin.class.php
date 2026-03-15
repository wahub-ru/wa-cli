<?php
/**
 * Tips plugin for Shop-Script 5+
 *
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version 1.4.0
 * @copyright Serge Rodovnichenko, 2015-2016
 * @license MIT
 */

/**
 * Main plugin class
 */
class shopTipsPlugin extends shopPlugin
{
    public function hookBackendProduct($product)
    {
        $result = array();
        $format = $this->getSettings('product_date');
        if (in_array($format, array('date', 'datetime'))) {
            $format = 'human' . $format;
            $result['toolbar_section'] =
                '<div class="small"><table class="zebra bottom-bordered"><tr><td>' . _wp('Created') . ':</td><td style="white-space: nowrap;text-align: right; font-weight: bold">' .
                waDateTime::format($format, strtotime($product['create_datetime'])) .
                '</td></tr>' .
                ($product['edit_datetime'] !== null ? '<tr><td>' . _wp('Updated') . ':</td><td style="white-space: nowrap;text-align: right;font-weight: bold">' . waDateTime::format($format, strtotime($product['edit_datetime'])) . '</td></tr>' : '') .
                '</table></div>';
        }

        if ((bool)$this->getSettings('edit_history')) {
            $view = wa()->getView();
            $result['tab_li'] = $view->fetch($this->path . '/templates/hooks/backend_product_tab_li.html');
        }

        return $result;
    }

    public function hookBackendProducts()
    {
        if ((bool)$this->getSettings('edit_history')) {
            $this->addJs('js/tips.' . (waSystemConfig::isDebug() ? 'js' : 'min.js'));
        }
    }

    /**
     * Returns info about coupon by its ID
     *
     * @param int|string $coupon_id
     * @return array|null
     */
    public static function getCouponById($coupon_id)
    {
        $Coupon = new shopCouponModel();

        return $Coupon->getById($coupon_id);
    }

    public function hookBackendOrder($params)
    {
        $est_delivery = ifset($params, 'params', 'shipping_est_delivery', '');
        if (!$est_delivery) {
            return array();
        }

//        $est_delivery = htmlentities($est_delivery, ENT_QUOTES, 'UTF-8');
        $est_delivery_str = _wp('Estimated delivery date');

        $html = <<<EOT
<script type="text/javascript">
$(function(){ $('p.s-order-address', 'div.s-order').before('<p style="margin-bottom: 0.5em"><span class="gray">$est_delivery_str &mdash;</span> $est_delivery</p>') });
</script>
EOT;
        return array('info_section' => $html);
    }

    public function routing($route = array())
    {
        if ($this->getSettings('add2cart')) {
            return array(
                'plugin_tips/to_cart/' => 'cart/add'
            );
        }
        return array();
    }

    protected function addJs($url, $is_plugin = true)
    {
        wa()->getResponse()->addJs(
            ltrim(wa()->getAppStaticUrl('shop'), '/') . $this->getUrl($url, $is_plugin) . '?' . (waSystemConfig::isDebug() ? time() : 'v=' . $this->getVersion()),
            false
        );
    }
}
