<?php
/**
 * managing all about the orders
 *
 * $Id: store.orders.php,v 1.29 2008/07/08 16:21:24 sbeam Exp $
 */
require_once(CONFIG_DIR . 'cshop.config.php');
require_once(CSHOP_CLASSES_USER . '.class.php');
require_once(CSHOP_CLASSES_ORDER . '.class.php');
require_once(CSHOP_CLASSES_CART . '.class.php');
require_once(CSHOP_CLASSES_PAYMENT . '.class.php');

require_once('formex.class.php');
require_once('mosh_tool.class.php');
require_once("fu_HTML_Table.class.php");      
require_once("filter_form.class.php");      
require_once("res_pager.class.php");      

/* set of actions this script may perform */
define ('OP_UPDATE', 'UPDATE');
define ('OP_VIEW', 'VIEW ORDER');
define ('OP_DUMP', 'CSVDUMP');    
define ('OP_TRANSACT', 'TRANSACT');    
define ('OP_EDIT_LINEITEM', 'EDIT ORDER ITEMS');    

$ACTION = null;
$SHOWFORM = false;
$errs = array();
$PRINT_TEMPLATE = false;

$parentid = null;
$itemid = null;
$reqIdKey = 'tok';
$table_title = 'Order';


$orderclass = CSHOP_CLASSES_ORDER;
$order = new $orderclass($pdb);

/** decide on a course of action... **/
if (isset($_POST['op_update']) and $_POST['op_update'] == OP_UPDATE) {
    $ACTION = OP_UPDATE;
    $itemid = $_POST[$reqIdKey];
}
elseif (isset($_GET[$reqIdKey]) and !empty($_GET[$reqIdKey])) {
    $itemid = $_GET[$reqIdKey];
    if (isset($_GET['do_dump'])) {
        $ACTION = OP_DUMP;
    }
    else {           
        $ACTION = OP_VIEW;
    }
}
elseif (isset($_GET['op_print']) and !empty($_GET['op_print'])) {
    $itemid = $_GET['op_print'];
    $ACTION = OP_VIEW;
    $PRINT_TEMPLATE = true;
}
elseif (isset($_POST['op_oiform'])) {
    $ACTION = OP_EDIT_LINEITEM;
    $itemid = $_POST[$reqIdKey];
}
elseif (isset($_POST['op_xaction'])) {
    $ACTION = OP_TRANSACT;
    $itemid = $_POST[$reqIdKey];
}
else {
    $SHOWFORM = false;
}
/** **/

/** hook up smarty with the currency_format function from cmCart */
$c = CSHOP_CLASSES_CART;
$cart = new $c($pdb);
$smarty->register_modifier('currency_format', array(&$cart, 'currency_format'));

