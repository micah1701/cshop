<?php
/**
 * creating basic reports on sales and orders
 *
 * $Id: orders.reports.php,v 1.11 2008/07/08 16:21:24 sbeam Exp $
 */
require_once(CONFIG_DIR . 'cshop.config.php');
require_once("fu_HTML_Table.class.php");      
require_once("res_pager.class.php");      
require_once("csv_table.class.php");      
require_once(CSHOP_CLASSES_ORDER.'.class.php');
require_once(CSHOP_CLASSES_PRODUCT.'.class.php');

/* set of actions this script may perform */
define ('OP_UPDATE', 'UPDATE');
define ('OP_VIEW', 'VIEW ORDER');

$ACTION = null;
$SHOWFORM = false;
$errs = array();
$xgets = array();

$parentid = null;
$itemid = null;
$reqIdKey = 'tok';
$table_title = 'Order';

define('OP_BY_USER', 1);
define('OP_BY_DATE', 2);
define('OP_BY_STATUS', 3);
define('OP_BY_PRODUCT', 4);
define('OP_VIEWS', 5);
define('OP_ORDERS_PRODUCTS', 6);

$ACTION_NAMES = array(OP_ORDERS_PRODUCTS => 'Order History',
                      OP_BY_USER => 'By Customer',
                      OP_BY_DATE => 'By Month',
                      OP_BY_STATUS => 'By Status',
                      OP_BY_PRODUCT => 'By Product',
                      OP_VIEWS => 'Product Views');


$orderclass = CSHOP_CLASSES_ORDER;
$cmOrder = new $orderclass($pdb);

$c = CSHOP_CLASSES_PRODUCT;
$cmProduct = new $c($pdb);

$ACTION = OP_ORDERS_PRODUCTS;
if (isset($_GET['report'])) {
    $ACTION = $_GET['report'];
}



/* decide what month we are on */
if (isset($_GET['m']) && preg_match('/^\d{4}-\d{2}$/', $_GET['m'])) {
    $req_month = $_GET['m'];
}
else {
    $req_month = date('Y-m');
}

/* get next and prev months links */
$heading = date('M Y', strtotime($req_month . '-01'));
list($y, $m) = split('-', $req_month, 2);
$heading_nextlink = "?report=$ACTION&m=" . date('Y-m', mktime(0, 0, 0, intval($m)+1,   1,   intval($y)));
$heading_prevlink = "?report=$ACTION&m=" . date('Y-m', mktime(0, 0, 0, intval($m)-1,   1,   intval($y)));
$xgets[] = "m=$req_month";





