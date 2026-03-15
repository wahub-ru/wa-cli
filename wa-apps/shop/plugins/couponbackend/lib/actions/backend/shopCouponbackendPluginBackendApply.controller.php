<?php

class shopCouponbackendPluginBackendApplyController extends waJsonController
{
  public function execute() {
    $type = waRequest::post('type');
    $code = waRequest::post('code');
    switch($type) {
      case 'clear':
        wa('shop')->getStorage()->remove('backend_promo_code');
      break;
      case 'apply':
        wa('shop')->getStorage()->remove('backend_promo_code');
        $this->response['valid'] = false;
        $code = trim($code);
        $cm = new shopCouponModel();
        if ($code) {
          $coupon = $cm->getByField('code', $code);
          if ($coupon && shopCouponModel::isEnabled($coupon)) {
              wa()->getStorage()->write('backend_promo_code', $code);
              $this->response['valid'] = true;
          }
        }
      break;
      default:
        $this->errors = "Invalid action";
    }
  }
}
