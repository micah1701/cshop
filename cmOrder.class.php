<?php
require_once(CSHOP_CLASSES_CART . '.class.php');

/**
 * basic container class for orders
 *
 * $Id: cmOrder.class.php,v 1.54 2008/07/01 15:39:07 sbeam Exp $
 */
define ('CM_ORDER_STATUS_NEW', 1);
define ('CM_ORDER_STATUS_PROCESSING', 2);
define ('CM_ORDER_STATUS_SHIPPED', 3);
define ('CM_ORDER_STATUS_DELIVERED', 4);
define ('CM_ORDER_STATUS_CANCELLED', 5);
define ('CM_ORDER_STATUS_CLOSED', 6);
define ('CM_ORDER_STATUS_BACKORDER', 7);

class cmOrder extends db_container {
    var $_sesskey = '_circusCart';

    var $_table = 'cm_orders';

    // TODO should not have these here, should be other ctr classes
    var $_items_table = 'cm_order_items';
    var $_items_options_table = 'cm_order_items_options';
    var $_users_table = 'auth_user';
    var $_history_table = 'cm_order_history';
    var $_transaction_history_table = 'cm_order_transactions';
    var $_cart_totals_table = 'cm_cart_extra_totals';


    // names of columns in address table we can deal with
    var $_addr_cols = array('name','company','street_addr','addr2','city','state','postcode','country','phone','email');

    /** token by which we identify all orders, a random string of somekind usually */
    var $order_token;

    var $statuses = array(CM_ORDER_STATUS_NEW=> 'NEW',
                          CM_ORDER_STATUS_PROCESSING=> 'PROCESSING',
                          CM_ORDER_STATUS_SHIPPED=> 'SHIPPED',
                          CM_ORDER_STATUS_DELIVERED=> 'DELIVERED',
                          CM_ORDER_STATUS_CANCELLED=> 'CANCELLED',
                          CM_ORDER_STATUS_CLOSED=> 'CLOSED',
                          CM_ORDER_STATUS_BACKORDER=> 'BACKORDERED');

    /* default initial status for new orders. Usually 'NEW' but you never know (HUB) */
    var $default_order_status = CM_ORDER_STATUS_NEW;

    /** should the subtotal for everything always be equal to zero? because
     * there is a crazy sale? or no charge for this particuar user?  */
    var $sale_zero_all = false; 


    var $colmap = array(
            'payment_method' => array('Payment Method', 'text'),
            'cc_type' => array('cc_type', 'text'),
            'cc_owner' => array('cc_owner', 'text'),
            'cc_number' => array('cc_number', 'text'),
            'cc_expires' => array('cc_expires', 'text'),
            'order_create_date' => array('Order Created', 'text'),
            'last_modified' => array('Last Modified', 'text'),
            'orders_status' => array('Status', 'select', array()),
            'orders_date_finished' => array('Date Finished', 'text'),
            'currency' => array('Currency', 'text'),
            'currency_value' => array('Currency Value', 'text'),
            'tax_total' => array('Tax', 'text'),
            'tax_method' => array('Tax Method', 'text'),
            'ship_total' => array('Shipping', 'text'),
            'ship_method_id' => array('ship_method_id', 'text'),
            'ship_method' => array('Ship Method', 'text'),
            'tracking_no' => array('Tracking#', 'text'),
            'ship_date' => array('Ship Date', 'date', 4, null, 0),
            'delivery_date' => array('Delivery Date', 'date', 4, null, 0),
            'amt_quoted' => array('Total', 'text'),
            'amt_billed_to_date' => array('Total Billed to Date', 'text'),
        );
     

    /** holds the current order total */
    var $order_total_amt = 0;

    /** holds the subtotal of this order */
    var $order_subtotal_amt = 0;


    function set_user($user) {
        $this->user = $user;
    }
    function set_cart($cart) {
        $this->cart = $cart;
    }

    /* get a user object assoc with this order */
    function get_user() {
        if (!isset($this->user)) {
            $class = CSHOP_CLASSES_USER;
            $this->user = new $class($this->db);
            if (!$this->header or !isset($this->header['user_id'])) {
                $this->fetch(array('user_id'));
            }
            $this->user->set_id($this->header['user_id']);
        }
        return $this->user;
    }

