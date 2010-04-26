<?php
/**
 * the first checkout page - gathers shipping and billing addrs and payment
 * info and injects into DB via container objects
 *
 * $Id: checkout.php,v 1.37 2008/01/10 20:15:25 sbeam Exp $
 */
require_once(CONFIG_DIR . 'cshop.config.php');
require_once('formex.class.php');
require_once('mosh_tool.class.php');
require_once(CSHOP_CLASSES_CART.'.class.php');
require_once(CSHOP_CLASSES_USER.'.class.php');
require_once(CSHOP_CLASSES_PAYMETHOD.'.class.php');
require_once(CSHOP_CLASSES_TAXCALCULATOR.'.class.php');
require_once(CSHOP_CLASSES_SHIPMETHOD.'.class.php');
require_once(CSHOP_CLASSES_GIFTCARD.'.class.php');


// init page auth objects
page_open(array('sess'=>CSHOP_CLASSES_AUTH_SESSION, 'auth'=>CSHOP_CLASSES_AUTH_AUTH, 'perm'=>CSHOP_CLASSES_AUTH_PERM));


// flag for smarty
$smarty->assign('page_id', 'checkout');
$smarty->assign('pagetitle', 'Checkout');

// control flags
$ACTION = null;
$SHOWFORM = true;
$SUCCESS = null;
$errs = array();

/* are we going to need to collect Payment info ? */
$PAYMENT_REQUIRED = true; // it should be, unless there are 100% discounts or giftcards to cover it

/** define set of actions this script can perform **/
define('OP_GET_PASS', 'LOGIN');
define('OP_GET_SHIP_ADDR', 'NEXT'); // duhmb
define('OP_ADD_SHIP', 3);
define('OP_PICK_SHIP', 'CONTINUE'); // duhmb
define('OP_GET_BILL', 'PROCEED'); // duhmb
define('OP_ADD_BILL', 4);
define('OP_ADD_GC', 5);
define('OP_KILL_GC', 6);

/** decide on a course of action **/
if (isset($_POST['f_op']) and $_POST['f_op'] == OP_GET_SHIP_ADDR) {
    $ACTION = OP_ADD_SHIP;
}
elseif (isset($_POST['f_op']) and $_POST['f_op'] == OP_GET_BILL) {
    $ACTION = OP_ADD_BILL;
}
elseif (isset($_POST['f_op']) and $_POST['f_op'] == OP_PICK_SHIP) {
    $ACTION = OP_PICK_SHIP;
}
elseif (isset($_POST['f_op_gc_add'])) {
    $ACTION = OP_ADD_GC;
    $smarty->assign('pagetitle', 'Gift Card');
}
// they need to pick a shipping method
elseif (isset($_GET['pickship'])) {
    $ACTION = OP_PICK_SHIP;
    $smarty->assign('pagetitle', 'Shipping Method');
}
// they apparently passed the dogs of shipping. Show them a billing info form
elseif (isset($_GET['billing'])) {
    $ACTION = OP_GET_BILL;
    $smarty->assign('pagetitle', 'Billing Information');
}
elseif (isset($_GET['op_gc_del'])) {
    $ACTION = OP_KILL_GC;
}
// first step, tell us the shipping addr
else { #if (isset($_GET['shipping'])) {
    $ACTION = OP_GET_SHIP_ADDR;
    $smarty->assign('pagetitle', 'Shipping Address');
}




/* the cart and the user. That's what its all about */
$cart = cmClassFactory::getInstanceOf(CSHOP_CLASSES_CART, $pdb);
$user = cmClassFactory::getInstanceOf(CSHOP_CLASSES_USER, $pdb);

/* now what it really is all about is payment */
$pay = cmClassFactory::getInstanceOf(CSHOP_CLASSES_PAYMETHOD, $pdb);

/* decide what currency to show. They would have set this in the cart */
$sess->register('CSHOP_CURRENCY_DISPLAY');
$cart->set_display_currency($CSHOP_CURRENCY_DISPLAY);

/** setup smarty with a method from the $cart object to convery currencies */
$smarty->register_modifier('currency_format', array(&$cart, 'currency_format'));


$uid = $user->get_auth_id();
$user->set_id($uid);

$cartid = $cart->get_id(); // actually called set_id() on cart too! yes, strange.



