<?php
/** 
 * final checkout confirmation step. Shows all order details, then accepts the
 * confirmation via _POST if given and creates the new order record with a
 * circusOrder object
 *
 * $Id: checkout_confirm.php,v 1.20 2007/11/12 17:46:00 sbeam Exp $
 */
require_once(CONFIG_DIR.'cshop.config.php');
require_once('formex.class.php');
require_once(CSHOP_CLASSES_CART.'.class.php');
require_once(CSHOP_CLASSES_ORDER.'.class.php');
require_once(CSHOP_CLASSES_USER.'.class.php');
require_once(CSHOP_CLASSES_PAYMETHOD.'.class.php');
require_once(CSHOP_CLASSES_PAYMENT.'.class.php');
require_once(CSHOP_CLASSES_GIFTCARD.'.class.php');

// init page auth objects
page_open(array('sess'=>CSHOP_CLASSES_AUTH_SESSION, 'auth'=>CSHOP_CLASSES_AUTH_AUTH, 'perm'=>CSHOP_CLASSES_AUTH_PERM));
print '<pre>DEBUG: at line '.__LINE__.' of '.__FILE__."\n";
print_r($auth);
print '</pre>';

// flag for smarty
$smarty->assign('page_id', 'checkout_confirm');
$smarty->assign('pagetitle', 'Confirm Order');

$payment_error = "";
$payment_error_type = null;

$c = CSHOP_CLASSES_CART;
$cart = new $c($pdb);

$c = CSHOP_CLASSES_USER;
$user = new $c($pdb);

$uid = $user->get_auth_id();
$user->set_id($uid);

$cart_total = $cart->get_grandtotal();

$pay = null;
if ($cart_total > 0) {
    if (! $payid = $cart->get_payment_id()) {
        trigger_error('could not find payment info', E_USER_NOTICE);
        $payment_error = "Sorry, your payment record could not be found.  Please go back and re-enter the payment information";
        $payment_error_type = 'PAYMENT_MISSING';
    }
    else {
        /* now what it really is all about is payment */
        $c = CSHOP_CLASSES_PAYMETHOD;
        $pay = new $c($pdb);
        $pay->set_id($payid);
        if (!$pay->fetch()) {
            trigger_error('payment info cannot be re-used', E_USER_NOTICE);
            $payment_error = "Sorry, your payment info cannot be re-submitted. Please go back and re-enter the payment information";
            $payment_error_type = 'PAYMENT_RESUBMIT';
            $pay = null;
        }
    }
}

/* decide what currency to show. They would have set this in the cart */
$sess->register('CSHOP_CURRENCY_DISPLAY');
if (isset($_GET['curr'])) {
    $CSHOP_CURRENCY_DISPLAY = $_GET['curr'];
}
if (isset($CSHOP_CURRENCY_DISPLAY)) {
    $cart->set_display_currency($CSHOP_CURRENCY_DISPLAY);
}
if (count($cart->currency_opts) > 1) {
    $smarty->assign('currency_opts', $cart->currency_opts);
    $smarty->assign('current_currency_display', $cart->get_display_currency());
}


/** setup smarty with a method from the $cart object to convery currencies */
$smarty->register_modifier('currency_format', array(&$cart, 'currency_format'));


