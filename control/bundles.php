<?php

/** 
 * special UI for creating product bundles and adding categories and then 
 * products within those categories
 *
 *  1) list all bundles
 *  2) edit bundle attributes, allow checkbox selection from all product
 *     categories
 *  3) click on product category name to select products from that category to
 *     be included
 *
 * also allow deleting a bundle if there are no categories assoicated with it
 *
 *
 */

require_once(CONFIG_DIR . 'cshop.config.php');

require_once('formex.class.php');
require_once('db_container.class.php');
require_once('mosh_tool.class.php');
require_once("fu_HTML_Table.class.php");      

$pagetitle = 'Product Custom Builder - ';

$SHOWFORM = false; // are we showing a form or not?

/* set of actions this script may perform */
$ACTION = null;
define ('OP_ADD', 'ADD BUNDLE');
define ('OP_EDIT', 'EDIT BUNDLE');
define ('OP_KILL', 'REMOVE BUNDLE');
define ('OP_PICK', 'ALLOW PRODUCTS IN BUNDLE');

$req_id = null;
$errs = array();
$msg = '';
$base_get_vars = '';



/* decide on a course of action... **//*{{{*/
if (isset($_POST['op']) and $_POST['op'] == OP_EDIT) {
    $ACTION = OP_EDIT;
    $bundleid = $_POST['id'];
}
elseif (isset($_POST['op']) and $_POST['op'] == OP_ADD) {
    $ACTION = OP_ADD;
}
elseif (isset($_POST['op']) and $_POST['op'] == OP_PICK) {
    $ACTION = OP_PICK;
    $bundleid = $_POST['bundleid'];
    $categoryid = $_POST['id'];
}
elseif (isset($_POST['id']) and isset($_POST['op_kill'])) {
    $bundleid = $_POST['id'];
    $ACTION = OP_KILL;
}
elseif (isset($_GET['bundleid']) and !empty($_GET['bundleid'])) {
    $bundleid = $_GET['bundleid'];
    $ACTION = OP_EDIT;
}
elseif (isset($_GET['op_product_pick'])) {
    $bundleid = $_GET['bid'];
    $categoryid = $_GET['catid'];
    $ACTION = OP_PICK;
}
elseif (isset($_GET['op_edit'])) {
    $ACTION = OP_EDIT;
    $req_id = $_GET['op_edit'];
}
elseif (isset($_GET['op_add'])) {
    $ACTION = OP_ADD;
}
/** **//*}}}*/

$pagetitle .= strtolower($ACTION); // make a nice title

// in table, how many rows per page set?
$rows_per_page = 50;

$bundle = cmClassFactory::getSingletonOf(CSHOP_CLASSES_BUNDLE, $pdb);



/*****************************************************************************
 *
 * subclass formex_field to provide these special funky ass methods for bundling */
class bundler_formex_field extends formex_field {/*{{{*/

    /**
     * choose categories from  checkbox arrays for the bundle
     * based on _field_checkarray - just makes 2 parallel arrays
     */
    function _field_bundler_cats($fval) {

        $res = '<table border="0">
                <tr>
                    <th>&nbsp;</th>
                    <th><a href="#" onclick="return false;" style="cursor: help" title="Quantity required for this Bundle.">Required?</a></th>
                </tr>';

        $opts = $this->_array_stringify($this->opts);

        foreach ($opts as $k => $txt) {

            $res .= "<tr><td align=\"right\">$txt</td>";
            $res .= sprintf("<td align=\"center\"><input type=\"text\" value=\"%s\" name=\"pcat_req_vals[%d]\" size=\"4\" maxlength=\"4\"  /></td></tr>\n",
                            (isset($fval[$k]))? $fval[$k] : '',
                            $k);
        }
        $res .= "</table>\n\n";
        return $res;
    }

