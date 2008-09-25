<?php
/**
 * add or edit products

 * TODO this controller is a mess. It uses some of the db_container stuff but
 * not all. Really it should all be updated to just go through
 * store.dbcwrap.php
 *
 */
error_reporting(E_ALL);

require_once(CONFIG_DIR.'cshop.config.php');
require_once(CSHOP_CLASSES_PRODUCT . '.class.php');
require_once('formex.class.php');
require_once('mosh_tool.class.php');
require_once('uploadable.class.php');

require_once("store.edit.inc.php");      

$pagetitle = 'Store Products - ';

$SHOWFORM = true; // are we showing a form or not?

/* set of actions this script may perform */
$ACTION = null;
define ('OP_ADD', 'ADD PRODUCT');
define ('OP_EDIT', 'EDIT PRODUCT');
define ('OP_KILL', 'REMOVE PRODUCT');

$productid = null;

/** decide on a course of action... **/
if (isset($_POST['f_op']) and $_POST['f_op'] == OP_EDIT) {
    $ACTION = OP_EDIT;
    $productid = $_POST['f_id'];
}
elseif (isset($_POST['f_op']) and $_POST['f_op'] == OP_ADD) {
    $ACTION = OP_ADD;
}
elseif (isset($_POST['f_id']) and isset($_POST['f_op_kill'])) {
    $productid = $_POST['f_id'];
    $ACTION = OP_KILL;
}
elseif (isset($_GET['productid']) and !empty($_GET['productid'])) {
    $productid = $_GET['productid'];
    $ACTION = OP_EDIT;
}
else {
    $ACTION = OP_ADD;
}
/** **/


$pagetitle .= strtolower($ACTION); // make a nice title

$errs = array();

$c = CSHOP_CLASSES_PRODUCT;
$pc = new $c($pdb);
$colmap = $pc->colmap;

