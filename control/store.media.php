<?php
/**
 * add or edit products
 *
 */
error_reporting(E_ALL);

require_once(CONFIG_DIR.'cshop.config.php');
require_once('formex.class.php');
require_once('mosh_tool.class.php');
require_once('uploadable.class.php');
require_once('imagestretcher.class.php');
require_once("fu_HTML_Table.class.php");      

require_once("store.edit.inc.php");      

/* set of actions this script may perform */
$ACTION = null;
define ('OP_ADD', 'ADD IMAGE');
define ('OP_EDIT', 'EDIT IMAGE');
define ('OP_KILL', 'REMOVE IMAGE');



$tablename = 'cm_product_images';

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






$allowed_image_types = (function_exists('imagecreatefromgif'))? 'web_images' : 'web_images_nogif';

$colmap = array('upfile' => array('Image', 'image_upload', null, array('allowed'=>$allowed_image_types,
                                                        'maxdims' => IMG_MAX_DIMS,
                                                        'path'=> CSHOP_MEDIA_FULLPATH,
                                                        'ws_path' => CSHOP_MEDIA_URLPATH), true),
                'class' => array('Class', 'select', $CSHOP_MEDIA_CLASSES, null, false),
                'colorways_id' => array('Colorway', 'select', array(), null, false),
                'order_weight' => array('Order Weight', 'numeric', null, array('size'=>3), false));



