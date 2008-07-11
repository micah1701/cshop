<?php
/**
 * posting transactions against a payment gateway. 
 * DOES NOT WORK YET.
 *
 * $Id: store.order.transactions.php,v 1.1 2005/09/30 17:31:24 sbeam Exp $
 */
require_once('formex.class.php');
require_once('mosh_tool.class.php');
require_once('cshop/cmUser.class.php');
require_once('cshop/cmOrder.class.php');
require_once("fu_HTML_Table.class.php");      
require_once("filter_form.class.php");      
require_once("res_pager.class.php");      

/* set of actions this script may perform */
define ('OP_UPDATE', 'UPDATE');
define ('OP_VIEW', 'VIEW ORDER');

$ACTION = null;
$SHOWFORM = false;
$errs = array();

$parentid = null;
$itemid = null;
$reqIdKey = 'oid';
$table_title = 'Order';



if ($orders = $order->fetch_any($cols, $offset, $range, $orby, $where)) {
    
    /** list all cm_categories in one big ass dump using HTML_Table **/
    $table = new fu_HTML_Table(array("width" => "600"));
    $table->setAutoGrow(true);
    $table->setAutoFill("n/a");
    $table->addSortRow($header_row, null, null, 'TH', null);

    /* we got orders. add to $table object */
    foreach ($orders as $o) {
        $vals = array("{$o['fname']} {$o['lname']} [{$o['company']}] &lt;{$o['email']}&gt;",
                      $order->statuses[$o['orders_status']],
                      $o['order_create_date'],
                      $o['amt_quoted'],
                      $o['ship_date']);
        $link = sprintf('%s?%s=%d',
                          $_SERVER['PHP_SELF'], 
                          $reqIdKey,
                          $o['id']);
        $table->addRow_fu(array_values($vals), '', true, $link);
    }

}

