<?php

/**
 * help someone recover their password to access this site
 *
 * this file is part of cshop
 *
 * $Id: pass.recover.php,v 1.6 2008/01/16 19:01:37 sbeam Exp $
 */
require_once('formex.class.php');
require_once('mosh_tool.class.php');
require_once('cshop/cmCart.class.php');
require_once('cshop/cmUser.class.php');

// init page auth objects
page_open(array('sess'=>CSHOP_CLASSES_AUTH_SESSION, 'auth'=>'defaultAuth'));

// control flags
$ACTION = null;
$SHOWFORM = true;
$SUCCESS = null;
$errs = array();

$recover_key_name = 'm';

/** define set of actions this script can perform **/
define('OP_GET_EMAIL', 'NEXT STEP');
define('OP_SEND_TOKEN', 'TOKEN SENT');
define('OP_RESET_PASS', 'RESET PASSWORD');
define('OP_RESET_PASS_PROC', 'DO RESET PASSWORD');


$user = new cmUser($pdb);
$mosh = new mosh_tool();


/** decide on a course of action **/
if (isset($_POST['f_op']) and $_POST['f_op'] == OP_GET_EMAIL) {
    $ACTION = OP_SEND_TOKEN;
}
elseif (isset($_POST['f_op_send'])) {
    $ACTION = OP_RESET_PASS_PROC;
}
elseif (!empty($_GET[$recover_key_name])) {
    $ACTION = OP_RESET_PASS;
}
else {
    $ACTION = OP_GET_EMAIL;
}


/** now take the action **/
if ($ACTION == OP_SEND_TOKEN) { // check email addr and send email to user
    $femail = $_POST['f_email'];
    if (!$mosh->is_proper_email($_POST['f_email'])) {
        $smarty->assign('BAD_EMAIL', htmlspecialchars($femail));
        $ACTION = OP_GET_EMAIL;
    }
    else {
        if (!$uid = $user->get_id_by_email($femail)) {
            $smarty->assign('EMAIL_NOT_FOUND', htmlspecialchars($femail));
            $ACTION = OP_GET_EMAIL;
        }
        else {
            $user->set_id($uid);
            $user->force_pword_change(false);
            $smarty->assign('EMAIL_SENT', htmlspecialchars($femail));
            $user->send_pass_notification(false);
            $auth->unauth();
            $SHOWFORM = false;
        }
    }                 
}
elseif ($ACTION == OP_RESET_PASS) { // link in email was clicked - check it out...
    $err = NULL;
    $SHOWFORM = NULL;
    if (!preg_match('/^[a-f0-9]{16}$/', $_GET[$recover_key_name])) {
        $err = "MASH_INCOMPLETE";
    }
    elseif (!preg_match('/^[0-9]+$/', $_GET['u'])) {
        $err = "MASH_INCOMPLETE";
    }
    else {
        $uid = $user->get_id_by_token($_GET[$recover_key_name]);
        if (!$uid) {
            $err = "MASH_NO_MATCH";
        }
        else {
            if ($uid != $_GET['u']) {
                $err = "UID_NOT_FOUND";
            }
            else {
            }
        }
    }

    if ($err) {
        $smarty->assign('KEY_ERROR', $err);
    }
    else { // they checked out 100%. Show the form for entering the new pass (twice)
        $uniq = md5(uniqid($_GET[$recover_key_name], true));

        $_SESSION['change_password_uniq'] = $uniq; // just to make sure they have cookies?????

        // create form for adding new password
        $fex = new formex();
        $fex->add_element('newpass', array('password', 'password', 1));
        $fex->add_element('newpass2', array('password', 'password', 1));
        $fex->add_element('uniq', array('uniq', 'hidden', $uniq, 1));
        $fex->add_element('mash', array('mash', 'hidden', $_GET[$recover_key_name], 1));
        $fex->add_element('uid', array('uid', 'hidden', $_GET['u'], 1));
        $fex->add_element('op_send', array('CHANGE PASSWORD', 'submit'));
        $smarty->assign('pwform', $fex->get_struct());
        $SHOWFORM = false;
    }
}    
elseif ($ACTION == OP_RESET_PASS_PROC) { // new password entered - check and change it.
    $err = NULL;
    $SHOWFORM = NULL;

    $newpw = $_POST['f_newpass'];
    if (strcasecmp($_POST['f_uniq'], $_SESSION['change_password_uniq']) != 0) {
        $err = "UNIQ_NO_MATCH";
    }
    elseif (!preg_match('/^[0-9]+$/', $_POST['f_uid'])) {
        $err = "BAD_PARAM";
    }
    elseif (strcmp($newpw, $_POST['f_newpass2']) != 0) {
        $err = "PASS_NO_MATCH";
    }
    elseif (strlen($newpw) < 5 or !preg_match('/[0-9]+/', $newpw)) {
        $err = "PASS_TOO_EASY";
    }
    else { // all passed. Change the pw in the DB and congratulate the user.
        unset($_SESSION['change_password_uniq']);
        $user->set_id($_POST['f_uid']);
        if (!$user->fetch('username')) { // just make sure the is was valid
            $err = "INVALID_UID";
        }
        else {
            $res = $user->change_pword($newpw);
            if (PEAR::isError($res)) {
                $err = $res->getMessage();
            }
            $user->force_pword_change(false);
        }
    }

    if ($err) {
        $smarty->assign('BACK_LINK', sprintf("%s?%s=%s&u=%d",
                                             $_SERVER['PHP_SELF'],
                                             $recover_key_name ,
                                             $_POST['f_mash'],
                                             $_POST['f_uid']));
        $smarty->assign('CHANGE_ERROR', $err);
    }
    else {
        $smarty->assign('CHANGE_SUCCESS', true);
    }
}



/*** form for getting email addr (step 1) only **/
if ($SHOWFORM) {
    $fex = new formex();
    $fex->add_element('op', array($ACTION, 'submit'));

    if ($ACTION == OP_GET_EMAIL) {
        $fex->add_element('email', array('Enter your username or email address', 'email', null, array('size'=>40), 1));
    }
    $smarty->assign('cform', $fex->get_struct());
}

$tpl = 'pass.recover.tpl';
$smarty->assign('ACTION', $ACTION);
$smarty->display("float:$tpl");










