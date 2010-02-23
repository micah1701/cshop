<?php

/** 
 * special UI for editing Zone rate shipping matrix. Admin can add a variety of
 * ship methods. Each ship method can be optionally limited to one or more
 * countries. Each method can have a price or weight basis. Then for each
 * method's basis, there is a range of weights/prices with the associated cost
 * for orders falling in said range.
 *
 * $Id: shipping.zone.php,v 1.6 2008/04/22 20:35:32 sbeam Exp $
 */
error_reporting(E_ALL);

require_once(CONFIG_DIR . 'cshop.config.php');

require_once('formex.class.php');
require_once('db_container.class.php');
require_once('mosh_tool.class.php');
require_once("fu_HTML_Table.class.php");      

require_once(CSHOP_CLASSES_PRODUCT . '.class.php');
require_once('cshop/cmShipMethod_Zone.class.php');

$pagetitle = 'Zone Shipping Method';

$SHOWFORM = false; // are we showing a form or not?

/* set of actions this script may perform */
$ACTION = null;
define ('OP_ADD', 'ADD '.strtoupper($pagetitle));
define ('OP_EDIT', 'EDIT '.strtoupper($pagetitle));
define ('OP_KILL', 'REMOVE '.strtoupper($pagetitle));
define ('OP_KILL_R', 'REMOVE RANGE');

$errs = array();
$msg = null;



$sm = new cmShipMethod_Zone();

$sm->dbcontainerSingleton();
$colmap = $sm->colmap;

/* form submission happened. See what we got */
if (isset($_POST['op'])) {

    $mosh = new mosh_tool();
    $mosh->form_field_prefix = '';

    /** if we have an id param, this is an edit not an add */
    if (isset($_POST['id'])) {
        $req_id = $_POST['id'];
        $ACTION = OP_EDIT;
        $sm->dbc->set_id($req_id);
    }
    else {
        $ACTION = OP_ADD;
    }

    if (! ($errs = $mosh->check_form($colmap))) {

        $vals = $mosh->get_form_vals($colmap);

        $sm->dbc->setErrorHandling(PEAR_ERROR_RETURN);
        $res = $sm->dbc->store($vals);

        if (PEAR::isError($res) and $res->getCode() != DBCON_ZERO_EFFECT) {
            $errs[] = "Storage Error: " . $res->getMessage();
        }
        else {
            if ($ACTION == OP_EDIT) {
                $msg .= sprintf("'%s' entry was updated.", $pagetitle);
            }
            else {
                $msg .= sprintf("Inserted new '%s' entry.", $pagetitle);
            }
        }
    }
}
elseif (isset($_POST['op_basis'])) {

    /** edit name, zone, basis for this method (or add as case may be) */
    if (isset($_POST['method_id'])) {
        $req_id = $_POST['method_id'];
        $sm->dbc->set_id($req_id);
        $msg = "Ship Method edited. ";
    }
    else {
        $msg = "Ship Method added. ";
    }
    $mosh = new mosh_tool();
    $mosh->form_field_prefix = '';
    $vals = $mosh->get_form_vals($sm->colmap);
    $sm->dbc->store($vals);
    $req_id = $sm->dbc->get_id();


    /* clear all basis for this method and add new as needed */
    $sm->clear_basises($req_id);
    for ($i=1; ; $i++) { // existing ones are in xxxxx1, xxxxxx2, xxxxxxx3, etc
        if (empty($_POST["trBasisMax$i"]) && empty($_POST["trBasisMin$i"])) {
            break;
        }
        $sm->store_basis($req_id, $_POST["trBasisMin$i"], $_POST["trBasisMax$i"], $_POST["trCost$i"]);
    }

    /* they may have put something in the new basis boxen */
    if (!empty($_POST['add_basis_min']) || !empty($_POST['add_basis_max'])) {
        $sm->dbc->set_id($req_id);
        $sm->store_basis($req_id, $_POST['add_basis_min'], $_POST['add_basis_max'], $_POST['add_cost']);
        $msg .= " Cost basis added.";
    }


    $base_get_vars = "op_edit=$req_id";
    $ACTION = OP_EDIT;
}


