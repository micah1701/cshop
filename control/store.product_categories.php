<?php
/**
 * add or edit product options - called from w/in IFRAME in products editor
 *
 * $Id: store.product_categories.php,v 1.1 2008/06/12 15:58:22 sbeam Exp $
 */
error_reporting(E_ALL);

require_once(CONFIG_DIR.'cshop.config.php');
require_once('formex.class.php');
require_once('mosh_tool.class.php');
require_once("fu_HTML_Table.class.php");      


$thing = 'Product Categories';

$ACTION = null;
define ('OP_ADD', 'Add new '.$thing);
define ('OP_EDIT', 'Update '.$thing);

$pc = cmClassFactory::getInstanceOf(CSHOP_CLASSES_PRODUCT, $pdb);
$pcat = cmClassFactory::getInstanceOf(CSHOP_CLASSES_PRODUCT_CATEGORY, $pdb);

$msg = null;
$productid = null;
$errs = array();

$ACTION = OP_EDIT;

/** decide on a course of action... **/
if (isset($_POST['f_op']) and $_POST['f_op'] == OP_EDIT) {
    $productid = $_POST['f_nid'];
    $ACTION = OP_EDIT;
}
elseif (isset($_GET['nid']) and !empty($_GET['nid'])) {
    $productid = $_GET['nid'];
}


/** **/
if (!$productid) {
    trigger_error("productid was not passed", E_USER_ERROR);
}

$pc->set_id($productid);














$colmap = array('cm_products_categories' => array('Product Categories', 'select_multiple', null, array('size'=>15), false));









/** POST rec'd, check valid, proc. upload and save if OK */
if (isset($_POST['f_op']) and ($ACTION == OP_EDIT)) {


    $mosh = new mosh_tool();

    if ($errs = $mosh->check_form($colmap)) {
        // handled below
    }
    else {
        $vals = $mosh->get_form_vals($colmap);

        if (!isset($vals['cm_products_categories'])) {
            $vals['cm_products_categories'] = array();
        }

        if (!count($errs)) {

            PEAR::setErrorHandling(PEAR_ERROR_RETURN);
            $res = $pc->store($vals);

            if (PEAR::isError($res)) {
                $errs[] = "A database error ocurred: " . $res->getMessage();
            }
            else {
                if ($ACTION == OP_EDIT) {
                    $msg = "Changes have been made to " . $thing;
                }
            }
        }
    }
    PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'pear_error_handler');
}

if ($msg and !count($errs)) { /** redir on success **/
    header(sprintf("Location: %s?nid=%d&msg=%s", $_SERVER['PHP_SELF'], $productid, base64_encode($msg)));
    exit();
}


















/** build form **/

$fex = new formex('POST');

$allcats = $pcat->get_categories_for_select();

$fex->js_src_inline = true;
$fex->add_element($colmap);
$fex->set_element_opts('cm_products_categories', $allcats);
$fex->add_element('op', array($ACTION, 'submit', null, null, 1));
$fex->add_element('nid', array('id', 'hidden', $productid, 1));

$product_info = $pc->fetch(array('title'), true);
$fex->set_elem_default_vals('cm_products_categories', $product_info['cm_products_categories']);

/** **/




// get message for display if any
if (isset($_GET['msg'])) {
    $msg = base64_decode($_GET['msg']);
}






# output template
##############################################################################
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>product media control framelet</title>
<link rel="stylesheet" href="/control/control.css" type="text/css">
<link rel="stylesheet" href="/control/cshop/store.css" type="text/css">
</head>
<body>
<div style="margin: 5px 10% 0 10%">
<? if (!empty($msg)) { ?>
    <div class="indicator">
      <?= htmlentities($msg) ?>
    </div>
    <br />
<? } ?>

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

<? if (empty($msg) && empty($errs)) { ?>
    <div class="userNotice"><strong>Select all categories this product should be associated with.</strong></div>
<? } ?>


    <? if (isset($fex)) { ?>       
        <? if ($ACTION == OP_ADD) { ?>Adding new <?= $thing ?><? } ?>
        <? if ($ACTION == OP_EDIT) { ?>Updating <?= $thing ?><? } ?>
        <br />
        <? $fex->display() ?>
    <? } elseif (isset($table)) { ?>
        <div style="text-align: right; padding: 4px">
          <a class="buttonAddItem" href="<?= $_SERVER['PHP_SELF'] ?>?op_add&amp;nid=<?= $productid ?>">Add new <?= $thing ?></a>
        </div>
        <? if ($numrows) { ?>
          <br />
          <? echo $table->toHTML() ?>
        <? } ?>
    <? } ?>
</div>
<? 
$smarty->assign('suppress_footer', 1);
$smarty->display('control/footer.tpl');

