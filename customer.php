<?php


class CustomerCoupon
{
  public $ID;
  public $name;
  public $birthdate;
  public $expiry_coupon;
  protected $birth_month;

  public function __construct($args)
  {
    $this->ID = $args['ID'];
    $this->name = $args['name'];
    $this->birthdate = $args['birthdate'];
    $this->expiry_coupon = $args['expiry_coupon'];
    $this->birth_month = $args['birth_month'];
  }

  public function get_birth_month()
  {
    return date('m', strtotime($this->birthdate));
  }


  public function get_last_birth_month()
  {
    return date('Y-m-t', strtotime($this->birthdate));
  }

  public function delete_meta()
  {
    delete_user_meta($this->ID, COUPON_EXPIRY_DATE);
    delete_user_meta($this->ID, COUPON_CODE);
  }
}


function get_customer($callback)
{
  $user_id = get_current_user_id();
  if ($user_id == 0) return;

  $customer = new WC_Customer($user_id);

  // si no existe el cliente retorna
  if (!$customer) return;

  // validamos si el cliente es de rol customer
  if ($customer->get_role() !== 'customer') return;

  $callback($customer);
}




function get_customer_birthdate($customer_id, $callback)
{
  $customer_birthday = get_user_meta($customer_id, BILLING_BIRTH_DATE, true);
  if (!$customer_birthday) return;
  $callback($customer_birthday);
}
