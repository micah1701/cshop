<?php
/**
 * managing users, addresses, login credentials etc.
 *
 * $Id: store.users.php,v 1.13 2008/05/01 18:09:01 sbeam Exp $
 */
require_once('formex.class.php');
require_once("fu_HTML_Table.class.php");      
require_once(CONFIG_DIR.'cshop.config.php');    
require_once(CSHOP_CLASSES_USER . '.class.php');
require_once("res_pager.class.php");      
require_once("filter_form.class.php");      


$SHOWFORM = true; // are we showing a form or not?
$errs = array();
$msg = '';


/** decide on which result page to show **/
$range = 50;
$offset = (isset($_GET['page']))? (($_GET['page']-1) * $range) : 0;
/** **/

/* set of actions this script may perform */
$ACTION = null;
define ('OP_ADD', 'ADD USER');
define ('OP_EDIT', 'EDIT USER');
define ('OP_KILL', 'REMOVE USER');
define ('OP_PASS', 'RESET PASSWORD');

$itemid = null;
$reqIdKey = 'uid';

/** decide on a course of action... **/
if (isset($_POST['op']) and $_POST['op'] == OP_EDIT) {
    $ACTION = OP_EDIT;
    $itemid = $_POST[$reqIdKey];
}
elseif (isset($_POST['op']) and $_POST['op'] == OP_ADD) {
    $ACTION = OP_ADD;
}
elseif (isset($_POST[$reqIdKey]) and isset($_POST['op_kill'])) {
    $itemid = $_POST[$reqIdKey];
    $ACTION = OP_KILL;
}
elseif (isset($_POST[$reqIdKey]) and isset($_POST['op_pass'])) {
    $itemid = $_POST[$reqIdKey];
    $ACTION = OP_PASS;
}
elseif (isset($_GET[$reqIdKey]) and !empty($_GET[$reqIdKey])) {
    $itemid = $_GET[$reqIdKey];
    $ACTION = OP_EDIT;
}
elseif (isset($_GET['op_add'])) {
    $ACTION = OP_ADD;
}
else {
    $SHOWFORM = false;
}
/** **/


$user = cmClassFactory::getInstanceOf(CSHOP_CLASSES_USER, $pdb);

$pagetitle = 'Users';
$table_title = 'User';
$table_namecol = 'email';


/** POST rec'd, check valid, proc. upload and save if OK */
if (isset($_POST['op']) and ($ACTION == OP_ADD or $ACTION == OP_EDIT)) {
    $mosh = new mosh_tool();
    $mosh->form_field_prefix = '';

    $vals = array();
    $img_vals = array();
    if ($errs = $mosh->check_form($user->get_colmap())) {
        // handled below
    }
    else {
        $vals = $mosh->get_form_vals($user->colmap);
        $user->db->pushErrorHandling(PEAR_ERROR_RETURN);

        if ($ACTION == OP_EDIT) { // update the row in $tablenameable
            $user->set_id($itemid);
            if (defined('CSHOP_ALLOW_ANON_ACCOUNT')) {
                $user_rec = $user->fetch(array('is_anon'));
                if ($user_rec['is_anon']) {
                    $vals['anon_email'] = $vals['email'];
                    $vals['email'] = null;
                }
            }
            $res = $user->store($vals, false);
            if (PEAR::isError($res)) {
                $errs[] = $res->getMessage();
            }
            else {
                $msg .= sprintf('%s "%s" was updated.', $table_title, $vals[$table_namecol]);
            }

        }
        else { // OP_ADD

            $itemid = $pdb->nextId($user->get_table_name());
            $vals['id'] = $itemid;
            if (empty($vals['username'])) $vals['username'] = null;
            $res = $user->store($vals, false);
            if (PEAR::isError($res)) {
                $errs[] = $res->getDebugInfo();
            }
            else {
                $uniq = $user->force_pword_change(); // force pw change by default!
                $res = $user->send_pass_notification(true);

                $msg .= sprintf('Inserted new %s "%s".', $table_title, $vals[$table_namecol]);
            }
        }
        $user->db->popErrorHandling();
    }
}
elseif (($ACTION == OP_KILL)) {
    $user->set_id($itemid);

    if ($orders = $user->fetch_order_history()) {
        $errs[] = "User cannot be removed with existing orders";
    }
    else {
        $res = $user->kill();
        $msg = "The selected $table_title was totally removed.";
    }
}
elseif ($ACTION == OP_PASS) {
    $user->set_id($itemid);
    $user->force_pword_change();
    $res = $user->send_pass_notification();
    $msg = "The password for this user has been reset and an email has been sent to
            {$user->header['email']} allowing them to reset it [$res]";
}


// send back to self with messageness
if ($msg) {
    header("Location: {$_SERVER['PHP_SELF']}?info=" . base64_encode($msg));
    exit();
}



