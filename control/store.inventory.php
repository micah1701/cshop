<?php
/**
 * add or edit products
 *
 */

require_once(CONFIG_DIR.'cshop.config.php');
require_once('formex.class.php');
require_once('mosh_tool.class.php');
require_once('uploadable.class.php');
require_once("fu_HTML_Table.class.php");      
require_once(CSHOP_CLASSES_PRODUCT . '.class.php');

require_once("store.edit.inc.php");      

/* set of actions this script may perform */
$ACTION = null;
define ('OP_ADD', 'ADD INVENTORY');
define ('OP_EDIT', 'EDIT QUANTITY');
define ('OP_KILL', 'REMOVE INVENTORY');

$inventory_table = 'cm_inventory';

$c = CSHOP_CLASSES_PRODUCT;
$pc = new $c($pdb);

$productid = null;
$SUCCESS = null;
$ERROR = null;

/* do we allow changing prices for each inventory item, calc from product base price? */
$USE_ADDERS = defined('CSHOP_INVENTORY_ALLOW_ADDER') && (CSHOP_INVENTORY_ALLOW_ADDER == true);


/** decide on a course of action... **/
if (isset($_GET['op_edit'])) {
    $invid = $_GET['op_edit'];
    $productid = $_GET['nid'];
    $ACTION = OP_EDIT;
}
elseif (isset($_POST['f_op'])) {
    $productid = $_POST['f_nid'];
    $ACTION = OP_ADD;
}
elseif (isset($_GET['nid']) and isset($_GET['op_kill'])) {
    $productid = $_GET['nid'];
    $invid = $_GET['op_kill'];
    $ACTION = OP_KILL;
}
elseif (isset($_GET['nid']) and !empty($_GET['nid'])) {
    $productid = $_GET['nid'];
}

/** **/
if (!$productid) {
    trigger_error("productid was not passed", E_USER_ERROR);
}


/* handle adding a new inventory item */
if (isset($_POST['f_op']) and ($ACTION == OP_ADD or $ACTION == OP_EDIT)) {
    PEAR::pushErrorHandling(PEAR_ERROR_RETURN); 

    $vals = array('sizes_id' => $_POST['f_sizes'],
                  'colorways_id' => $_POST['f_colors'],
                  'qty' => $_POST['f_qty'],
                  'product_id' => $productid,
                  'sku' => $_POST['f_sku']);
    if ($USE_ADDERS) {
        $vals['adder'] = ($_POST['f_adder'] == 0)? null : $_POST['f_adder'];
    }

    $where = sprintf("product_id = %d AND sizes_id = %d AND colorways_id = %d AND sku = '%s'",
                    $productid,
                    $vals['sizes_id'],
                    $vals['colorways_id'],
                    addslashes($vals['sku']));

    $sql = "SELECT id FROM $inventory_table WHERE $where";
    if ($pdb->getOne($sql)) {
        $ACTION = OP_EDIT;
    }

    if ($ACTION == OP_ADD) {
        $vals['id'] = $pdb->nextId($inventory_table);
        $res = $pdb->autoExecute($inventory_table, $vals, DB_AUTOQUERY_INSERT);
        if (!PEAR::isError($res)) {
            $SUCCESS = "inventory record added";
        }
    }
    else {
        $res = $pdb->autoExecute($inventory_table, $vals, DB_AUTOQUERY_UPDATE, $where);
        if (!PEAR::isError($res)) {
            $SUCCESS = "inventory record updated";
        }
    }
    if (PEAR::isError($res)) {
        if ($res->getCode() == DB_ERROR_ALREADY_EXISTS) {
            $ERROR = "The SKU number '".htmlentities($vals['sku'])."' already 
                      exists. Please enter a unique SKU for each inventory record";
        }
        else {
            $ERROR = $res->getMessage();
        }
    }
    PEAR::popErrorHandling();
}
elseif ($ACTION == OP_KILL) {
    $sql = sprintf("DELETE FROM %s WHERE id = %d",
                   $inventory_table,
                   $invid);
    $res = $pdb->query($sql);
    if (!PEAR::isError($res)) {
        $SUCCESS = "inventory record removed from the system";
    }
}


/** setup the form that goes at the top */
$pc->set_id($productid);

$onchange = 'onchange="cmSetSkuField()"';

$fex = new formex('POST');
$fex->add_element('sizes',  array('Size', 'select', array(), null, $onchange, 1));
$fex->add_element('colors', array('Colors', 'select', array(), null, $onchange, 1));
$fex->add_element('qty',    array('Qty', 'text', 1, array('size'=>3,'maxlength'=>6), 1));
$fex->add_element('sku',    array('SKU', 'text', '', array('size'=>16,'maxlength'=>64), 1));
if ($USE_ADDERS) {
    $fex->add_element('adder',  array('Adder', 'text', '0.00', array('size'=>7,'maxlength'=>10), 'onchange="cmAddPrice(this.value)"', 1));
}
$fex->add_element('nid',    array('id', 'hidden', $productid, null));

$fex->set_element_opts('sizes', (array(''=>'-----') + cshopUtils::get_all_sizes($pdb)));
$fex->set_element_opts('colors', (array(''=>'-----') + cshopUtils::get_all_colors($pdb)));
if (isset($invid) and $ACTION == OP_EDIT) {
    $sql = sprintf("SELECT i.sizes_id AS sizes, i.colorways_id AS colors, i.qty, i.sku, adder
                            , IFNULL((p.price + adder), p.price) AS total_price
                    FROM $inventory_table i, cm_products p WHERE i.id = %d AND p.id = i.product_id",
                    $invid);
    $inv_record = $pdb->getRow($sql);
    $fex->elem_vals = $inv_record;
    $killlink = sprintf('%s?op_kill=%d&nid=%d', $_SERVER['PHP_SELF'], $invid, $productid);
}

