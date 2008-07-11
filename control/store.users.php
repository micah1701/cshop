<?php
/**
 * managing users, addresses, login credentials etc.
 *
 * $Id: store.users.php,v 1.13 2008/05/01 18:09:01 sbeam Exp $
 */
require_once('formex.class.php');
require_once("fu_HTML_Table.class.php");      
require_once(CONFIG_DIR.'circusShop.config.php');    
require_once(CSHOP_CLASSES_USER . '.class.php');


$tablename = 'cm_categories';
$pagetitle = 'Users';
$table_title = 'User';
$table_namecol = 'username';

$SHOWFORM = true; // are we showing a form or not?
$errs = array();
$msg = '';

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


$userclass = CSHOP_CLASSES_USER;
$user = new $userclass($pdb);


/** POST rec'd, check valid, proc. upload and save if OK */
if (isset($_POST['op']) and ($ACTION == OP_ADD or $ACTION == OP_EDIT)) {
    $mosh = new mosh_tool();
    $mosh->form_field_prefix = '';

    $vals = array();
    $img_vals = array();
    if ($errs = $mosh->check_form($user->colmap)) {
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
                $errs[] = $user->getErrorMessage($res);
            }
            else {
                $msg .= sprintf('%s "%s" was updated.', $table_title, $vals[$table_namecol]);
            }

        }
        else { // OP_ADD

            $itemid = $pdb->nextId($tablename);
            $vals['id'] = $itemid;
            if (empty($vals['username'])) $vals['username'] = null;
            $res = $user->store($vals, false);
            if (PEAR::isError($res)) {
                $errs[] = $user->getErrorMessage($res);
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
    $res = $user->kill();
    $msg = "The selected $table_title was totally removed.";
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
        $fex->elem_vals = $user->fetch();
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
    $fex->add_element($user->get_colmap()); 
    $fex->add_element('op', array($ACTION, 'submit')); // the button
}
else {
    /** list all cm_categories in one big ass dump using HTML_Table **/
    $table = new fu_HTML_Table(array("width" => "600"));
    $table->setAutoGrow(true);
    $table->setAutoFill("n/a");

    $cols = array('cust_name', 'company', 'email');

    $header_row = array();
    foreach ($cols as $k) {
        if (!empty($user->colmap[$k])) {
            $header_row[$k] = $user->colmap[$k][0];
        }
    }

    if (isset($_GET['by']) and in_array($_GET['by'], $cols)) {
        $orderby = $_GET['by'];
    }
    else {
        $orderby = 'email';
    }
    $orderdir = (isset($_GET['dir']) and $_GET['dir'] == 'D')? 'DESC' : 'ASC';

    $table->addSortRow($header_row, null, null, 'TH', null);
    $cols = array_keys($header_row);

    if (defined('CSHOP_ALLOW_ANON_ACCOUNT')) {
        $sql = sprintf("SELECT id, %s, IFNULL(email, anon_email) AS email FROM %s ORDER BY %s %s",
                        join(',', $cols),
                        $user->get_table_name(),
                        $orderby, $orderdir);
    }
    else {
        $sql = sprintf("SELECT id, %s FROM %s ORDER BY %s %s",
                        join(',', $cols),
                        $user->get_table_name(),
                        $orderby, $orderdir);
    }

    $res = $pdb->query($sql);
    while ($row = $res->fetchRow()) {
        $vals = array();
        foreach ($cols as $k) {
            $vals[] = $row[$k];
        }

        $link = sprintf('%s?%s=%d',
                          $_SERVER['PHP_SELF'], 
                          $reqIdKey,
                          $row['id']);
        $table->addRow_fu($vals, '', true, $link);
    }
    $numrows = $res->numRows();
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
              <iframe id="invFrame" src="store.users.addresses.php?<?= $reqIdKey ?>=<?= $itemid ?>"  frameborder="0" marginwidth="0" marginheight="0" width="590" height="200" scrolling="yes" ></iframe> 
            </div>

          </div>
        <? } ?>
        

     </div>
  </div>
<? } else { ?>
  <div style="width: 600px; border: 1px solid black; padding: 4px">
    <div align="right" style="width: 600px">
      <a href="<?= $_SERVER['PHP_SELF'] ?>?op_add" class="buttonAddItem">Add New User</a>
    </div>
    <? if (!$numrows) { ?>
	No customers have signed up yet.
    <? } else { ?>
      <br />
      <? echo $table->toHTML() ?>
    <? } ?>
  </div>
<? } ?>
	
</div>
	
<? 
$smarty->display('control/footer.tpl');
