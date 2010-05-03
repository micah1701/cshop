<?php
/**
 * setup of new user accounts and account info maintenance, and viewing old orders
 *
 * $Id: profile.php,v 1.8 2008/02/05 16:35:21 sbeam Exp $
 */
require_once(CONFIG_DIR.'cshop.config.php');
require_once('formex.class.php');
require_once(CSHOP_CLASSES_USER.'.class.php');

// init page auth objects
page_open(array('sess'=>CSHOP_CLASSES_AUTH_SESSION, 'auth'=>'defaultAuth'));

// flag for smarty
$smarty->assign('page_id', 'user_profile');

/* the cart and the user. That's what its all about */
$c = CSHOP_CLASSES_USER;
$user = new $c($pdb);

$user->set_auth($auth);
$auth_logged_in = ($auth->is_logged_in() && !$auth->has_bypass_flag());
$smarty->assign('auth_logged_in', $auth_logged_in);
$smarty->assign('user', $user->fetch());

if ($auth_logged_in) {
    $userinfo = $user->fetch(); // empty unless they are logged in
}
else {
    $userinfo = null;
}

// control flags
$ACTION = null;
$SHOWFORM = false;
$SUCCESS = null;
$errs = array();
$msg = '';

/** define set of actions this script can perform **/
define('OP_NEW_USER', 'CREATE ACCOUNT');
define('OP_VIEW_ACCOUNT', 'YOUR PROFILE');
define('OP_EDIT_PROFILE', 'UPDATE PROFILE');
define('OP_EDIT_ADDR', 'UPDATE ADDRESS');
define('OP_KILL_ADDR', 'DELETE ADDRESS');
define('OP_SHOW_ORDERS', 'YOUR ORDER HISTORY');
define('OP_EDIT_LOGIN', 'UPDATE LOGIN');


$cart = cmClassFactory::getInstanceOf(CSHOP_CLASSES_CART,$pdb);

/* decide what currency to show. They would have set this in the cart */
$sess->register('CSHOP_CURRENCY_DISPLAY');
$cart->set_display_currency($CSHOP_CURRENCY_DISPLAY);

/** setup smarty with a method from the $cart object to convery currencies */
$smarty->register_modifier('currency_format', array(&$cart, 'currency_format'));

// setup the minicart
$smarty->assign('minicart', $cart->get_minicart_values());
$smarty->assign('cartitems', $cart->fetch_items());

/** decide on a course of action **/
if ($userinfo and empty($_POST)) { // flags in GET causes various forms to display
    $ACTION = OP_VIEW_ACCOUNT;
    if (isset($_GET['op_prof'])) {
        $ACTION = OP_EDIT_PROFILE;
        $SHOWFORM = true;
    }
    elseif (isset($_GET['op_addr'])) {
        $ACTION = OP_EDIT_ADDR;
        $req_id = $_GET['id'];
        $SHOWFORM = true;
    }
    elseif (isset($_GET['op_addr_del'])) {
        $ACTION = OP_KILL_ADDR;
        $req_id = $_GET['id'];
    }
    elseif (isset($_GET['op_login'])) {
        $ACTION = OP_EDIT_LOGIN;
        $SHOWFORM = true;
    }
    elseif (isset($_GET['op_orders'])) {
        $ACTION = OP_SHOW_ORDERS;
    }
}
elseif (isset($_POST['f_op'])) { // have data to process
    $ACTION = $_POST['f_op'];
    $SHOWFORM = true;
}
else { // default action
    $ACTION = OP_NEW_USER;
    $SHOWFORM = true;
}


