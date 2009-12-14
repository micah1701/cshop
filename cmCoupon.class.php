<?php
require_once('db_container.class.php');
require_once('Mail.php');
require_once('Mail/mime.php');
/** a coupon object to be attached to cmCart objects. does not know much but
  * it can calculate its own discount based on the order subtotal
  *
  * $Id: cmCoupon.class.php,v 1.8 2008/04/24 01:45:16 sbeam Exp $
  */
class cmCoupon extends db_container {
    var $numeric_pk = false;

    var $_table = 'cm_coupons';

    var $_pk_col = 'code';

        /** next 3 have to be set for use by store.dbcwrap.php. Really they should
     * be abstract in the parent class so we can make sure they exist */
    var $class_descrip = 'Discount Code'; // english name of this class, what it represents
    var $table_orderby_default = 'code'; // column to sort listings by, by default
    var $table_name_column = 'code'; // column to get the "name" or description of a single instance from


    var $child_relations = array('cm_coupons_categories'=> 'cm_categories_id');

    var $control_header_cols = array('code'=>'Code', 'descrip'=>'Description', 'percent_off'=>'Pct. off', 'amt_off'=> '$ off', 'used' => '# uses', 'is_active'=>'Active?');

    var $colmap = array('code' => array('Coupon code', 'text', '', array('maxlength'=>10), true),
                        'descrip' => array('Description', 'text', '', array('maxlength'=>32), true),
                        'percent_off' => array('Percent off (%)', 'numeric', '00', array('maxlength'=>2), false),
                        'amt_off' => array('Amount off ($)', 'numeric', '0.00', array('maxlength'=>5), false),
                        'is_active' => array('Active?', 'toggle', false),
                        'expires' => array('Expiration', 'date', null, array('year_end'=>'+5'),false),
                        'never_expires' => array('Never expires', 'checkbox', false),
                        'max_uses' => array('Max # of uses', 'numeric', '', array('maxlength'=>4), false),
                        'used' => array('Number of times used', 'numeric', '', array('maxlength'=>4), false),
                        'cm_coupons_categories' => array('Categories', 'select_multiple', '', array('size'=>4), false),
                        'hdr_belongs' => array('Coupon recipient (optional)', 'heading', false),
                        'belongs_name' => array('Recipient name', 'text', false),
                        'belongs_email' => array('Recipient email', 'text', false),
                        'do_notify' => array('Notify recipient?', 'toggle', false),
                        );

    var $colmap_help = array('code' => 'Enter any desired combination of numbers and letters, up to 10 characters. Must be unique',
                             'descrip' => 'Description of the coupon/discount. Will be shown to customers in the cart and order detail pages. Required',
                             'percent_off' => 'Percentage to deduct from subtotal',
                             'amt_off' => 'Fixed amount to deduct from subtotal',
                             'is_active' => 'Check to activate this discount code for use',
                             'expires' => 'Expiration date (optional)',
                             'never_expires' => 'if checked, code never expires',
                             'max_uses' => 'Once used in this many orders, stops working. Set to 0 for no limit',
                             'used' => 'Number of times this coupon has been used in orders to-date',
                             'belongs_name' => 'Name of intended recipient',
                             'belongs_email' => 'email addr. Enter multiple separated by commas',
                             'cm_coupons_categories' => 'List of Product Categories this coupon is valid for. For all, select ALL',
                             'do_notify' => 'if checked, an email will be sent with the code and instructions on use');

    /** if true, each coupon can only be used once. So it is marked as 'used'
     * at some point during checkout */
    var $one_time_only = false;

    /** has the current coupon been used before? */
    var $used = false;

    var $_is_valid = false;

    /** subject to use in the notification email */
    var $coupon_notification_subject = "Discount Code Notification";


    /*
     * fillout category options in colmap and return
     */
    function get_colmap()
    {
        if (empty($this->_filled_colmap)) {
            if (defined('CSHOP_COUPONS_HAVE_CATEGORIES') && CSHOP_COUPONS_HAVE_CATEGORIES) {
                $opts = cmClassFactory::getInstanceOf(CSHOP_CLASSES_PRODUCT, $this->db)->get_product_category_options(true);
                $this->colmap['cm_coupons_categories'][2] = $opts;
            }
            else {
                unset($this->colmap['cm_coupons_categories']);
            }
            $this->_filled_colmap = true;
        }
        return $this->colmap;
    }
    /* this coupon most likely is attached to a cart object and was
     * instantiated from it in cmCart::get_coupon_object(). If so the cart
     * would like to let the coupon know who its daddy is. 
     * @param $cart object cmCart instance
     */
    function set_owner_cart(&$cart) {
        $this->cart = $cart;
    }