/* get the product SKU as a base, for the JS magic in the Size/Color selects */
$sku_base = $pc->get_header('sku');
$base_price = $pc->get_header('price');
$total_price = (isset($inv_record))? $inv_record['total_price'] : $base_price;






/** list all inventory records in one big ass dump using HTML_Table **/
$table = new fu_HTML_Table(array('width'=>'90%', 'align'=>'center'));
$table->setAutoGrow(true);
$table->setAutoFill("-");
$header_row = array('sku'=>'SKU', 'size'=>'size', 'color' => 'Color', 'qty' => 'Qty.');
if ($USE_ADDERS) { 
    $header_row['adder'] = 'Adder';
    $header_row['total_price'] = 'Total';
}

$table->addRow(array_values($header_row), null, 'TH');

$inv = $pc->fetch_all_inventory();
foreach ($inv as $row) {
    $vals = array();
    foreach (array_keys($header_row) as $k) {
        $vals[] = $row[$k];
    }
    // store.edit.php?nid=444
    $link = sprintf('%s?op_edit=%d&nid=%d',
                      $_SERVER['PHP_SELF'],
                      $row['id'],
                      $productid);
    $table->addRow($vals, null, true, $link);
}
$numrows = count($inv);

if (!$numrows) {
    $fex->set_elem_default_vals('sku', $sku_base);
    $fex->add_element('op', array('CREATE', 'submit', null, null, 1));
}
else {
    $fex->add_element('op', array('UPDATE', 'submit', null, null, 1));
}
$invform = $fex->get_struct();





##############################################################################
# output template
##############################################################################
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>inventory control framelet</title>
<link rel="stylesheet" href="/control/cshop/store.css" type="text/css">
<link rel="stylesheet" href="/control/control.css" type="text/css">
<script type="text/javascript">
<!--
    var attribFields = new Array('f_sizes', 'f_colors');
    var skuBase = '<?= addslashes($sku_base) ?>';

    function cmBuildSku() {
        var sku = new Array(skuBase);
        for (i=0; i<attribFields.length; i++) {
            elem = document.getElementById(attribFields[i]);
            if (elem && elem.selectedIndex > 0) {
                // get whatever is inside the <option> tags
                val = elem.options[elem.selectedIndex].firstChild.nodeValue; 
                m = val.match(/\(([^\)]+)\)/); // match anything inside (parens)
                if (m) {
                    sku.push(m[1]);
                }
                else {
                    sku.push(val);
                }
            }
        }
        return sku.join('-');
    }

    function cmSetSkuField() {
        newSku = cmBuildSku();
        if (document.getElementById('f_sku')) {
            document.getElementById('f_sku').value = newSku;
        }
    }

    function cmAddPrice(val) {
        val = cmBasePrice + parseFloat(val);
        val = val.toFixed(2);
        document.getElementById('fTotalPrice').firstChild.nodeValue = val.toString();
    }

    var cmBasePrice = <?= $base_price ?>;

// -->
</script>
</head>
<body>
<div style="margin: 0 10% 0 10%; text-align: center">

<? if ($SUCCESS) { ?>
    <div class="indicator">
      <?= $SUCCESS ?>
    </div>
<? } ?>
<? if ($ERROR) { ?>
    <div class="userError">
      <?= $ERROR ?>
    </div>
<? } ?>


        <?= $invform['FORM'] ?>
          <? if (isset($killlink)) { ?>
            <div class="controlKillLink">
              <a href="<?= $killlink ?>" class="buttonEditItem">[delete]</a>
            </div>
          <? } ?>

        <?= $invform['HIDDENS'] ?>
          <table style="border: 1px solid #222; width: 100%">
           <tr>
             <td colspan="2">
               Size: <?= $invform['sizes']['TAG'] ?>&nbsp;Color: <?= $invform['colors']['TAG'] ?>
             </td>
             <td>&nbsp;</td>
           </tr>
           <tr>
             <td colspan="2">
               SKU: <?= $invform['sku']['TAG'] ?>
             </td>
             <td>&nbsp;</td>
           </tr>
<? if ($USE_ADDERS) { ?>
           <tr>
             <td colspan="2">
               <table cellpadding="0" cellspacing="0">
                 <tr><td>Pricing:&nbsp;&nbsp;&nbsp;</td> <td> <span style="color: #666">base:</td><td>$<?= $base_price ?> </span></td></tr>
                    <tr><td>&nbsp;</td><td>Adder:</td><td> <?= $invform['adder']['TAG'] ?></td></tr>
                    <tr><td>&nbsp;</td><td><span style="color: #666">total:</td><td> $<span id="fTotalPrice"><?= $total_price ?></span></span></td></tr>
               </table>
                 
             </td>
             <td>&nbsp;</td>
           </tr>
<? } ?>
           <tr>
             <td colspan="2" align="right">
               Qty on hand: <?= $invform['qty']['TAG'] ?>
             </td>
             <td>
               <?= $invform['op']['TAG'] ?>
             </td>
           </tr>
         </table>
        </form>
<? if ($numrows) { ?>
  <br />
  <? echo $table->toHTML() ?>
<? } ?>
</div>

<? 
$smarty->assign('suppress_footer', 1);
$smarty->display('control/footer.tpl');
