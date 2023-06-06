<?php


function get_counpon_by_code($code, $callback)
{
  $coupon = new WC_Coupon($code);
  if (!$coupon) wc_add_notice('El cupón no existe o ha expirado', 'error');

  $callback($coupon);
}
