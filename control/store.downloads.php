<?php
/**
 * digital downloads tab in products admin
 */

require_once(CONFIG_DIR.'cshop.config.php');
require_once('formex.class.php');
require_once("fu_HTML_Table.class.php");      


$thing = 'Digital Download';

$ACTION = null;
define ('OP_ADD', 'Add new '.$thing);
define ('OP_EDIT', 'Update '.$thing);
define ('OP_KILL', 'Remove '.$thing);


$msg = null;
$errs = array();

$ACTION = null;
$thisid = null;

/** decide on a course of action... **/
if (isset($_POST['f_op']) and isset($_POST['f_cm_products_id'])) {

    $productid = $_POST['f_cm_products_id'];

    if ($_POST['f_op'] == OP_EDIT) {
        $ACTION = OP_EDIT;
        $thisid = $_POST['f_id'];
    }
    else
        $ACTION = OP_ADD;

}
elseif (isset($_POST['f_op_kill'])) {
    $ACTION = OP_KILL;
    $productid = $_POST['f_cm_products_id'];
    $thisid = $_POST['f_id'];
}
elseif (!empty($_GET['pid'])) {

    $productid = $_GET['pid'];

    if (isset($_GET['op_edit'])) {
        $ACTION = OP_EDIT;
        $thisid = $_GET['op_edit'];
    }
    elseif (isset($_GET['op_add'])) 
        $ACTION = OP_ADD;

}

if (empty($productid))
    trigger_error("productid was not passed", E_USER_ERROR);


$product = cmClassFactory::getInstanceOf(CSHOP_CLASSES_PRODUCT, $pdb);
$product->set_id($productid);
if (! ($product->fetch(array('title'))))
    trigger_error("unknown product id!", E_USER_ERROR);


$download = cmClassFactory::getInstanceOf(CSHOP_CLASSES_DOWNLOADS, $pdb);
$colmap = $download->get_colmap();


/** init form */
$fex = new formex('POST');
$fex->add_element($colmap);

/** POST rec'd, check valid, proc. upload and save if OK */
if (isset($_POST['f_op']) and ($ACTION == OP_EDIT or $ACTION == OP_ADD)) {

    if (! ($errs = $fex->validate($_POST))) {
        $vals = $fex->get_submitted_vals($_POST);

        if (!$download->do_validate($vals)) {
            foreach ($download->get_validation_errors() as $err) {
                $errs[] = $err['message'];
            }
        }
        else {
            if ($ACTION == OP_EDIT)
                $download->set_id($thisid);

            PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
            $res = $download->store($vals);
            PEAR::popErrorHandling();

            if (PEAR::isError($res)) {
                $errs[] = "A database error ocurred: " . $res->getMessage();
            }
            else {
                if ($ACTION == OP_EDIT) 
                    $msg = "Changes have been made to " . $thing;
                elseif ($ACTION == OP_ADD) 
                    $msg = $thing . " has been added";
            }
        }
    }
}
elseif ($ACTION == OP_KILL) {
    $download->set_id($thisid);
    if ($download->kill()) 
        $msg = $thing . ' has been deleted.';
}
if (!empty($msg) and !count($errs)) { /** redir on success **/
    header(sprintf("Location: %s?pid=%d&msg=%s", $_SERVER['PHP_SELF'], $productid, base64_encode($msg)));
    exit();
}




if (!$ACTION) {
    $table = new fu_HTML_Table(array('width'=>'90%', 'align'=>'center'));
    $header_row = array('name' => 'Name', 'url'=>'URL', 'is_active'=>'Active?');
    $table->addSortRow($header_row);

    $numrows = 0;

    if ($downloads = $download->fetch_by_product_id($productid)) {

        foreach ($downloads as $row) {
            $vals = array();

            $row['url'] = sprintf('<a href="%s">%s</a>', $row['url'], $row['url']);

            foreach (array_keys($header_row) as $k) {
                $vals[] = $row[$k];
            }

            // store.edit.php?nid=444
            $link = sprintf('%s?op_edit=%d&pid=%d',
                              $_SERVER['PHP_SELF'],
                              $row['id'],
                              $productid);
                              
            $table->addRow($vals, null, true, $link);
        }
        $numrows = count($downloads);
    }
}

/** build form **/
$fex->js_src_inline = true;
$fex->add_element('op', array($ACTION, 'submit', null, null, 1));
$fex->add_element('cm_products_id', array('id', 'hidden', $productid, 1));
/** **/

if ($ACTION == OP_EDIT) {
    $download->set_id($thisid);

    $fex->add_element('op_kill',array(OP_KILL, 'submit', null, null, 'onclick="return confirm(\'Are you sure?\')"'));
    $fex->add_element('id', array('', 'hidden'));
    $fex->set_elem_value($download->fetch());
}




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
<title>product download control framelet</title>
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

    <? if (isset($table)) { ?>
        <div style="text-align: right; padding: 4px">
          <a class="buttonAddItem" href="<?= $_SERVER['PHP_SELF'] ?>?op_add&amp;pid=<?= $productid ?>">Add new <?= $thing ?></a>
        </div>
        <? if ($numrows) { ?>
          <br />
          <? echo $table->toHTML() ?>
        <? } ?>
    <? } elseif (isset($fex)) { ?>       
        <div style="float: right">
            <a href="?pid=<?= $productid ?>">&laquo; back</a>
        </div>  
        <? if ($ACTION == OP_ADD) { ?>Adding new <?= $thing ?><? } ?>
        <? if ($ACTION == OP_EDIT) { ?>Updating <?= $thing ?><? } ?>
        <br />
        <? $fex->display() ?>
    <? } ?>
</div>
<? 
$smarty->assign('suppress_footer', 1);
$smarty->display('control/footer.tpl');