/* update the backordered amount for a certain line item */
if ($ACTION == OP_EDIT_LINEITEM) {
    $order->set_id_by_token($itemid);
    $count = 0;
    foreach ($_POST as $k => $v) {
        if (preg_match('/^dBackOrder_(\d+)/', $k, $m)) {
            $li = $m[1];
            $res = $order->update_backorder($li, $v);
            if ($res && !PEAR::isError($res)) {
                $count++;
            }
        }
    }
    if ($count > 0) {
        $msg = sprintf('Backordered Qty was updated for %d line item%s', $count, ($count>1)?'s':'');
    }
    else {
        $msg = "No changes were made";
    }

    // send back to self with messageness
    header("Location: {$_SERVER['PHP_SELF']}?$reqIdKey=$itemid&info=" . base64_encode($msg));
    exit();
}
/* run an A.net transaction based on this order. */
elseif ($ACTION == OP_TRANSACT && defined('CSHOP_CONTROL_SHOW_TRANSACTION_CONTROLLER') && CSHOP_CONTROL_SHOW_TRANSACTION_CONTROLLER) {
    $order->set_id_by_token($itemid);
    $user = $order->get_user();

    $pay = cmClassFactory::getInstanceOf(CSHOP_CLASSES_PAYMETHOD, $pdb);

    $gateclass = CSHOP_CLASSES_PAYMENT;
    $gate = new $gateclass($user, $pay, $order);

    $xtype = $_POST['xtype'];
    $amt = $_POST['xamt'];

    if (empty($errs)) {
        try {
            $res = $gate->run_transaction($xtype, $amt);
            $order->record_transaction($gate);

            if (PEAR::isError($res)) {
                $msg = "Could not send transaction to payment gateway: " . $res->getMessage();
            }
            else {
                $msg = "Transaction has been sent to payment gateway. See below for result message";
                if ($xtype == 'capture') { // <-- todo should the order class know how to do this?
                    $order->increment_billed_amount($amt);
                }
            }
            // send back to self with messageness
            header("Location: {$_SERVER['PHP_SELF']}?$reqIdKey=$itemid&info=" . base64_encode($msg));
            exit();
        }
        catch (Exception $e) {
            if ($gate->get_trans_amount()) $order->record_transaction($gate);
            $errs[] = $e->getMessage();
            $ACTION = OP_VIEW;
            //throw $e;
        }
    }

}
/* update the order status */
elseif ($ACTION == OP_UPDATE) {
    $mosh = new mosh_tool();
    $mosh->form_field_prefix = '';
    $msg = '';

    $order->set_id_by_token($itemid);

    $vals = array();
    if ($errs = $mosh->check_form($order->colmap)) { // handled below
    }
    else {
        $vals = $mosh->get_form_vals($order->colmap);
        $status = null;
        if (isset($vals['orders_status'])) {
            $status = $vals['orders_status'];
            unset($vals['orders_status']);
        }

        if ($vals) {
            $res = $order->store($vals);
            if (PEAR::isError($res) and $res->getCode() != DBCON_ZERO_EFFECT) {
                $errs[] = $res->getMessage();
            }
        }
        if (empty($errs)) {
            if ($status) $order->set_status($status);
            $notify = isset($_POST['do_notify']);
            $order->store_history($_POST['comments'], $notify);

            $msg .= sprintf('%s was updated.', $table_title);

            if ($notify) $msg .= ' Customer was notified.';

            // send back to self with messageness
            header("Location: {$_SERVER['PHP_SELF']}?$reqIdKey=$itemid&info=" . base64_encode($msg));
            exit();
        }
    }
}

if ($ACTION == OP_DUMP) {
    $order->set_id_by_token($itemid);
    $file = $order->dump_data_file();
    if ($file) {
        $msg = "Order info has been dumped to $file";
        header("Location: {$_SERVER['PHP_SELF']}?$reqIdKey=$itemid&info=" . base64_encode($msg));
        exit();
    }
    else {
        $ACTION = OP_VIEW;
        $errs[] = "Could not write to file $file";
    }
}          

