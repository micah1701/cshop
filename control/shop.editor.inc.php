<?php

/**
 * manange all sorts of product stuff
 * this is meant to be require()d from another file. After the following has
 * been set up, eg:
 *
 * =========================================
 * $table_title = "Manufacturer";
 * $tablename = 'cm_manufacturers';
 * $table_ordercol = 'name'; // default column to order by
 * $table_namecol = 'name'; // column that should be use as the title/name/text to describe the row, for error reporting
 * 
 * 
 * $colmap = array('name' =>      array('Team Name', 'text', null, 1),
 *                'is_active' => array('Is Active?', 'toggle'),
 *                'url' => array('Web Site', 'text', null, 0),
 *                'description' => array('Description', 'textarea', null, 0),
 *                );
 * require ('./cshop/shop.editor.inc.php');
 * =========================================
 *
 * TODO - didnt use db_container here for some reason. That would make this a
 * lot simpler. But, since it works...
 *
 * DEPRECATED (about 10 minutes after it was written) - wrote store.dbcwrap.php
 * which does the similar thing but better. However it does require a standup
 * utility class to be defined for each table that needs to be edited *
 * 
 * $Id: shop.editor.inc.php,v 1.10 2006/10/16 03:39:10 sbeam Exp $
 */
error_reporting(E_ALL);

require_once('formex.class.php');
require_once('mosh_tool.class.php');
require_once('uploadable.class.php');
require_once("fu_HTML_Table.class.php");      


$pagetitle = "Store $table_title - ";

$SHOWFORM = true; // are we showing a form or not?

$ACTION = null;
define ('OP_ADD', 'ADD '. strtoupper($table_title));
define ('OP_EDIT', 'EDIT '. strtoupper($table_title));
define ('OP_KILL', 'REMOVE '. strtoupper($table_title));

$itemid = null;

/** decide on a course of action... **/
if (isset($_POST['f_op']) and $_POST['f_op'] == OP_EDIT) {
    $ACTION = OP_EDIT;
    $itemid = $_POST['f_id'];
}
elseif (isset($_POST['f_op']) and $_POST['f_op'] == OP_ADD) {
    $ACTION = OP_ADD;
}
elseif (isset($_POST['f_id']) and isset($_POST['f_op_kill'])) {
    $itemid = $_POST['f_id'];
    $ACTION = OP_KILL;
}
elseif (isset($_GET['op_edit']) and !empty($_GET['op_edit'])) {
    $itemid = $_GET['op_edit'];
    $ACTION = OP_EDIT;
}
elseif (isset($_GET['op_add'])) {
    $ACTION = OP_ADD;
}
else {
    $SHOWFORM = false;
}
/** **/



$errs = array();

/** POST rec'd, check valid, proc. upload and save if OK */
if (isset($_POST['f_op']) and ($ACTION == OP_ADD or $ACTION == OP_EDIT)) {
    $mosh = new mosh_tool();
    $msg = '';

    $vals = array();
    $img_vals = array();
    if ($errs = $mosh->check_form($colmap)) {
        // handled below
    }
    else {
        $vals = $mosh->get_form_vals($colmap);

        /** get list of all file upload fields from colmap **/
        $upfiles = array();
        foreach ($colmap as $k => $params) {
            if ($params[1] == 'image_upload' or $params[1] == 'file') {
                $upfiles[$k] = array();
            }
        }

        /** process each uploaded image **/
        foreach (array_keys($upfiles) as $upfile) {
            $uplo = new uploadable("f_$upfile");
            if (!$uplo->is_uploaded_file()) {
                if ($ACTION == OP_ADD) {
                    // not req.
                }
            }
            else { // we got one - use $uplo to check it and save it and create DB vals as needed
                $uplo->unique_filename = true;
                $uplo->preserve_original_name = true;

                $uplo->setErrorHandling(PEAR_ERROR_RETURN);
                // tell $uplo what kind of image we expect and where to put it:
                $uplo->params(array('path'=> CSHOP_MEDIA_FULLPATH,         
                                    'allowed'=> 'web_images',
                                    'ws_path' => CSHOP_MEDIA_URLPATH));
                $res = $uplo->save_upload();

                if (PEAR::isError($res)) {
                    $errs[] = $res->getMessage();
                }
                else {
                    // get the name of the new file
                    $newfilename = $uplo->get_newname();
                    // create a thumbnail right here:
                    // vals to be put in DB
                    $upfiles[$upfile] = array('fImage' => $newfilename,
                                             #'sType' => 'image',
                                             'dtDate' => date('Y-m-d h:i'),
                                             'bGallery' => 'No',
                                             'bLive' => 'Yes');
                }
            }
        }


        $imgcount = 0;

        /** try to insert or update image info to DB table media_prod **/
        foreach ($upfiles as $upfile => $img_vals) {
            if (count($img_vals)) {
                $res = $pdb->autoExecute('media', $img_vals, DB_AUTOQUERY_INSERT);
                $vals[$upfile] = $pdb->getOne('SELECT LAST_INSERT_ID()');
                $imgcount++;
            }
        }

        /** insert/update as needed **/
        if (!count($errs)) {
            if ($ACTION == OP_EDIT) { // update the row in $tablenameable
                $res = $pdb->autoExecute($tablename, $vals, DB_AUTOQUERY_UPDATE, "id = $itemid");

                $msg .= sprintf('%s "%s" was updated.', $table_title, $vals[$table_namecol]);

            }
            else { // OP_ADD

                $itemid = $pdb->nextId($tablename);
                $vals['id'] = $itemid;
                $res = $pdb->autoExecute($tablename, $vals, DB_AUTOQUERY_INSERT);

                $msg .= sprintf('Inserted new %s "%s".', $table_title, $vals[$table_namecol]);
            }

            // append nice info to $msg
            if ($imgcount) {
                $msg .= sprintf(" %d image%s loaded.", $imgcount, ($imgcount>1)? 's were':' was');
            }

            // send back to self with messageness
            header("Location: {$_SERVER['PHP_SELF']}?info=" . base64_encode($msg));
        }
    }
}
elseif (isset($_POST['f_op_kill']) and ($ACTION == OP_KILL)) {

    $sql = sprintf("DELETE FROM $tablename WHERE id = %d", $itemid);
    $res = $pdb->query($sql);

    $msg = "The selected $table_title was totally removed.";
    // send back to self with messageness
    header("Location: {$_SERVER['PHP_SELF']}?info=" . base64_encode($msg));
}


