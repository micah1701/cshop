<?php
/**
 * manange product categoriums
 *
 */
error_reporting(E_ALL);

require_once('formex.class.php');
require_once('mosh_tool.class.php');
require_once('uploadable.class.php');
require_once("fu_HTML_Table.class.php");      
require_once("store.edit.inc.php");      

$tablename = 'cm_categories';
$pagetitle = 'Store Product Categories - ';

$SHOWFORM = true; // are we showing a form or not?

// list of available shipping classes TODO: put in a DB table
$shipTypes = array(0=>'Normal', 2 => 'Gift Card', 3=>'Free');

/* set of actions this script may perform */
$ACTION = null;
define ('OP_ADD', 'ADD CATEGORY');
define ('OP_EDIT', 'EDIT CATEGORY');
define ('OP_KILL', 'REMOVE CATEGORY');

$catid = null;

/** decide on a course of action... **/
if (isset($_POST['f_op']) and $_POST['f_op'] == OP_EDIT) {
    $ACTION = OP_EDIT;
    $catid = $_POST['f_id'];
}
elseif (isset($_POST['f_op']) and $_POST['f_op'] == OP_ADD) {
    $ACTION = OP_ADD;
}
elseif (isset($_POST['f_id']) and isset($_POST['f_op_kill'])) {
    $catid = $_POST['f_id'];
    $ACTION = OP_KILL;
}
elseif (isset($_GET['cat']) and !empty($_GET['cat'])) {
    $catid = $_GET['cat'];
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
$colmap = array('name' =>          array('Category Name', 'text', null, 1),
                'level' =>         array('Level', 'select', array('0'=>'top', '1'=>'sub'), 1),
                'parent_cat_id' =>  array('Parent Category', 'select', array(), array('top_value' => 'No Parent Category')),
                'descrip' =>        array('Description', 'textarea', null),
                'ship_class_id' =>       array('Shipping Class', 'select', array(), 1),
                'is_taxable' =>        array('Taxable?', 'toggle'),
                // 'icon_media_id' =>         array('Category Icon', 'image_upload', null, array('allowed'=>'web_images',
                //                                                             'ws_path'=>CSHOP_MEDIA_URLPATH)),
                'feature_rank' =>   array('Feature Rank', 'select', array(0,1,2,3,4,5)),
                'order_weight' =>   array('Order Weight', 'text', null, array('size'=>4,'maxlength'=>4),null),
                'is_active' =>      array('Is Active?', 'toggle'),
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
            if ($ACTION == OP_EDIT) { // update the row in cm_categories table
                $res = $pdb->autoExecute('cm_categories', $vals, DB_AUTOQUERY_UPDATE, "id = $catid");

                $msg .= sprintf('Category "%s" was updated.', $vals['name']);

            }
            else { // OP_ADD

                $catid = $pdb->nextId('cm_categories');
                $vals['id'] = $catid;
                $res = $pdb->autoExecute('cm_categories', $vals, DB_AUTOQUERY_INSERT);

                $msg .= sprintf('Inserted new category "%s".', $vals['name']);
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
    /** kill from all 3 tables where we find category stuffs. Ideally a ON
     * DELETE CASCADE would take care of this but this is mysql afterall */
    $sql = sprintf("DELETE FROM $tablename WHERE id = %d", $catid);
    $res = $pdb->query($sql);
    /*
    * $sql = sprintf("DELETE FROM bundle_categories WHERE categoryid = %d", $catid);
    * $res = $pdb->query($sql);

    * $sql = sprintf("DELETE FROM categories WHERE id = %d", $catid);
    * $res = $pdb->query($sql);
    */

    $msg = "The selected category was totally removed.";
    // send back to self with messageness
    header("Location: {$_SERVER['PHP_SELF']}?info=" . base64_encode($msg));
}


if ($SHOWFORM) {
    $fex = new formex('POST');
    $fex->js_src_inline = true;
    $fex->add_element($colmap); // all those things in $colmap are in the form now
    $fex->set_element_opts('ship_class_id', $shipTypes);

    $fex->add_element('op', array($ACTION, 'submit')); // the button
    $fex->set_element_opts('parent_cat_id', cshopUtils::get_all_parent_categories($pdb));

    if ($ACTION == OP_EDIT) {

        $sql = sprintf("SELECT name, descrip, ship_class_id, is_taxable, level, feature_rank, is_active, parent_cat_id , order_weight
                               , m.filename AS icon_media_id
                        FROM cm_categories c LEFT JOIN cm_media_files m ON (m.id = c.icon_media_id)
                        WHERE c.id = %d",
                        $catid);
        $fex->elem_vals = $pdb->getRow($sql);

        $fex->add_element('id', array('hid id', 'hidden', $catid, null)); // important

        $cat_name = $fex->elem_vals['name'];


        /** if there are zero products in this category, let them delete it **/
        $sql = sprintf("SELECT COUNT(*) FROM cm_products_categories WHERE cm_categories_id = %d", $catid);
        $product_count = $pdb->getOne($sql);
        if ($product_count) {
            $confirm_msg = "There are $product_count products that are in this category. If you delete the category, they may be hidden from customers. Are you sure?";
        }
        else {
            $confirm_msg = 'This will remove this category from the site permanently. Are you sure?';
        }
        $fex->add_element('op_kill', array(OP_KILL, 'submit', null, null, 'onclick="return confirm(\''. $confirm_msg . '\')"'));

    }
}
else {
    /** list all cm_categories in one big ass dump using HTML_Table **/
    $table = new fu_HTML_Table(array("width" => "600"));
    $table->setAutoGrow(true);
    $table->setAutoFill("n/a");
    $header_row = array('name'=>'Name',
                  'shipType'=> 'Ship Class',
                  'taxable'=> 'Tax',
                  'iLevel' => 'Level',
                  'is_active' => 'live?',
                  'feature_rank' => 'Feature Rank',
                  'order_weight' => 'Order Weight',
                  'product_count' => '#Products');

    $table->addSortRow($header_row, null, null, 'TH', null);

    $sql = "SELECT c.id, c.name, c.ship_class_id, c.is_taxable, c.level, c.is_active, c.parent_cat_id, c.feature_rank, c.order_weight
                   , COUNT(pc.cm_products_id) AS product_count
                   , IFNULL(CONCAT(cat.name, ':', c.name), c.name) AS concat_name
            FROM $tablename c 
            LEFT JOIN cm_products_categories pc ON (pc.cm_categories_id = c.id)
            LEFT JOIN cm_categories cat ON (cat.id = c.parent_cat_id)
            GROUP BY (c.id)
            ORDER BY c.order_weight, concat_name";

    $res = $pdb->query($sql);
    while ($row = $res->fetchRow()) {
        $vals = array($row['concat_name'],
                       $shipTypes[$row['ship_class_id']],
                       ($row['is_taxable'])? 'Y' : 'N',
                       ($row['level'] == 0)? 'top' : 'sub',
                       ($row['is_active'])? 'Y' : 'N',
                       ($row['feature_rank'])? $row['feature_rank'] : '-',
                       $row['order_weight'],
                       $row['product_count']);
        $link = sprintf('%s?cat=%d',
                          $_SERVER['PHP_SELF'], 
                          $row['id']);
        $table->addRow_fu($vals, '', null, $link);
    }
    $numrows = $res->numRows();
}

##############################################################################
# output template
##############################################################################
$smarty->display('control/header.tpl');

?>


<div align="center" style="margin: 10px">

<? if ($ACTION) { ?>
    <div style="text-align: left; width: 600px">
    <a href="<?= $_SERVER['PHP_SELF'] ?>">Categories</a>
    <? if (isset($cat_name)) { ?>
        &raquo;&nbsp;<a href="<?= $_SERVER['PHP_SELF'] ?>?cat=<?= $catid ?>"><?= $cat_name ?></a>
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
          Product Categories::
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
      <a href="<?= $_SERVER['PHP_SELF'] ?>?op_add" class="buttonAddItem">Add New Category</a>
    </div>
    <br />
    <? echo $table->toHTML() ?>
  </div>
<? } ?>
	
</div>
	
<? 
$smarty->display('control/footer.tpl');
