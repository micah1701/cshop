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
require_once(CONFIG_DIR . 'cshop.config.php');
require_once(CSHOP_CLASSES_PRODUCT . '.class.php');
require_once(CSHOP_CLASSES_CART . '.class.php');
require_once(CSHOP_CLASSES_USER . '.class.php');

// flag for smarty
$smarty->assign('page_id', 'cart');
$smarty->assign('pagetitle', CSHOP_CART_PAGETITLE);

$cart = cmClassFactory::getInstanceOf(CSHOP_CLASSES_CART, $pdb);
$user = cmClassFactory::getInstanceOf(CSHOP_CLASSES_USER, $pdb);
$product = cmClassFactory::getInstanceOf(CSHOP_CLASSES_PRODUCT, $pdb);

// init page auth objects
page_open(array('sess'=>CSHOP_CLASSES_AUTH_SESSION, 'auth'=>'defaultAuth'));

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



/** ADD AN ITEM **/
if (isset($_POST['op_add_sku'])) {
    $product->setErrorHandling(PEAR_ERROR_RETURN);
    $cart->setErrorHandling(PEAR_ERROR_RETURN);
    $res = $product->set_id_by_sku($_POST['op_add_sku']);
    if (!PEAR::isError($res)) {
        $pid = $product->get_id();
        $qty = (isset($_POST['qty']) and is_numeric($_POST['qty']))? $_POST['qty'] : 1;

        $res = $cart->add_item($pid, $qty);
    }
    $success = (!PEAR::isError($res) or $res->getMessage() == 'warning: 0 rows were changed');
    if (isset($_POST['format']) && $_POST['format'] == 'txt') {
        echo ($success)? 'op_add_cart_success=true' : 'op_add_cart_fail='.$res->getMessage();
        exit();
    }
    else {
        $smarty->assign('op_add_cart_success', true);
    }
}
elseif (isset($_REQUEST['op_add_pid'])) {
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
    $success = (!PEAR::isError($res) or $res->getMessage() == 'warning: 0 rows were changed');

    if (isset($_POST['format']) && $_POST['format'] == 'txt') {
        echo ($success)? 'op_add_cart_success=true' : 'op_add_cart_fail='.$res->getMessage();
        exit();
    }
    else {
        $smarty->assign('op_add_cart_success', true);
    }


    if (isset($_REQUEST['op_buy_now']) || isset($_REQUEST['op_buy_now_x'])) {
        header("Location: checkout.php");
        exit();
    }
}
/** EMPTY THE CART **/
elseif (isset($_REQUEST['op_empty'])) {
    $res = $cart->emptyCart();
    if (PEAR::isError($res)) {
        trigger_error($res->getMessage(), E_USER_ERROR);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
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
elseif (isset($_POST['op_remove_item']) and is_numeric($_POST['op_remove_item'])) {
    $res = $cart->update_qty($_POST['op_remove_item'], 0);
    $smarty->assign('item_removed', $res); 
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



$cartitems = $cart->fetch_items();

if (count($cartitems)) {
    $totals = $cart->fetch_totals();
    unset($totals['grand_total']);

    if (defined('CSHOP_SHOW_RELATED_PRODUCTS_IN_CART') and CSHOP_SHOW_RELATED_PRODUCTS_IN_CART) {
        if ($related_ids = $cart->fetch_related_products()) {
            foreach ($related_ids as $pid) {
                $product->set_id($pid);
                $smarty->append('related', $product->fetch(array('title','id'),false,true));
            }
        }
    }

    /** check for any coupons attached to this cart */
    if ($coupon_amt = $cart->get_discount_total($totals['subtotal'])) {
        $smarty->assign('discount_amt', $coupon_amt);
        $smarty->assign('discount_descrip', $cart->get_discount_descrip());
    }

    if (defined('CSHOP_SHOW_PRODUCT_THUMBNAILS_IN_CART') and CSHOP_SHOW_PRODUCT_THUMBNAILS_IN_CART) {
        foreach ($cartitems as $k => $item) {
            $product->set_id($item['product_id']);
            $cartitems[$k]['product'] = $product->fetch(array('title','description','id'), false, 'cart_thumb');
        }
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

if (!defined('CSHOP_SHOW_CART_CATEGORIES') or CSHOP_SHOW_CART_CATEGORIES) {
    $cats = $product->get_categories();
    $smarty->assign('product_categories', $cats);

    $products = array();
    if ($cats) {
        foreach ($cats as $catid => $name) {
           $products[$catid] = $product->selectByCategory($catid);
        }
        $smarty->assign('productlist', $products);
    }
}

if (isset($_GET['ajax'])) {
    $smarty->display('float:cart_contents.tpl');
}
else {
    $smarty->display('float:cart_display.tpl');
}