    /** creates a random string to use as the order ID/token (used in all user 
     * correspondence, so we try and make it look nice and professional) I 
     * spent a bit too much time on it but it works. will always start with 5 
     * numbers then the rest are either numbers or letters e.g., like 27839WG
     *
     * @param $len int length of token to create
     * @return string
     */
    function create_order_token($len=7) { // 104,038,844 different possible
        if (!$this->order_token) { // 28936HW
            $tok = mt_rand(10000, 99999);
            $chrs = 'QWERTYUPASDFGHJKLZXCVBNM1234567890';
            for ($i=0; $i<$len-5; $i++) {
                $tok .= $chrs{mt_rand(0, strlen($chrs)-1)};
            }
            $sql = "SELECT COUNT(*) FROM {$this->get_table_name()} WHERE order_token = '$tok'";
            $res = $this->db->getOne($sql);
            if ($res > 0) {
                $tok = $this->create_order_token($len);
            }
            $this->order_token = $tok;
        }
        return $this->order_token;
    }

    /* sql for selecting many a row for the order listing */
    function _get_fetch_any_sql($cols, $orderby, $where, $orderdir='ASC') {

        array_push($cols, 'cust_name','company','email','telephone','fax');

        $sql = sprintf("SELECT ord.id, ord.user_id, %s
                        FROM %s ord LEFT JOIN %s u ON (u.id = ord.user_id)",
                                ($cols)? join(',', $cols) : '*',
                                $this->get_table_name(),
                                $this->_users_table);
        if ($where) $sql .= "\nWHERE " . $where;
        if ($orderby) $sql .= "\nORDER BY $orderby $orderdir";
        return $sql;
    }



    /**
     * call a new order into existance
     * @param $vals array any of the columns in $this->_table
     * @return PEAR error on error */
    function create($vals=array()) {

        if (!is_array($vals)) {
            return $this->raiseError('cannot create empty order!', DB_ERROR_NEED_MORE_DATA);
        }

        $cart_totals = $this->cart->fetch_totals();
        $vals['cart_id'] = $this->cart->get_id();

        $insuff_inv = $this->cart->check_inventory();
        if (CSHOP_STOCK_BLOCK and $insuff_inv) {
            return $this->raiseError("Some cart line item quantities exceed inventory levels");
        }

        $vals['user_id'] = $this->user->get_id();
        if (empty($vals['order_token'])) $vals['order_token'] = $this->create_order_token();
        $vals['amt_quoted'] = $cart_totals['grand_total'];

        if ($this->sale_zero_all) { // 100% discount for some reason! (incl tax, shipping)
            $vals['discount_amt'] = $vals['amt_quoted'];
        }

        if ($insuff_inv == false) {
            $vals['orders_status'] = $this->default_order_status; 
        }
        else {
            $vals['orders_status'] = CM_ORDER_STATUS_BACKORDER; 
        }

        if (!$this->cart->requires_shipping()) { 
            // shipping amounts from the cart
            $vals['ship_total'] = $cart_totals['shipping']['amt'];
            $vals['ship_method'] = $cart_totals['shipping']['method'];
        }

        // tax amounts pulled from cart.
        if (!empty($cart_totals['tax'])) {
            $vals['tax_total'] = $cart_totals['tax']['amt'];
            $vals['tax_method'] = $cart_totals['tax']['method'];
        }
        else {
            $vals['tax_total'] = 0;
            $vals['tax_method'] = null;
        }

        if ($map = $this->cart->get_colmap_shipping_options()) {
            $vals = array_merge($vals, $this->cart->fetch(array_keys($map))); 
        }

        if ($d = $this->cart->get_discount(1)) {
            if (!empty($cart_totals['discount'])) {
                $vals['discount_amt'] = $cart_totals['discount'];
            }
            $vals['discount_descrip'] = $this->cart->get_discount_descrip();
            $vals['coupon_code'] = $this->cart->get_header('coupon_code');
        }

        if ($gc_total = $this->cart->get_giftcard_total()) {
            $vals['giftcard_total'] = $gc_total;
        }

        $this->db->autoCommit(false); // BEGIN.

        try {
            $res = $this->store($vals);
            $res = $this->store_billing_addr();
            $res = $this->store_shipping_addr();
            $res = $this->set_create_date();
            $res = $this->store_lineitems($insuff_inv);
            $this->order_total_amt = $vals['amt_quoted'];
            $res = $this->db->commit();
        }
        catch (Exception $e) {
            $this->db->rollback();
            $this->db->autoCommit(true); // reset?
            throw $e;
        }
        $this->db->autoCommit(true);
        return $res;
    }

    


