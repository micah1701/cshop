<?php
require_once(CONFIG_DIR.'circusShop.config.php');

/**
 * managing users, addresses, login credentials etc.
 *
 * $Id: store.users.addresses.php,v 1.9 2008/07/09 11:59:12 sbeam Exp $
 */
require_once('formex.class.php');
require_once(CSHOP_CLASSES_USER.'.class.php');

/* set of actions this script may perform */
define ('OP_ADD', 'ADD ADDRESS');
define ('OP_EDIT', 'EDIT ADDRESS');
define ('OP_KILL', 'REMOVE ADDRESS');
define ('OP_ACTIVATE', 'ACTIVATE ADDRESS');
define ('OP_SET_BILLING', 'SET');

$ACTION = null;
$SHOWFORM = false;
$errs = array();

$parentid = null;
$itemid = null;
$reqIdKey = 'uid';
$table_title = 'Address';

/** decide on a course of action... **/
if (isset($_GET['op_edit'])) {
    $itemid = $_GET['op_edit'];
    $parentid = $_GET[$reqIdKey];
    $ACTION = OP_EDIT;
    $SHOWFORM = true;
}
elseif (isset($_GET['op_add'])) {
    $parentid = $_GET[$reqIdKey];
    $ACTION = OP_ADD;
    $SHOWFORM = true;
}
elseif (isset($_GET['op_activate'])) {
    $itemid = $_GET['op_activate'];
    $parentid = $_GET[$reqIdKey];
    $ACTION = OP_ACTIVATE;
}
elseif (isset($_POST['op'])) {
    $parentid = $_POST[$reqIdKey];
    if (isset($_POST['itemid'])) $itemid = $_POST['itemid'];
    $ACTION = $_POST['op'];
}
elseif (isset($_POST[$reqIdKey]) and isset($_POST['op_kill'])) {
    $parentid = $_POST[$reqIdKey];
    $itemid = $_POST['itemid'];
    $ACTION = OP_KILL;
}
elseif (isset($_GET[$reqIdKey]) and !empty($_GET[$reqIdKey])) {
    $parentid = $_GET[$reqIdKey];
}

if (!$parentid) {
    trigger_error("parentid was not passed", E_USER_ERROR);
}
/** **/


$userclass = CSHOP_CLASSES_USER;
$user = new $userclass($pdb);
$user->set_id($parentid);


/** POST rec'd, check valid, proc. upload and save if OK */
if (isset($_POST['op']) and ($ACTION == OP_ADD or $ACTION == OP_EDIT)) {
    $mosh = new mosh_tool();
    $mosh->form_field_prefix = '';
    $msg = '';

    $vals = array();
    $img_vals = array();
    if ($errs = $mosh->check_form($user->addr->colmap)) {
        // handled below
        $SHOWFORM = true;
    }
    else {
        $vals = $mosh->get_form_vals($user->addr->colmap);

        /* check for existing addr_code, on insert or update with change */
        if (method_exists($user->addr, 'fetch_by_addr_code') and $res = $user->addr->fetch_by_addr_code($vals['addr_code'])) {
            if (empty($itemid) or $res['id'] != $itemid) {
                $errs[] = "Cannot create or update record: the IRT address code '{$vals['addr_code']}' already exists";
            }
            $SHOWFORM = true;
        }

        if (empty($errs)) {

            if ($ACTION == OP_EDIT) { // update the row in $tablenameable
                $user->set_id($parentid);
                $user->addr->set_id($itemid);
                $res = $user->addr->store($vals);

                $msg .= sprintf('%s was updated.', $table_title);

            }
            else { // OP_ADD

                $vals['user_id'] = $parentid;
                $res = $user->addr->store($vals);

                $msg .= sprintf('Inserted new %s', $table_title);
            }

            // send back to self with messageness
            header("Location: {$_SERVER['PHP_SELF']}?$reqIdKey=$parentid&info=" . base64_encode($msg));
            exit();
        }
    }
}
elseif (($ACTION == OP_KILL)) {
    $user->set_id($parentid);
    $user->addr->set_id($itemid);
    $res = $user->addr->kill();

    $msg = "The selected $table_title was totally removed.";
    // send back to self with messageness
    header("Location: {$_SERVER['PHP_SELF']}?$reqIdKey=$parentid&info=" . base64_encode($msg));
    exit();
}
elseif ($ACTION == OP_ACTIVATE) {
    $type = ($_GET['type'] == 'bill')? 'billing' : 'shipping';
    $user->activateAddress($type, $itemid);

    $msg = "The $type address for this user has been set.";
    // send back to self with messageness
    header("Location: {$_SERVER['PHP_SELF']}?$reqIdKey=$parentid&info=" . base64_encode($msg));
    exit();
}