/** need to remove a shipmethod */
if (isset($_GET['op_del']) or ($ACTION == OP_KILL)) { 
    $req_id = $_GET['id'];
    $sm->dbc->set_id($req_id);
    $res = $sm->dbc->kill();
    $ACTION = OP_KILL;
    if (PEAR::isError($res) and $res->getCode() != DBCON_ZERO_EFFECT) {
        $errs[] = "Could not remove: " . $res->getMessage();
    }
    else {
        $msg = "Entry was removed";
    }
}
elseif (isset($_GET['op_range_del']) && is_numeric($_GET['method_id']) && is_numeric($_GET['op_range_del'])) {
    $method_id = $_GET['method_id'];
    $sm->dbc->set_id($method_id);
    $res = $sm->remove_range($_GET['op_range_del']);
    $ACTION = OP_KILL;
    $base_get_vars = "op_edit=$method_id";
    if (PEAR::isError($res) and $res->getCode() != DBCON_ZERO_EFFECT) {
        $errs[] = "Could not remove: " . $res->getMessage();
    }
    else {
        $msg = "Range was removed";
    }
}

/** if there was a sucessful POST, do a redirect */
if ($msg and !count($errs) and ($ACTION)) {
    // send back to self with messageness
    header("Location: {$_SERVER['PHP_SELF']}?$base_get_vars&info=" . base64_encode($msg));
    exit();
}

/** we didn't have a post? maybe we need to show the form... */
if (isset($_GET['op_add'])) $ACTION = OP_ADD;
elseif (isset($_GET['op_edit'])) {
    $ACTION = OP_EDIT;
    $req_id = $_GET['op_edit'];
}

if ($ACTION) $SHOWFORM = true;


/** either show an adding/editing form **************************************************/
if ($SHOWFORM) {

    $c = CSHOP_CLASSES_PRODUCT;
    $pc = new $c($pdb);

    $fex = new formex();
    $fex->js_src_inline = true;
    $fex->left_td_style = '';
    $fex->field_prefix = '';
    $fex->add_element($sm->get_colmap()); // all those things in $colmap are in the form now

    $fex->add_element('op', array($ACTION, 'submit')); // the button


    if ($ACTION == OP_EDIT) {
        $sm->dbc->set_id($req_id);
        $vals = $sm->dbc->fetch();
        $fex->elem_vals = $vals;
        $method_title = $vals['name'];
        $curr_basis = $vals['basis'];

        $fex->add_element('id', array('hid id', 'hidden', $req_id)); // important

        $rbasis = $sm->fetch_basises($req_id);
    }
    else {
        $method_title = 'ADD NEW METHOD';
    }
    $form = $fex->get_struct();
}



/** or a fu_HTML_Table showing all coupons TODO paging ***********************************/
$table = new fu_HTML_Table(array('width'=>'100%'));
$table->setAutoGrow(true);
$table->setAutoFill("&mdash;");

$table->addRow(array('Method','Basis','Zone'), 'TH');

if ($rows = $sm->fetch_method_list()) {
    $have_zones = true;
    foreach ($rows as $row) {
        $class = (isset($req_id) && $row['id'] == $req_id)? 'controlListingRowSelected' : 'controlListingRow';
        $link = sprintf('%s?op_edit=%d', $_SERVER['PHP_SELF'], $row['id']);
        unset($row['id']);
        $table->addRow_fu(array_values($row), $class, true, $link);
    }
}




##############################################################################
# output template
##############################################################################
$smarty->display('control/header.tpl');

?>


<div style="position: relative; width: 600px; margin: 10px">