if ($ACTION == OP_VIEWS) {
    $sql = "SELECT view_count, id, title
            FROM cm_products ORDER BY %s %s";
    $header_row = array('title'=>'Product','view_count'=>'Views');
    $link_fmt = 'store.edit.php?productid=%d';
    $link_vals = array('id');
    $def_orby = 'view_count';
}
elseif ($ACTION == OP_BY_DATE) {
    $sql = "SELECT DATE_FORMAT(last_modified, '%%b %%Y') AS month, DATE_FORMAT(last_modified, '%%Y%%m') AS month_o 
                , SUM(amt_billed_to_date) AS sum 
            FROM cm_orders WHERE orders_status != 5 GROUP BY month ORDER BY %s %s";
    $header_row = array('sum'=>'Total', 'month' => 'Month');
    $link_fmt = 'store.orders.php?f_month=%s&op_filter=GO';
    $link_vals = array('month');
    if (isset($_GET['by']) && $_GET['by'] == 'month') $orby = 'month_o';
    $def_orby = 'month_o';
}
elseif ($ACTION == OP_BY_STATUS) {
    $sql = "SELECT SUM(amt_billed_to_date) AS sum, COUNT(orders_status) AS num_orders
                , orders_status 
            FROM cm_orders GROUP BY orders_status
            ORDER BY %s %s";
    $header_row = array('orders_status'=>'Status', 'num_orders'=>'# Orders', 'sum'=>'Total value');
    $link_fmt = 'store.orders.php?status=%d&f_oid=&op_filter=GO';
    $link_vals = array('orders_status');
    $def_orby = 'orders_status';

    $sta = $cmOrder->get_statuses();
}
elseif ($ACTION == OP_BY_PRODUCT) {
    $sql = "SELECT product_id, COUNT(product_id) AS orcnt, SUM(qty) AS prcnt, p.title 
            FROM cm_order_items LEFT JOIN cm_products p ON (p.id = product_id)  
            GROUP BY product_id
            ORDER BY %s %s";
    $header_row = array('title'=>'Product', 'orcnt'=>'Order Count', 'prcnt'=>'Total Sold');
    $link_fmt = 'store.edit.php?productid=%d';
    $link_vals = array('product_id');
    $def_orby = 'prcnt';
}
elseif ($ACTION == OP_BY_USER) {

    /* create a condition clause based on month and order status */
    $where = "orders_status != ".CM_ORDER_STATUS_CANCELLED; 
    $where .= ' AND DATE_FORMAT(order_create_date, "%%Y-%%m") = "' . $req_month . '"';

    $sql = "SELECT user_id 
                , u.cust_name
                , IFNULL(u.email, u.anon_email) AS email
                , (CASE WHEN u.anon_email IS NULL THEN '' ELSE 'Guest' END) AS is_guest
                , SUM(amt_billed_to_date) as sum
                , COUNT(o.id) as num
            FROM cm_orders o left join cm_auth_user u ON (u.id = o.user_id) 
            WHERE $where
            GROUP BY user_id 
            ORDER BY %s %s";
    $header_row = array('cust_name'=> 'Customer', 'email' => 'email', 'is_guest' => 'Guest', 'num'=>'#Orders', 'sum'=>'Total');
    $link_fmt = "store.orders.php?uid=%d&month=$req_month&f_oid=&op_filter=GO";
    $link_vals = array('user_id');
    $def_orby = 'sum';
}
else { // if ($ACTION == OP_ORDERS_PRODUCTS) {

    /* set up format for header sort links */
    $link_fmt = 'store.orders.php?tok=%s&op_filter=GO';
    $link_vals = array('order_token');
    $def_orby = 'id';

    /* things that go after SELECT in the $sql */
    $fields = array('id', 'order_token', 'order_create_date', 'DATE_FORMAT(order_create_date, \'%%d %%b %%Y\') AS datef'
                  , 'ship_total', 'tax_total', 'tax_method', 'amt_quoted');


    /* override sort by date, or redirect on click of 'fake' column names */
    if (isset($_GET['by']) && $_GET['by'] == 'datef') $orby = 'order_create_date';
    elseif (isset($_GET['by']) && preg_match('/^product_(\d+)$/', $_GET['by'], $m)) {
        header(sprintf('Location: store.edit.php?productid=%d', $m[1]));
        exit();
    }
    /* for sort by tax amount, we do a crazy hack to add a boolean field to the SQL and sort on that. 
     * This is useless but it prevents a SQL erros and is consistent UI at least. */
    elseif (isset($_GET['by']) && preg_match('/^tax_(\w+)$/', $_GET['by'], $m)) {
        $column_alias = $m[1];
        $raw_method = str_replace('_', ' ', $column_alias);
        $fields[] = "(tax_method = '". addslashes($raw_method)."') AS tax_$column_alias";
        $orby = "tax_$column_alias DESC, tax_total";
    }


    /* create a condition clause based on month and order status */
    $where = "orders_status != ".CM_ORDER_STATUS_CANCELLED; 
    $where .= ' AND DATE_FORMAT(order_create_date, "%%Y-%%m") = "' . $req_month . '"';

    /* base select for order data */
    $fields = join(',', $fields);
    $sql = "SELECT $fields FROM cm_orders WHERE $where ORDER BY %s %s";

    /* the base columns for the report, shortly to be added to. */
    $header_row = array('order_token'=>'Order Number', 'datef' => 'Date', 'ship_total'=>'Shipping', 'tax_total'=>'Tax', 'amt_quoted' => 'Total');

    $where = preg_replace('/%%/', '%', $where); // dupe %'s in sprintf format cause problems.

    // get sums of costs and product sales total for summary footer
    $sql_sums = "SELECT SUM(ship_total) AS ship_total, SUM(tax_total) AS tax_total, SUM(amt_quoted) AS amt_quoted
                 FROM cm_orders WHERE $where";
    $table_sums = $pdb->getRow($sql_sums);
    $product_counts = array(); 
    $num_orders = array(); 

    // for each product sold in the time period, get the stats on total amounts sold and #of orders 
    $sql_sums_products = "SELECT p.id, p.title, p.sku, SUM(qty*oi.price) AS tot, SUM(oi.qty) AS cnt, COUNT(o.id) AS num_orders 
                          FROM cm_orders o JOIN cm_order_items oi ON o.id = oi.order_id 
                                           JOIN cm_products p ON oi.product_id = p.id 
                          WHERE $where AND o.id=oi.order_id 
                          GROUP BY product_id
                          ORDER BY num_orders DESC";
    $res = $pdb->query($sql_sums_products);

    while ($gr_product = $res->fetchRow()) {
        $txt = sprintf('%s%s<br>[%s]', substr($gr_product['title'], 0, 30), (strlen($gr_product['title']) > 30)? '...' : '', $gr_product['sku']); 
        $header_row['product_'.$gr_product['id']] = $txt;

        $table_sums['product_'.$gr_product['id']] = $gr_product['tot'];
        $num_orders['product_'.$gr_product['id']] = $gr_product['num_orders'];
        $product_counts['product_'.$gr_product['id']] = $gr_product['cnt'];
    }

    // need to add a column for each type of tax collected, with sums
    $sqltax = "SELECT SUM(tax_total) AS tot, tax_method AS method FROM cm_orders 
                WHERE tax_method != '' AND tax_method IS NOT NULL AND $where
                GROUP BY tax_method
                ORDER BY method";
    $res = $pdb->query($sqltax);
    while ($row = $res->fetchRow()) {
        $key = preg_replace('/\W+/', '_', $row['method']);
        $header_row['tax_'.$key] = $row['method'];
        $table_sums['tax_'.$key] = $row['tot'];
    }
}