    /** get all the items from the current cart and put in the order_items table.
     * @return number of items inserted
     */
    function store_lineitems($insuff_inv=false)
    {
        if (!$this->cart) {
            return $this->raiseError("must have a cart object");
        }
        $items = $this->cart->fetch_items();
        $cnt = 0;
        foreach ($items as $cartitem) {
            $oi = db_container::factory($this->db, $this->_items_table);
            $order_items_cols = array('inventory_id','product_id','qty','price','discount','product_sku','product_descrip','is_digital');

            $newitem = array();
            foreach ($order_items_cols as $col) {
                if (isset($cartitem[$col])) $newitem[$col] = $cartitem[$col];
            }

            $newitem['product_attribs'] = serialize($cartitem['product_attribs']); // silliness
            $newitem['normalized_attribs'] = serialize($cartitem['normalized_attribs']); // silliness
            $newitem['order_id'] = $this->get_id();

            if (CSHOP_DO_INVENTORY_CHECK && is_array($insuff_inv) && isset($insuff_inv[$cartitem['inventory_id']]) && $cartitem['out_of_stock'])  {
                $newitem['backorder_qty'] = $cartitem['qty'] - $insuff_inv[$cartitem['inventory_id']];
                $newitem['stock_status'] = false;
            }

            $cart_item_id = $cartitem['id']; // save for item_optiosn

            $newitem['has_item_options'] = (!empty($cartitem['has_item_options']));

            $oi->store($newitem);

            if (!empty($cartitem['has_item_options'])) {
                $res = $this->store_item_options($oi, $cartitem['item_options']);
                if (!PEAR::isError($res)) { $cnt++; }
            }
            unset($oi);
        }
        return $cnt;
    }
    

    /**
     * transfer all item options from cart_items to order_items while saving
     * our new order_items.id values but keeping Everything Else the same 
     * @param $oitem obj a DBC instance of order_item table
     * @param $cart_item_id int row id of the cm_cart_items row we are using
     * @return true or PEAR::Error 
     */
     function store_item_options(&$oitem, $ci_opts)
     {
         if ($oitem_id = $oitem->get_id()) {

             $opt_table = $this->_items_options_table;
             $sql = "REPLACE INTO $opt_table VALUES (?,?,?,?)";
             $sth = $this->db->prepare($sql);

             foreach ($ci_opts as $key => $v) {
                 $args = array($oitem_id, $key, $v['descr'], $v['value']);
                 $res2 = $this->db->execute($sth, $args);
                 if (PEAR::isError($res2)) {
                     return $this->raiseError('could not save options: '. $res2->getMessage());
                 }
             }
             return true;
         }
     }

    /** utility function to get the current total amount for the order
     * @return double
     */
    function get_total() {
        if (!$this->order_total_amt) {
            $res = $this->fetch(array('amt_quoted'));
            $this->order_total_amt = $this->header['amt_quoted'];
        }
        return $this->order_total_amt;
    }


    /** utility function to get the current total amount for the order
     * @return double
     */
    function get_subtotal() {
        if (!$this->order_subtotal_amt) {
            $sql = sprintf("SELECT SUM(qty * price) FROM %s
                             WHERE order_id = %d",
                             $this->_items_table,
                             $this->get_id());
            $this->order_subtotal_amt = $this->db->getOne($sql);
        }
        return $this->order_subtotal_amt;
    }




