<?php

class shopCouponbackendPluginDiscount extends shopDiscounts
{
  public static function backendCoupon(&$order, $contact, $apply) {
      $discount = [];
      if (self::isEnabled('coupons')) {
          $discount = self::byBackendCoupons($order, $contact, $apply);
      }
      return $discount;
  }

  protected static function getBackendCoupon()
  {
      $result = [];
      $cm = new shopCouponModel();
      $coupon_code = wa('shop')->getStorage()->read('backend_promo_code');

      if ($coupon_code) {
          $coupon = $cm->getByField('code', $coupon_code);

          // Coupon must be available for use.
          if ($coupon && shopCouponModel::isEnabled($coupon)) {
              $result = $coupon;
          }
      }

      return $result;
  }

  protected static function byBackendCoupons(&$order, $contact, $apply)
  {
      $coupon = self::getBackendCoupon();
      $result = [];

      // If there is no coupon or there are no items in the order, do not apply the coupon
      if (!$coupon || empty($order['items'])) {
          return $result;
      }

      $description = _w('Coupon code').' '.$coupon['code'];

      switch ($coupon['type']) {
          case '$FS':
              $result = self::getFreeShippingByCoupons($order, $description);
              break;
          case '%':
              $result = self::getPercentDiscountByCoupons($order, $coupon, $description);
              break;
          default:
              $result = self::getIntegerDiscountByCoupons($order, $coupon, $description);
              break;
      }

      if ($result) {
          if ($apply && !$order['id']) {
              (new shopCouponModel())->useOne($coupon['id']);
          }

          $order['params']['coupon_id'] = $coupon['id'];
          // Record total coupon discount
          $order['params']['coupon_discount'] = $result['coupon_discount'];
          // Remove from result as unnecessary
          unset($result['coupon_discount']);
          //Say that free shipping has been applied
          if ($coupon['type'] == '$FS') {
              $order['params']['coupon_free_shipping'] = 1;
          }
      }

      return $result;
  }
}