    /** used herein to make a product list with checkboxes to include the
     * product in the bundle, and a text field for each one to enter the
     * "adder" for that product */
    function _field_bundler_product_picker($fval)
    {
        
        /* similar acrobatics to get the list of adders for all products in
         * this bundle-cat */
        $save_fname = $this->fname;
        $this->fname = 'adders';
        $adders = $this->fex->_find_form_value($this, 'adders');
        $this->fname = $save_fname;

        $res = '<table border="0">
                <tr>
                    <th>&nbsp;</th>
                    <th>Included?</th>
                    <th>Adder</th>
                </tr>';

        $opts = $this->_array_stringify($this->opts);

        $i = 0;
        foreach ($opts as $k => $txt) {

            $link = sprintf('<a href="../store.edit.php?productid=%d">%s</a>',
                            $k, $txt);
            $res .= sprintf("<tr bgcolor=\"%s\"><td align=\"left\">%s</td>
                             <td align=\"left\"><input type=\"checkbox\" value=\"%s\" name=\"%s[]\" %s /></td>
                             <td align=\"left\"><input type=\"text\" value=\"%s\" name=\"adders_%s\" size=\"8\" /></td></tr>\n",
                            ($i%2)? '#c9c9c9' : '#dedede',
                            $link,
                            $k,
                            $this->fname,
                            (is_array($fval) and in_array($k, $fval))? "checked" : "",
                            (isset($adders[$k]))? $adders[$k] : "0.00",
                            $k );
            $i++;
        }
        $res .= "</td></tr></table>\n\n";
        return $res;
    }
}
/****************************************************************************//*}}}*/



// set up form for validation and forming
$fex = new formex('POST', 'bundler_formex_field');
$fex->js_src_inline = true;
$fex->left_td_style = '';
$fex->field_prefix = '';
$fex->add_element($bundle->get_colmap()); 


// handle ADD and EDIT/*{{{*/
if (isset($_POST['op']) and ($ACTION == OP_ADD or $ACTION == OP_EDIT)) {
    
    $errs = $fex->validate($_POST);
    if (!$errs) {
        $vals = $fex->get_submitted_vals($_POST);
        if (!empty($_POST['pcat_req_vals']) and is_array($_POST['pcat_req_vals'])) {
            $vals['required'] = $_POST['pcat_req_vals'];
        }
        try {
            if ($ACTION == OP_EDIT) {
                $bundle->set_id($_POST['id']);
                $req_id = $_POST['id'];
                if ($bundle->store($vals)) {
                    $msg .= sprintf('Bundle "%s" was updated', $vals['title']);
                }
                $base_get_vars .= 'op_edit=' . $bundle->get_id();
            }
            else {
                if ($bundle->store($vals)) {
                    $msg .= sprintf('Created new Bundle "%s"', $vals['title']);
                }
            }
        } catch (Exception $e) {
            $errs[] = "Database error. Bundle record could not be saved.";
            throw $e;
        }
    }
}/*}}}*/
// handle DELETE/*{{{*/
elseif ($ACTION == OP_KILL) {
    $bundle->set_id($_POST['id']);
    $res = $bundle->kill();
    if (PEAR::isError($res) and $res->getCode() != DBCON_ZERO_EFFECT) {
        $errs[] = "Could not remove: " . $res->getMessage();
    }
    else {
        $msg = "Bundle was removed";
    }
}/*}}}*/


/** if there was a sucessful POST, do a redirect *//*{{{*/
if ($msg and !count($errs) and ($ACTION)) {
    // send back to self with messageness
    header("Location: {$_SERVER['PHP_SELF']}?$base_get_vars&info=" . base64_encode($msg));
    exit();
}/*}}}*/


if ($ACTION) $SHOWFORM = true;