     /*
      * add a row to the order_history table using a generic db_con
      * @param $comment str any annotation
      * @param $do_notify bool did we send a note to customer?
      * @return success
      */
     function store_history($comment, $do_notify=false) {

         $vals = array('order_id' => $this->get_id(),
                       'order_status' => $this->get_status(),
                       'user_notify' => $do_notify,
                       'stamp' => date('Y-m-d H:i:s'),
                       'comments' => $comment);

         $hist = db_container::factory($this->db, $this->_history_table);
         $res = $hist->store($vals);

         if ($do_notify) {
             $this->send_user_notification($comment);
         }

         // I wish I could use a trigger to do this.
         if (defined('CSHOP_CCNO_TRUNCATE') && (CSHOP_CCNO_TRUNCATE && $this->header['orders_status'] > CM_ORDER_STATUS_NEW)) {
             $sql = "UPDATE " . $this->get_table_name() . "
                     SET cc_number = RIGHT(cc_number, 4) WHERE id = " . $this->get_id();
             $this->db->query($sql);
         }
         return $res;
     }


     /*
      * get all history items for this order
      * @return array
      */
     function fetch_history() {
         $oid = $this->get_id();
         $where = "order_id = $oid";
         $hist = db_container::factory($this->db, $this->_history_table);
         return $hist->fetch_any(null, 0, 0, 'stamp', $where, 'DESC');
     }



    /**
     * set the creation date of an order. Called when create() is called, usually
     * @param date a mysql formatted date
     */
    function set_create_date($date=null) {
        $sql = sprintf("UPDATE %s SET order_create_date = %s, last_modified = NOW() 
                        WHERE id = %d",
                       $this->get_table_name(),
                       ($date)? "'$date'" : 'NOW()',
                       $this->get_id());
        $this->db->query($sql);
    }




    /**
     * get billing info from user container and throw it into the orders table
     */
    function store_billing_addr() {
        $vals = array();
        $this->get_user();

        if ($billing = $this->user->fetchBillingAddr($this->_addr_cols)) {
            foreach ($billing as $k => $v) {
                $vals["billing_$k"] = $v;
            }
            return $this->store($vals);
        }
    }

    /**
     * get shipping info from user container and throw it into the orders table
     */
    function store_shipping_addr($id=null) {
        $vals = array();
        $user = $this->get_user();

        if ($shipping = $user->fetchShippingAddr($this->_addr_cols)) {
            foreach ($shipping as $k => $v) {
                $vals["shipping_$k"] = $v;
            }
            return $this->store($vals);
        }
    }

    /**
     * get the order id by looking up the given token and call set_id() on this object
     * not sure why
     * @param token string
     * @return int the order id */
    function set_id_by_token($tok) {
        $sql = sprintf("SELECT id FROM %s WHERE order_token = %s",
                        $this->get_table_name(),
                        $this->db->quoteSmart(strtoupper($tok)));
        $res = $this->db->getOne($sql);
        if ($res and !PEAR::isError($res)) {
            $this->set_id($res);
        }
        return $res;
    }

    /**
     * set the current order status to the value given
     *
     * @param $status int one of the CM_ORDER_STATUS_* values
     * @return true on success
     */
    function set_status($s) {
        if (isset($this->statuses[$s])) {
            $vals = array('orders_status' => $s);
            return $this->store($vals);
        }
    }


    /**
     * get the string corresponding to the status for this order
     * @return string
     */
    function get_status() {
        if (!$this->header or !isset($this->header['orders_status'])) {
            $this->fetch(array('orders_status'));
        }
        return $this->statuses[$this->header['orders_status']];
    }

    /**
     * get a list of all potential statuses for orders on this system
     */
    function get_statuses() {
        return $this->statuses;
    }

    /**
     * get some address info
     * @param $addr_type string the type of address
     * @return array std address stuff
     */
    function fetch_addr($addr_type) {
        if ($addr_type != 'shipping' and $addr_type != 'billing') {
            return $this->raiseError("arg[0] must be billing or shipping");
        }
        $cols = array();
        foreach ($this->_addr_cols as $c) {
            $cols[] = $addr_type . '_' . $c;
        }
        $hdr = $this->fetch($cols);

        $ret = array();
        for ($i=0; $i<count($hdr); $i++) {
            $ret[$this->_addr_cols[$i]] = $hdr[$cols[$i]];
        }
        return $ret;
    }