/** POST rec'd, check valid, proc. upload and save if OK */
if (isset($_POST['f_op']) and ($ACTION == OP_ADD or $ACTION == OP_EDIT)) {
    $mosh = new mosh_tool();

    if ($errs = $mosh->check_form($colmap)) {
        // handled below
    }
    else {
        $vals = $mosh->get_form_vals($colmap);
        $vals['cm_products_id'] = $productid;

        $uplo = new uploadable("f_upfile");

        if (!$uplo->is_uploaded_file()) {
            if ($ACTION == OP_ADD) {
                $errs[] = 'You must upload image!';
            }
        }
        else { // we got one - use $uplo to check it and save it and create DB vals as needed
            $uplo->preserve_original_name = true;

            $uplo->setErrorHandling(PEAR_ERROR_RETURN);
            // tell $uplo what kind of image we expect and where to put it:
            $uplo->params = $colmap['upfile'][3];

            $res = $uplo->save_upload();

            if (PEAR::isError($res)) {
                $errs[] = $res->getMessage();
            }
            else {
                // get the name of the new file
                $newfilename = $uplo->get_newname();

                $fullpath = $uplo->fullPathtoFile;

                // save a "zoomed in" or xtra-large version, not bigger than ZOOM_MAX
                if (defined('ZOOM_MAX_W') and defined('ZOOM_MAX_H')) {
                    $zoom_file = $uplo->params["path"] . '/' . 'z_' . $newfilename;
                    $stretch = new imagestretcher($fullpath);
                    if ($stretch->shrink_to_fit(ZOOM_MAX_W, ZOOM_MAX_H)) {
                        $res = $stretch->save_to_file($zoom_file);
                    }
                    else { // it was already smaller than zoom_max - so ignore it!
                        copy($fullpath, $zoom_file);
                    }
                    $stretch->free();
                }


                $img_dims = $uplo->get_img_dims();

                // save "medium" or default image but not bigger than MAX_
                if (defined('MAX_W') and defined('MAX_H')) {
                    if ($uplo->imgwidth > MAX_W or $uplo->imgheight > MAX_H) {
                        $stretch = new imagestretcher($fullpath);
                        $stretch->shrink_to_fit(MAX_W, MAX_H);
                        $stretch->save_to_file($fullpath);
                        $img_dims = $stretch->get_img_dims();
                        $stretch->free();
                    }
                }




                // create a thumbnail right here:
                $needs_thumb = false;
                if (defined('THUMB_MAX_W') and defined('THUMB_MAX_H')) {
                    $thumb1 = $uplo->save_thumbnail(THUMB_MAX_W, THUMB_MAX_H, 'shrink_to_fit');
                    $needs_thumb = true;
                }
                elseif (defined('THUMB_EXACT_W') and defined('THUMB_EXACT_H')) {
                    $thumb1 = $uplo->save_thumbnail(THUMB_EXACT_W, THUMB_EXACT_H, 'shrink_to_size');
                    $needs_thumb = true;
                }
                if ($needs_thumb && empty($thumb1)) {
                    $thumb1 = $newfilename;
                }

                if (PEAR::isError($thumb1)) {
                    $errs[] = $thumb1->getMessage();
                }
                else {
                    $vals['system_location'] = CSHOP_MEDIA_URLPATH;
                    $vals['filename_large'] = $newfilename;
                    $vals['dims_large'] = $img_dims;
                    $vals['filename_thumb'] = $thumb1;
                    $vals['dims_thumb'] = $uplo->get_thumb_dims();
                    $vals['mime_type'] = $uplo->get_filetype();
                    if (isset($zoom_file)) $vals['filename_zoom'] = basename($zoom_file);
                }
            }
        }

        if (!count($errs)) {
            if ($ACTION == OP_ADD) {
                $vals['id'] = $pdb->nextId($tablename);
                $res = $pdb->autoExecute($tablename, $vals, DB_AUTOQUERY_INSERT);
            }
            elseif ($ACTION == OP_EDIT) {
                $where = sprintf("id = %d", $reqid);
                $res = $pdb->autoExecute($tablename, $vals, DB_AUTOQUERY_UPDATE, $where);
            }
            if (PEAR::isError($res)) {
                $errs[] = "A database error ocurred: " . $res->getMessage();
            }
            else {
                if ($ACTION == OP_ADD) {
                    $msg = "New product images has been added";
                }
                elseif ($ACTION == OP_EDIT) {
                    $msg = "Changes have been made to product images";
                }
            }
        }
    }
}
elseif ($ACTION == OP_KILL) {
    $sql = sprintf("SELECT pi.id, system_location, filename_large, filename_thumb, filename_zoom
                    FROM cm_product_images pi 
                    WHERE id = %d",
                    $reqid);
    if (! $row = $pdb->getRow($sql)) {
        $errs[] = "Cant find any such image";
    }
    else {
        foreach (array('filename_large', 'filename_thumb', 'filename_zoom') as $k) {
            $file = CSHOP_MEDIA_FULLPATH . '/' . $row[$k];
            if (is_file($file)) {
                unlink($file);
            }
        }
        $sql = sprintf("DELETE FROM cm_product_images WHERE id = %d", $reqid);
        $res = $pdb->query($sql);
        if (PEAR::isError($res)) {
            $errs[] = $res->getMessage();
        }
        else {
            $msg = "Product media file was successfully removed";
        }
    }
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
    $fex->add_element('nid', array('id', 'hidden', $productid, null));

    $fex->set_element_opts('colorways_id', cshopUtils::get_all_colors($pdb, true));
    if ($ACTION == OP_EDIT) {
        $sql = sprintf("SELECT colorways_id, order_weight, class, filename_large AS upfile
                        FROM $tablename WHERE id = %d",
                        $reqid);
        $row = $pdb->getRow($sql);
        $fex->elem_vals = $row;

        $fex->add_element('reqid',array('reqid', 'hidden', $reqid, null));
        $fex->add_element('op_kill',array(OP_KILL, 'submit', null, null, 'onclick="return confirm(\'Are you sure?\')"'));
    }
}
else {
    /** list all cm_categories in one big ass dump using HTML_Table **/
    $table = new fu_HTML_Table(array('width'=>'90%', 'align'=>'center'));
    $table->setAutoGrow(true);
    $table->setAutoFill("-");
    $header_row = array('class' => 'Class', 'filename_fmt'=>'File Name', 'colorname'=>'Colorway', 'order_weight'=>'weight');

    $table->addRow(array_values($header_row), null, 'TH');
    $table->addCol(array('&nbsp;'), 'align="center"', 'th');

    $sql = sprintf("SELECT pi.id, pi.class, pi.order_weight, system_location, filename_large, filename_thumb, dims_thumb, cw.name AS colorname
                    FROM cm_product_images pi 
                        LEFT JOIN cm_colorways cw ON (cw.id = colorways_id)
                    WHERE cm_products_id = %d
                    ORDER BY pi.order_weight, cw.name",
                    $productid);

    $res = $pdb->query($sql);
    while ($row = $res->fetchRow()) {
        $vals = array();
        $row['filename_fmt'] = (strlen($row['filename_large']) > 30)? substr($row['filename_large'], 0,27) . '...' : $row['filename_large'];
        foreach (array_keys($header_row) as $k) {
            $vals[] = $row[$k];
        }

        // store.edit.php?nid=444
        $vals[] = sprintf('<img src="%s/%s" %s alt="" />', 
                          $row['system_location'],
                          $row['filename_thumb'],
                          $row['dims_thumb']);
        $link = sprintf('%s?op_edit=%d&nid=%d',
                          $_SERVER['PHP_SELF'],
                          $row['id'],
                          $productid);
                          
        $table->addRow($vals, null, true, $link);
    }
    $numrows = $res->numRows();
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
        <? if ($ACTION == OP_ADD) { ?>Adding new product media file<? } ?>
        <? if ($ACTION == OP_EDIT) { ?>Updating product media file<? } ?>
        <br />
        <? $fex->display() ?>
    <? } elseif (isset($table)) { ?>
        <div style="text-align: right">
          <a class="buttonAddItem" href="<?= $_SERVER['PHP_SELF'] ?>?op_add&amp;nid=<?= $productid ?>">Add new image</a>
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
