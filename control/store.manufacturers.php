<?php
/**
 * manange product manufactuers - this is a shameful cut&paste job from
 * store.categories.php!
 *
 * $Id: store.manufacturers.php,v 1.3 2006/07/28 18:06:40 sbeam Exp $
 */
error_reporting(E_ALL);

require_once('formex.class.php');
require_once('mosh_tool.class.php');
require_once('uploadable.class.php');
require_once("fu_HTML_Table.class.php");      

$pagetitle = 'Store Product Manufacturers - ';

$SHOWFORM = true; // are we showing a form or not?

/* set of actions this script may perform */
$ACTION = null;
define ('OP_ADD', 'ADD MANUFACTURER');
define ('OP_EDIT', 'EDIT MANUFACTURER');
define ('OP_KILL', 'REMOVE MANUFACTURER');

$mfrid = null;

/** decide on a course of action... **/
if (isset($_POST['f_op']) and $_POST['f_op'] == OP_EDIT) {
    $ACTION = OP_EDIT;
    $mfrid = $_POST['f_id'];
}
elseif (isset($_POST['f_op']) and $_POST['f_op'] == OP_ADD) {
    $ACTION = OP_ADD;
}
elseif (isset($_POST['f_id']) and isset($_POST['f_op_kill'])) {
    $mfrid = $_POST['f_id'];
    $ACTION = OP_KILL;
}
elseif (isset($_GET['mfr']) and !empty($_GET['mfr'])) {
    $mfrid = $_GET['mfr'];
    $ACTION = OP_EDIT;
}
elseif (isset($_GET['op_add'])) {
    $ACTION = OP_ADD;
}
else {
    $SHOWFORM = false;
}
/** **/


$pagetitle .= strtolower($ACTION); // make a nice title

$errs = array();