if ($SHOWFORM) {
    $fex = new formex('POST');
    $fex->js_src_inline = true;
    $fex->add_element($colmap); // all those things in $colmap are in the form now

    $fex->add_element('op', array($ACTION, 'submit')); // the button

    if ($ACTION == OP_EDIT) {

        $sql = sprintf("SELECT %s
                        FROM $tablename c 
                        WHERE c.id = %d",
                        join(',', array_keys($colmap)),
                        $itemid);
        $fex->elem_vals = $pdb->getRow($sql);

        $fex->add_element('id', array('hid id', 'hidden', $itemid, 0)); // important

        $cat_name = $fex->elem_vals[$table_namecol];

        $confirm_msg = sprintf('This will remove this %s from the site permanently. Are you sure?', $table_title);
        $fex->add_element('op_kill', array(OP_KILL, 'submit', null, null, 'onclick="return confirm(\''. $confirm_msg . '\')"'));

    }
}
else {
    /** list all cm_colors in one big ass dump using HTML_Table **/
    $table = new fu_HTML_Table(array("width" => "600"));
    $table->setAutoGrow(true);
    $table->setAutoFill("n/a");
    
    $header_row = array();
    if (!isset($list_display_columns)) {
        foreach ($colmap as $k => $v) {
            $header_row[$k] = $v[0];
        }
    }
    else {
        foreach ($list_display_columns as $c) {
            $header_row[$c] = $colmap[$c][0];
        }
    }

    $table->addSortRow($header_row, null, null, 'TH', null);

    if (isset($_GET['by'])) {
        $orby = $_GET['by'];
        if (in_array($orby, array_keys($header_row))) {
            $table_ordercol = $orby;
        }
    }

    $sql = "SELECT id, " . join(',', array_keys($header_row)) .
            " FROM $tablename c
            ORDER BY $table_ordercol";

    $res = $pdb->query($sql);
    while ($row = $res->fetchRow()) {
        $vals = array();

        foreach ($header_row as $k => $v) {
            if ($colmap[$k][1] == 'colorpicker') {
                $row[$k] = sprintf('<span style="width: 12px; height: 12px; border: 2px solid black; background-color: %s">&nbsp;&nbsp;&nbsp;</span>&nbsp;%s', $row[$k], $row[$k]);
            }
            $vals[] = $row[$k];
        }

        // store.edit.php?nid=444
        $link = sprintf('%s?op_edit=%d', $_SERVER['PHP_SELF'], $row['id']);
        $table->addRow_fu($vals, '', null, $link);
    }
    $numrows = $res->numRows();
}

##############################################################################
# output template
##############################################################################
$smarty->assign('pagetitle', strtolower($ACTION)); // make a nice title
$smarty->display('control/header.tpl');
?>


<div align="center" style="margin: 10px">

<? if ($ACTION) { ?>
    <div style="text-align: left; width: 600px">
    <a href="<?= $_SERVER['PHP_SELF'] ?>"><?= $table_title ?></a>
    <? if (isset($cat_name)) { ?>
        &raquo;&nbsp;<a href="<?= $_SERVER['PHP_SELF'] ?>?op_edit=<?= $itemid ?>"><?= $cat_name ?></a>
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
    
    <div style="width: 600px" class="container">
      <div class="heading">
          <?= $table_title ?>
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
    </div>
<? } else { ?>
  <div style="width: 600px; border: 1px solid black; padding: 4px">
    <div align="right" style="width: 600px">
      <a href="<?= $_SERVER['PHP_SELF'] ?>?op_add" class="buttonAddItem">Add New <?= $table_title ?></a>
    </div>
    <? if (!$numrows) { ?>
	    No records found. [<a href="<?= $_SERVER['PHP_SELF'] ?>?op_add">ADD</a>]
    <? } else { ?>
      <br />
      <? echo $table->toHTML() ?>
    <? } ?>
  </div>
<? } ?>
	
</div>
	
<? 
$smarty->display('control/footer.tpl');