/** here is where the order is offically created **/
if (isset($_POST['op_confirm'])) {
    $c = CSHOP_CLASSES_ORDER;
    $order = new $c($pdb);
    $order->set_user($user);
    $order->set_cart($cart);
    $res = $order->create();

    $c = CSHOP_CLASSES_PAYMENT;
    $gate = new $c($user, $pay, $order);
    $gate->setErrorHandling (PEAR_ERROR_RETURN);

    $PAYMENT_SUCCESS = false;

    /* check all giftcards attached to this cart for validity. If any fail, set $payment_error_type */
    if (CSHOP_ACCEPT_GIFTCARDS) {
        $giftcardclass = CSHOP_CLASSES_GIFTCARD;
        foreach ($cart->get_giftcards() as $gc_vals) {
            $gc = new $giftcardclass($pdb);
            $gc->setErrorHandling (PEAR_ERROR_RETURN);
            $gc->set_id($gc_vals['id']);

            // send request to GC processor, make sure it's still valid for the req. amt
            $res = $gc->redeem($order);

            if (PEAR::isError($res)) {
                $payment_error = $res->getMessage();
                $payment_error_type = 'INVALID GIFTCARD';
                trigger_error("Giftcard could not be redeemed: $payment_error", E_USER_NOTICE);
            }
        }
    }

    /* TODO if any other than the first GC fail, or GCs succeed but CC fails
     * below, we have already debited thier cards. In case of any GC or CC
     * failure we need to re-credit all the GCs that have been bled dry */

    /* cart has a zero total */
    if (!$pay && $cart_total == 0 && $payment_error_type) { 
        if (CSHOP_ACCEPT_GIFTCARDS) { // paranoia
            $PAYMENT_SUCCESS = true; // because we dont actually need payments
        }
        elseif (CSHOP_DO_TAKE_COUPONS) { 
            // TODO calc if discount_amt = grandtotal
        }
    }
    /* they need to pay up - check CC# or other */
    elseif ($pay && !$payment_error_type) {
        /* check a.net, PFP, etc for this guy */
        $res = $gate->authorize(); // here is where it all happens

        $order->record_transaction($gate);

        if (!PEAR::isError($res)) {
            $PAYMENT_SUCCESS = true;
        }
        else {
            $pay->kill();
            $payment_error = $res->getMessage();
            $payment_error_type = $gate->get_trans_result();
            trigger_error("$payment_error : $payment_error_type", E_USER_NOTICE);
        }
    }

    if (!$PAYMENT_SUCCESS) {
        $order->kill(); 
    }
    else { // SUCCESS!!
        $order->finalize($pay, $gate, $cart);

        $key = $order->fetch_token();
        $_SESSION['cshop_is_new_order'] = $key;
        header("Location: order_detail.php?tok=$key\n");

        $user->unset_sesskey();

        /* if anon account, unset the bypass flag so they will be asked for password next time */
        if (defined('CSHOP_ALLOW_ANON_ACCOUNT') and $auth->has_bypass_flag()) {
            $auth->set_bypass_flag(false);
        }

        exit();
    }
}
/** **/

if ($pay) {
    $payment = $pay->fetch();
    $payment['ccno'] = substr($payment['ccno'], -4, 4);
    $smarty->assign('payment_info', $payment);
}


$cart_totals = $cart->fetch_totals();
$subtotal = $cart->get_subtotal();
$grand_total = $cart->get_grandtotal();

/** set and display ***********************************************************/
$smarty->assign('cart_totals', $cart_totals);
$smarty->assign('grand_total', $grand_total); 
$smarty->assign('cart', $cart->fetch_items());
$smarty->assign('cart_itemcount', $cart->count_items());
$smarty->assign('subtotal', number_format($subtotal, 2));

$smarty->assign('discount_amt', $cart->get_discount($subtotal));
$smarty->assign('discount_descrip', $cart->get_discount_descrip());

if (isset($payment_error_type)) {
    $smarty->assign('payment_error_msg', $payment_error);
    $smarty->assign('payment_error', $payment_error_type);
}

if ($pay) {
    if ( !$billing = $user->fetchBillingAddr()) {
        trigger_error("did not get billing address for uid $uid", E_USER_ERROR);
    }
    else {
        $smarty->assign('billing', $billing);
        $billing['country'] = formex_field::_get_countries(true, $billing['country']);
    }
}

if (!$cart->requires_shipping()) {
    $shipping = array('name' => 'n/a');
}
else {
    if (!$shipping = $user->fetchShippingAddr()) {
        trigger_error("did not get shipping address for uid $uid", E_USER_ERROR);
    }
    $shipping['country'] = formex_field::_get_countries(true, $shipping['country']);
}


if (defined('CSHOP_ACCEPT_GIFTCARDS') && CSHOP_ACCEPT_GIFTCARDS) {
    $gc_total = $cart->get_giftcard_total();
    $smarty->assign('giftcards', $cart->get_giftcards());
    $smarty->assign('gc_total', $gc_total);
}


$smarty->assign('user', $user->fetch());
$smarty->assign('user_email', $user->get_email());
$smarty->assign('shipping', $shipping);

$smarty->display('float:checkout_confirm.tpl');
