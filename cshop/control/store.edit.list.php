<?php
/**
 * list all products in a big table with links to editform - with sorting!
 *
 *  @author sbeam
 *  @copyright Circus Media LLC
 *  @see HTML_Table
 *  @todo page result sets
 *
 * $Id: store.edit.list.php,v 1.10 2008/06/24 19:34:47 sbeam Exp $
 */
require_once(CONFIG_DIR.'cshop.config.php');
require_once("fu_HTML_Table.class.php");      
require_once("filter_form.class.php");      
require_once("res_pager.class.php");      

require_once("store.edit.inc.php");      

/** **/
$range = 50;
$offset = (isset($_GET['page']))? (($_GET['page']-1) * $range) : 0;

$filt = new filter_form('GET');
$filt->left_td_style = '';
$filt->field_prefix = '';
$filt->add_element('hdr1', array('<b>Filter by::</b> Category:', 'heading'));
$filt->add_element('cat', array('', 'select', cshopUtils::get_all_categories($pdb, true)));
$filt->add_element('hdr2', array('Manufacturer', 'heading'));
$filt->add_element('mfr', array('', 'select', cshopUtils::get_all_manufacturers($pdb, true)));
$filt->add_element('op_filter', array('GO', 'submit'));


/** if there were things selected in the filter forms then make up some WHERE
 * clauses to narrow the results */
$where = '';
$getvars = '';
if (isset($_GET['mfr']) or isset($_GET['cat'])) {
    $where = 'WHERE 1=1 ';
    if (!empty($_GET['mfr'])) {
        $where .= ' AND cm_manufacturers_id = ' . addslashes($_GET['mfr']);
        $getvars .= '&mfr=' . urlencode($_GET['mfr']);
    }
    if (!empty($_GET['cat'])) {
        $where .= ' AND pc.cm_categories_id = ' . addslashes($_GET['cat']);
        $getvars .= '&cat=' . urlencode($_GET['cat']);
    }
}


/** list all products in one big ass dump using HTML_Table **/
$table = new fu_HTML_Table(array("width" => "600"));
$table->setAutoGrow(true);
$table->setAutoFill("n/a");
$header_row = array('title'=>'Product',
              '' => 'Categories',
              'mfr'=> 'Mfr',
              'sku' => 'SKU',
              'is_active' => 'live?',
              'order_weight' => 'sort',
              'feature_rank' => 'feature');


/** decide how to order the results */
$orderable = array_keys($header_row);
if (!empty($_GET['by']) and in_array($_GET['by'], $orderable)) {
    $orderby = $_GET['by'];
}
else {
    $orderby = 'sku';
}
$orderdir = (isset($_GET['dir']) and $_GET['dir'] == 'D')? 'DESC' : 'ASC';



$table->addSortRow($header_row, $orderby, null, 'TH', $getvars, $orderdir);

$sql = "SELECT p.id, title, m.name AS mfr, sku, p.is_active, is_featured, feature_rank, p.order_weight
        FROM cm_products p LEFT JOIN cm_manufacturers m ON (m.id = p.cm_manufacturers_id) 
                        LEFT JOIN cm_products_categories pc ON (pc.cm_products_id = p.id)
        $where
        GROUP BY p.id
        ORDER BY $orderby $orderdir, title $orderdir";

/* prep query to get the list of categories belonging to a given productid */
$res = $pdb->query($sql);
$sth_cats = $pdb->prepare("SELECT name FROM cm_products_categories pc, cm_categories c
                           WHERE pc.cm_products_id = ? AND c.id = pc.cm_categories_id
                           ORDER BY c.order_weight");

$numrows = $res->numRows();
$ptrlimit = (($range + $offset) < $numrows)? ($range + $offset) : $numrows;
for ($ptr=$offset; $ptr<$ptrlimit; $ptr++) { 
    if (! $row = $res->fetchRow(DB_FETCHMODE_ASSOC, $ptr)) break;

    /** fetch all the categories this product is in and put the names in array $cats */
    $cats = array();
    $res_cats =& $pdb->execute($sth_cats, array($row['id']));
    while ($cat_row = $res_cats->fetchRow(DB_FETCHMODE_ORDERED)) {
        $cats[] = $cat_row[0];
    }
    $res_cats->free();

    $vals = array($row['title'],
                   join(',', $cats),
                   $row['mfr'],
                   $row['sku'],
                   ($row['is_active'])? 'Y' : 'N',
                   $row['order_weight'],
                   ($row['feature_rank'] == 0)? '-' : $row['feature_rank'],
                   ); 
    // store.edit.php?nid=444
    $link = sprintf('store.edit.php?productid=%d', $row['id']);
    $table->addRow_fu($vals, '', true, $link);
}

$pager = new res_pager($offset, $range, $numrows);
$smarty->assign('pager', $pager);

##############################################################################

$smarty->display('control/header.tpl');
?>

<div align="center" style="margin: 10px">
<div style="width: 600px; border: 1px solid black; padding: 4px">


<? if (isset($_GET['info'])) { ?>
    <div class="indicator">
      <?= htmlentities(base64_decode($_GET['info'])) ?>
    </div>
    <br />
<? } ?>


<? $filt->display(); ?>

    <br />
    <div align="right" style="width: 600px; padding: 4px">
      <a href="store.edit.php" class="buttonAddItem">Add New Product</a>
    </div>
    <br />

<? if (!$numrows) { ?>
    <div align="center" class="userError">
    <strong>No matching records were found</strong>
    </div>
<? } else { ?>
    <? $smarty->display('cart/control/res_pager.tpl') ?>
    <? echo $table->toHTML() ?>
<? } ?>

</div>
</div>
<?
$smarty->display('control/footer.tpl');