    /** checks to see if this is a valid coupon. basicaly does its own set_id()
     * and tries to fetch itself.
     * @param $code str the coupon code
     * @return array db_result or false
     */
    function validate($code) {

        $code = preg_replace('/\s*/', '', $code);
        //error_log("\n$code : ", 3, '/tmp/coup.log');

        if (!$this->_is_valid or $code != $this->get_id()) {
            //error_log("getting... ", 3, '/tmp/coup.log');
            $this->_is_valid = false;
            $this->set_id($code);
            if (! ($this->fetch())) {
                //error_log("not found\n", 3, '/tmp/coup.log');
                return false;
            }
            else {
                $this->used = $this->header['used'];
                if ($this->used > 0 and $this->one_time_only) { // all used up?
                    //error_log("used up ({$this->one_time_only})\n", 3, '/tmp/coup.log');
                    return false;
                }
                elseif ($this->header['max_uses'] > 0 && $this->header['used'] >= $this->header['max_uses']) {
                    //error_log("over used\n", 3, '/tmp/coup.log');
                    return false;
                }
                elseif (empty($this->header['is_active'])) { // active flag unset?
                    //error_log("inactive\n", 3, '/tmp/coup.log');
                    return false;
                }
                elseif (empty($this->header['never_expires']) && strtotime($this->header['expires']) < time()) { // expired?
                    //error_log("expired\n", 3, '/tmp/coup.log');
                    return false;
                }
                else {
                    //error_log("OK\n", 3, '/tmp/coup.log');
                    $this->_is_valid = true; // looks ok
                }
            }
        }
        if ($this->_is_valid) {
            return $this->header;
        }
    }

    /** find out how much I am worth
     * @param $amt float the amount of the order so far (subtotal)
     * @return float
     */
    function calculate_discount($amt, $product_id=null) {
        if ($this->fetch(null, true)) {

            $does_apply = true;
            if (!empty($this->header['cm_coupons_categories']) && $product_id) {
                $does_apply = false;
                $product = cmClassFactory::getInstanceOf(CSHOP_CLASSES_PRODUCT, $this->db);
                $product->set_id($product_id);
                $product_cats = $product->fetch_product_categories();
                foreach ($product_cats as $pcat) {
                    if (in_array($pcat['id'], $this->header['cm_coupons_categories'])) {
                        $does_apply = true;
                    }
                }
            }

            if ($does_apply) {
                if ($this->header['percent_off']) {
                    return $amt * $this->header['percent_off'] / 100;
                }
                else {
                    return $this->header['amt_off'];
                }
            }
            else {
                return 0;
            }
        }
    }


    /**
     * get a description of this coupon from the db
     * @return string
     */
    function get_descrip() {
        return $this->get_header('descrip');
    }

    /**
     * show the value of this coupon in % or fixed amt
     * @return string
     */
    function get_value()
    {
        if (!empty($this->header) or $this->fetch()) {
            if ($this->header['percent_off']) {
                return $this->header['percent_off'] . '%';
            }
            else {
                return $this->header['amt_off'];
            }
        }
    }



    /**
     * mark this coupon as 'used'
     * @return t/PE
     */
    function set_used() {
        if ($code = $this->get_id()) {
            $sql = "UPDATE ". $this->get_table_name() . " SET used = used+1 
                    WHERE code = " . $this->db->quote($code);
            return $this->db->query($sql);
        }
    }



    /**
     * store form values. Special post-save proc for sending notifications.
     */
    function store($vals) {

        $res = parent::store($vals);

        if (!PEAR::isError($res) or $res->getCode() == DBCON_ZERO_EFFECT) {
            if (!empty($vals['do_notify']) && !empty($vals['belongs_email'])) {
                $this->send_coupon_notification($vals['belongs_email'], $vals['belongs_name']);
            }
        }
        return $res;
    }


    function applies_to_lineitems() {
        if (defined('CSHOP_COUPONS_HAVE_CATEGORIES') && CSHOP_COUPONS_HAVE_CATEGORIES) {
            $vals = $this->fetch(null, true); // get everything and see if it belongs to a category. If so, we need to apply this per-lineitem.
            if (!empty($vals['cm_coupons_categories']) && ($vals['cm_coupons_categories'][0] != 0 or count($vals['cm_coupons_categories']) > 1)) {
                return true;
            }
        }
        elseif (defined('CSHOP_APPLY_DISCOUNT_TO_LINE_ITEMS') && CSHOP_APPLY_DISCOUNT_TO_LINE_ITEMS) {
            return true;
        }
    }


    function send_coupon_notification($email, $name) {
        global $smarty;
        $sender = EMAIL_SENDER;

        $headers['From']   = $sender;
        $headers["Return-Path"] = $sender;  // Return path for errors
        $headers['Subject'] = SITE_DOMAIN_NAME . " : " . $this->coupon_notification_subject;

        $params = "-f".EMAIL_SENDER;

        $smarty->assign('recip_name', $name);
        $smarty->assign('recip_email', $email);
        $smarty->assign('coupon_descrip', $this->header['descrip']);
        $smarty->assign('coupon_code', $this->get_id());

        $msg = $smarty->fetch("float:emails/coupon_notify.txt.tpl");
        $msg_html = $smarty->fetch("float:emails/coupon_notify.html.tpl");

        $recip = sprintf("%s <%s>", $name, $email);
        
        $mm = new Mail_mime("\n");
        $mm->setTXTBody($msg);
        $mm->setHTMLBody($msg_html);

        $body = $mm->get();
        $headers = $mm->headers($headers);

        $m =& Mail::factory('mail', $params);

        $res = $m->send($recip, $headers, $body);
        return $res;
    }
}