if ($ACTION == OP_VIEW) {

    $order->set_id_by_token($itemid);
    if (!$orderinfo = $order->fetch()) {
        trigger_error("The given parameter did not match any order", E_USER_ERROR);
    }

    $user = $order->get_user();
    $smarty->assign('user', $user->fetch());
    $smarty->assign('user_email', $user->get_email());

    // mask the cc#
    if (strlen($orderinfo['cc_number']) == 4) {
        $orderinfo['cc_number'] = '(last 4) '.$orderinfo['cc_number'];
    }

    $orderitems = $order->fetch_items();

    foreach ($orderitems as $oi) {
        if (isset($oi['product_attribs']['design_url'])) {
            $smarty->assign('has_custom_designs', true);
            break;
        }
    }
    $smarty->assign('orderinfo', $orderinfo);
    $smarty->assign('suppress_update', true);

    $cart_totals = $order->fetch_totals();

/** set and display ***********************************************************/
    $smarty->assign('cart_totals', $cart_totals);

    $smarty->assign('discount_amt', abs($orderinfo['discount_amt']));
    $smarty->assign('discount_descrip', $orderinfo['discount_descrip']);

    $smarty->assign('currency', $orderinfo['currency']);
    $smarty->assign('order_status', $order->get_status());
    $smarty->assign('cart', $orderitems);
    $smarty->assign('numitems', count($orderitems));
    $smarty->assign('billing', $order->fetch_addr('billing'));
    $smarty->assign('shipping', $order->fetch_addr('shipping'));


    $h = $order->fetch_history();
    $smarty->assign('history', $h);

    if (defined('CSHOP_CONTROL_SHOW_TRANSACTIONS') && CSHOP_CONTROL_SHOW_TRANSACTIONS) {
        $trans = $order->fetch_transaction_summary();
        $smarty->assign('transactions', $trans);

        /* show form for running financial transactions thru the gateway */
        if (defined('CSHOP_CONTROL_SHOW_TRANSACTION_CONTROLLER') && CSHOP_CONTROL_SHOW_TRANSACTION_CONTROLLER) {

            $order_totals = $order->fetch_totals();

            // all this to get an instance of CSHOP_CLASSES_PAYMENT
            $user = $order->get_user();
            $pay = cmClassFactory::getInstanceOf(CSHOP_CLASSES_PAYMETHOD, $pdb);
            $gateclass = CSHOP_CLASSES_PAYMENT;
            $gate = new $gateclass($user, $pay, $order);

            if ($transaction_options = $gate->get_transaction_options()) {

                $fex = new formex();
                $fex->field_prefix = '';
                $fex->add_element('xtype', array('Type', 'select', $transaction_options));
                $fex->add_element('xamt', array('Amount', 'text', '', array('size'=>5)));
                $fex->add_element('xamt', array('Amount', 'text', '', array('size'=>5)));
                $fex->add_element('op_xaction', array('RUN', 'submit'));
                $fex->add_element($reqIdKey, array(null, 'hidden', $itemid)); // important

                if ($order_totals['billed_to_date'] == 0) {
                    $fex->elem_vals = array('xamt' => $order_totals['grand_total']);
                }

                $smarty->assign('xform', $fex->get_struct());
            }
        }
    }

    /* ORDER UPDATE FORM - built in the USA */
    $fex = new formex('POST');
    $fex->js_src_inline = true;
    $fex->field_prefix = '';
    $fex->max_size = 25;
    $fex->add_element($order->colmap);

    /* add elements for the customer notify */
    $fex->add_element('do_notify', array('Notify Customer?', 'toggle'));
    $fex->add_element('comments', array('Comments', 'textarea'));

    $fex->add_element('op_update', array('UPDATE', 'submit')); // the button
    $fex->add_element($reqIdKey, array(null, 'hidden', $itemid)); // important
    $fex->set_element_opts('orders_status', $order->get_statuses());

    $fex->elem_vals = $orderinfo;
    if (empty($orderinfo['ship_date'])) $fex->elem_vals['ship_date'] = date('Y-m-d');
    if (empty($orderinfo['delivery_date'])) $fex->elem_vals['delivery_date'] = date('Y-m-d');

    $smarty->assign('upform', $fex->get_struct());

    $pagetitle = 'ORDER DETAIL - ' . $itemid;
    /* */
    $SHOWFORM = 1;
}
else {
    /** create filter form **/
    $filt = new filter_form('GET');
    $filt->left_td_style = '';
    $filt->field_prefix = '';
    $filt->add_element('hdr1', array('<b>Filter by::</b> Status:', 'heading'));
    $filt->add_element('status', array('', 'select', null));
    $filt->add_element('hdr2', array('Order ID:', 'heading'));
    $filt->add_element('f_oid', array('', 'text', null, array('size'=>4)));
    $filt->add_element('op_filter', array('GO', 'submit'));
    $filt->set_element_opts('status', array(''=>'[ANY]') + $order->get_statuses());


    /** decide on which result page to show **/
    $range = 50;
    $offset = (isset($_GET['page']))? (($_GET['page']-1) * $range) : 0;
    /** **/

    /** decide how to order the results */
    $orderable = array('id','order_create_date', 'email', 'orders_status', 'amt_quoted', 'ship_date');
    if (isset($_GET['by']) and in_array($_GET['by'], $orderable)) {
        $orby = $_GET['by'];
        $orderdir = (isset($_GET['dir']) and $_GET['dir'] == 'D')? 'DESC' : 'ASC';
    }
    else {
        $orby = 'order_create_date';
        $orderdir = 'DESC';
    }
    /** **/

    /** decide how to filter the results */
    $where = 'orders_status != 6'; // 6 = CLOSED { TODO dont hardcode that }
    if (isset($_GET['op_filter'])) {
        $w = array();
        if (!empty($_GET['status'])) {
            $w[] = sprintf('orders_status = %d', $_GET['status']);
        }
        if (!empty($_GET['f_oid'])) {
            $w[] = sprintf('ord.id = %d', $_GET['f_oid']);
        }
        if (!empty($_GET['uid'])) {
            $w[] = sprintf('ord.user_id = %d', $_GET['uid']);
        }
        if (!empty($_GET['f_month'])) {
            $w[] = sprintf('DATE_FORMAT(ord.order_create_date, \'%%b %%Y\') = \'%s\'',
                           addslashes($_GET['f_month']));
        }
        if (count($w)) {
            $where = join(' AND ', $w);
        }
    }
    /** **/

    $cols = array('cust_name','company','email','order_create_date', 'orders_status', 'currency', 'ship_date', 'amt_quoted','order_token');
    if (defined('CSHOP_ALLOW_ANON_ACCOUNT')) {
        $cols[] = 'anon_email';
    }
    $header_row = array('id'=>'Order ID','order_token'=>'Order Number','email'=>'User', 'orders_status'=>'Status', 'order_create_date'=>'Order Date', 'amt_quoted'=>'Total', 'ship_date'=>'Ship Date');

    if ($orders = $order->fetch_any($cols, $offset, $range, $orby, $where, $orderdir)) {
        
        /** list all cm_categories in one big ass dump using HTML_Table **/
        $table = new fu_HTML_Table(array("width" => "600"));
        $table->setAutoGrow(true);
        $table->setAutoFill("n/a");
        $table->addSortRow($header_row, $orby, null, 'TH', null, $orderdir);

        /* we got orders. add to $table object */
        foreach ($orders as $o) {
            $name = $o['cust_name'];
            if (!empty($o['company'])) $name .= " [{$o['company']}]";
            $email = (!empty($o['email']))? $o['email'] : $o['anon_email'];
            $vals = array($o['id'],
                          $o['order_token'],
                          "$name &lt;{$email}&gt;",
                          $order->statuses[$o['orders_status']],
                          $o['order_create_date'],
                          $o['amt_quoted'],
                          $o['ship_date']);
            $link = sprintf('%s?%s=%s',
                              $_SERVER['PHP_SELF'], 
                              $reqIdKey,
                              $o['order_token']);
            $table->addRow_fu(array_values($vals), '', true, $link);
        }

        $pager = new res_pager($offset, $range, $order->numRows);
        $smarty->assign('pager', $pager);
    }
    $pagetitle = 'ORDERS';
}