<? if ($ACTION) { ?>
    <div style="text-align: left; width: 300px">
    <a href="<?= $_SERVER['PHP_SELF'] ?>"><?= $pagetitle ?></a>
    <? if (isset($cat_name)) { ?>
        &raquo;&nbsp;<a href="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $req_id ?>"><?= $cat_name ?></a>
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
    <div style="position: absolute; padding: 3px; margin-left: 310px; width: 300px" class="container">
      <div class="heading">
          <? if ($ACTION == OP_EDIT) { ?>
            <div style="float: right">
              <a href="?op_del&id=<?= $req_id ?>" style="color: #fc0" onclick="return confirm('remove this shipping method?')">[delete]</a>
            </div>
          <? } ?>

          <?= $pagetitle ?>::<?= $method_title ?>
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
        <form action="<? $_SERVER['PHP_SELF'] ?>" method="post">
        <div style="text-align: left; background: #555; color: #fff; font-weight: bold; padding: 4px">
            Method: <?= $form['name']['TAG'] ?> 
                   <br />
            Zone: <?= $form['cm_shipmethods_zone_zones_id']['TAG'] ?>
                   <br />
            Basis: <?= $form['basis']['TAG'] ?>
        </div>
    <? if ($ACTION == OP_EDIT) { ?>
        <div>
            <table width="100%">
              <tr>
                <td>
                    <?= ucfirst($curr_basis) ?> Range
                </td>
                <td align="center">
                    Cost (USD)
                </td>
              </tr>
              <? $i=1; foreach ($rbasis as $rc) { ?>
              <tr style="background-color: <?= ($i%2==0)? '#9e9e9e' : '#aeaeae' ?>">
                <td>
                    <? if ($curr_basis == 'price') { ?>$<? } ?>
                    <input name="trBasisMin<?= $i ?>" type="text" class="trBasisInput" value="<?= $rc['basis_min'] ?>" onblur="this.style.backgroundColor='inherit'" onfocus="this.style.backgroundColor='#fff'" />
                    <? if ($curr_basis == 'weight') { ?>lbs.<? } ?>
                    &ndash;
                    <? if ($curr_basis == 'price') { ?>$<? } ?>
                    <input name="trBasisMax<?= $i ?>" type="text" class="trBasisInput" value="<?= $rc['basis_max'] ?>" onblur="this.style.backgroundColor='inherit'" onfocus="this.style.backgroundColor='#fff'" />
                    <? if ($curr_basis == 'weight') { ?>lbs.<? } ?>
                </td>
                <td align="center">
                    <input name="trCost<?= $i ?>" type="text" class="trShipInput" value="<?= $rc['cost'] ?>" onblur="this.style.backgroundColor='inherit'" onfocus="this.style.backgroundColor='#fff'" />
                </td>
                <td align="center">
                    <a href="#" onclick="if (confirm('Are you sure you want to delete this range?')) { document.location = this.rel }" rel="<?= $_SERVER['PHP_SELF'] ?>?method_id=<?= $req_id ?>&op_range_del=<?= $rc['id'] ?>"><img src="img/delete.png" border="0"></a>
                </td>
              </tr>
              <? $i++; } ?>
              <tr style="background-color: <?= ($i%2==0)? '#9e9e9e' : '#aeaeae' ?>">
                <td valign="bottom">
                    <strong>Add new cost basis:</strong><br />
                    from <input type="text" size="4" name="add_basis_min" value="" />
                    to <input type="text" size="4" name="add_basis_max" value="" />
                    <? if ($curr_basis == 'price') { ?>$<? } ?>
                    <? if ($curr_basis == 'weight') { ?>lbs.<? } ?>
                </td>
                <td valign="bottom" align="center">
                    $<input type="text" size="4" name="add_cost" value="" />
                </td>
                <td></td>
              </tr>
            </table>
        </div>
        <? } ?>
        <div style="background: #555; color: #fff; font-weight: bold; padding: 4px; text-align: right">
            <input type="submit" name="op_basis" value="SAVE CHANGES" />
    <? if ($ACTION == OP_EDIT) { ?>
            <input type="hidden" name="method_id" value="<?= $req_id ?>" />
    <? } ?>
        </div>
        </form>
      </div>
    </div>
<? } ?>
	

  <div style="width: 300px; border: 1px solid black; padding: 4px; margin-left: 10px;">
    <div align="right" style="width: 300px">
      <a href="<?= $_SERVER['PHP_SELF'] ?>?op_add" class="buttonAddItem">Add New <?= $pagetitle ?></a>
    </div>
    <br />
    <? if (!empty($have_zones)) { ?>
        <? echo $table->toHTML() ?>
    <? } else { ?>
        No Zone Shipping Methods are set up yet.
    <? } ?>
  </div>
</div>
<? 
$smarty->display('control/footer.tpl');