/* create colmap for fex and mosh, but adding uname and email fields in case needed. */
$colmap = $user->addr->get_colmap();

// try to fetch a user row based on whatvever is in $_SESSION[$user->_sesskey]. yes its quite safe!
if (! (CSHOP_ALLOW_ANON_ACCOUNT and $auth->has_bypass_flag())) {
    if (! ($userinfo = $user->fetch())) {
        trigger_error('auth info not found!', E_USER_WARNING);
    }
}

/** making sure they did not mysteriously lose the cart somehow, if so redirect and complain */
$cart_itemcount = $cart->count_items();
if (!$cart_itemcount or PEAR::isError($cart_itemcount)) {
    header("Location: cart.php");
    trigger_error("Attempt to checkout with an empty cart.", E_USER_ERROR);
    exit();
}


$fex = new formex();


/* enter user shipping addr */
if ($ACTION == OP_ADD_SHIP) {
    /* they would like to proceed without choosing a password and such. Create an "anonymous" user object stub and log them in automatically */
    if (CSHOP_ALLOW_ANON_ACCOUNT and $auth->has_bypass_flag()) { 
        $user = cmClassFactory::getInstanceOf(CSHOP_CLASSES_USER, $pdb);       

        $fex_anon_user = new formex();
        $fex_anon_user->add_element($user->get_colmap());

        $vals = $fex_anon_user->get_submitted_vals($_POST);

        $res = $user->create_anon_user($vals['email'], $vals);

        if (PEAR::isError($res)) {
            trigger_error($res->getCode(), E_USER_ERROR);
        }
        $auth->force_preauth($user->get_id()); // magically logs them in with the new uid
    }

    // save the comments on billing/shipping eitheway, its shared
    if (!empty($_POST['f_user_comments'])) {
        $cart->set_user_comment($_POST['f_user_comments']);
    }

    if (!$cart->requires_shipping()) {
        header("Location: {$_SERVER['PHP_SELF']}?billing\n"); // goto: billing 
        exit();
    }
    else {
        
        $fex->add_element($user->addr->get_colmap());

        if (! ($errs = $fex->validate($_POST))) {
            //$thiscolmap = $user->addr->get_colmap();
            $vals = $fex->get_submitted_vals($_POST);
            $vals['user_id'] = $user->get_id();

            if (!empty($_POST['f_shipping_addr_id'])) { // they are editing an address that was already in the DB
                $user->addr->set_id($_POST['f_shipping_addr_id']);
            }
            $res = $user->addr->store($vals);

            if (PEAR::isError($res) and $res->getCode() != DBCON_ZERO_EFFECT) { //"0 rows were changed"
                trigger_error($res->getMessage(), E_USER_ERROR);
            }
            else {
                if ($user->store(array('shipping_addr_id' => $user->addr->get_id()))) {
                    header("Location: {$_SERVER['PHP_SELF']}?pickship\n"); // SUCCESS, goto: pick a shipping method
                    exit();
                }
            }
        }
    }
    $ACTION = OP_GET_SHIP_ADDR;
}
/** enter the selected shipping method **/
elseif (isset($_POST['f_ship_method']) and $ACTION == OP_PICK_SHIP) {

    if (empty($_POST['f_ship_method'])) {
        $errs[] = "Please choose one of the shipping methods provided";
    }
    else {
        // we saved our last set of quotes in the session to check for tampering
        $sess->register('shipquotes');
        if (! in_array($_POST['f_ship_method'], array_keys($shipquotes))) {
            trigger_error("Selected shipmethod was not a valid selection", E_USER_ERROR);
        }
        // try to extract the price from the special value format
        elseif (! ($res = cmShipping::parse_shipmethod($_POST['f_ship_method']))) {
            trigger_error("could not parse the requested ship method", E_USER_ERROR);
        }
        list($ship_method, $ship_total) = $res;
        $SUCCESS = $cart->store(array('ship_total'=>$ship_total,
                                      'ship_method'=>$ship_method));
        if ($SUCCESS) {
            $sess->unregister('shipquotes'); // forget those old quotes, for safety
            header("Location: {$_SERVER['PHP_SELF']}?billing\n");
            exit();
        }
    }
}
/** check/enter payment info and billing addr */
elseif ($ACTION == OP_ADD_BILL) {
    $mosh = new mosh_tool();

    $cart_total = $cart->get_grandtotal();

    if ($cart_total == 0) { /* seems we have giftcards or discounts. No payment needed */
        header("Location: checkout_confirm.php\n");
        exit();
    }
    else {
        $thiscolmap = $pay->get_colmap();
        if ($errs = $mosh->check_form($thiscolmap)) {
        }
        else {
            $payvals = $mosh->get_form_vals($thiscolmap);
            $payvals['ccno'] = cmPaymentCC::clean_ccno($payvals['ccno']);
            $errs = $pay->check_values($payvals);
        }

        if (!$errs) { 
            $addrcolmap = $user->addr->get_colmap();
            if (isset($_POST['f_same_as_shipping'])) {
                /* should be a method in cmUser() for this... */
                $ship = $user->fetch(array('shipping_addr_id'));
                $user->activateAddress('billing', $ship['shipping_addr_id']);
            }
            elseif ($errs = $mosh->check_form($addrcolmap)) {
            }
            else {

                $vals = $mosh->get_form_vals($addrcolmap);
                $vals['user_id'] = $uid;
                $user->addr->store($vals);

                $res = $user->addr->store($vals);
                if (PEAR::isError($res) and $res->getCode() != DBCON_ZERO_EFFECT) { //"0 rows were changed"
                    trigger_error($res->getMessage(), E_USER_ERROR);
                }
                else { // store the id of the new addr row in the user table
                    $user->activateAddress('billing', $user->addr->get_id());
                }
            }
        }

        if (!$errs) {
            // get values from the CC input form and store
            $payvals['user_id'] = $user->get_id();
            $pay->set_csc($_POST['f_csc1']);
            $payid = $pay->store($payvals);

            $cart->set_payment($pay);

            // save the comments on billing/shipping eitheway, its shared
            if (!empty($_POST['f_user_comments'])) {
                $cart->set_user_comment($_POST['f_user_comments']);
            }

            header("Location: checkout_confirm.php\n");
            exit();
        }
    }
    // something failed, we will be doing SHOWFORM below
    $ACTION = OP_GET_BILL;
}
/* add a giftcard, verifying it and checking applied amt v/s cart totals */
elseif (defined('CSHOP_ACCEPT_GIFTCARDS') && CSHOP_ACCEPT_GIFTCARDS && $ACTION == OP_ADD_GC) {
    $c = CSHOP_CLASSES_GIFTCARD;
    $gc = new $c($pdb);
    $mosh = new mosh_tool();

    PEAR::setErrorHandling(PEAR_ERROR_RETURN);

    $gc_colmap = $gc->get_colmap();
    $vals = $mosh->get_form_vals($gc_colmap);
    $gc->set_number($vals['gc_no']);
    $gc->set_amount($vals['gc_amt']);

    if (empty($vals['gc_no'])) {
        $errs[] = "Please enter a valid Gift Card Number";
    }

    $gc_balance = $gc->get_balance();
    if (! $gc_balance ) {
        $errs[] = "Sorry, your giftcard number is invalid or has a zero balance";
    }
    elseif (PEAR::isError($gc_balance)) {
        $errs[] = "Sorry, your giftcard cannot be processed at this time : " . $gc_balance->getMessage() . " - Please try again later";
    }
    else {
        if (empty($vals['gc_amt']) or $gc_balance < $vals['gc_amt']) {
            $smarty->assign('NOTICE', "Giftcard balance was only ". $gc_balance. ". The available amount is applied to your order");
            $vals['gc_amt'] = $gc_balance;
        }

        $cart_total = $cart->get_grandtotal();
        if ($vals['gc_amt'] > $cart_total) {
            $vals['gc_amt'] = $cart_total;
        }

        $vals['cart_id'] = $cart->get_id(); 
        $res = $gc->store($vals); // asplode on error
        if (PEAR::isError($res) and $res->getCode() == DB_ERROR_ALREADY_EXISTS) {
            $errs[] = "You have already added that card to this cart";
        }
        else {
            $smarty->assign('NOTICE', "Giftcard is applied to your order");
        }
    }

    PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'pear_error_handler');
    $ACTION = OP_GET_BILL;
}
/* remove a gift card */
elseif (defined('CSHOP_ACCEPT_GIFTCARDS') && CSHOP_ACCEPT_GIFTCARDS && $ACTION == OP_KILL_GC) {
    $req_gc = $_GET['id'];
    $c = CSHOP_CLASSES_GIFTCARD;
    $gc = new $c($pdb);
    $res = $gc->set_id($req_gc);
    $res = $gc->kill();
    if (!PEAR::isError($res)) {
        $smarty->assign('NOTICE', "Giftcard was removed from the order");
    }
    $ACTION = OP_GET_BILL;
}