if (isset($_GET['info'])) {
    $msg = base64_decode($_GET['info']);
}


$smarty->assign('pagetitle', $pagetitle);
##############################################################################
# output template
##############################################################################
if (!$PRINT_TEMPLATE) $smarty->display('control/header.tpl');
?>

<div align="center" style="margin: 10px">
<?

if (isset($_GET['info'])) { ?>
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


<? if ($ACTION == OP_VIEW) { ?>
    <? if ($PRINT_TEMPLATE) { ?>
        <? $smarty->display('cart/control/order_print.tpl') ?>
    <? } else { ?>
        <? $smarty->display('cart/control/order.tpl') ?>
    <? } ?>

<? } else { ?>
    <div class="headlineW">
       <h2 class="productName headline"><?= SITE_DOMAIN_NAME ?> :: <?= $pagetitle ?></h2>
    </div>
    <div style="width: 600px; padding: 4px;" align="right">
      <? $filt->display(); ?>
    </div>
    <div style="width: 600px; border: 1px solid black; padding: 4px">
    <? if (!isset($table)) { ?>
        No matching orders were found.
    <? } else { ?>
        <? $smarty->display('cart/control/res_pager.tpl') ?>
        <? echo $table->toHTML() ?>
    <? } ?>
    </div>
<? } ?>
</div>  
<? 
if (!$PRINT_TEMPLATE) $smarty->display('control/footer.tpl');
