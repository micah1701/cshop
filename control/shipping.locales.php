<?php

/** 
 * special UI for editing shipping Zone Locales. From a list of countries (no states or provinces, yet)
 * choose any to create a zone (Europe, SE Asia, etc)
 *
 * $Id: shipping.locales.php,v 1.2 2007/06/04 15:19:17 sbeam Exp $
 */
error_reporting(E_ALL);

require_once(CONFIG_DIR . 'cshop.config.php');

require_once('formex.class.php');
require_once('db_container.class.php');
require_once('mosh_tool.class.php');
require_once("fu_HTML_Table.class.php");      

require_once(CSHOP_CLASSES_PRODUCT . '.class.php');
require_once('cshop/cmShipMethod_Zone.class.php');

$pagetitle = 'Shipping Zone';

$SHOWFORM = false; // are we showing a form or not?

/* set of actions this script may perform */
$ACTION = null;
define ('OP_ADD', 'ADD '.strtoupper($pagetitle));
define ('OP_EDIT', 'EDIT '.strtoupper($pagetitle));
define ('OP_KILL', 'REMOVE '.strtoupper($pagetitle));

$errs = array();
$msg = null;
$req_id = null;



$sm = new cmShipMethod_Zone();

$sm->dbcontainerSingleton(); // wtf
$colmap = $sm->colmap_zones;

/* form submission happened. See what we got */
if (isset($_POST['op'])) {

    $mosh = new mosh_tool();
    $mosh->form_field_prefix = '';

    /** if we have an id param, this is an edit not an add */
    if (isset($_POST['id'])) {
        $req_id = $_POST['id'];
        $ACTION = OP_EDIT;
    }
    else {
        $ACTION = OP_ADD;
    }

    if (! ($errs = $mosh->check_form($colmap))) {
        $vals = $mosh->get_form_vals($colmap);

        $sm->dbc->setErrorHandling(PEAR_ERROR_RETURN);
        $res = $sm->store_zone($vals, $req_id);

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


/** need to remove a shipmethod */
if (isset($_GET['op_del']) or ($ACTION == OP_KILL)) { 
    $req_id = $_GET['id'];
    $res = $sm->remove_zone($req_id);

    $ACTION = OP_KILL;

    if (PEAR::isError($res) and $res->getCode() != DBCON_ZERO_EFFECT) {
        $errs[] = "Could not remove: " . $res->getMessage();
    }
    else {
        $msg = "Entry was removed";
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
    $fex->add_element($sm->colmap_zones); // all those things in $colmap are in the form now

    $fex->add_element('op', array($ACTION, 'submit')); // the button

    $country_opts = formex::get_country_opts(true);
    $fex->set_element_opts('cm_shipmethods_zone_locales', $country_opts);

    if ($ACTION == OP_EDIT) {
        $vals = $sm->fetch_zone($req_id);
        /* convert linear array of ISO codes to isocode => countryname */
        $cy = array();
        foreach ($vals['cm_shipmethods_zone_locales'] as $iso) {
            $cy[$iso] = $country_opts[$iso];
        }
        $vals['cm_shipmethods_zone_locales'] = $cy;

        $fex->elem_vals = $vals;
        $method_title = $vals['zone_name'];

        $fex->add_element('id', array('hid id', 'hidden', $req_id)); // important
    }
    else {
        $method_title = 'ADD NEW ZONE';
    }
    $form = $fex->get_struct();
}
else {
    /** or a fu_HTML_Table showing all coupons TODO paging ***********************************/
    $table = new fu_HTML_Table(array('width'=>'100%'));
    $table->setAutoGrow(true);
    $table->setAutoFill("&mdash;");

    $have_zones = false;
    if ($rows = $sm->get_avail_zones()) {
        $have_zones = true;
        $table->addRow(array('Zone Name'), 'TH');
        foreach ($rows as $row) {
            $class = 'controlListingRow';
            $link = sprintf('%s?op_edit=%d', $_SERVER['PHP_SELF'], $row['id']);
            unset($row['id']);
            $table->addRow(array_values($row), $class, true, $link);
        }
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
    <div class="container">
      <div class="heading">
          <? if ($ACTION == OP_EDIT) { ?>
            <div style="float: right">
              <a href="?op_del&id=<?= $req_id ?>" style="color: #fc0" onclick="return confirm('remove this shipping zone? Shipping methods that are configured to use this zone will no longer work')">[delete]</a>
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
        <?= $form['FORM'] ?>
        <div style="text-align: left; background: #555; color: #fff; font-weight: bold; padding: 4px">
            Zone Name: <?= $form['zone_name']['TAG'] ?> 
                   <br />
            Included Countries: <?= $form['cm_shipmethods_zone_locales']['TAG'] ?>

        </div>
        <div style="background: #555; color: #fff; font-weight: bold; padding: 4px; text-align: right">
            <input type="submit" name="op" value="SAVE CHANGES" />
        <?= $form['HIDDENS'] ?>
        </div>
        </form>
      </div>
    </div>
<? } else { ?>
	

  <div style="width: 300px; border: 1px solid black; padding: 4px; margin-left: 10px;">
    <div align="right" style="width: 300px">
      <a href="<?= $_SERVER['PHP_SELF'] ?>?op_add" class="buttonAddItem">Add New <?= $pagetitle ?></a>
    </div>
    <br />
    <? if (!empty($have_zones)) { ?>
        <? echo $table->toHTML() ?>
    <? } else { ?>
        No Shipping Zones are set up yet.
    <? } ?>
  </div>

<? } ?>
</div>
<? 
$smarty->display('control/footer.tpl');