/** POST rec'd, check valid, proc. upload and save if OK */
if (isset($_POST['f_op']) and ($ACTION == OP_ADD or $ACTION == OP_EDIT)) {
    $mosh = new mosh_tool();
    $msg = '';

    $vals = array();
    $img_vals = array();


    $colmap = $pc->get_colmap();
    if ($errs = $mosh->check_form($colmap)) {
        // handled below
    }
    else {
        $vals = $mosh->get_form_vals($colmap);
        $upfiles = array('imageid' => array(), 
                         'feature_imageid' => array()); // tracks all new files we need to insert in media_prod table


        /** process each uploaded image **/
        foreach ($upfiles as $upfile => $img_vals) {
            // upfile =  name of field with uploaded file in it

            $uplo = new uploadable("f_$upfile");

            if (!$uplo->is_uploaded_file()) {
                /*
                if ($ACTION == OP_ADD and $upfile == 'imageid') {
                    $errs[] = 'You must upload image for this product!';
                }
                */
            }
            else { // we got one - use $uplo to check it and save it and create DB vals as needed
                $uplo->unique_filename = true;
                $uplo->preserve_original_name = true;

                $uplo->setErrorHandling(PEAR_ERROR_RETURN);
                // tell $uplo what kind of image we expect and where to put it:
                $uplo->params = $colmap[$upfile][3]; 

                $res = $uplo->save_upload();

                if (PEAR::isError($res)) {
                    $errs[] = $res->getMessage();
                }
                else {
                    // get the name of the new file
                    $newfilename = $uplo->get_newname();

                    // vals to be put in DB
                    $upfiles[$upfile] = array('sFilename' => $newfilename,
                                             'sType' => 'image',
                                             'dtDate' => date('Y-m-d h:i'),
                                             'img_dims' => $uplo->get_img_dims());

                    if ($upfile == 'imageid') {
                        // create a thumbnail right here:
                        $thumb1 = $uplo->save_thumbnail(THUMB_MAX_W, THUMB_MAX_H);
                        if (PEAR::isError($thumb1)) {
                            $errs[] = $thumb1->getMessage();
                        }
                        else {
                            // another upfile for the thumbnail image
                            $upfiles['image_thumbid'] = array('sFilename' => $thumb1,
                                                              'sType' => 'image',
                                                              'dtDate' => date('Y-m-d h:i'),
                                                              'img_dims'=>$uplo->get_thumb_dims());
                        }
                    }
                }
            }
        }


        $imgcount = 0;

        /** try to insert or update image info to DB table media_prod **/
        foreach ($upfiles as $upfile => $img_vals) {
            if (count($img_vals)) {
                $imgcount++;
                $oldimg = null;
                $sql = sprintf("SELECT %s FROM cm_products WHERE id = %d",
                                $upfile,
                                $productid);

                if ($oldfileid = $pdb->getOne($sql)) {
                    $sql = sprintf("SELECT id, sFilename FROM media_prod 
                                    WHERE id = %d",
                                    $oldfileid);
                    $oldimg = $pdb->getRow($sql);
                }

                if ($ACTION == OP_EDIT and $oldimg) {

                    $vals[$upfile] = $oldimg['id'];

                    // try to get rid of the old one here...
                    if ($oldimg['sFilename'] != $img_vals['sFilename']) {
                        unlink(PRODUCTS_CSHOP_MEDIA_FULLPATH . '/' . $oldimg['sFilename']);
                    }
                    $res = $pdb->autoExecute('media_prod', $img_vals, DB_AUTOQUERY_UPDATE, "id = {$oldimg['id']}");
                } 
                else {
                    $res = $pdb->autoExecute('media_prod', $img_vals, DB_AUTOQUERY_INSERT);
                    $vals[$upfile] = $pdb->getOne('SELECT LAST_INSERT_ID()');
                }
            }
        }

                        
        /** insert/update as needed **/
        if (!count($errs)) {
            if ($ACTION == OP_EDIT) {
                $pc->set_id($productid);
                $msg .= sprintf('Product "%s" was updated.', $vals['title']);
            }
            else { // OP_ADD
                $msg .= sprintf('Inserted new product for "%s".', $vals['title']);
            }
            $pc->store($vals);

            $productid = $pc->get_id();

            // append nice info to $msg
            if ($imgcount) {
                $msg .= sprintf(" %d image%s loaded.", $imgcount, ($imgcount>1)? 's were':' was');
            }

            // send back to self with messageness
            header("Location: {$_SERVER['PHP_SELF']}?productid=$productid&info=" . urlencode($msg));
        }
    }
}
/** its the end of the line for this product **/
elseif ($ACTION == OP_KILL) {

    // sing the tune of TRIGGERS and ON DELETE CASCADE
    $imgs = array('image', 'image_thumb', 'feature_thumb');

    $sql = sprintf("SELECT id, system_location, filename_large, filename_thumb
                    FROM cm_product_images 
                    WHERE cm_products_id = %d",
                    $productid);

    $res = $pdb->query($sql);
    while ($img = $res->fetchRow()) {
        foreach (array('filename_large', 'filename_thumb') as $col) {
            $file = SITE_ROOT_DIR . $img['system_location'] . '/' . $img[$col]; 
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    $sql = sprintf("DELETE FROM cm_product_images  WHERE cm_products_id = %d",
                    $productid);
    $res = $pdb->query($sql);

    $sql = sprintf("DELETE FROM cm_inventory WHERE product_id = %d",
            $productid);
    $res = $pdb->query($sql);

    $sql = sprintf("DELETE FROM cm_products_categories WHERE cm_products_id = %d",
            $productid);
    $res = $pdb->query($sql);

    $sql = sprintf("DELETE FROM cm_products WHERE id = %d",
            $productid);
    $res = $pdb->query($sql);

    if ($pdb->affectedRows() > 0) {
        $msg = "Product was successfully removed from the site";
    }
    header("Location: store.edit.list.php?info=" . base64_encode($msg));
}


if ($SHOWFORM) {
    $fex = new formex('POST');
    $fex->js_src_inline = true;
    $fex->left_td_style = '';

    if ($ACTION == OP_EDIT) {
        $pc->set_id($productid);
    }
    $fex->add_element($pc->get_colmap());

    if (isset($pc->colmap_help) && is_array($pc->colmap_help)) {
        foreach ($pc->colmap_help as $k => $text) {
            $fex->set_elem_helptext($k, $text);
        }
    }

    if (isset($colmap['cm_manufacturers_id'])) {
        $fex->set_element_opts('cm_manufacturers_id', cshopUtils::get_all_manufacturers($pdb));
    }
    if (isset($colmap['cm_ship_class_id'])) {
        $fex->set_element_opts('cm_ship_class_id', cshopUtils::get_all_ship_classes($pdb));
    }

    if ($ACTION == OP_EDIT) {
        $fex->elem_vals = $pc->fetch(null, true);

        $product_name = $fex->elem_vals['title'];

        $fex->add_element('id', array('', 'hidden', $productid, null)); // important
    }
    $fex->add_element('op', array($ACTION, 'submit')); // the button

    if ($ACTION == OP_EDIT) {
        $confirm_msg = 'This will remove this product from the site permanently. Are you sure?';
        $fex->add_element('op_kill', array(OP_KILL, 'submit', null, array('class'=>'killButton'), "onclick=\"return confirm('$confirm_msg')\""));
    }
}


/* decide which tab to show based on GET param 'win' */
$tabs = array('product','inventory','media','options');
if ($ACTION == OP_EDIT && isset($_GET['win']) && in_array($_GET['win'], $tabs)) {
    $tab = $_GET['win'];
}
else {
    $tab = $tabs[0];
}


##############################################################################
# output template
##############################################################################
$smarty->display('control/header.tpl');

?>
<div align="center" style="margin: 10px">

<? if ($ACTION) { ?>
    <div style="text-align: left; width: 600px">
    <a href="store.edit.list.php">Products</a>
    <? if (isset($product_name)) { ?>
        &raquo;&nbsp;<a href="<?= $_SERVER['PHP_SELF'] ?>?productid=<?= $productid ?>"><?= $product_name ?></a>
    <? } ?>
        &raquo;&nbsp;<?= ucwords(strtolower($ACTION)) ?>
    </div>
    <br />
    <br />
<? } ?>

<? if (isset($_GET['info'])) { ?>
    <div class="indicator">
      <?= htmlentities($_GET['info']) ?>
    </div>
    <br />
<? } ?>

<div id="tabContentContainer" style="border: 0">
  <div id="tabContainer">
    <div class="tabLabel<?= ($tab == 'product')? ' tabSelected' : '' ?>" id="tabdet" rel="detContainer">Product Details</a></div>
<? if ($ACTION == OP_EDIT) { ?>
  	<div class="tabLabel<?= ($tab == 'categories')? ' tabSelected' : '' ?>" id="tabcat" rel="catContainer">Categories</div>
  	<div class="tabLabel<?= ($tab == 'inventory')? ' tabSelected' : '' ?>" id="tabinv" rel="invContainer">Inventory</div>
  	<div class="tabLabel<?= ($tab == 'media')? ' tabSelected' : '' ?>" id="tabmed" rel="medContainer">Media</div>
    <? if (CSHOP_USE_PRODUCT_OPTION_TAB) { ?>
  	  <div class="tabLabel<?= ($tab == 'options')? ' tabSelected' : '' ?>" id="tabopt" rel="optContainer">Options</div>
    <? } ?>
    <? if (CSHOP_USE_RELATED_PRODUCTS) { ?>
  	  <div class="tabLabel<?= ($tab == 'related')? ' tabSelected' : '' ?>" id="tabrel" rel="relContainer">Related</div>
    <? } ?>
<? } ?>
  </div>
  <div id="tabActiveContent">

<? if ($SHOWFORM) { ?>
    
<div class="formContainer" id="detContainer"<? if ($tab == 'product') { ?> style="display: block"<? } ?>>
      <div class="heading">
          :: Product Information ::
      </div>
      <div id="productForm" class="formWrapper">
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
    </div>


    <? if ($ACTION == OP_EDIT) { ?>

      <div class="formContainer" id="catContainer"<? if ($tab == 'related') { ?> style="display: block"<? } ?>>
        <div class="heading">
            :: Categories ::
        </div>
        <div id="mediaWrap" class="formWrapper">
          <iframe id="categoriesFrame" src="store.product_categories.php?nid=<?=$productid?>"  frameborder="0" marginwidth="0" marginheight="0" width="590" height="400" scrolling="yes" ></iframe> 
        </div>
      </div>

      <div class="formContainer" id="invContainer"<? if ($tab == 'inventory') { ?> style="display: block"<? } ?>>
        <div class="heading">
            :: Inventory Manager ::
        </div>
        <div id="inventoryWrap" class="formWrapper">
          <iframe id="invFrame" src="store.inventory.php?nid=<?=$productid?>"  frameborder="0" marginwidth="0" marginheight="0" width="590" height="400" scrolling="yes" ></iframe> 
        </div>
      </div>


      <div class="formContainer" id="medContainer"<? if ($tab == 'media') { ?> style="display: block"<? } ?>>
        <div class="heading">
            :: Media Manager ::
        </div>
        <div id="mediaWrap" class="formWrapper">
          <iframe id="mediaFrame" src="store.media.php?nid=<?=$productid?>"  frameborder="0" marginwidth="0" marginheight="0" width="590" height="400" scrolling="yes" ></iframe> 
        </div>
      </div>


      <? if (CSHOP_USE_PRODUCT_OPTION_TAB) { ?>
          <div class="formContainer" id="optContainer"<? if ($tab == 'options') { ?> style="display: block"<? } ?>>
            <div class="heading">
                :: Product Options ::
            </div>
            <div id="mediaWrap" class="formWrapper">
              <iframe id="optionFrame" src="store.product_options.php?nid=<?=$productid?>"  frameborder="0" marginwidth="0" marginheight="0" width="590" height="400" scrolling="yes" ></iframe> 
            </div>
          </div>
      <? } ?>

      <? if (CSHOP_USE_RELATED_PRODUCTS) { ?>
          <div class="formContainer" id="relContainer"<? if ($tab == 'related') { ?> style="display: block"<? } ?>>
            <div class="heading">
                :: Related Products ::
            </div>
            <div id="mediaWrap" class="formWrapper">
              <iframe id="relatedProductsFrame" src="store.product_relations.php?nid=<?=$productid?>"  frameborder="0" marginwidth="0" marginheight="0" width="590" height="400" scrolling="yes" ></iframe> 
            </div>
          </div>
      <? } ?>

   <? } ?>
<? } ?>
	
	
    </div>
  </div>
</div>

<script type="text/javascript">

// do it on loading
if (document.getElementById('f_feature_imageid')) {
  elem = document.getElementById('f_feature_imageid').parentNode.parentNode.parentNode; 
  elem.style.visibility = (document.getElementById('f_bFeatured').checked)? 'visible' : 'hidden';
}

</script>

<? 
$smarty->display('control/footer.tpl');