/** process user account info */
if (isset($_POST['f_op'])) {
    $fex = new formex();

    /* new user. proc userinfo, address and login simultaneously */
    if ($ACTION == OP_NEW_USER && $user->do_require_address_on_register) {
        $user->addr->colmap['name'][2] = false; // dont require a Name on the address...
        $colmap = $user->get_colmap() + $user->addr->colmap;
    }
    elseif ($ACTION == OP_EDIT_PROFILE) { // profile update only 
        $colmap = $user->get_colmap();
        if (isset($colmap['username'])) unset($colmap['username']); //cant change it
    }
    elseif ($ACTION == OP_EDIT_ADDR) { // change an address
        $req_id = $_POST['f_addr_id'];
        $colmap = $user->addr->get_colmap();
    }
    else {
        $colmap = $user->get_colmap();
    }

    if (!empty($colmap)) {
        $fex->add_element($colmap);
        $errs = $fex->validate($_POST); // handled below
    }

    /* checking the password validity */
    if ($ACTION == OP_NEW_USER or !empty($_POST['f_password'])) {
        if ($_POST['f_password'] != $_POST['f_password2']) {
            $errs[] = 'The two passwords you entered did not match. Please make sure there 
                       is the same value in both password fields';
        }
        elseif (strlen($_POST['f_password']) < 6) {
            $errs[] = 'Your password must be 6 or more characters long';
        }
        elseif (isset($_POST['f_username']) && $_POST['f_password'] == $_POST['f_username']) {
            $errs[] = 'Your password cannot be the same as your username';
        }
    }


    if (!count($errs)) {

        $pdb->autoCommit(false); // begin trans, because we have potential two stages here that each can fail validation

        if ($ACTION == OP_NEW_USER or $ACTION == OP_EDIT_PROFILE) {
            $vals = $fex->get_submitted_vals($_POST);
            PEAR::setErrorHandling(PEAR_ERROR_RETURN);
            /* make sure an INSERT is executed, and removes the sesskey too */
            if ($ACTION == OP_NEW_USER) {
                $user->set_id(null); 
            }
            $res = $user->store($vals);
            if (PEAR::isError($res) and $res->getCode() != DBCON_ZERO_EFFECT) { //"0 rows were changed"
                if ($res->getCode() == DB_ERROR_ALREADY_EXISTS) {
                    $smarty->assign('DUPE_EMAIL', $vals['email']);
                }
                else {
                    trigger_error($res->getMessage(), E_USER_ERROR);
                }
            }
            elseif ($ACTION == OP_NEW_USER) { // its a brand new user account, save login info and addr too
                $user->change_pword($_POST['f_password']);

                // store shipping address
                $fex->_elems = array();
                $fex->add_element($user->addr->get_colmap());
                $shipvals = $fex->get_submitted_vals($_POST);
                $user->store_address('shipping', $shipvals);

                // sets up the auth object to believe this person has logged in
                $auth->force_preauth($user->get_id()); 
                $auth->auth['first_time'] = true;
                $SUCCESS = true;
            }
            else {
                $msg = "Profile information has been updated";
            }
        }
        elseif ($ACTION == OP_EDIT_ADDR) { // edit the given address, should be ident. by $req_id
            $fex->_elems = array();
            $fex->add_element($user->addr->get_colmap());
            $shipvals = $fex->get_submitted_vals($_POST);
            $user->addr->set_id($req_id);
            $user->store_address('shipping', $shipvals);
            $msg = "Address has been updated";
        }
        elseif ($ACTION == OP_EDIT_LOGIN) { // pass update only
            $user->change_pword($_POST['f_password']);
            $msg = "Your password has been changed";
        }

        if ($ACTION == OP_NEW_USER && $SUCCESS) {
            $pdb->commit();
            header("Location: checkout.php?shipping&new\n");
            exit();
        }
        elseif ($msg && empty($errs)) {
            $pdb->commit();
            header("Location: profile.php?info=" . base64_encode($msg));
            exit();
        }

        $pdb->rollback();
        $pdb->autoCommit(true);
    }
}

/* remove an address */
elseif ($ACTION == OP_KILL_ADDR) {
    $user->addr->set_id($req_id); // it doesn't let you remove other peoples addresses (?)
    $user->addr->kill();
    $msg = "Address was removed from the system";
    header("Location: profile.php?info=" . base64_encode($msg));
    exit();
}
elseif ($ACTION == OP_SHOW_ORDERS) {
     $smarty->assign('order_history', $user->fetch_order_history());
     $tpl = 'order_list.tpl';
 }



$smarty->assign('errors', $errs);

if ($SHOWFORM) { /* show one of the fine forms for updates */
    $fex = new formex();
    $fex->max_size = 24;
    $fex->add_element('op', array($ACTION, 'submit'));

    if ($ACTION == OP_NEW_USER) {
        $fex->add_element($user->get_colmap());
        if ($user->do_require_address_on_register) {
            $fex->add_element($user->addr->colmap);
            $smarty->assign('ADDRESS_REQUIRED', true);
        }
    }
    elseif ($ACTION == OP_EDIT_PROFILE) {
        $fex->add_element($user->colmap);
        $fex->elem_vals = $userinfo;
    }
    elseif ($ACTION == OP_EDIT_ADDR) {
        $fex->add_element('addr_id', array('', 'hidden', $req_id, null));
        $fex->add_element($user->addr->get_colmap());
        $user->addr->set_id($req_id);
        $fex->elem_vals = $user->addr->fetch();
    }

    if ($ACTION == OP_NEW_USER or $ACTION == OP_EDIT_LOGIN) { // add password elements
        if ($username = $user->fetch(array('username'))) {
            $smarty->assign('username', $username['username']);
        }
        $fex->add_element('password', array('Password', 'password', 1));
        $fex->add_element('password2', array('Password confirmation', 'password', 1));
    }
    $smarty->assign('cform', $fex->get_struct());
}
elseif ($userinfo) { // for static display in tpl
    $addrs = $user->fetchAllAddr();
    $smarty->assign('addrs', $addrs);
    $smarty->assign('userinfo', $userinfo);
}

$smarty->assign('ACTION', $ACTION);

if (isset($_GET['info'])) {
    $smarty->assign('msg', base64_decode($_GET['info']));
}

if (empty($tpl)) {
    $tpl = 'account.tpl';
}

$smarty->display("float:$tpl");