if ($SHOWFORM) {
    $fex = new formex('POST');
    $fex->js_src_inline = true;
    $fex->left_td_style = '';
    $fex->field_prefix = '';

    if ($ACTION == OP_EDIT) {

        $user->set_id($itemid);
        if (! ($fex->elem_vals = $user->fetch())) {
            $errs[] = 'No such user found';
        }
        else {
            if (defined('CSHOP_ALLOW_ANON_ACCOUNT') and empty($fex->elem_vals['email'])) {
                $fex->elem_vals['email'] = $fex->elem_vals['anon_email'];
            }

            $item_name = $fex->elem_vals[$table_namecol];
            
            $fex->add_element($reqIdKey, array('hid id', 'hidden', $itemid, 0)); // important

            $confirm_msg = sprintf('This will remove this %s from the site permanently. Are you sure?', $table_title);
            $fex->add_element('op_kill', array(OP_KILL, 'submit', null, null, 'onclick="return confirm(\''. $confirm_msg . '\')"'));

            /** get all addrs belonging to this captain **/
            $billaddr = $user->fetchBillingAddr();
        }
    }
    $fex->add_element($user->get_colmap()); 
    $fex->add_element('op', array($ACTION, 'submit')); // the button

    if ($orders = $user->fetch_order_history()) {
        $table = new fu_HTML_Table(array("width" => "820"));
        $table->setAutoGrow(true);
        $table->setAutoFill("-");
        $table->addRow(array('Order Number', 'Ship name', 'Status', 'Date', 'Amt Quoted'), 'header', false);
        foreach ($orders as $o) {
            $vals = array($o['order_token'],
                          $o['shipping_name'],
                          $o['status'],
                          date('d M Y', strtotime($o['order_create_date'])),
                          $o['amt_quoted']);
            $link = sprintf('store.orders.php?tok=%s',
                              $o['order_token']);
            $table->addRow($vals, '', false, $link);
        }


    }
}
else {
    /** list all cm_categories in one big ass dump using HTML_Table **/
    $table = new fu_HTML_Table(array("width" => "600"));
    $table->setAutoGrow(true);
    $table->setAutoFill("n/a");


    $header_row = array();
    if (!isset($user->control_header_cols)) {
        $cols = array('cust_name', 'last_name', 'first_name', 'company', 'email', 'perms');
        foreach ($cols as $k) {
            if (!empty($user->colmap[$k])) {
                $header_row[$k] = $user->colmap[$k][0];
            }
        }
    }
    else {
        $header_row =& $user->control_header_cols;
        $cols = array_keys($user->control_header_cols);
    }

    if (isset($_GET['by']) and (in_array($_GET['by'], $cols) or $_GET['by'] == 'num_orders')) {
        $orderby = $_GET['by'];
    }
    else {
        $orderby = 'email';
    }
    $orderdir = (isset($_GET['dir']) and $_GET['dir'] == 'D')? 'DESC' : 'ASC';

    $cols = array_keys($header_row);

    $header_row['num_orders'] = '#Orders';
    $table->addSortRow($header_row, $orderby, null, 'TH', null, $orderdir);

    /** decide how to filter the results */
    $where = "1=1"; 
    if (isset($_GET['op_filter'])) {
        $w = array();
        if (!empty($_GET['f_perms'])) {
            $w[] = sprintf('perms = %s', $pdb->quoteSmart($_GET['f_perms']));
        }
        if (!empty($_GET['f_email'])) {
            $w[] = sprintf('email LIKE %s', preg_replace('/[%*]+/', '%%', $pdb->quoteSmart($_GET['f_email'])));
        }
        if (count($w)) {
            $where = join(' AND ', $w);
        }
    }
    /** **/

    if (defined('CSHOP_ALLOW_ANON_ACCOUNT')) {
        $sql = sprintf("SELECT u.id, %s, IFNULL(email, anon_email) AS email, COUNT(o.user_id) AS num_orders FROM %s u LEFT JOIN cm_orders o ON (o.user_id = u.id) WHERE $where GROUP BY u.id ORDER BY %s %s",
                        join(',', $cols),
                        $user->get_table_name(),
                        $orderby, $orderdir);
    }
    else {
        $sql = sprintf("SELECT u.id, %s, COUNT(o.user_id) AS num_orders FROM %s u LEFT JOIN cm_orders o ON (o.user_id = u.id) WHERE $where GROUP BY u.id ORDER BY %s %s",
                        join(',', $cols),
                        $user->get_table_name(),
                        $orderby, $orderdir);
    }

    $res = $pdb->query($sql);

    $numrows = $res->numRows();

    if ($numrows) {
        for ($ptr = $offset; ($range == 0) or (($offset + $range) > $ptr); $ptr++) {
            if (! $row = $res->fetchRow(DB_FETCHMODE_ASSOC, $ptr)) break;

            if (!empty($row['is_anon'])) $row['perms'] = 'anon';

            $vals = array();
            foreach ($cols as $k) {
                $vals[] = $row[$k];
            }
            $vals[] = $row['num_orders'];

            $link = sprintf('%s?%s=%d',
                              $_SERVER['PHP_SELF'], 
                              $reqIdKey,
                              $row['id']);
            $table->addRow($vals, '', false, $link);
        }
    }

    $pager = new res_pager($offset, $range, $numrows, 0, 26);
    $smarty->assign('pager', $pager);

    /** create filter form **/
    $colmap = $user->get_colmap();
    $filt = new filter_form('GET');
    $filt->left_td_style = '';
    $filt->field_prefix = '';
    $filt->add_element('hdr1', array('<b>Filter by::</b>', 'heading'));
    if (isset($colmap['perms'])) {
        $filt->add_element('hdr1', array('Permissions:', 'heading'));
        $filt->add_element('f_perms', array('', 'select', null));
    }
    $filt->add_element('hdr2', array('email:', 'heading'));
    $filt->add_element('f_email', array('', 'text', null, array('size'=>20)));
    $filt->add_element('op_filter', array('GO', 'submit'));
    if (isset($colmap['perms'])) {
        $filt->set_element_opts('f_perms', array(''=>'[ANY]') + $colmap['perms'][2]);
    }
}



