    /**
     * fetch the token belonging to this order
     */
    function fetch_token() {
        if (!$this->get_id()) {
            return;
        }
        if ($this->order_token) {
            return $this->order_token;
        }
        elseif (!empty($this->header['order_token'])) {
            return $this->header['order_token'];
        }
        elseif ($tok = $this->fetch(array('order_token'))) {
            return $tok['order_token'];
        }
    }


    /**
     * get info on all the items that were in this order
     * @return array
     */
    function fetch_items() {
         $items = array();
         $sql = sprintf("SELECT id, inventory_id, product_id, qty, price, discount, product_sku, is_digital
                         , product_descrip, product_attribs, normalized_attribs, has_item_options, backorder_qty, stock_status
                         FROM %s
                         WHERE order_id = %d
                         ORDER BY product_id DESC",
                         $this->_items_table,
                         $this->get_id());
        $res = $this->db->query($sql);

        $sql_o = sprintf("SELECT optkey, opt_descr, opt_value FROM %s WHERE %s_id = ?",
                            $this->_items_options_table,
                            $this->_items_table);
        $sth_o = $this->db->prepare($sql_o);

        while ($row = $res->fetchRow()) {
            if ($row['normalized_attribs']) $row['normalized_attribs'] = unserialize($row['normalized_attribs']);

            if (!empty($row['has_item_options'])) {
                $opts = array();
                $res_o = $this->db->execute($sth_o, array($row['id']));
                while ($row_o = $res_o->fetchRow()) {
                    $opts[$row_o['optkey']] = array('descr'=>$row_o['opt_descr'], 'value'=>$row_o['opt_value']);
                }
                $res_o->free();
                $row['item_options'] = $opts;
            }
            $row['product_attribs'] = unserialize($row['product_attribs']);

            /* adjust the price if the discount column is nonzero */
            if ($row['discount'] != 0) {
                $row['full_price'] = $row['price'] + abs($row['discount']);;
            }

            $row['line_price'] = sprintf('%.02f', ($row['qty'] * $row['price']));
            $items[] = $row;
        }
        return $items;
    }


    /**
     * makes a record of any payment gateway transaction run against this order
     * in the cm_order_transactions table 
     * @param $gate obj a cmPaymentGateway object
     * @return success 
     */
    function record_transaction(&$gate) {

        $xtype = $gate->get_trans_type();
        $th = db_container::factory($this->db, $this->_transaction_history_table);
        $tresult = $gate->get_trans_result();
        $vals = array('cm_orders_id' => $this->get_id(),
                      'user_id' => $this->user->get_id(),
                      'trans_type' => $xtype,
                      'trans_id' => $gate->get_trans_id(),
                      'trans_auth_code' => $gate->get_auth_code(),
                      'trans_result' => $tresult,
                      'trans_result_msg' => (!empty($tresult))? $gate->get_trans_result_msg() : '',
                      'trans_amount' => $gate->get_trans_amount(),
                      'trans_request' => $gate->get_trans_request(1),
                      'trans_response' => $gate->get_trans_response(),
                      'is_voided' => NULL,
                      'verify_zip' => $gate->get_avs_result('zip'),
                      'verify_addr' => $gate->get_avs_result('addr'),
                      'verify_international' => $gate->get_avs_result('intl'),
                      'verify_csc' => $gate->get_csc_result(),
                      'stamp' => date('Y-m-d H:i:s'),
                      'has_avs_result' => ($gate->does_AVS && $gate->avs_result_flags != null));
        return $th->store($vals);
    }


    /**
     * get a summary of all the transactions assoc with this order
     * @param $cols array optional columsn to select
     * @return array
     */
    function fetch_transaction_summary($cols=null)
    {
        $oid = $this->get_id();
        $where = "cm_orders_id = $oid";
        $th = db_container::factory($this->db, $this->_transaction_history_table);
        if (!$cols) $cols = array('id','stamp','trans_type','trans_id','trans_result','trans_result_msg','trans_amount', 'has_avs_result',
                                  'is_voided', 'verify_addr', 'verify_zip', 'verify_name', 'verify_international','verify_csc');
        return $th->fetch_any($cols, 0, 0, 'stamp', $where, 'DESC');
    }


