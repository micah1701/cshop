<?php
/**
 * load inventory sku->quantity map via a CSV file, with check/confirmation step.
 *
 * $Id: load.inventory.php,v 1.6 2008/06/24 19:29:50 sbeam Exp $
 */
require_once(CONFIG_DIR . 'cshop.config.php');
require_once("mosh_tool.class.php");      
require_once("fu_HTML_Table.class.php");      
require_once(CSHOP_CLASSES_PRODUCT.'.class.php');
require_once("uploadable.class.php");      


// column indexes where sku and qty will be found
$datafile_pos = array('sku' => 0,
                      'name' => null,
                      'price'=>null,
                      'weight'=>null,
                      'description'=>null,
                      'cat' => null,
                      'size' => null,
                      'color' => null,
                      'qty' => 1,
                    );

// how many columns should be in the datafile?
$datafile_expected_cols = 0;
foreach (array_values($datafile_pos) as $p) {
    if ($p !== null) $datafile_expected_cols++;
}
    


$SHOWFORM = true;


$mdb = MDB2::connect(PEAR_DSN, array('seqcol_name'=>'id'));

$mosh = new mosh_tool();

$errs = array();

/* data loading has been confirmed; proceed with loading the stored CSV data */
if (isset($_POST['op_confirm'])) {


    /* holds counts of the various things we did */
    $counts = array('updated_inv'=>0, 'new_inv'=>0, 'new_cat' => 0, 'new_color'=>0, 'new_size'=>0, 'new_product'=>0, 'updated_product'=>0);

    if (!$mdb->exec("SHOW FULL TABLES LIKE 'tmp_inv'")) {
        $errs[] = "The temporary data is gone. Please begin the process again.";
    }
    else {

        $mdb->beginTransaction();


        // colors
        $sth_ins = $mdb->prepare("INSERT INTO cm_colorways (id, name, code) VALUES (?, ?, ?)", null, MDB2_PREPARE_MANIP);
        //
        $sql = "SELECT DISTINCT UPPER(TRIM(color)) AS color FROM tmp_inv 
                    WHERE color != 'N/A' AND TRIM(color) != '-' AND TRIM(color) != '' AND color IS NOT NULL 
                    AND TRIM(color) NOT IN (SELECT DISTINCT(UPPER(name)) FROM cm_colorways)";
        $res = $mdb->query($sql);
        while ($row = $res->fetchRow()) {
            $counts['new_color'] += $sth_ins->execute(array($mdb->nextID('cm_colorways'), $row[0], sizecode($row[0])));
        }
        $res->free();



        // sizes
        $sth_ins = $mdb->prepare("INSERT INTO cm_sizes (id, fullname, code) VALUES (?, ?, ?)");
        //
        $sql = "SELECT DISTINCT UPPER(TRIM(size)) AS size FROM tmp_inv 
                    WHERE size != 'N/A' AND TRIM(size) != '-' AND TRIM(size) != '' AND size IS NOT NULL 
                    AND TRIM(size) NOT IN (SELECT DISTINCT(UPPER(fullname)) FROM cm_sizes)";
        $res = $mdb->query($sql);
        while ($row = $res->fetchRow()) {
            $counts['new_size'] += $sth_ins->execute(array($mdb->nextID('cm_sizes'), $row[0], sizecode($row[0])));
        }
        $res->free();



        // cats
        $sth_ins = $mdb->prepare("INSERT INTO cm_categories (id, name, is_taxable, is_active, parent_cat_id, urlkey) 
                                                     VALUES (?, ?, 1, 1, 0, ?)", null, MDB2_PREPARE_MANIP);
        //
        $sql = "SELECT DISTINCT UPPER(TRIM(cat)) AS cat FROM tmp_inv 
                    WHERE TRIM(cat) != '' AND cat IS NOT NULL 
                    AND UPPER(TRIM(cat)) NOT IN (SELECT DISTINCT(UPPER(name)) FROM cm_categories)";
        $res = $mdb->query($sql);
        while ($row = $res->fetchRow()) {
            $counts['new_cat'] += $sth_ins->execute(array($mdb->nextID('cm_categories'), $row[0], sizecode($row[0])));
        }
        $res->free();


        // products
        $sth_sel = $mdb->prepare("SELECT id FROM cm_products WHERE UPPER(sku) = ?");
        $sth_ins = $mdb->prepare("INSERT INTO cm_products (id, sku, title, price, weight, description) VALUES (?, ?, ?, ?, ?, ?)", null, MDB2_PREPARE_MANIP);
        $sth_up = $mdb->prepare("UPDATE cm_products SET price = ?, weight = ?, description = ? WHERE id = ?", null, MDB2_PREPARE_MANIP);
        //
        $sth_catmap = $mdb->prepare("REPLACE INTO cm_products_categories (cm_products_id, cm_categories_id) 
                                     VALUES(?, (SELECT id FROM cm_categories WHERE name = ?))", null, MDB2_PREPARE_MANIP);

        $sql = "SELECT sku, name, cat, price, weight, description FROM tmp_inv GROUP BY name";
        $res = $mdb->query($sql);

        while ($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            if ($row['name'] or $row['price']) { // if there is nothing good in the data, skip it.
                $res_sel = $sth_sel->execute(strtoupper($row['sku']));
                if ($row_p = $res_sel->fetchRow()) {
                    $pid = $row_p[0];
                    $counts['updated_product'] += $sth_up->execute(array($row['price'], $row['weight'], $row['description'], $pid));
                }
                else {
                    $pid = $mdb->nextId('cm_products');
                    $counts['new_product'] += $sth_ins->execute(array($pid, $row['sku'], $row['name'], $row['price'], $row['weight'], $row['description']));
                }

                // cats
                $res_cat = $sth_catmap->execute(array($pid, $row['cat']));
            }
        }


        // inventory
        $sth_inv = $mdb->prepare("INSERT INTO cm_inventory (id, qty, sku, product_id, sizes_id, colorways_id)
                                    VALUES (?, ?, ?,
                                    (SELECT id FROM cm_products WHERE title = ?),
                                    (SELECT id FROM cm_sizes WHERE UPPER(code) = ?),
                                    (SELECT id FROM cm_colorways WHERE LOWER(name) = ?))", null, MDB2_PREPARE_MANIP);

        $sth_up = $mdb->prepare("UPDATE cm_inventory SET qty = ? WHERE sku = ?", null, MDB2_PREPARE_MANIP);

        $sql = "SELECT tmp.sku, tmp.name, tmp.size, tmp.color, tmp.qty, inv.id AS invid 
                FROM tmp_inv tmp LEFT JOIN cm_inventory inv ON (inv.sku = tmp.sku)";
        $res = $mdb->query($sql);

        while ($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            if (empty($row['invid'])) {
                $counts['new_inv'] += $sth_inv->execute(array($mdb->nextID('cm_inventory'), 
                                                   $row['qty'], 
                                                   $row['sku'], 
                                                   $row['name'], 
                                                   sizecode($row['size']),
                                                   sizecode(strtolower($row['color'])))
                                               );
            }
            else {
                $counts['updated_inv'] += $sth_up->execute(array($row['qty'], $row['sku']));
            }
        }

        /* cleanup! */
        $mdb->query("DROP TABLE tmp_inv");





        $mdb->commit();


        $SHOWFORM = false;
        $DID_PROCESS = true;
    }

}
/* get the file from the form and test it for valid data and that all SKUs therein match what's in the DB now */
elseif (isset($_POST['op_up'])) {
    $uplo = new uploadable('datafile');
    if (!$uplo->is_uploaded_file()) {
        $errs[] = "You must upload a data file to get started.";
    }
    else {
        $uplo->setErrorHandling(PEAR_ERROR_RETURN);
        $uplo->preserve_original_name = false;
        $uplo->params = array( #'allowed'=>array('text/csv'),
                               'path'=> CSHOP_MEDIA_FULLPATH,
                               'ws_path' => CSHOP_MEDIA_URLPATH,
                                'fnamebase' => uniqid('inventory-data.000'));

        $res = $uplo->save_upload();

        if (PEAR::isError($res)) {
            $errs[] = $res->getMessage();
        }
        else {

            $newfilename = $uplo->get_newname(); // get the name of the new file

            $fullpathfile = $uplo->fullPathtoFile;

            if (!($fh = fopen($fullpathfile, "r"))) {
                $errs[] = "Unable to open uploaded file data. Can not continue.";
            }
            else {
                $skus_found = 0;
                $notfound = array();
                $skip_first_row = isset($_POST['skip_first_row']);


                $res = create_tmp_table($mdb);

                $cols = join(',', array_keys($datafile_pos));
                $sql = "INSERT INTO tmp_inv ($cols)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"; 

                if (! ($sth_insert_tmp_row = $mdb->prepare($sql, null, MDB2_PREPARE_MANIP))) {
                    $errs[] = "Could not prepare the database insert.";
                }

                $sth_get_sku = $mdb->prepare("SELECT product_id FROM cm_inventory WHERE sku = ?");

                while (($data = fgetcsv($fh, 64000, ",")) !== FALSE) {
                    if ($skip_first_row) {
                        $skip_first_row = false;
                        continue;
                    }

                    if (count($data) != $datafile_expected_cols) {
                        $errs[] = "Datafile does not seem to be correctly formatted.";
                        break;
                    }
                    $res = $sth_get_sku->execute(array($data[$datafile_pos['sku']]));
                    if (!$res->numRows()) {
                        array_push($notfound, $data);
                    }
                    else {
                        $skus_found++;
                    }

                    try {
                        $vals = array();
                        foreach ($datafile_pos as $col => $pos) {
                            if ($pos !== null)
                                $vals[] = $data[$pos];
                            else
                                $vals[] = null;
                        }

                        $res = $sth_insert_tmp_row->execute($vals);

                    } catch (PDOException $e) {
                        $errs[] = "An error occurred while testing the data: " . $e->getMessage();
                        break;
                    }
                    
                }
                $SHOWFORM = false;

                unlink($fullpathfile);
            }
        }
    }
    $DO_VERIFY = true;
}

if (isset($_GET['op_cancel']) && !empty($_GET['op_cancel'])) {
    $fullpathfile = CSHOP_MEDIA_FULLPATH . '/' . stripslashes($_GET['op_cancel']);
    if (is_file($fullpathfile)) {
        unlink($fullpathfile);
    } 
}

if ($SHOWFORM) {
    $uploform = new formex();
    $uploform->left_td_style = '';
    $uploform->field_prefix = '';
    #$uploform->add_element('hdr1', array('<b>Options:</b>', 'heading'));
    #$uploform->add_element('do_create_new', array('Create new products/inventory items?', 'toggle'));
    $uploform->add_element('skip_first_row', array('Skip first row?', 'toggle'));
    //$uploform->add_element('do_reset_zero', array('zero quantities for all existing SKUs not found in the uploaded data?', 'toggle'));
    $uploform->add_element('datafile', array('Data File', 'file', true));
    $uploform->add_element('op_up', array('NEXT', 'submit'));
}

##############################################################################
# output template
##############################################################################
$smarty->display('control/header.tpl');
?>
<div id="loadInventoryWrap">
<h2 class="headline">Inventory Data Loader</h2>

<? if (!empty($errs)) { ?>
    <div class="userError">
        Errors occurred while processing your request.
        <ul class="userError">
        <? foreach ($errs as $e) { ?>
            <li><?= $e ?></li>
        <? } ?>
        </ul>
    </div>
<? } ?>

<? if (isset($_GET['op_cancel'])) { ?>
    <div class="userWarning">
        Your process has been cancelled.
    </div>
<? } ?>


<? if (!empty($DID_PROCESS)) { ?>
    <div class="steps">1 | 2 | <strong>3</strong></div>

    <p>Your inventory data has been processed. The database was modified as follows:</p>
    <table>
        <tr><td>New products</td><td><?= $counts['new_product'] ?></td></tr>
        <tr><td>Updated products</td><td><?= $counts['updated_product'] ?></td></tr>
        <tr><td>New SKUs</td><td><?= $counts['new_inv'] ?></td></tr>
        <tr><td>Updated quantities</td><td><?= $counts['updated_inv'] ?></td></tr>
        <tr><td>New Categories</td><td><?= $counts['new_cat'] ?></td></tr>
        <tr><td>New Colors</td><td><?= $counts['new_color'] ?></td></tr>
        <tr><td>New Sizes</td><td><?= $counts['new_size'] ?></td></tr>
        <tr><td>Errors</td><td><?= count($errs) ?></td></tr>
    </table>

    <a href="load.inventory.php">&lt;&lt;back</a>

<? } elseif (!empty($DO_VERIFY)) { ?>

    <div class="steps">1 | <strong>2</strong> | 3</div>

    <p>Review changes and confirm.</p>

    <? if (empty($skus_found)) { ?>

        <div class="userError">None of the SKUs in the datafile matched any existing inventory record. Please check your datafile format and SKU numbers against the Products/Inventory control.</div>

        <a href="load.inventory.php?op_cancel=<?= urlencode($newfilename) ?>">&lt;&lt;start over</a>

    <? } else { ?>
        <? if (!empty($notfound)) { ?>
            <div class="userError">The following SKUs in the data file did not match any existing inventory record. These inventory items will be created and added to the system.</div>
            <div class="reportBox">
                <table>
                <? foreach ($notfound as $line) {  ?>
                   <tr>
                    <? foreach ($line as $c) { ?>
                        <td><?= htmlspecialchars($c) ?></td>
                    <? } ?>
                    </tr>
                <? } ?>
                </table>
            </div>
        <? } ?>

        <p><strong><?= $skus_found ?> inventory item<? if ($skus_found > 1) { ?>s<? } ?> will be updated based on SKUs matching existing items.</strong></p>

        <form method="post">
            <p><strong>Press the button below to confirm these changes:</strong></p>
            <input type="hidden" name="tmpdatafile" value="<?= $newfilename ?>">
            <input type="submit" name="op_confirm" value="CONFIRM CHANGES">
        </form>
        <a href="load.inventory.php?op_cancel=<?= urlencode($newfilename) ?>">&lt;&lt;start over</a>
     <? } ?>
<? } else { ?>

    <div class="steps"><strong>1</strong> | 2 | 3</div>

    <p>
        Inventory quantities for this web store can be bulk-loaded from a Text/Comma-separated-values (CSV) file.
    </p>

    <p>
        The data file should be a standard comma-separated values, quoted with &quot;, with fields in the following order:
        <? 
        asort($datafile_pos);
        $cols = array();
        foreach ($datafile_pos as $col => $pos) { 
            if ($pos !== null) { 
                $cols[] = strtoupper($col);
            }
        }
        ?>
    </p>
        <pre><?= join('&nbsp;|&nbsp;', $cols) ?><pre>

    <? $uploform->display(); ?>
<? } ?>


</div>
<? 
$smarty->display('control/footer.tpl');







function create_tmp_table($mdb) {


    try {
        $res = $mdb->query("CREATE TABLE IF NOT EXISTS `tmp_inv` (
                                `id` int(10) unsigned NOT NULL auto_increment,
                                `name` varchar(255) default NULL,
                                `sku` varchar(30) default NULL,
                                `size` varchar(32) default NULL,
                                `color` char(16) default NULL,
                                `qty` int(6) NOT NULL,
                                `cat` varchar(64) default NULL,
                                `price` double(9,2) default NULL,
                                `weight` double(9,2) default NULL,
                                description text,
                                PRIMARY KEY  (`id`),
                                UNIQUE (sku))");
        $res = $mdb->query("DELETE FROM `tmp_inv`");

    } catch (PDOException $e) {
        trigger_error( 'Could not create temp table: ' . $e->getMessage(), E_USER_ERROR);
    }
    return $res;

}







function sizecode($s) {
    return preg_replace('/[^\w\d"]+/', '-', strtoupper($s));
}