/* form definition arrays suitable for formex() */
$colmap = array('sName' =>          array('Manufacturer Name', 'text', null, 1),
                'descrip' =>        array('Description', 'textarea', null),
                'iconid' =>         array('Mfr Icon', 'image_upload', null, array('allowed'=>'web_images',
                                                                                  'ws_path'=>CSHOP_MEDIA_URLPATH,
                                                                                  'exact_dims'=>'40x40',
                                                                                  'path'=> CSHOP_MEDIA_FULLPATH)),
                );


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
                $uplo->params($colmap[$upfile][3]);
                $res = $uplo->save_upload();

                if (PEAR::isError($res)) {
                    $errs[] = $res->getMessage();
                }
                else {
                    // get the name of the new file
                    $newfilename = $uplo->get_newname();
                    // create a thumbnail right here:
                    // vals to be put in DB
                    $upfiles[$upfile] = array('sFilename' => $newfilename,
                                             'sType' => 'image',
                                             'dtDate' => date('Y-m-d h:i'));
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
            if ($ACTION == OP_EDIT) { // update the row in manufacturers table
                $res = $pdb->autoExecute('manufacturers', $vals, DB_AUTOQUERY_UPDATE, "id = $mfrid");

                $msg .= sprintf('Manufacturer "%s" was updated.', $vals['sName']);

            }
            else { // OP_ADD
                $res = $pdb->autoExecute('manufacturers', $vals, DB_AUTOQUERY_INSERT);
                $mfrid = $pdb->getOne('SELECT LAST_INSERT_ID()');

                $msg .= sprintf('Inserted new manufacturer "%s".', $vals['sName']);
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
    /** kill from all 3 tables where we find manufacturer stuffs. Ideally a ON
     * DELETE CASCADE would take care of this but this is mysql afterall */
    $sql = sprintf("DELETE FROM products_categories WHERE categoryid = %d", $mfrid);
    $res = $pdb->query($sql);

    $sql = sprintf("DELETE FROM bundle_categories WHERE categoryid = %d", $mfrid);
    $res = $pdb->query($sql);

    $sql = sprintf("DELETE FROM manufacturers WHERE id = %d", $mfrid);
    $res = $pdb->query($sql);

    $msg = "The selected manufacturer was totally removed.";
    // send back to self with messageness
    header("Location: {$_SERVER['PHP_SELF']}?info=" . base64_encode($msg));
}


if ($SHOWFORM) {
    $fex = new formex('POST');
    $fex->js_src_inline = true;
    $fex->left_td_style = '';
    $fex->add_element($colmap); // all those things in $colmap are in the form now

    $fex->add_element('op', array($ACTION, 'submit')); // the button

    if ($ACTION == OP_EDIT) {

        $sql = sprintf("SELECT sName, descrip
                               , m.sFilename AS iconid
                        FROM manufacturers mfr LEFT JOIN media m ON (m.id = mfr.iconid)
                        WHERE mfr.id = %d",
                        $mfrid);
        $fex->elem_vals = $pdb->getRow($sql);

        $fex->add_element('id', array('hid id', 'hidden', $mfrid)); // important

        $cat_name = $fex->elem_vals['sName'];


        /** if there are zero products in this manufacturer, let them delete it **/
        $sql = sprintf("SELECT COUNT(*) FROM products WHERE manufacturerid = %d", $mfrid);
        $product_count = $pdb->getOne($sql);
        if ($product_count) {
            $confirm_msg = "There are $product_count products that are associated with this manufacturer. You cannot delete the manufacturer until all these products have been removed or reassigned";
            $fex->add_element('op_kill', array(OP_KILL, 'submit', null, null, 'onclick="alert(\''. $confirm_msg . '\'); return false"'));
        }
        else {
            $confirm_msg = 'This will remove this manufacturer from the site permanently. Are you sure?';
            $fex->add_element('op_kill', array(OP_KILL, 'submit', null, null, 'onclick="return confirm(\''. $confirm_msg . '\')"'));
        }

    }
}
else {
    /** list all manufacturers in one big ass dump using HTML_Table **/
    $table = new fu_HTML_Table(array("width" => "600"));
    $table->setAutoGrow(true);
    $table->setAutoFill("n/a");
    $header_row = array('sName'=>'Name',
                  'product_count' => '#Products');

    $table->addSortRow($header_row, null, null, 'TH', null);
    $table->addCol(array('Edit/Del'), 'align="center"', 'th');

    $sql = "SELECT m.id, m.sName, COUNT(p.id) AS product_count
            FROM  manufacturers m LEFT JOIN products p ON (p.manufacturerid = m.id) 
            GROUP BY (m.id)
            ORDER BY sName";

    $res = $pdb->query($sql);
    while ($row = $res->fetchRow()) {
        $vals = array($row['sName']);
        // store.edit.php?nid=444
        $vals[] = sprintf('<a href="store.edit.list.php?mfr=%d">%d</a>', $row['id'], $row['product_count']);
        $vals[] = sprintf('<a href="store.manufacturers.php?mfr=%d" class="buttonEditItem">&raquo;</a>', $row['id']);
        $table->addRow($vals);
    }
    $numrows = $res->numRows();
}

##############################################################################
# output template
##############################################################################
include('templates/control/header.php');

include("control.header.php");
?>


<div align="center" style="margin: 10px">

<? if ($ACTION) { ?>
    <div style="text-align: left; width: 600px">
    <a href="<?= $_SERVER['PHP_SELF'] ?>">Manufacturers</a>
    <? if (isset($cat_name)) { ?>
        &raquo;&nbsp;<a href="<?= $_SERVER['PHP_SELF'] ?>?mfr=<?= $mfrid ?>"><?= $cat_name ?></a>
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
          Product Manufacturers::
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
      <a href="<?= $_SERVER['PHP_SELF'] ?>?op_add" class="buttonAddItem">Add New Manufacturer</a>
    </div>
    <br />
    <? echo $table->toHTML() ?>
  </div>
<? } ?>
	
</div>
	
<? 
include('templates/control/footer.php');
