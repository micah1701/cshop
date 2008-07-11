<?php
/**
 * the main cart controller for circushop
 * allows adding items, update/remove qty, and emptying
 * uses templates for output
 *
 * TODO more abstraction for various attributes
 *      have a config for template locations...
 *
 */
require_once(CONFIG_DIR . 'circusShop.config.php');
require_once(CSHOP_CLASSES_PRODUCT . '.class.php');
require_once(CSHOP_CLASSES_CART . '.class.php');
require_once(CSHOP_CLASSES_USER . '.class.php');

// flag for smarty
$smarty->assign('page_id', 'cart');
$smarty->assign('pagetitle', CSHOP_CART_PAGETITLE);

$cartclass = CSHOP_CLASSES_CART;
$cart = new $cartclass($pdb);

$c = CSHOP_CLASSES_USER;
$user = new $c($pdb);

// init page auth objects
page_open(array('sess'=>'jen_Session', 'auth'=>'defaultAuth'));


if($auth->auth['uid'] == "nobody"){
	header("Location: ../index.php");
}


$product_detail_page = CSHOP_PRODUCT_DETAIL_PAGE;
$smarty->assign('product_detail_page', $product_detail_page);

/** setup smarty with a method from the $cart object to convery currencies */
$smarty->register_modifier('currency_format', array(&$cart, 'currency_format'));

/* decide what currency to display here */
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


$cart->setErrorHandling(PEAR_ERROR_CALLBACK, 'cmCartErrorHandler');

/** ADD AN ITEM **/
if (isset($_REQUEST['op_add_pid'])) {
    $attribs = array();

    /* can specify both 'sizes' and 'colorid' to find the inventory item
     * matching these attribs */
    if (isset($_REQUEST['sizes'])) $attribs['sizes_id'] = $_REQUEST['sizes']; 
    if (isset($_REQUEST['colorid'])) $attribs['colorways_id'] = $_REQUEST['colorid'];

    /* inventoryid will fetch a specific row from cm_inventory. Any other
     * attrib is redundant */
    if (isset($_REQUEST['inventoryid'])) $attribs['id'] = $_REQUEST['inventoryid'];

    // add more than one maybe
    $qty = (isset($_REQUEST['qty']) and is_numeric($_REQUEST['qty']))? $_REQUEST['qty'] : 1;

    /* if this cart defines cetain options for each line item in this store,
     * and we seem to have user input, save them in the $options array to be stored */
    $options = array();
    if (isset($cart->item_custom_options)) {
        foreach ($cart->item_custom_options as $k => $v) {
            if (!empty($_REQUEST[$k])) $options[$k] = $_REQUEST[$k];
        }
    }
    $res = $cart->add_item($_REQUEST['op_add_pid'], $qty, $attribs, $options);

    if (isset($_REQUEST['op_buy_now']) || isset($_REQUEST['op_buy_now_x'])) {
        header("Location: checkout.php");
        exit();
    }
}
/** UPDATE QTYS **/
elseif (isset($_POST['op_update'])) {

    if (!empty($_POST['discount_code'])) {
        if (!$cart->apply_discount($_POST['discount_code'])) {
            header("Location: {$_SERVER['PHP_SELF']}?discounterr");
            exit();
        }
    }

    foreach ($_POST as $k => $v) {
        if (substr($k, 0, 3) == 'qty') {
            $invid = substr($k, 3);
            $res = $cart->update_qty($invid, $v);
        }
    }
    if (isset($_POST['op_checkout']) || isset($_POST['op_checkout_x'])) {
        if (CSHOP_STOCK_BLOCK and $cart->check_inventory()) {
            $cart->raiseError('Some cart line item quantities exceed inventory levels.', ERR_CART_NO_INV);
        }
        else {
            header("Location: checkout.php");
            exit();
        }
    }
    else {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
/** EMPTY THE CART **/
elseif (isset($_REQUEST['empty'])) {
    $res = $cart->emptyCart();
    if (PEAR::isError($res)) {
        trigger_error($res->getMessage(), E_USER_ERROR);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_GET['discounterr'])) { // didn't like that discount code from earlier
    $smarty->assign('discount_error', 1);
}
elseif (isset($_GET['clearcoupon'])) { // didn't like that discount code from earlier
    $cart->remove_discount();
    $smarty->assign('discount_cleared', 1);
}

$sess->register('sess_promo_code');

if (!empty($sess_promo_code)) {
    if (!$cart->apply_discount($sess_promo_code)) {
        header("Location: {$_SERVER['PHP_SELF']}?discounterr");
        exit();
    }
}



$c = CSHOP_CLASSES_PRODUCT;
$pctr = new $c($pdb);

$cartitems = $cart->fetch_items();

if (count($cartitems)) {
    $totals = $cart->fetch_totals();
    unset($totals['grand_total']);

    if (defined('CSHOP_SHOW_RELATED_PRODUCTS_IN_CART') and CSHOP_SHOW_RELATED_PRODUCTS_IN_CART) {
        if ($related_ids = $cart->fetch_related_products()) {
            foreach ($related_ids as $pid) {
                $pctr->set_id($pid);
                $smarty->append('related', $pctr->fetch(array('title','id'),false,true));
            }
        }
    }

    /** check for any coupons attached to this cart */
    if ($coupon_amt = $cart->get_discount_total($totals['subtotal'])) {
        $smarty->assign('discount_amt', $coupon_amt);
        $smarty->assign('discount_descrip', $cart->get_discount_descrip());
    }

    $smarty->assign('cart', $cartitems);
    $smarty->assign('cart_totals', $totals);
    $smarty->assign('subtotal', number_format($cart->get_subtotal(), 2));
}
$smarty->assign('do_check_coupons', $cart->do_check_coupons);




/** set and display ***********************************************************/
if (isset($_REQUEST['err']) and isset($_REQUEST['inventoryerror'])) {
    $smarty->assign('inventoryerror', true);
    $smarty->assign('msg', base64_decode($_REQUEST['err']));
}

$cats = $pctr->get_categories();
$smarty->assign('product_categories', $cats);

$products = array();
foreach ($cats as $catid => $name) {
   $products[$catid] = $pctr->selectByCategory($catid);
}
$smarty->assign('productlist', $products);

$smarty->display('float:cart_contents.tpl');







/******************************************************** ********************/
/** handle cart errors, doing a special redirect back to ourselves if its an
 * inventory/stock issue */
function cmCartErrorHandler(&$err_obj) {
    if ($err_obj->getCode() == ERR_CART_NO_STOCK or $err_obj->getCode() == ERR_CART_NO_INV) { 

        $msg = base64_encode($err_obj->getMessage());

        if (isset($_POST['op_add_pid'])) {
            /* totally uncool hack to get the original $pid from the backtrace of
             * the error object rather than just using global() */
            $pid = $err_obj->backtrace[2]['args'][0];
            $url = sprintf('%s?pid=%d&inventoryerror&err=%s', 
                            CSHOP_PRODUCT_DETAIL_PAGE,
                            $pid,
                            $msg);
        }
        else {
            $url = sprintf('%s?inventoryerror&err=%s', 
                            $_SERVER['PHP_SELF'],
                            $msg);
        }
        header("Location: $url\n");
        exit();
    }
    elseif ($err_obj->getCode() != DBCON_ZERO_EFFECT) { //"0 rows were changed"
        pear_error_handler($err_obj);
    }
    exit();
}