if ($SHOWFORM) {
    $fex = new formex('POST');
    $fex->js_src_inline = true;
    $fex->left_td_style = '';
    $fex->field_prefix = '';
    $fex->add_element($user->addr->colmap); 
    $fex->add_element('op', array($ACTION, 'submit')); // the button
    $fex->add_element($reqIdKey, array(null, 'hidden', $parentid, 0)); // important
    $fex->add_element('itemid', array(null, 'hidden', $itemid, 0)); // important

    if ($ACTION == OP_EDIT) {

        $user->set_id($parentid);
        $user->addr->set_id($itemid);
        $fex->elem_vals = $user->addr->fetch();

        $confirm_msg = sprintf('This will remove this %s from the site permanently. Are you sure?', $table_title);
        $fex->add_element('op_kill', array(OP_KILL, 'submit', null, null, 'onclick="return confirm(\''. $confirm_msg . '\')"'));

    }
}
else {
    $addrs = $user->fetchAllAddr();
    /* get the id's of the user shipping and billing addrs for reference later */
    $last_addrs = $user->fetch(array('billing_addr_id', 'shipping_addr_id'));
    $bill_id = ($last_addrs['billing_addr_id'])? $last_addrs['billing_addr_id'] : null;
    $ship_id = ($last_addrs['shipping_addr_id'])? $last_addrs['shipping_addr_id'] : null;
}


$msg = '';
if (isset($_GET['info'])) {
    $msg = base64_decode($_GET['info']);
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>user address control framelet</title>
<link rel="stylesheet" href="/control/cshop/store.css" type="text/css">
<link rel="stylesheet" href="/control/control.css" type="text/css">
<script type="text/javascript">
<!--
    function doSelectAddr(type, pid, uid) {
        document.location = '<?= $_SERVER['PHP_SELF'] ?>?<?= $reqIdKey ?>='+uid+'&op_activate='+pid+'&type='+type;
    }
// -->
</script>

</head>
<body>
<div style="margin: 2% 5%; text-align: left">
<? if ($msg) { ?>
    <div class="indicator">
        <?= $msg ?>
    </div>
<? } ?>

<? if (count($errs)) { ?>
    <div class="userError">
      Please correct the following errors:
      <ul>
      <? foreach ($errs as $e) { ?>
          <li><?= $e ?></li>
      <? } ?>
      <ul>
    </div>
<? } ?>

<? if ($SHOWFORM) { ?>
    <? $fex->display() ?>
<? } else { ?>

    <div style="text-align: right">
    <a href="<?= $_SERVER['PHP_SELF'] ?>?<?= $reqIdKey ?>=<?= $parentid ?>&op_add" class="buttonAddItem">Add New <?= $table_title ?></a>
    </div>
    <br />
    <br />


    <? if (count($addrs)) { ?>
        <div style="text-align: right">
            SHIPPING BILLING
        </div>
    <? } else { ?>
        No addresses have been entered by this customer.
    <? } ?>
    
    <? foreach ($addrs as $addr) { ?>
        <div style="padding-bottom: 1em;">

            <div style="background: #dedede; text-align: right">
                <input type="radio" name="shipSelector" value="<?= $addr['id'] ?>" <?= ($addr['id'] == $ship_id)? 'checked':'' ?>  onclick="doSelectAddr('ship', this.value, <?= $parentid ?>)" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="radio" name="billSelector" value="<?= $addr['id'] ?>" <?= ($addr['id'] == $bill_id)? 'checked':'' ?> onclick="doSelectAddr('bill', this.value, <?= $parentid ?>)" />&nbsp;&nbsp;&nbsp;&nbsp;
            </div>


            <?  $smarty->assign('address', $addr);
                $smarty->display('cart/address_format.tpl');
             ?>
             <div style="text-align: right">
               [<a href="<?= $_SERVER['PHP_SELF'] ?>?<?= $reqIdKey ?>=<?= $parentid ?>&op_edit=<?= $addr['id'] ?>">edit</a>]
             </div>
        </div>
    <? } ?>
</div>
</body></html>
<? 

}