    /**
     * fetch the last payment gateway transaction ID attached to this order
     * @return string
     */
    function fetch_payment_transaction_id()
    {
        $sql = "SELECT trans_id FROM {$this->_transaction_history_table}
                WHERE trans_id IS NOT NULL AND trans_id != '0' 
                  AND cm_orders_id = " . $this->get_id();
        if ( $ids = $this->db->getCol($sql) ) {
            return $ids[0];
        }
    }

    /**
     * fetch the last payment gateway authorization code for this order. Used to place CAPTUREs
     * @return string
     */
    function fetch_payment_auth_code()
    {
        $sql = "SELECT trans_auth_code FROM {$this->_transaction_history_table}
                WHERE trans_type = 'AUTH_ONLY' AND cm_orders_id = " . $this->get_id();
        if ( $ids = $this->db->getCol($sql) ) {
            return $ids[0];
        }
    }

    /**
     * sets the cc_* columns when a CC payment is attempted
     * @param $pay obj a cmPayment object
     * @param $gate obj a cmGateway object
     * @return success 
     */
    function store_payment_info(&$pay, &$gate) {

        $billing = $this->fetch_addr('billing');

        $ccno = $gate->truncate_ccno($pay->get_ccno()); // may or may not chop the # off

        $vals = array('cc_type' => $pay->get_cctype(),
                      'cc_owner' => $billing['name'],
                      'cc_number' => $ccno,
                      'cc_expires' => $pay->get_ccexp('my'));

        $vals['amt_billed_to_date'] = $gate->get_captured_amount();
        $vals['currency'] = $gate->currency_code;
        $vals['payment_method'] = $gate->gateway_name;

        return $this->store($vals);
    }



    /** util method to mark this order as shipped in one go. From an automated
     * script or similar. Will also notify the user via the usual method, if
     * 3rd param is true
     * @param str the tracking#
     * @param str shipping method acutally used
     * @param str the ship date (mysql YYYY-MM-DD) opt
     * @param bool notify notify the customer in email?
     * @param str a comment to use in the history log/email notification
     */
    function set_shipped($trackno, $method, $ship_date=null, $do_notify=false, $comment = null)
    {

        if (!$this->get_id()) {
            $this->raiseError("must set id first");
        }
        $vals = array('tracking_no' => $trackno,
                      'ship_method' => $method,
                      'ship_date' => ($ship_date)? $ship_date : date('Y-m-d'),
                      'orders_status' => CM_ORDER_STATUS_SHIPPED);
        $this->store($vals);

        if (!$comment) $comment = "This is an automatic ship notification";
        $this->store_history($comment, $do_notify);
    }


    /**
     * utility method to finalize an order, record all payment and transaction info as needed,
     * and clear up the cart record and put it away 
     * @param $pay obj a cmPayment object
     * @param $gate obj a cmGateway object
     * @param $cart obj a cmCart object 
     * @return void
     * @throws Exception
     */
    function finalize(&$pay, &$gate, &$cart) {
        $this->db->autoCommit(false);
        try {
            if ($pay) {
                $this->store_payment_info($pay, $gate);
                $pay->kill(); // erase ccno from db!
            }

            $cart->pull_inventory();
            $cart->set_purchased();

            $this->store_history($cart->get_user_comment(), true); // notify user here
            $cart->set_user_comment('');
            $this->db->commit();
        }
        catch (Exception $e) {
            $this->db->rollback();
            $this->db->autoCommit(true);
            throw $e;
        }
        $this->db->autoCommit(true);

    }

