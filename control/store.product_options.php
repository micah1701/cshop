<?php
/**
 * add or edit product options - called from w/in IFRAME in products editor
 *
 * TODO this all pretty much sucks, need to make more abstract 
 *
 * $Id: store.product_options.php,v 1.6 2007/05/23 20:55:07 sbeam Exp $
 */
error_reporting(E_ALL);

require_once(CONFIG_DIR.'circusShop.config.php');
require_once('formex.class.php');
require_once('mosh_tool.class.php');
require_once("fu_HTML_Table.class.php");      
require_once(CSHOP_CLASSES_PRODUCT_OPTION . '.class.php');

/* set of actions this script may perform */
/* init db_container instance to make life easier */
$c = CSHOP_CLASSES_PRODUCT_OPTION;
$cpo = new $c($pdb);

$thing = $cpo->class_descrip;

$ACTION = null;
define ('OP_ADD', 'ADD '.$thing);
define ('OP_EDIT', 'EDIT '.$thing);
define ('OP_KILL', 'REMOVE '.$thing);

$msg = null;
$productid = null;
$errs = array();

$ACTION = null;

/** decide on a course of action... **/
if (isset($_GET['op_edit'])) {
    $reqid = $_GET['op_edit'];
    $productid = $_GET['nid'];
    $ACTION = OP_EDIT;
}
elseif (isset($_GET['op_add'])) {
    $productid = $_GET['nid'];
    $ACTION = OP_ADD;
}
elseif (isset($_POST['f_op']) and $_POST['f_op'] == OP_ADD) {
    $productid = $_POST['f_nid'];
    $ACTION = OP_ADD;
}
elseif (isset($_POST['f_op']) and $_POST['f_op'] == OP_EDIT) {
    $reqid = $_POST['f_reqid'];
    $productid = $_POST['f_nid'];
    $ACTION = OP_EDIT;
}
elseif (isset($_POST['f_nid']) and isset($_POST['f_op_kill'])) {
    $reqid = $_POST['f_reqid'];
    $productid = $_POST['f_nid'];
    $ACTION = OP_KILL;
}
elseif (isset($_GET['nid']) and !empty($_GET['nid'])) {
    $productid = $_GET['nid'];
}


/** **/
if (!$productid) {
    trigger_error("productid was not passed", E_USER_ERROR);
}


$cpo->set_cm_product_id($productid);

$colmap = $cpo->get_colmap();

/** POST rec'd, check valid, proc. upload and save if OK */
if (isset($_POST['f_op']) and ($ACTION == OP_ADD or $ACTION == OP_EDIT)) {
    $mosh = new mosh_tool();

    if ($errs = $mosh->check_form($colmap)) {
        // handled below
    }
    else {
        $vals = $mosh->get_form_vals($colmap);
        $vals['cm_products_id'] = $productid;

        if (!count($errs)) {
            if ($ACTION == OP_EDIT) {
                $cpo->set_id($reqid);
            }

            PEAR::setErrorHandling(PEAR_ERROR_RETURN);
            $res = $cpo->store($vals);

            if (PEAR::isError($res) and $res->getCode() == DB_ERROR_ALREADY_EXISTS) {
                $errs[] = sprintf("An option for this product with key '%s' and value '%s' already exists",
                                  $vals['optkey'],
                                  $vals['opt_value']);
            }
            elseif (PEAR::isError($res)) {
                $errs[] = "A database error ocurred: " . $res->getMessage();
            }
            else {
                if ($ACTION == OP_ADD) {
                    $msg = "New product option has been added";
                }
                elseif ($ACTION == OP_EDIT) {
                    $msg = "Changes have been made to product option";
                }
            }
        }
    }
    PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'pear_error_handler');
}
elseif ($ACTION == OP_KILL) {
    $cpo->set_id($reqid);
    $cpo->kill();
}


if ($msg and !count($errs)) { /** redir on success **/
    header(sprintf("Location: %s?nid=%d&msg=%s", $_SERVER['PHP_SELF'], $productid, base64_encode($msg)));
    exit();
}



















if ($ACTION == OP_ADD or $ACTION == OP_EDIT) {
    $fex = new formex('POST');
    $fex->js_src_inline = true;
    $fex->add_element($colmap);
    $fex->add_element('op', array($ACTION, 'submit', null, null, 1));
    $fex->add_element('nid', array('id', 'hidden', $productid, 1));

    if ($ACTION == OP_EDIT) {
        $cpo->set_id($reqid);
        $hdr = $cpo->fetch();

        $fex->elem_vals = $hdr;

        $fex->add_element('reqid',array('reqid', 'hidden', $reqid, null));
        $fex->add_element('op_kill',array(OP_KILL, 'submit', null, null, 'onclick="return confirm(\'Are you sure?\')"'));
    }
}
else {
    /** list all cm_categories in one big ass dump using HTML_Table **/
    $table = new fu_HTML_Table(array('width'=>'90%', 'align'=>'center'));
    $table->setAutoGrow(true);
    $table->setAutoFill("-");
    $header_row = $cpo->control_header_cols;

    $table->addRow(array_values($header_row), null, 'TH');

    $cols = array_keys($header_row); 
    array_unshift($cols, 'id');

    if ($rows = $cpo->fetch_any()) {
        foreach ($rows as $row) {

            $link = sprintf('%s?op_edit=%d&nid=%d',
                            $_SERVER['PHP_SELF'],
                            urlencode($row['id']),
                            $productid);

            $rowvals = array();
            foreach ($header_row as $k => $v) {
                $rowvals[] = $row[$k];
            }
                              
            $table->addRow_fu($rowvals, null, true, $link);
        }
    }

    $numrows = $cpo->numRows;
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
