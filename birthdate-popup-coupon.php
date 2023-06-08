<?php

/**
 * @package Bithdate-popup-coupon
 */
/*
Plugin Name: Birthdate Popup Coupon
Plugin URI: https://oswaldohuillca.vercel.app
Description: Asigna cupones por el cumpleaños del cliente en woocommerce.
Version: 0.1
Requires at least: 5.0
Requires PHP: 5.2
Author: Oswaldo
Author URI: https://oswaldohuillca.vercel.app
License: GPLv2 or later
Text Domain: wcbirthdate
*/


/*
plugin
*/

defined('ABSPATH') || exit;

define('COUPON_EXPIRY_DATE', 'coupon_expiry_date');
define('COUPON_CODE', 'coupon_code');
define('COUPON_ID', 'coupon_id');
define('BILLING_DNI', 'billing_dni');
define('BILLING_BIRTH_DATE', 'billing_birth_date');

define('COUPON_AMOUNT', 15);
define('DISCOUNT_TYPE', 'percent');

require_once __DIR__ . '/customer.php';

function bt_script_registro()
{
  // wp_register_style('bt-daysi-ui', 'https://cdn.jsdelivr.net/npm/daisyui@3.0.0/dist/full.css');
  wp_register_style("bt-registro", plugins_url('/assets/index.css', __FILE__));
  wp_register_script("confetti-js", 'https://cdn.jsdelivr.net/npm/js-confetti@latest/dist/js-confetti.browser.js');
  wp_register_script("bt-registro", plugins_url('/assets/index.js', __FILE__), ['confetti-js']);

  wp_enqueue_style('bt-registro');
  wp_enqueue_script("bt-registro");
}

add_action("wp_enqueue_scripts", "bt_script_registro");


function bt_render_modal($coupon_code)
{
?>
  <button class="btn" onclick="bt_birthdate_modal.showModal()">open modal</button>
  <dialog id="bt_birthdate_modal" class="bt-modal">
    <form method="dialog" class="bt-modal-box">
      <button class="btn bt-btn-close" id="bt_close">
        <svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M18.8315 6.91504L6.83154 18.915" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          <path d="M6.83154 6.91504L18.8315 18.915" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>

      </button>

      <div class="box-content">

        <h3 class="bt-title">¡CELEBRAMOS <br> TODO EL MES!</h3>

        <div class="box-text">
          <p class="bt-description">
            ¡FELIZ CUMPLEAÑOS! <br>
            Ingresa desde tu cuenta y escribe el cupon:
          </p>
          <a href="#" class="bt-coupon"> <?= $coupon_code ?></a>
        </div>
      </div>

    </form>
  </dialog>
<?php
}

//Sirve para la eliminacion del cupon
function deleteUserMetaCoupon($user_id)
{
  delete_user_meta($user_id, COUPON_EXPIRY_DATE);
  delete_user_meta($user_id, COUPON_CODE);
}

function bt_create_custom_coupon($code, $coupon_amount, $discount_type, $usage_limit, $expiry_date, $user_id)
{
  global $wpdb;

  $post_title = $code;
  $post_content = 'Descuento: ' . $coupon_amount . $discount_type;
  $post_excerpt = 'Cupón de cumpleaños generado automáticamente.';

  // Crear el post del cupón
  $wpdb->insert(
    $wpdb->prefix . 'posts',
    array(
      'post_title' => $post_title,
      'post_content' => $post_content,
      'post_excerpt' => $post_excerpt,
      'post_status' => 'publish',
      'post_type' => 'shop_coupon'
    )
  );

  $coupon_id = $wpdb->insert_id;

  // Establecer los metadatos del cupón
  update_post_meta($coupon_id, 'discount_type', $discount_type);
  update_post_meta($coupon_id, 'coupon_amount', $coupon_amount);
  update_post_meta($coupon_id, 'individual_use', 'no');
  update_post_meta($coupon_id, 'product_ids', '');
  update_post_meta($coupon_id, 'exclude_product_ids', '');
  update_post_meta($coupon_id, 'usage_limit', $usage_limit);
  update_post_meta($coupon_id, 'expiry_date', strtotime($expiry_date));

  // Guardar el código del cupón
  update_post_meta($coupon_id, 'code', $code);

  // Asociar el cupón con el usuario
  update_user_meta($user_id, COUPON_EXPIRY_DATE, strtotime($expiry_date));
  update_user_meta($user_id, COUPON_CODE, $code);
  // update_user_meta($user_id, COUPON_ID, $coupon_id);

  return $coupon_id;
}



function bt_render_popup_html()
{
  get_customer(function ($customer) { // Obtenemos el cliente

    $customer_id = $customer->get_id(); // Obtenemos el ID del cliente

    get_customer_birthdate($customer_id, function ($customer_birthday) use ($customer_id) { // Obtenemos cumpleaños del cliente

      $coupon_expiry_date = get_user_meta($customer_id, COUPON_EXPIRY_DATE, true);
      $coupon_code = get_user_meta($customer_id, COUPON_CODE, true);

      // validamos si existe el cupon
      // si no existe eliminados los metadatos del usuario
      $obj_coupon_code = new WC_Coupon($coupon_code);
      if (!$obj_coupon_code->get_id()) {
        deleteUserMetaCoupon($customer_id);
      }

      $coupon_code = get_user_meta($customer_id, COUPON_CODE, true);

      // obtenemos el mes actual en numero
      $current_month = date('m');
      $current_minute = date('i');
      $client_month_birth = date('m', strtotime($customer_birthday));
      $last_date = date('Y-m-t', strtotime($customer_birthday));

      // Si la mes actual NO es igual al mes de cumpleaños del cliente retorna.
      if ($current_month !== $client_month_birth) {
        // Eliminanos el cupon para no tener relleno en la base de datos
        if (!empty($coupon_code)) {
          $obj_coupon_code = new WC_Coupon($coupon_code);
          if (!empty($obj_coupon_code->get_id())) {
            wp_delete_post($obj_coupon_code->get_id());
          }
        }
        // Eliminanos metadatos del usuario para no tener relleno en la base de datos
        deleteUserMetaCoupon($customer_id);
        return;
      };

      // validamos si existe el tiempo de expiracion y si el timestamp actual es menor al timestamp de la expiracion del cupón
      // si esto es verdad indica que aun esta en dentro del mes de su cumpleaños
      if ($coupon_code && $coupon_expiry_date && time() < intval($coupon_expiry_date)) {
        bt_render_modal($coupon_code);
        return;
      }


      $codigo = "feliz{$current_month}{$current_minute}";
      $cantidadMaxima = 0;
      $expirationDate = $last_date;

      $coupon_id = bt_create_custom_coupon($codigo, COUPON_AMOUNT, DISCOUNT_TYPE, $cantidadMaxima, $expirationDate, $customer_id);

      bt_render_modal($codigo);
    });
  });
}


add_action('wp_footer', 'bt_render_popup_html');




function validate_customer_coupun($passed, $coupon)
{
  get_customer(function ($customer) use ($coupon, $passed) {

    $customer_id = $customer->get_id();

    $billing_dni = get_user_meta($customer_id, BILLING_DNI, true);

    $coupon_code = $coupon->get_code();
    if (strlen($coupon_code) < 10) {
      $passed = false;
      wc_add_notice('El cupón debe tener al menos 6 letras.', 'error');
    }
    return $passed;
  });
}

// add_filter('woocommerce_coupon_is_valid', 'validate_customer_coupun', 10, 2);