    /**
     * similar to cmCart::fetch_totals()
     * get all subtotal, tax, shipping and 'other' charges for this order. As
     * well as the 'grand total' which we actually use the amount billed
     * (amt_billed_to_date) by the cmPayment class (any discrepancy is cause
     * for alarm)
     * @return array 
     */
    function fetch_totals() {
        $shiptax = $this->fetch(array('cart_id', 'amt_quoted', 'amt_billed_to_date', 'ship_total','ship_method','tax_total','tax_method', 'discount_amt', 'discount_descrip'));

        extract($shiptax);

        $tots = array('grand_total' => $amt_quoted,
                'billed_to_date' => $amt_billed_to_date,
                'subtotal' => $this->get_subtotal(),
                'discount' => array('amt' => $discount_amt, 'descrip'=> $discount_descrip),
                'shipping' => array('amt' => $ship_total, 'method'=> $ship_method),
                'tax' => array('amt' => $tax_total, 'method'=> $tax_method),
                'giftcards' => array('total'=>$this->get_giftcard_total(), 'list'=>$this->get_giftcards()));

        $tots['other'] = array();
        $sql = sprintf("SELECT total, method FROM %s WHERE cart_id = %d",
                        $this->_cart_totals_table,
                        $cart_id);
        $res = $this->db->query($sql);
        while ($row = $res->fetchRow()) { // grab up the 'other' charges from the old cart!
            $tots['other'][] = array('amt'=> $row['total'], 'method' => $row['method']);
        }

        return $tots;
    }




    /**
     * get total of all giftcards that are applied to this order
     * @return float 
     */
    function get_giftcard_total() {
        if (defined('CSHOP_ACCEPT_GIFTCARDS') && CSHOP_ACCEPT_GIFTCARDS) {
            $hdr = $this->fetch(array('giftcard_total'));
            return $hdr['giftcard_total'];
        }
        else {
            return 0;
        }
    }

    /** get a list of info on all giftcars belonging to this cart. Giftcards
     * are usually attached to the cart using the cmGiftcard object
     *
     * @return array
     */
    function get_giftcards() {
        if (defined('CSHOP_ACCEPT_GIFTCARDS') && CSHOP_ACCEPT_GIFTCARDS) {
            $sql = "SELECT id, CONCAT('xxxxxxxxx', SUBSTRING(gc_no, -3)) AS gc_no, gc_amt 
                    FROM cm_giftcards WHERE order_id = " . $this->get_id();
            return $this->db->getAll($sql);
        }
        else {
            return 0;
        }
    }


    /**
     * update the backorder_qty column for a given line item. Used in control by admin.
     * also records an order_history record to show what changed, and updates 
     * stock_status if the backorder goes to 0
     *
     * @params $liid id of the line item in cm_order_items
     * @params $qty the new backorder qty
     * @return db_container::store() result or false if no change needed.
     */
    function update_backorder($liid, $qty) {
        $oi = db_container::factory($this->db, $this->_items_table);
        $vals = array('backorder_qty' => $qty);

        $oi->set_id($liid);
        $old = $oi->fetch(array('backorder_qty', 'product_sku'));

        if ($qty != $old['backorder_qty']) {
            $msg = sprintf('Product "%s" : Backorder changed from "%d" to "%d"', 
                            $old['product_sku'], 
                            $old['backorder_qty'], 
                            $qty);
            $this->store_history($msg, false);

            $vals['stock_status'] = ($qty == 0)? 1 : 0;
            return $oi->store($vals);
        }
    }

    function fetch_lineitem_report($id=null) {
        if (empty($id)) $id = $this->get_id();
        if ($id) {
            $items = array();
            $sql = sprintf("SELECT product_id, (qty*items.price) AS line_total
                            FROM %s items, cm_products product
                            WHERE order_id = %d AND product.id = items.product_id
                            ORDER BY product.title DESC",
                            $this->_items_table,
                            $id);
            $res = $this->db->query($sql);
            while ($row = $res->fetchRow()) {
                if (!isset($items[$row['product_id']])) {
                    $items[$row['product_id']] = 0;
                }
                $items[$row['product_id']] += $row['line_total'];
            }
            return $items;
        }
    }