// determine order column and direction
if (isset($_GET['by']) and in_array($_GET['by'], array_keys($header_row))) {
    $fake_orby = $_GET['by']; // used for template only
}
if (empty($orby)) {
    $orby = (isset($fake_orby))? $fake_orby : $def_orby;
}

if (!isset($fake_orby)) $fake_orby = $def_orby;

$order_dir = (empty($_GET['dir']) or $_GET['dir'] == 'D')? 'DESC' : 'ASC';

$sql = sprintf($sql, $orby, $order_dir);

/** go get im **/
$res = $pdb->query($sql);


/** list all cm_categories in one big ass dump using HTML_Table **/
/** list all cm_categories in one big ass dump using HTML_Table **/
if (isset($_GET['op_csv'])) {
    $table = new CSV_Table_Fu();

    $csv_headers = array();
    foreach ($header_row as $k => $v) {
        $csv_headers[] = preg_replace('/<[^>]+>/', " ", $v);
    }
    $table->addSortRow(array_values($csv_headers));
}
else {
    $table = new fu_HTML_Table();
    $table->setAutoGrow(true);
    $table->setAutoFill("-");

    $xgets[] = "report=$ACTION";
    $table->addSortRow($header_row, $fake_orby, null, 'TH', join('&', $xgets), $order_dir);
    $sep = (strpos($_SERVER['REQUEST_URI'], '?') === false)? '?' : '&';
    $csv_link = $_SERVER['REQUEST_URI'] . $sep . 'op_csv';
}