/* either show an adding/editing form **************************************************//*{{{*/
if ($SHOWFORM) {

    /* get all categories which can be included in any bundle */
    $cat = cmClassFactory::getSingletonOf(CSHOP_CLASSES_PRODUCT_CATEGORY, $pdb);
    $cats = $cat->get_categories_for_bundles(array('name', 'id'));
    $cat_options = array();
    if (empty($cats)) {
        $errs[] = 'No Product Categories have been flagged as being available to bundle here.';
    }
    else {
        foreach ($cats as $c) {
            $cat_options[$c['id']] = $c['name'];
        }
    }
    $fex->add_element('required_cats', array('', 'bundler_cats', $cat_options, 0));

    $fex->add_element('op', array($ACTION, 'submit')); // the button

    if ($ACTION == OP_EDIT) {
        $bundle->set_id($req_id);
        $vals = $bundle->fetch();

        $fex->elem_vals = $vals;
        $method_title = $vals['title'];

        $fex->add_element('id', array('hid id', 'hidden', $req_id)); // important


        $confirm_msg = 'This will remove this item from the site permanently. Are you sure?';
        $fex->add_element('op_kill', array('REMOVE', 'submit', null, array('class'=>'ccomKillSwitch'), "onclick=\"return confirm('$confirm_msg')\""));


    }
    else {
        $method_title = 'ADD NEW ZONE';
    }
    $form = $fex->get_struct();
}                                                                                           /*}}}*/
/* or a fu_HTML_Table showing all coupons *********************************************//*{{{*/
else { 
    $table = new fu_HTML_Table(array('width'=>'100%'));
    $table->setAutoGrow(true);
    $table->setAutoFill("&mdash;");

    $header_row = array('title'=>'Bundle Name', 'base_price' => 'Base');
    $table_ordercol = (isset($_GET['by']))? $_GET['by'] : 'title';
    $order_dir =  (!isset($_GET['dir']) or $_GET['dir'] == 'A')? 'ASC' : 'DESC';

    /** decide on which result page to show **/
    $bundle->set_range($rows_per_page);
    $offset = (isset($_GET['page']))? ($rows_per_page * $_GET['page']-1) : 0;
    $bundle->set_offset($offset);
    /** **/

    $have_table = false;
    $cols = array_merge(array('id'), array_keys($header_row));

    if ($rows = $bundle->fetch_any($cols, 0, 0, $table_ordercol, null, $order_dir)) {
        $have_table = true;

        $table->addSortRow($header_row, $table_ordercol, null, 'TH', '', $order_dir);

        foreach ($rows as $row) {
            $class = 'controlListingRow';
            $link = sprintf('%s?op_edit=%d', $_SERVER['PHP_SELF'], $row['id']);
            unset($row['id']);
            $table->addRow_fu(array_values($row), $class, true, $link);
        }
    }

    $pager = new res_pager($offset, $rows_per_page, $bundle->numRows);
    $smarty->assign('pager', $pager);
}/*}}}*/



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

<? if ($ACTION) { ?>

    <div id="tabContentContainer" style="border: 0">
      <div id="tabContainer">
        <div class="tabLabel<?= ($tab == 'product')? ' tabSelected' : '' ?>" id="tabdet" rel="detContainer">Bundle Setup</a></div>
    <? if ($ACTION == OP_EDIT) { ?>
        <div class="tabLabel<?= ($tab == 'media')? ' tabSelected' : '' ?>" id="tabmed" rel="medContainer">Media</div>
    <? } ?>
      </div>

    <div id="tabActiveContent">
    <? if ($SHOWFORM) { ?>
        <div class="formContainer" id="detContainer"<? if (!isset($tab) or $tab == 'product') { ?> style="display: block"<? } ?>>
              <div class="heading">
                  :: Bundle Setup ::
              </div>
              <div id="productForm" class="formWrapper">

            <? if (isset($msg)) { ?>
               <strong><?= htmlentities($msg) ?></strong>
                  <br />
            <? } ?>

               <?= $fex->render_form(); ?>

            </div>
        </div>

        <? if ($ACTION == OP_EDIT) { ?>
          <div class="formContainer" id="medContainer"<? if ($tab == 'media') { ?> style="display: block"<? } ?>>
            <div class="heading">
                :: Media Manager ::
            </div>
            <div id="mediaWrap" class="formWrapper">
              <iframe id="mediaFrame" src="store.media.php?nid=<?=$bundle->get_id()?>&phase=bundle"  frameborder="0" marginwidth="0" marginheight="0" width="590" height="400" scrolling="yes" ></iframe> 
            </div>
          </div>
        <? } ?>
      </div>
   <? } ?>



<? } elseif (isset($table) and is_object($table)) { ?>


    <div style="width: 600px" class="container" style="padding: 4px">
        <div align="right" style="width: 600px">
          <a href="<?= $_SERVER['PHP_SELF'] ?>?op_add" class="buttonAddItem">Add New Bundle</a>
        </div>
        <br />
        <? $smarty->display('cart/control/res_pager.tpl') ?>
        <? if ($have_table) { ?>
            <? echo $table->toHTML() ?>
        <? } else { ?>
            No Bundles are set up yet.
        <? } ?>
    </div>

<? } ?>
</div>
<? 
$smarty->display('control/footer.tpl');