$tab = 'user';





##############################################################################
# output template
##############################################################################
$smarty->display('control/header.tpl');
?>


<div align="center" style="margin: 10px">

<? if ($ACTION) { ?>
    <div style="text-align: left; width: 600px">
    <a href="<?= $_SERVER['PHP_SELF'] ?>"><?= $table_title ?></a>
    <? if (isset($item_name)) { ?>
        &raquo;&nbsp;<a href="<?= $_SERVER['PHP_SELF'] ?>?<?= $reqIdKey ?>=<?= $itemid ?>"><?= $item_name ?></a>
    <? } ?>
        &raquo;&nbsp;<?= ucwords(strtolower($ACTION)) ?>
    </div>
    <br />
    <br />
<? } ?>

<? if (isset($_GET['info'])) { ?>
    <div class="indicator">
      <?= htmlentities(base64_decode($_GET['info'])) ?>
    </div>
    <br />
<? } ?>

<? if ($SHOWFORM) { ?>
    
<div id="tabContentContainer">
  <div id="tabContainer">
    <div class="tabLabel<?= ($tab == 'user')? ' tabSelected' : '' ?>" id="tabdet" rel="detContainer">User Information</a></div>
<? if ($ACTION == OP_EDIT) { ?>
  	<div class="tabLabel<?= ($tab == 'addr')? ' tabSelected' : '' ?>" id="tabaddr" rel="addrContainer">Addresses</div>
<? } ?>
  </div>
    <div id="tabActiveContent">

    <div class="formContainer" id="detContainer"<? if ($tab == 'user') { ?> style="display: block"<? } ?>>
          <div class="heading">
              :: User Information ::
          </div>
        <? if (count($errs)) { ?>
           <div class="userError">
           Please correct the following errors:
           <ul>
           <? foreach ($errs as $e) { ?>
             <li><?= htmlentities($e) ?></li>
           <? } ?>
           </ul>
           </div>
        <? } ?>
        <? echo $fex->render_form() ?>	
        <? if ($ACTION == OP_EDIT) { ?>
          <p>
          <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
            <input type="hidden" value="<?= $itemid ?>" name="<?= $reqIdKey ?>">
            <input type="submit" value="RESET PASSWORD" name="op_pass" onclick="return confirm('This will reset this user\'s password and send them an email allowing them to choose a new one. Are you sure?')">
          </form>
          </p>
        <? } ?>
    </div>

        <? if ($ACTION == OP_EDIT) { ?>
          <div class="formContainer" id="addrContainer"<? if ($tab == 'media') { ?> style="display: block"<? } ?>>
            <div class="heading">
                :: Addresses ::
            </div>
            <div id="inventoryWrap" class="formWrapper">
              <iframe id="invFrame" src="store.users.addresses.php?<?= $reqIdKey ?>=<?= $itemid ?>"  frameborder="0" marginwidth="0" marginheight="0" width="590" height="400" scrolling="yes" ></iframe> 
            </div>

          </div>
        <? } ?>
        

     </div>
  </div>

  <div style="margin: 5em 0">
    <div class="headlineW">
      <h2 class="productName headline">Order History</h2>
    </div>
    <div class="history" style="width: 854px">
        <? if (!empty($orders)) { ?>
          <?= $table->toHTML() ?>
        <? } else { ?>
            No orders found for this user.
        <? } ?>
    </div>
  </div>

<? } else { ?>
    <div style="width: 600px; padding: 4px;" align="right">
      <? $filt->display(); ?>
    </div>
  <div style="width: 600px; border: 1px solid black; padding: 4px">
    <div align="right" style="width: 600px">
      <a href="<?= $_SERVER['PHP_SELF'] ?>?op_add" class="buttonAddItem">Add New User</a>
    </div>
    <? if (!$numrows) { ?>
	No records found.
    <? } else { ?>
      <? $smarty->display('cart/control/res_pager.tpl') ?>
      <br />
      <? echo $table->toHTML() ?>
    <? } ?>
  </div>
<? } ?>
	
</div>
	
<? 
$smarty->display('control/footer.tpl');