while ($row = $res->fetchRow()) {
    $vals = array();
    $link = null;

    if (isset($link_fmt)) {
        $args = array();
        foreach ($link_vals as $v) {
            $args[] = $row[$v];
        }
        $link = vsprintf($link_fmt, $args);
    }

    if ($ACTION == OP_BY_STATUS) {
        $row['orders_status'] = $sta[$row['orders_status']];
    }
    elseif ($ACTION == OP_ORDERS_PRODUCTS) {
        /* add a "column" for each product in the order pointing to the total 
         * for that product (merging all inventory items within the product) */
        foreach ($cmOrder->fetch_lineitem_report($row['id']) as $id => $tot) {
            $row['product_'.$id] = number_format($tot, 2);
        }

        /* add a new "column" pointing to the tax collected, if any  */
        if ($row['tax_total']) {
            $taxkey = preg_replace('/\W+/', '_', $row['tax_method']);
            $row['tax_'.$taxkey] = $row['tax_total'];
        }
    }

    foreach (array_keys($header_row) as $k) {
        $vals[] = (isset($row[$k]))? $row[$k] : null;
    }


    $table->addRow($vals, null, (!empty($link)), $link);
}
$numrows = $res->numRows();



if ($ACTION == OP_ORDERS_PRODUCTS) {
    /* add two rows for the sums of order totals and product counts */
    foreach (array('table_sums' => 'SALE TOTAL:', 'product_counts' => '#sold', 'num_orders'=>'#orders') as $var => $label) {
        $vals = array();
        foreach (array_keys($header_row) as $k) {
            $vals[] = (isset(${$var}[$k]))? ${$var}[$k] : null;
        }
        $vals[0] = $label;
        $table->addRow($vals, 'tfoot');
    }
}



if (isset($_GET['op_csv'])) {
    if (!empty($heading) && $ACTION == OP_ORDERS_PRODUCTS) {
        $filename = $heading;
    }
    else {
        $filename = strtolower($ACTION_NAMES[$ACTION]); 
    }
    $filename = preg_replace('/[^\w\d]+/', '-', $filename);
    $table->print_csv_headers(SITE_DOMAIN_NAME . ".$filename.csv");
    print $table->displayAll($res);
    exit();
}




##############################################################################
# output template
##############################################################################
$smarty->display('control/header.tpl');
?>
<div id="reportW">
    <h4>Sales Reports</h4>
    <ul class="reportmenu">
        <? foreach ($ACTION_NAMES as $i => $act) { ?>
            <li class="<? if ($ACTION == $i) echo 'sel' ?>"><a href="<?= $_SERVER['PHP_SELF'] ?>?report=<?= $i ?>"><?= $act ?></a></li>
        <? } ?>
    </ul>

    <? if ($numrows) { ?>
        <div class="csvVersion"><a href="<?= $csv_link ?>">Download</a></div>
    <? } ?>

    <div style="clear: left">&nbsp;</div>
    <? if (!empty($heading)) { ?>
        <h2 class="report">
            <? if (!empty($heading_prevlink)) { ?><a href="<?= $heading_prevlink ?>">&laquo;</a><? } ?>&nbsp;&nbsp;&nbsp;
            <?= $heading ?>
            &nbsp;&nbsp;&nbsp;<? if (!empty($heading_nextlink)) { ?><a href="<?= $heading_nextlink ?>">&raquo;</a><? } ?>
        </h2>
    <? } ?>

    <? if (!$numrows) { ?>
        <strong class="indicator">No results found.</strong>
    <? } else { ?>
        <? echo $table->toHTML() ?>
    <? } ?>
</div>

<? 
$smarty->display('control/footer.tpl');







function add_month($ym, $adder=1) {
}

