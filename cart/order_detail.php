<?php
/**
 * show orders to the user, if the order belongs to him
 *
 * $Id: order_detail.php,v 1.16 2008/05/01 20:21:15 sbeam Exp $
 */
require_once(CONFIG_DIR . 'cshop.config.php');
require_once('formex.class.php');
require_once(CSHOP_CLASSES_ORDER.'.class.php');
require_once(CSHOP_CLASSES_USER.'.class.php');
require_once(CSHOP_CLASSES_CART.'.class.php');

// init page auth objects
page_open(array('sess'=>'jen_Session', 'auth'=>'selfAuth'));

// flag for smarty
$smarty->assign('page_id', 'order_detail');
$smarty->assign('pagetitle', 'Order Detail');

$c = CSHOP_CLASSES_USER;
$user = new $c($pdb);

$uid = $user->get_auth_id();
$user->set_id($uid);
$orderinfo = null;
$err = null;

/** setup smarty with a method from the $cart object to convery currencies */
$c = CSHOP_CLASSES_CART;
$cart = new $c($pdb);
$smarty->register_modifier('currency_format', array(&$cart, 'currency_format'));

$c = CSHOP_CLASSES_ORDER;
$order = new $c($pdb);

$IS_NEW_ORDER = false;

if (isset($_SESSION['cshop_is_new_order'])) {
    $order->set_id_by_token($_SESSION['cshop_is_new_order']);
    unset($_SESSION['cshop_is_new_order']);
    $IS_NEW_ORDER = true;
}
elseif (isset($_GET['tok'])) {
    $order->set_id_by_token($_GET['tok']);
}
elseif (isset($_GET['oid'])) {
    $order->set_id($_GET['oid']);
}
else {
    trigger_error("no order parameter was given", E_USER_ERROR);
}

if ($IS_NEW_ORDER) {
    $smarty->assign('new_order', true);
}

if (!$orderinfo = $order->fetch()) {
    trigger_error("The given parameter did not match any order", E_USER_ERROR);
}
else {
    if ($uid != $orderinfo['user_id']) {
        trigger_error("illegal attempt to access order", E_USER_ERROR);
    }
    else {
        $orderitems = $order->fetch_items();
        $orderinfo['cc_number'] = 'xxxxxxxxxxxx'.substr($orderinfo['cc_number'], -4, 4);
        $smarty->assign('orderinfo', $orderinfo);

        $grand_total = $orderinfo['amt_billed_to_date'];
        $cart_totals = $order->fetch_totals();

        $gc_total = $order->get_giftcard_total();
        $smarty->assign('giftcards', $order->get_giftcards());
        $smarty->assign('gc_total', $gc_total);

        /** set and display ***********************************************************/
        $smarty->assign('cart_totals', $cart_totals);
        $smarty->assign('grand_total', $grand_total); 

        $smarty->assign('cart', $orderitems);
        $smarty->assign('billing', $order->fetch_addr('billing'));
        $smarty->assign('shipping', $order->fetch_addr('shipping'));

        //$smarty->assign('discount_amt', abs($cart_totals['discount']['amt']));
        $smarty->assign('discount_descrip', $cart_totals['discount']['descrip']);
    }
}

$smarty->assign('product_detail_page', CSHOP_HOME_PAGE);

$smarty->assign('user', $user->fetch());
$smarty->assign('user_email', $user->get_email());
$smarty->display('float:order_detail.tpl');