/** set and display ***********************************************************/
$smarty->assign('cartitems', $cart->fetch_items());
$smarty->assign('cart_itemcount', $cart->count_items());

$subtotal = $cart->get_subtotal();
$smarty->assign('subtotal', $subtotal);


/*** create form object, set up and pass to smarty **/
if ($SHOWFORM) {
    $fex->add_element('op', array(null, 'hidden', $ACTION));
    $fex->add_element('butt', array('CONTINUE', 'submit', null, null, " onclick=\"this.value='Please wait...';\"", 0));

    if ($ACTION == OP_GET_PASS) {
        $fex->max_size = 16;
        $fex->add_element('username', array('Username/email', 'text', null, array('class'=>'cartLogin'), 1));
        $fex->add_element('password', array('Password', 'password', null, array('class'=>'cartLogin'), 1));
        $tpl = 'checkout_login.tpl';
    }
    else {

        $fex->max_size = 20;
        $fex->add_element($colmap);

        if ($ACTION == OP_GET_SHIP_ADDR) {

            if (CSHOP_ALLOW_ANON_ACCOUNT and $auth->has_bypass_flag()) {
                $fex->add_element($user->get_colmap());
            }

            if (!$cart->requires_shipping()) { // bypass shipping addr form if everything is not shippable
                $smarty->assign('skip_shipping_addr', true);
            }
            $op_new_ship = isset($_GET['op_add_ship']);

            $ship = cmClassFactory::getInstanceOf(CSHOP_CLASSES_SHIPMETHOD, $pdb);

            /* limits the country select if need be, depending on the ship method */
            if ($countrylist = $ship->get_avail_countries()) {
                $fex->set_elem_attrib('country', 'limit_to', $countrylist);
            }

            if (empty($errs) && !$op_new_ship and ($shipping = $user->fetchShippingAddr())) {
                $smarty->assign('has_shipping', true);
                $fex->add_element('shipping_addr_id', array(null, 'hidden', $shipping['id']));

                // set the shipto name to the customers name if available
                if (empty($shipping['name']) and isset($userinfo['cust_name'])) {
                    $shipping['name'] = $userinfo['cust_name'];
                }

                $fex->elem_vals = $shipping; // set defaults for the shipping addr here
            }
            if (isset($auth->auth['first_time'])) {  // its a new user
                $smarty->assign('is_new_user', true);
                unset($auth->auth['first_time']);
            }
            $smarty->assign('action', 'checkout_shipping');
            $tpl = 'checkout.tpl';
        }
        elseif ($ACTION == OP_PICK_SHIP) {

            $shipdest = $user->fetchShippingAddr(array('street_addr', 'country', 'state', 'postcode'));

            /** get avail ship methods **/
            PEAR::setErrorHandling(PEAR_ERROR_RETURN);
            $shipclass = CSHOP_CLASSES_SHIPMETHOD;
            $ship = new $shipclass();
            $ship->set_destination($shipdest);
            $shipquotes = $ship->get_all_quotes($cart);
            if (PEAR::isError($shipquotes)) {
                $errs[] = $shipquotes->getMessage();
            }
            elseif ($ship->has_calculation_error) {
                $errs = $ship->calculation_errors;
            }
            elseif (count($shipquotes))  {
                if ($ship->qualifies_freeship) {
                    $smarty->assign('HAVE_FREE_SHIP', true);
                }
                $fex->add_element('ship_method', array('Shipping Method', 'radio', $shipquotes, 1));
                // set the first radio button to default checked:
                $fex->set_elem_default_vals('ship_method', array_shift(array_keys($shipquotes)));
                $smarty->assign('shipquotes', $shipquotes);
            }
            /* remember all the current quotes on the server to detect any tampering in next step*/
            $_SESSION['shipquotes'] = $shipquotes;

            /** also now we can calculate tax to that shipping address. Store in the cart table
              * and let the luser know */
            $taxtotal = $cart->calculate_tax();
            $smarty->assign('tax_total', $taxtotal);

            $smarty->assign('action', 'checkout_pickshipmethod');
            $tpl = 'checkout_pickship.tpl';
        }
        elseif ($ACTION == OP_GET_BILL) {

            $cart_total = $cart->get_grandtotal();

            $smarty->assign('cart_grandtotal', $cart_total);

            /* add form fields for GiftCards and get current totals for display */
            if (defined('CSHOP_ACCEPT_GIFTCARDS') && CSHOP_ACCEPT_GIFTCARDS) {
                $c = CSHOP_CLASSES_GIFTCARD;
                $gc = new $c($pdb);

                /* allow gift card entry */
                if ($cart_total > 0) {
                    $fex->add_element($gc->get_colmap());
                    $fex->add_element('op_gc_add', array('ADD CARD', 'submit', null, null)); 
                    $fex->elem_vals['gc_amt'] = number_format($cart_total, 2);
                    $fex->elem_vals['gc_no'] = '';
                }

                $gc_total = $cart->get_giftcard_total();

                $smarty->assign('giftcards', $cart->get_giftcards());
                $smarty->assign('gc_total', $gc_total);

                /* there must be enough giftcards to cover everything */
                if ($cart_total == 0) {
                    $PAYMENT_REQUIRED = false;
                }
            }

            /* try to find prev. shipping addr as entered */
            $shipping = array();
            if ($cart->requires_shipping()) {
                $shipping = $user->fetchShippingAddr();
                $smarty->assign('shipaddr', $shipping);
            }


            if ($PAYMENT_REQUIRED) {
                /* add form elems for payment info */
                $thiscolmap = $pay->get_colmap();
                $fex->add_element($thiscolmap);

                if ($shipping) { // /* the magic auto-fill checkbox */
                    $fex->add_element('same_as_shipping', array('Same as shipping', 
                                                                'checkbox', 
                                                                null, 
                                                                null, 
                                                                'onclick="addrAutoFill(this.checked)"', 
                                                                0));
                }

                /* just add in ship total here, there is no choice for ass! */
                $fex->add_element('ship_method', array(null, 'hidden', null));

                /* add a field for the CSC - this is not really saved anywhere so it is not in the object */
                $fex->add_element('csc1', array('Card Security Code', 'text', null, array('size'=>4), 1));

                /* use this persons previous billing addr if we have it */
                if ($billing = $user->fetchBillingAddr()) {
                    $smarty->assign('has_billing', true);
                    $fex->add_element('billing_addr_id', array(null, 'hidden', $billing['id']));
                    $fex->elem_vals = $billing; 
                }
            }

            $smarty->assign('PAYMENT_REQUIRED', $PAYMENT_REQUIRED);

            $smarty->assign('action', 'checkout_billing');
            $tpl = 'checkout_billing.tpl';
        }
        else {
            trigger_error("no valid action $ACTION", E_USER_ERROR);
        }
    }

    /* field for commments - shared with the one from shipping */
    $fex->add_element('user_comments', array('Comments on your order', 'textarea', null, array('cols'=>40)));
    if (isset($_SESSION['_cm_user_order_comment'])) $fex->elem_vals['user_comments'] = $_SESSION['_cm_user_order_comment'];

    // create convenience cust_name value if needed
    if (isset($userinfo)) {
        if (!isset($userinfo['cust_name'])) {
            $userinfo['cust_name'] = join(' ', array($userinfo['fname'], $userinfo['lname']));
        }
        if (!empty($userinfo['anon_email'])) {
            $userinfo['email'] = $userinfo['anon_email'];
        }
        $smarty->assign('user', $userinfo);
    }

    $smarty->assign('cform', $fex->get_struct());
    unset($fex);

    $smarty->assign('minicart', $cart->get_minicart_values());
}

// trigger_error sends a email to somebody who gives a damn (maybe)
if (count($errs)) trigger_error("Errors during checkout: " . join("\n", $errs), E_USER_NOTICE);

/* smarty will complain to the user */
$smarty->assign('errors', $errs);

$smarty->display("float:$tpl");