    /**
     * send an email to this order's owner with info on the order
     * @param $comment any new comment to be included
     * @return success
     */
    function send_user_notification($comment="") {
        global $smarty;


        $user = $this->get_user();
        $uservals = $user->fetch();
        if (empty($uservals['email'])) $uservals['email'] = $user->get_email();

        $smarty->assign('user', $uservals);

        $orderinfo = $this->fetch();

        // mask the cc#
        $orderinfo['cc_number'] = '(last 4) '.substr($orderinfo['cc_number'], -4, 4);

        $orderitems = $this->fetch_items();

        $smarty->assign('orderinfo', $orderinfo);
        $smarty->assign('suppress_update', true);
        $smarty->assign('comments', $comment);

        $cart_totals = $this->fetch_totals();

        /** set and display ***********************************************************/
        $smarty->assign('cart_totals', $cart_totals);

        $smarty->assign('discount_amt', abs($orderinfo['discount_amt']));
        $smarty->assign('discount_descrip', $orderinfo['discount_descrip']);

        $smarty->assign('currency', $orderinfo['currency']);
        $smarty->assign('order_status', $this->get_status());
        $smarty->assign('cart', $orderitems);
        $smarty->assign('numitems', count($orderitems));
        $smarty->assign('billing', $this->fetch_addr('billing'));

        if ($this->requires_shipping())
            $smarty->assign('shipping', $this->fetch_addr('shipping'));


        $h = $this->fetch_history();
        $smarty->assign('history', $h);


        $smarty->assign('order_view_link', sprintf('http://%s'.CSHOP_ORDER_DETAIL_PAGE_FMT,
                                                    SITE_DOMAIN_NAME,
                                                    $orderinfo['order_token']));


        // get 2 versions of the message
        $msg = $smarty->fetch("float:emails/order_notify.txt.tpl");
        $msg_html = $smarty->fetch("float:emails/order_notify.html.tpl");

        $recip = sprintf("%s <%s>", $uservals['cust_name'], $uservals['email']);

        if ($orderinfo['orders_status'] == $this->default_order_status)
            $status = 'CONFIRMATION';
        else 
            $status = $this->statuses[$orderinfo['orders_status']];

        $headers['From']   = EMAIL_SENDER;
        $headers['Subject'] = sprintf('%s: Order #%s - %s', 
                                      SITE_DOMAIN_NAME, 
                                      $orderinfo['order_token'], 
                                      $status);
                                      
        if (defined('CSHOP_ORDER_EMAIL_BCC')) 
            $headers['BCC']    = CSHOP_ORDER_EMAIL_BCC;
        else 
            $headers['BCC']    = ERROR_EMAIL_RECIP;

        $params = "-f".EMAIL_SENDER;

        $mm = new Mail_mime("\n");
        $mm->setTXTBody($msg);
        $mm->setHTMLBody($msg_html);

        $body = $mm->get();
        $headers = $mm->headers($headers);

        $m =& Mail::factory('mail', $params);

        $res = $m->send($recip, $headers, $body);
        return $res;
    }

    /**
     * increments the amt_billed_to_date column by the given amount. 
     * @return DB result
     */
    function increment_billed_amount($amt) {
        if (is_numeric($amt)) {
            $sql = sprintf("UPDATE %s SET amt_billed_to_date = amt_billed_to_date + %s WHERE id = %d",
                            $this->get_table_name(),
                            $this->db->quoteSmart($amt),
                            $this->get_id());
            return $this->db->query($sql);
        }
    }


    function requires_shipping() {
        if (!defined('CSHOP_ENABLE_DIGITAL_DOWNLOADS') || ! CSHOP_ENABLE_DIGITAL_DOWNLOADS) return true;

        $sql = sprintf("SELECT COUNT(*) FROM %s WHERE is_digital IS NULL AND order_id = %d", 
                        $this->_items_table,
                        $this->get_id());
        $res = $this->db->getOne($sql);
        return ($res !== '0');
    }

    function has_digital_goods() {
        if (!defined('CSHOP_ENABLE_DIGITAL_DOWNLOADS') || ! CSHOP_ENABLE_DIGITAL_DOWNLOADS) return;

        $sql = sprintf("SELECT COUNT(*) FROM %s WHERE is_digital = 1 AND order_id = %d", 
                        $this->_items_table,
                        $this->get_id());
        $res = $this->db->getOne($sql);
        return ($res >= 1);
    }


}


/* lame back-compat */
class circusOrder extends cmOrder { }
