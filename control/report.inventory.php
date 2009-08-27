<?php
/**
 * creating basic reports on sales and orders
 *
 * $Id: report.inventory.php,v 1.5 2008/06/23 02:54:50 sbeam Exp $
 */
require_once(CONFIG_DIR . 'cshop.config.php');
require_once("mosh_tool.class.php");      
require_once("fu_HTML_Table.class.php");      
require_once("res_pager.class.php");      
require_once("filter_form.class.php");      
require_once(CSHOP_CLASSES_ORDER.'.class.php');
require_once(CSHOP_CLASSES_PRODUCT.'.class.php');
require_once("store.edit.inc.php");      
require_once("csv_table.class.php");      


$c = CSHOP_CLASSES_PRODUCT;
$prod = new $c($pdb);

$mosh = new mosh_tool();

/** possibly limit by product */
$pid = (!empty($_GET['pid']))? $_GET['pid'] : null;

/** or possibly, by category */
$filter_cat_id = (!empty($_GET['cat']))? $_GET['cat'] : null;


$header_row = array('qty'=>'Qty','title'=>'Product','color'=>'Color','size_code' => 'Size','sku'=>'SKU');

$link_fmt = 'store.edit.php?win=inventory&productid=%d';
$link_vals = array('product_id');
$def_orby = 'title';


// determine order column and direction
$orby = $def_orby;
if (isset($_GET['by']) and in_array($_GET['by'], array_keys($header_row))) {
    $orby = $_GET['by'];
}
$order_dir = (empty($_GET['dir']) or $_GET['dir'] == 'A')? 'ASC' : 'DESC';


if (isset($_GET['op_csv'])) { // this will generate a CSV file to downlaod all of the current inventory items in the system

    // get all of the products joined with the inventory levels, colors, sizes, etc
    // organized: title, sku, size, color way, qty in stock
    $inv = $prod->fetch_inventory_report(null, 'title', 'asc', 0, 999000);
    $cols = array('title','sku','size_code','color_name','qty');
    $csv = new CSV_Table_Fu();
    $csv->show_cols = $cols;
    $csv->print_csv_headers(SITE_DOMAIN_NAME.'-inventory_dump.csv');
    $csv->dumpall($inv);
    exit();
}

$range = 50;
$offset = (isset($_GET['page']))? (($_GET['page']-1) * $range) : 0;

/** go get im **/
$report = $prod->fetch_inventory_report($pid, $orby, $order_dir, $offset, $range, $filter_cat_id);


/** list all cm_categories in one big ass dump using HTML_Table **/
$table = new fu_HTML_Table(array('width'=>'90%', 'align'=>'center', 'style'=>'padding-top: 25px'));
$table->setAutoGrow(true);
$table->setAutoFill("-");

$xgets = $mosh->make_get_params($_GET, array('by','dir'));

$table->addSortRow($header_row, $orby, null, 'TH', $xgets, $order_dir);
$sep = (strpos($_SERVER['REQUEST_URI'], '?') === false)? '?' : '&';
$csv_link = $_SERVER['REQUEST_URI'] . $sep . 'op_csv';

foreach ($report as $row) {
    $vals = array();
    $link = null;

    if (isset($link_fmt)) {
        $args = array();
        foreach ($link_vals as $v) {
            $args[] = $row[$v];
        }
        $link = vsprintf($link_fmt, $args);
    }

    foreach (array_keys($header_row) as $k) {
        $vals[] = $row[$k];
    }

    $table->addRow_fu($vals, null, (!empty($link)), $link);
}
$numrows = $prod->numRows;

$pager = new res_pager($offset, $range, $numrows);
$smarty->assign('pager', $pager);


$produx = array('' => '[ANY]') + $prod->get_product_list('title');

$filt = new filter_form('GET');
$filt->left_td_style = '';
$filt->field_prefix = '';
$filt->add_element('hdr1', array('<b>Filter by::</b> Product:', 'heading'));
$filt->add_element('pid', array('', 'select', $produx));
$filt->add_element('hdr2', array('Category:', 'heading'));
$filt->add_element('cat', array('', 'select', $prod->get_product_category_options(true)));
$filt->add_element('op_filter', array('GO', 'submit'));






##############################################################################
# output template
##############################################################################
$smarty->display('control/header.tpl');
?>
<div style="width: 90%; margin: 2em auto;">
<h2 class="headline">Inventory Report</h2>

<p>
Click on a row to edit inventory values for the product.
</p>

<? $filt->display(); ?>

<? $smarty->display('cart/control/res_pager.tpl') ?>

<? if (!$numrows) { ?>
    <strong class="userError">No matching items are found.</strong>
<? } else { ?>
    <div class="csvVersion"><a href="<?= $csv_link ?>">Download</a></div>

    <? echo $table->toHTML() ?>
<? } ?>

</div>
<? 
$smarty->display('control/footer.tpl');
