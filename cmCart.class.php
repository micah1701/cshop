<?php

require_once(CONFIG_DIR . 'cshop.config.php');
require_once('db_container.class.php');
require_once(CSHOP_CLASSES_PRODUCT.'.class.php');
require_once(CSHOP_CLASSES_USER.'.class.php');
require_once(CSHOP_CLASSES_COUPON.'.class.php');
require_once(CSHOP_CLASSES_TAXCALCULATOR.'.class.php');
require_once('cmCurrencyConverter.class.php');

define('ERR_CART_NO_INV', 2);
define('ERR_CART_NO_STOCK', 4);

/**
 * a cart implementation
 *
 * $Id: cmCart.class.php,v 1.57 2008/07/09 14:19:50 sbeam Exp $
 */
class cmCart extends db_container {
    var $_sesskey = '_circusCart';
    var $_items = array();

    var $_table = 'cm_cart';

    var $_product_container_class = CSHOP_CLASSES_PRODUCT;
    var $_coupon_container_class = CSHOP_CLASSES_COUPON;

    // TODO should not have these here, should be other ctr classes
    var $_users_table = 'cm_users';
    var $_items_table = 'cm_cart_items';
    var $_items_options_table = 'cm_cart_items_options';
    var $_totals_table = 'cm_cart_extra_totals';
    var $_inventory_table = 'cm_inventory';
    var $_products_table = 'cm_products';

    /** should we check the inventory level (based on attribs) for each item added to cart? */
    var $do_inventory_check = CSHOP_DO_INVENTORY_CHECK;

    /** should we check the inventory level (based on attribs) for each item added to cart? */
    var $stock_block = CSHOP_STOCK_BLOCK;

    /** should we look for any coupons/discounts each time the cart is viewed? */
    var $do_check_coupons = CSHOP_DO_TAKE_COUPONS;

    /** is there any user login here and should we expect an auth object to give us a user id? */
    var $do_login = false;
    /** force user login before anything is added to the cart?  */
    var $force_login_on_add = false;
    /** force user before checkout? */
    var $force_login_on_checkout = false;

    /** check for "other" totals in cm_cart_totals? */
    var $check_for_extra_totals = false;

    // holds the current subtotal of this cart. Dangerous.
    var $_subtotal;

    // holds a coupon object for this cart
    var $_coupon;
    // holds the current discount amount (float)
    var $_discount_amt;
    var $_coupon_code;

    /** session key for holding a user comment from checkout process */
    var $_user_comment_sesskey = '_cm_user_order_comment';

    /** all prices are in this currency, actually */
    var $_base_currency = 'USD';

    /** but we are displaying them in some other currency maybe */
    var $_display_currency = 'USD';

    /** the types of currency we can deal with in this implem. */
    var $currency_opts = array('USD' => 'US Dollars');

    /** each line item in the cart may have any number of "custom" attributes
     * like gift_card_text, special_instructions, etc. This array defines what
     * they would be. (optkey => 'Opt Name') */
    var $item_custom_options = array();

    /** private flag to track if any coupons/discounts have been applied to the
     *  cart subtotal before adding or not */ 
    var $_discount_is_applied = false;

    /** flag to determine whether we are applying coupons A) true, to each individual lineitem or B) false, 
     * as a chunk after the subtotal. Really, it makes no difference except 
     * that clients seem to prefer it different ways. But also, if coupons can 
     * be limited by categories, then this NEEDS to be true */
    var $apply_discount_to_line_items = CSHOP_APPLY_DISCOUNT_TO_LINE_ITEMS;

    /* probability in percent of garbage collection of old rows from cm_cart 
     * and cm_cart_items table, taken each time emptyCart() is called */
    var $gc_prob = 5;

    /* when garbaging old carts, delete those older than this many days */
    var $gc_age = 28;


    /* saves cart to session
     */
    function save() {
        $_SESSION[$this->_sesskey] = $this->_items;
    }


    /** override from parent to create a new row in cart table if we do not
     * find one for this user
     * @return int new cart.id from db
     */
    function get_id() {
        if ($this->_id) {
            return $this->_id;
        }
        else {
            if (isset($_SESSION[$this->_sesskey])) {
                $cid = $_SESSION[$this->_sesskey];

                /** make sure this cart has not been purchased already */
                $sql = sprintf("SELECT purchased FROM %s WHERE id = %d",
                                $this->get_table_name(),
                                $cid);
                if (!$this->db->getOne($sql)) { // null!
                    $this->set_id($cid);
                    return $cid;
                }
            }

            /** here is where we automagically create a new row in cart table. we HOPE */
            $cid = $this->create_stub();
            $_SESSION[$this->_sesskey] = $cid;
            $this->set_id($cid);
            return $cid;
        }
    }


    /**
     * create a new row in the cart table to cart_items will have something to reference
     * @return id of new row
     */
    function create_stub() {
        $this->store(array('user_id'=>NULL, 'create_date'=>$this->db->getOne('select now()')));
        return $this->get_id();
    }


    /**
     * get the id of the current user who called us into existence. 
     * grab from the global $auth object which should already be instantiated
     * @return int a user id
     */
    function get_user_id() {
        $user = cmClassFactory::getSingletonOf(CSHOP_CLASSES_USER, $this->db);
        return $user->get_auth_id();
    }


    /**
     * make the current cart object belong to the user identified by uid
     * @param $uid int a user object id
     * @todo actually merge with any cart they have leftover from previous login
     */
    function merge_user($uid) {
        if (!empty($uid) && is_numeric($uid) && (!isset($this->header['user_id']) || $this->header['user_id'] != $uid)) {
            if ($cid = $this->get_id()) {
                return $this->store(array('user_id'=>$uid));
            }
        }
    }



    /* add an item to ye old wagon
     * @param $id productid
     * @param $qty qty to add
     * @param $attribs mixed - either an array containing attributes
     *                         identifiers, or an integer giving the inventory_id 
     * @return true on success, error if no find it
     */
     function add_item($pid, $qty, $attribs=array(), $options=array()) {
         $pctr =& cmClassFactory::getSingletonOf($this->_product_container_class, $this->db);
         $pctr->set_id($pid);
         $product_price = $pctr->fetch_baseprice();

         if (!$product_price) {
             return $this->raiseError("$pid is not a valid product id");
         }
         if (!is_numeric($qty) or $qty<=0) {
             return $this->raiseError("Quantity must be a positive integer ($qty)");
         }

         /** check there is a inventory for this product/attrib combo **/
         if (is_numeric($attribs)) { // inv_id is given already
             $inv = $pctr->fetch_inventory($attribs);
         }
         elseif ($this->do_inventory_check) {  // figure out inv_id from attribs
             $inv = $pctr->fetch_inventory_item($pid, $attribs);
         }
         else { // we dont care about the attribs actually
             $inv = $pctr->fetch_inventory_item($pid);
         }

         if (!$inv) {
             return $this->raiseError("No inventory record could be found.", ERR_CART_NO_INV);
         }
         if ($this->stock_block) {  
             if ($inv['qty'] < 1) {
                 return $this->raiseError("The selected item/style is out of stock", ERR_CART_NO_STOCK);
             }
             elseif ($inv['qty'] < $qty) {
                 return $this->raiseError("There is not enough inventory for the selected item/style", ERR_CART_NO_STOCK);
             }
         }
         $invid = $inv['id'];
         /** **/

         /* check pre-existing item by cart keys (TODO would be nice if
          * db_container did this) */
         $has_in_cart = $this->check_for_item_in_cart($invid, $options);

         $citem = $this->create_cart_item();

         if ($has_in_cart) {
             $citem->set_id($has_in_cart);
             $vals = array('qty' => $qty);
         }
         else {

             $product_price = $pctr->get_price($invid, $options);

             $vals = array('cart_id' => $this->get_id(),
                           'product_id' => $pid,
                           'qty' => $qty,
                           'price' => $product_price,
                           'product_sku' => $inv['sku'], // prob should not copy this but...
                           'inventory_id' => $invid,
                           'product_descrip' => $pctr->get_title(),
                           #'product_attribs' => serialize($attribs), // obsolete? maybe.
                           'has_item_options' => (is_array($options) and count($options)));

             if ($this->do_apply_discount_to_lineitems()) {
                 $vals['discount'] = $this->get_discount($product_price, $pid);
             }
         }
         $this->_subtotal = null; // force recalc on next access

         if ($res = $citem->store($vals)) { // putt............... yes
             if (is_array($options) and count($options)) { // any special options?
                 $this->store_item_options($citem, $options);
             }
         }
         return $res;
     }


     /** check whether an item identified by the given inventoryid is in the
      * cart already or not. For swipeit we also loop thru each of
      * cm_cart_items_options for each item, matching only if all options for a
      * previous item match the current options. Basically this allows us to
      * add the same product/inventory_id to the cart multiple times, as long
      * as one option is different.
      * @param invid int an id in the inventory table
      * @param opts array k=>v of options to check
      * @return bool
      */
     function check_for_item_in_cart($invid, $opts = null) {
         $sql = sprintf("SELECT id FROM %s WHERE cart_id = %d AND inventory_id = %d",
                         $this->_items_table,
                         $this->get_id(),
                         $invid);
         $itemids = $this->db->getCol($sql);
         if (!count($itemids)) {
             return false;
         }
         else {
             if (!$opts) {
                 return $itemids[0]; // TODO this is lame if mult. items w/ same inv_id 
             }
             else {
                 $sql = "SELECT optkey, opt_value FROM ".$this->_items_options_table." 
                         WHERE cm_cart_items_id = ?
                           AND optkey = ?
                           AND ( cm_products_options_id = ? 
                              OR ( cm_products_options_id IS NULL AND opt_value = ?))";
                 $sth = $this->db->prepare($sql);

                 foreach ($itemids as $iid) {
                     $matched = true;
                     foreach ($opts as $optkey => $v) {
                         $res = $this->db->execute($sth, array($iid, $optkey, $v, $v));
                         if ($res->numRows() == 0) {
                             $matched = false;
                             $res->free();
                             break; // end inner foreach{}
                         }
                         $res->free();
                     }

                     // TODO check to make sure ALL options in the cart item
                     // match, not just the ones we were handed.
                     if ($matched) return $iid;
                 }
             }
         }
         return false;
     }


     /** get list of all items in the cart
      * @param bool return location of thumbnail images as well?
      * @return array
      */
     function fetch_items($get_thumbs=false) {

         $items = array();
         $cols = array('id', 'inventory_id', 'product_id', 'qty', 'price', 'discount'
                       , 'product_sku', 'product_descrip', 'product_attribs', 'has_item_options');
         if (!$get_thumbs) {
             $sql = sprintf("SELECT %s
                             FROM %s
                             WHERE cart_id = %d
                             ORDER BY modified_date DESC",
                             join(',', $cols),
                             $this->_items_table,
                             $this->get_id());
         }
         else {
             $sql = sprintf("SELECT %s, pi.system_location, pi.filename_thumb, pi.dims_thumb
                             FROM %s ci LEFT JOIN cm_product_images pi ON pi.cm_products_id = ci.product_id
                             WHERE cart_id = %d
                             ORDER BY modified_date DESC",
                             join(',', $cols),
                             $this->_items_table,
                             $this->get_id());
         }
        $res = $this->db->query($sql);

        $pc_class = $this->_product_container_class;
        $pctr =& new $pc_class($this->db);

        if ($insuff_inv = $this->check_inventory()) {
            $insuff_inv = array_keys($insuff_inv);
        }

        while ($row = $res->fetchRow()) {
            if (!empty($row['product_attribs'])) {
                $row['product_attribs'] = unserialize($row['product_attribs']); 
            }

            /* find any optional options for this line item */
            if (!empty($row['has_item_options'])) {
                $row['item_options'] = $this->_fetch_item_options($row['id'], false);
            }

            // TODO get_attrib_array is expensive, we shouldnt be doing this on each view...
            $row['normalized_attribs'] = $pctr->get_attrib_array($row['inventory_id']);

            if ($row['discount'] != 0 && $this->do_apply_discount_to_lineitems()) {
                $row['full_price'] = $row['price'];
                $row['price'] -= abs($row['discount']);
            }
            $row['line_price'] = sprintf('%.02f', ($row['qty'] * $row['price']));

            /* if there is no enough inventory for the current inventory item, we set a flag */
            $row['out_of_stock'] = ($insuff_inv and in_array($row['inventory_id'], $insuff_inv));

            $items[] = $row;
        }
        $res->free();
        return $items;

     }


     /* expensive but safe grab of anything matching this line item in
      * teh cart_item_options table 
      * @param $item_id int the cm_cart_items.id 
      * @param $ignore_emptykeys bool optional ignore options that have a NULL or empty key value? (def. true)
      * @return array 
      */
     function _fetch_item_options($item_id, $ignore_emptykeys=true)
     {
         if (!isset($this->_sth_items_opts)) {
             /* SQL to prep for getting the options */
             $sql_o = sprintf("SELECT optkey, opt_descr, opt_value FROM %s WHERE %s_id = ?",
                                 $this->_items_options_table,
                                 $this->_items_table);
             $this->_sth_items_opts = $this->db->prepare($sql_o);
         }

         $opts = array();
         $res = $this->db->execute($this->_sth_items_opts, array($item_id));
         while ($row = $res->fetchRow()) {
             // see how we ignore things that are not in $item_custom_options?
             if ($ignore_emptykeys == false or in_array($row['optkey'], array_keys($this->item_custom_options))) {
                 $opts[$row['optkey']] = array('descr'=>$row['opt_descr'], 'value'=>$row['opt_value']);
             }
         }
         $res->free();
         return $opts;
     }


     /**
      * count the number of line items in the user's cart
      * @return int
      */
      function count_items() {
         $sql = sprintf("SELECT COUNT(*) AS num FROM %s WHERE cart_id = %d",
                         $this->_items_table,
                         $this->get_id());
         return $this->db->getOne($sql);
      }

     /**
      * count the total quantity of items in the cart - make sure they are not
      * trying to sneak thru the 12-item express lane
      * @return int
      */
      function count_item_qty() {
         $sql = sprintf("SELECT SUM(qty) AS num FROM %s WHERE cart_id = %d",
                         $this->_items_table,
                         $this->get_id());
         return $this->db->getOne($sql);
      }



      /** get the weight of all ye cart items here ye ye
       * @return float
       */
     function get_weight() {
         $sql = sprintf("SELECT SUM(p.weight * ci.qty) as weight FROM %s ci, %s p
                         WHERE cart_id = %d AND ci.product_id = p.id",
                         $this->_items_table,
                         $this->_products_table,
                         $this->get_id());
         return $this->db->getOne($sql);
     }


     /** this is not in the textbook
      * create a db_container instance for the cart items
      * @return a $db_container object
      */
     function create_cart_item() {
         $citem =& new db_container($this->db);
         $citem->set_table($this->_items_table);
         /* we have a real id in cart_items now... so ? 
          * $citem->numeric_pk = false;
          * $citem->set_pk_col(array('cart_id','inventory_id'));
          */
         return $citem;
     }

     /**
      * store some optional fields for a cart line item. These fields are
      * freeform, and are basically an array, having (key => (Description, Value))
      * @param $citem obj a DBC instance for cm_cart_items table
      * @param $opts array assoc keys and values to store (descriptions come
      *                          from $this->item_custom_options 
      * @return true/err
      */
     function store_item_options(&$citem, $opts)
     {
         if ($item_id = $citem->get_id()) {
             $opt_table = $this->_items_options_table;

             // need to delete first? we should always be adding....
             $sql = "REPLACE INTO $opt_table VALUES (?,?,?,?,?)";
             $sth = $this->db->prepare($sql);

             foreach ($opts as $optkey => $v) {
                 $opt_descr = '';

                 $sql = sprintf("SELECT id, opt_descr, opt_value FROM cm_products_options
                                 WHERE id = %d", $v);

                 if ($row = $this->db->getRow($sql)) {
                     $opt_id = $row['id'];
                     $v = $row['opt_value'];
                     $opt_descr = $row['opt_descr'];
                 }
                 elseif (isset($this->item_custom_options[$optkey])) {
                     $opt_descr = $this->item_custom_options[$optkey];
                     $opt_id = null;
                 }

                 $res = $this->db->execute($sth, array($item_id, $opt_id, $optkey, $opt_descr, $v));
                 if (PEAR::isError($res)) {
                     return $this->raiseError('could not save options: '. $res->getMessage());
                 }
             }
             return true;
         }
     }


     /**
      * change the qty for a given line item identified by the inventory_id and cart_id
      * if qty == 0, delete the item and don't look back
      * @param $invid an id from $_inventory_table
      * @param $qty int
      * @return DB query result
      */
     function update_qty($item_id, $qty)
     {

         $this->_subtotal = null; // force recalc on next access

         if ($qty < 1) {
             return $this->remove_item($item_id);
         }
         elseif ($this->stock_block and $this->fetch_item_inventory_qty($item_id) < $qty) {
             return $this->raiseError("There is not sufficent inventory", ERR_CART_NO_STOCK);
         }
         else {
             $sql = sprintf("UPDATE %s SET qty = %d WHERE id = %d",
                             $this->_items_table,
                             $qty,
                             $item_id);
             return $this->db->query($sql);
         }
     }


     /** get the quantity available of the cart_item_id given
      * @param item_id an id from cm_cart_items
      * @return int the current inventory qty avail for that product/style
      */
     function fetch_item_inventory_qty($item_id)
     {
         $sql = "SELECT inv.qty FROM {$this->_inventory_table} inv, {$this->_items_table} items
                 WHERE items.id = " . intval($item_id) . " AND inv.id = items.inventory_id";
         return $this->db->getOne($sql);
     }


     /**
      * add the given number to the cart for an existing item
      * @param $invid an id from $_inventory_table
      * @param $qty int
      * @return DB query result
      */
     function add_qty($invid, $qty) {

         $this->_subtotal = null; // force recalc on next access

         $sql = sprintf("UPDATE %s SET qty = (qty+%d) WHERE cart_id = %d AND inventory_id = %d",
                         $this->_items_table,
                         $qty,
                         $this->get_id(),
                         $invid);
         return $this->db->query($sql);
     }


     /**
      * remove the item identified by item_id from cart_items
      * @param int an item_id
      * @return DB query result
      */
     function remove_item($item_id) {

         $this->_subtotal = null; // force recalc on next access

         /* cleanup cm_cart_items_options also TODO ON DELETE CASCADE would be nice */
         $sql = sprintf("DELETE FROM %s WHERE %s_id = %d",
                         $this->_items_options_table,
                         $this->_items_table,
                         $item_id);
         $res = $this->db->query($sql);

         $sql = sprintf("DELETE FROM %s WHERE id = %d",
                         $this->_items_table,
                         $item_id);
         return $this->db->query($sql);
     }

     /**
      * empty the cart of all items and dont look back. Also get rid of the
      * main cart table stub since any params in there will be suspect.
      *
      * @return DB query result
      */
      function emptyCart() {

          $this->_subtotal = null; // force recalc on next access

          /** todo using a subselect here. Again CASCADE would be nice */
          $sql = sprintf("SELECT id FROM cm_cart_items WHERE cart_id = %d",
                          $this->get_id());
          $item_ids = $this->db->getCol($sql);
          if (is_array($item_ids) and count($item_ids)) {
              $sql = sprintf("DELETE FROM %s 
                             WHERE %s_id IN (%s)",
                             $this->_items_options_table,
                             $this->_items_table,
                             join(',', $item_ids));
              $this->db->query($sql);
          }

          $sql = sprintf("DELETE FROM %s WHERE cart_id = %d",
                         $this->_items_table,
                         $this->get_id());
          $this->db->query($sql);

          $this->garbage_old_carts();

          return $this->db->affectedRows();
      }


     /**
      * set the purchased flag in this cart so it is not reused. Just saved for posterity.
      *
      * @return DB query result
      */
      function set_purchased() {

          if ($this->_coupon) { // there was a coupon attached previously!
              $this->_coupon->set_used();
          }

          $this->garbage_old_carts();

          return $this->store(array('purchased'=>1));
      }


     /**
      * get the subtotal for the items in this cart
      * @return float
      */
     function get_items_subtotal() {
         if (!$this->_subtotal) {
             $sql = sprintf("SELECT SUM(qty * (price-discount)) AS total
                             FROM %s
                             WHERE cart_id = %d",
                             $this->_items_table,
                             $this->get_id());
            $this->_subtotal = $this->db->getOne($sql);
         }
         return $this->_subtotal;
     }


     /* subtotal may be different for shipping calculations bc some items (ie
      * giftcards) may be marked as FREE SHIP items, so they should not be
      * included in pricing calcs */
     function get_shipping_subtotal() {
         if (empty($this->_shipping_subtotal)) {

             /* previous version was counting each product for EACH category it 
              * was in. But now we know better, and we have mysql5, so... use a dependent 
              * subquery to make sure no product in the cart is in a category 
              * that is marked FREE SHIP 
              * (subquery returns true if one or more of the products categories has a freeship shipping class...) */
             $sql = sprintf("SELECT SUM(qty * price) as shiptotal FROM cm_cart_items ci
                             WHERE cart_id = %d
                               AND NOT EXISTS (SELECT NULL FROM cm_products_categories pc, cm_categories c, cm_ship_class sc
                                               WHERE pc.cm_products_id = ci.product_id
                                                 AND pc.cm_categories_id = c.id
                                                 AND sc.id = c.ship_class_id
                                                 AND (sc.is_free = 1))",
                             $this->get_id());
             $this->_shipping_subtotal = $this->db->getOne($sql);
         }
         return $this->_shipping_subtotal;
     }



    /* find the given discount code in the codes table. If found, apply it to the cart
     * @param str a discount code
     * @return t/f
     */
    function apply_discount($code) {

         $coup = $this->get_coupon_object();

         if ($coup->validate($code)) {

             //error_log("in " . __FUNCTION__ . "(): $code is valid\n", 3, "/tmp/coup.log");

             $vals = array('id'=> $this->get_id(), 'coupon_code' => $this->_coupon->get_id());
             $res = $this->store($vals);
             if (!PEAR::isError($res)) {
                 if ($this->do_apply_discount_to_lineitems()) {
                     $items = $this->fetch_items();
                     foreach ($items as $item) {
                         if ($discount = $this->_coupon->calculate_discount($item['price'], $item['product_id'])) {
                             $sql = sprintf("UPDATE %s SET discount=%.02f WHERE id = %d",
                                             $this->_items_table,
                                             $discount,
                                             $item['id']);
                             $res = $this->db->query($sql);
                         }
                     }
                 }
             }
             return true;
         }
    }



    /* remove the tie between this cart and the discount/coupon object
     * @return t/f
     */
    function remove_discount() {
        if ($this->do_check_coupons) {
            $vals = array('id'=> $this->get_id(), 'coupon_code' => null);
            if ($this->do_apply_discount_to_lineitems()) {
                $sql = "UPDATE " . $this->_items_table . " SET discount = 0 WHERE cart_id = ".$this->get_id();
                $res = $this->db->query($sql);
            }
            $this->_coupon = null;
            return $this->store($vals);
        }
    }

     /**
      * get any discount (percent or fixed) that may be applied to this order
      * via a coupon or whathaveyou
      * @return float discount amount
      */
     function get_discount($base) {
         if (!$this->do_check_coupons) { // we dont take these, sorry
             return 0;
         }
         $this->get_coupon_object();

         $id = $this->get_id();
         if ($this->_coupon_code = $this->get_header('coupon_code')) {
             if ($this->_coupon->validate($this->_coupon_code)) {
                 $d = sprintf('%.02f', $this->_coupon->calculate_discount($base)); // float or false
                 return $d;
             }
         }
         else {
             return 0;
         }
     }

     function get_discount_total($tot) {
         if (!$this->do_check_coupons) { // we dont take these, sorry
             return 0;
         }
         if ($this->do_apply_discount_to_lineitems()) {
             $id = $this->get_id();
             if ($this->_coupon_code = $this->get_header('coupon_code')) {
                 if ($this->_coupon->validate($this->_coupon_code)) {
                     $sql = sprintf("SELECT SUM(discount*qty) FROM %s WHERE cart_id = %d",
                                     $this->_items_table,
                                     $this->get_id());
                     return $this->db->getOne($sql);
                 }
             }
         }
         else {
             return $this->get_discount($tot);
         }
     }


     /** singleton type method to get an instance of a cm_coupon object
      * @return cmCoupon object
      */
     function get_coupon_object() {
         if (!$this->_coupon) { // we have not looked yet
             $this->_coupon =& cmClassFactory::getInstanceOf($this->_coupon_container_class, $this->db);
             if (method_exists($this->_coupon, 'set_owner_cart')) {
                 $this->_coupon->set_owner_cart($this);
             }
         }
         return $this->_coupon;
     }

     /**
      * get description of the coupon/discount that is applied to this cart, if any
      * @return string
      */
     function get_discount_descrip()
     {
         if (!$this->do_check_coupons or !$this->get_header('coupon_code')) {
             return '';
         }
         $coup =& $this->get_coupon_object();
         return sprintf("%s (%s)", $coup->get_descrip(), $coup->get_value());
     }



     /** get the subtotal for the items in this cart. Now an alias for get_items_subtotal.
      * @return float
      */
     function get_subtotal() {
         return $this->get_items_subtotal();
     }


     /**
      * get the grandtotal of items = subtotal + shipping + tax
      * @return float
      */
     function get_grandtotal() {
         $subtotal = $this->get_subtotal();

         $tots = $this->fetch(array('ship_total', 'tax_total'));

         $gc_tot = $this->get_giftcard_total();

         $sum = $tots['ship_total'] + $tots['tax_total'] + $subtotal - $gc_tot;

         /** and in coupon/discount amount */
         if (!$this->do_apply_discount_to_lineitems()) {
             $sum -= $this->get_discount($subtotal);
         }

         if (!empty($this->check_for_extra_totals)) { // we have weird charges
               $sql = sprintf("SELECT SUM(total) FROM %s WHERE cart_id = %d",
                              $this->_totals_table,
                              $this->get_id());
               $sum += $this->db->getOne($sql);
         }

         return $sum;
     }


     /** find the total value of all taxable items in the cart. This means we
      * have to look in the categories table for each item in the cart and find
      * out if it is taxable or not 
      * @return float */
     function get_taxable_amount() {

         /* we have to use DISTINCT bc this goes on mySQL3 - LAME - otherwise I
          * would do
          * SELECT SUM(qty * price) FROM cm_cart_items ci
          * WHERE EXISTS 
          *      (SELECT NULL FROM cm_products_categories pc, cm_categories c
          *       WHERE ci.product_id = pc.cm_products_id AND c.is_taxable = 1)
          * AND cart_id = %d
         */

         $sql = sprintf("SELECT DISTINCT ci.id, (qty * price) as linetotal
                         FROM cm_cart_items ci, cm_products_categories pc, cm_categories c       
                         WHERE pc.cm_products_id = product_id
                             AND pc.cm_categories_id = c.id
                             AND c.is_taxable = 1
                             AND cart_id = %d",
                         $this->get_id());
         $amt = 0;
         $res = $this->db->query($sql);
         while ($row = $res->fetchRow()) {
             $amt += $row['linetotal'];
         }
         return $amt;
     }


     /**
      * set the payment id that is being used for this cart. Used during
      * checkout when a user has entered their payment info for an order but
      * has not been authorized or confirmed yet.  
      * @param $pay obj a cshop/cmPayment object of some kind
      * @return success
      */
      function set_payment(&$pay) {
          if (!is_object($pay)) {
              return $this->raiseError("$pay is not an object", E_USER_ERROR);
          }
          $this->_payment = $pay;
          return $this->store(array('cm_paymentcc_id' => $pay->get_id()));
      }

      /**
       * get the payment id that belongs to this cart, if any
       * @return int an id referencing the payment table / false
       */
       function get_payment_id() {
           $cartid = $this->get_id();
           if ($r = $this->fetch(array('cm_paymentcc_id'))) {
               return $r['cm_paymentcc_id'];
           }
       }


       /**
        * any comments added during checkout process. get them. (from the session)
        * @return str
        */
       function get_user_comment() {
           if (isset($_SESSION[$this->_user_comment_sesskey])) {
               return $_SESSION[$this->_user_comment_sesskey];
           }
       }

       /**
        * set the comment here...
        * @return str
        */
       function set_user_comment($str) {
           $_SESSION[$this->_user_comment_sesskey] = $str;
       }


       /** get an array representing all subtotals for this cart tax, shipping,
        * subtotal, grand_total and 'other' charges all included. the whole
        * shmeil
        * @return array
        */
       function fetch_totals() {
           $grand = 0;
           $subtotal = $this->get_subtotal();

           $gc_total = $this->get_giftcard_total();

           $tots = array('subtotal' => $subtotal);

           // get the regular stuff, shipping and tax
           if ( $shiptax = $this->fetch()) {

               $tax_total = $this->calculate_tax($shiptax['ship_total']);

               // the "grand total" is items + ship + tax - yes?
               $grand = $subtotal + $shiptax['ship_total'] + $tax_total - $gc_total;

               $tots['shipping'] = array('amt' => $shiptax['ship_total'], 'method'=> $shiptax['ship_method']);

               if ($tax_total) {
                   $tots['tax'] = array('amt' => $tax_total, 'method'=> $shiptax['tax_method']);
               }
           }

           $tots['giftcards'] = array('total'=>$gc_total, 'list'=>$this->get_giftcards());

           /** and in coupon/discount amount */
           if ($this->do_check_coupons and !$this->do_apply_discount_to_lineitems()) {
               $discount = $this->get_discount($subtotal);
               if (!$this->_discount_is_applied) {
                   $grand -= $discount;
               }
               $tots['discount'] = -1 * $discount;
           }


           if (!empty($this->check_for_extra_totals)) { // we have weird charges
               $tots['other'] = array(); // becomes sort of a sub-array
               $sql = sprintf("SELECT total, method FROM %s WHERE cart_id = %d",
                              $this->_totals_table,
                              $this->get_id());
               $res = $this->db->query($sql);
               while ($row = $res->fetchRow()) {
                   $tots['other'][] = array('amt'=> $row['total'], 'method' => $row['method']);
                   $grand += $row['total']; // add to the "grand"!
               }
           }

           $tots['grand_total'] = $grand;
           return $tots;
       }


       /** record in the auxillay cart totals table, some extra charge of some
        * kind to be included in the total cost of this cart
        @param $id str a unique id for this type of charge (ie giftwrap, handling, federal, whatver)
        @param $method str description of the charge
        @param $amt float how much
        @return pear/db result of the storage
        */
       function store_extra_total($id, $method, $amt)
       {
           $cid = $this->get_id();

           /* decide if we have seen this charge before (by id). If so, overwrite, if not, add */
           $sql = sprintf("SELECT COUNT(*) FROM %s WHERE cart_id = %d AND id = '%s'",
                          $this->_totals_table, 
                          $this->get_id(),
                          addslashes($id));
           $has_already = $this->db->getOne($sql);

           $vals = array('id' => $id,
                         'method' => $method,
                         'total' => $amt,
                         'cart_id' => $this->get_id());

           if ($has_already) {
               $where = sprintf("cart_id = %d AND id = '%s'",
                              $this->get_id(),
                              addslashes($id));
               $res = $this->db->autoExecute($this->_totals_table, $vals, DB_AUTOQUERY_UPDATE, $where);
           }
           else {
               $res = $this->db->autoExecute($this->_totals_table, $vals, DB_AUTOQUERY_INSERT);
           }
           return $res;
       }


    /** remove the items in the cart from the inventory (tracked by
     * products_inventory items, i.e. SKU's 
     * @return true on success
     */
    function pull_inventory()
    {
        $inv = $this->_get_cart_inventory();
        if (count($inv)) {
            $sql = "UPDATE {$this->_inventory_table} SET qty = (qty - ?) WHERE id = ?";
            $sth = $this->db->prepare($sql);
            foreach ($inv as $inv_id => $qty) {
                $res = $this->db->execute($sth, array($qty, $inv_id));
                if (PEAR::isError($res)) {
                    return $res;
                }
                $this->_inventory_monitor($inv_id);
            }
            return true;
        }
    }

    /**
     * for each item in the cart, add the given qty's back to the inventory.
     * Can be used if a transaction failed along the line somewhere.
     * @return true on success
     */
    function replace_inventory()
    {
        $inv = $this->_get_cart_inventory();
        if (count($inv)) {
            $sql = "UPDATE {$this->_inventory_table} SET qty = (qty + ?) WHERE id = ?";
            $sth = $this->db->prepare($sql);
            foreach ($inv as $inv_id => $qty) {
                $res = $this->db->execute($sth, array($qty, $inv_id));
                if (PEAR::isError($res)) {
                    return $res;
                }
            }
            return true;
        }
    }


    /**
     * check to make sure there is sufficient inventory for each item and qty in the cart
     * @return false if there is enough, or an array of the failed inventory id's if not
     */
    function check_inventory() {
        if (!$this->do_inventory_check) return false;

        $inv = $this->_get_cart_inventory();
        if (count($inv)) {
            $insuff = array();
            $sql = "SELECT qty FROM {$this->_inventory_table} WHERE id = ?";
            $sth = $this->db->prepare($sql);
            foreach ($inv as $inv_id => $qty) {
                $res = $this->db->execute($sth, $inv_id);
                if ($row = $res->fetchRow()) {
                    if ($qty > $row['qty']) {
                        $insuff[$inv_id] = $row['qty'];
                    }
                }
            }
            if (count($insuff)) return $insuff;
        }
    }



    /**
     * get a list of items in the cart, just the inventory_id and the qty
     * @return array (inv_id => qty)
     */
    function _get_cart_inventory()
    {
        $inv = array();
        $sql = sprintf("SELECT inventory_id, qty FROM %s WHERE cart_id = %d",
                        $this->_items_table,
                        $this->get_id());
        $res = $this->db->query($sql);
        while ($row = $res->fetchRow()) {
            $inv[$row['inventory_id']] = $row['qty'];
        }
        return $inv;
    }


    /** if configured, instant. a cmProduct obj and have it check the inventory
     * on the given inv, item 
     * @param $inv_id int inventory id
     */
    function _inventory_monitor($inv_id) {
        if (defined('CSHOP_INVENTORY_WARNING_LEVEL') && CSHOP_INVENTORY_WARNING_LEVEL > 0) {
            $c = CSHOP_CLASSES_PRODUCT;
            $pr = new $c($this->db);
            return $pr->check_inventory_level($inv_id);
        }
    }


    /** sets the currency for _display_ to one of the ISO4217 Currency codes 
     * @param $code str a code 
     */
    function set_display_currency($code)
    {
        if (!$code) return;
        if (!in_array($code, array_keys($this->currency_opts))) {
            return $this->raiseError("invalid currency for this store");
        }
        $this->_display_currency = $code;
    }

    /** return the current display currency 
     * @return str ISO code for the currency
     */
    function get_display_currency()
    {
        return $this->_display_currency;
    }

    /** convert the amount given into the currency given.
     * uses a singleton method of cmCurrencyConverter to grab a converter
     * object that knows how to access the DB and get the current rates .
     * 
     * also note this function can be easily registered with Smarty and then
     * used directly from inside the templates, like this:
     * controller script: $smarty->register_modifier('currency_format', array(&$cart, 'currency_format'));
     * template: <~ $products[i].price|currency_format:'CAD' ~>
     * 
     * @param $amt f amount to convert
     * @param $code str ISO4217 code to convert to
     * @return string converted amount. with symbols (ie "$42.17 CAD")
     * @todo mosh number_format() options to match euro format if needed, e.g. 12.002,02
     */

    function currency_format($amt, $code=null)
    {
        $cconv =& cmCurrencyConverter::getSingleton();

        if (!$code) $code = $this->_display_currency;
        if ($code != $this->_base_currency) {
            $amt = $cconv->convert($amt, $code);
        }
        $neg = ($amt < 0)? '-' : ''; // make sure any minus sign is on the outside
        $ret = $neg . $cconv->symbol($code) . number_format(abs($amt), 2);

        if ($code != $this->_base_currency) { $ret .= " $code"; }

        return $ret;
    }


     /**
      * fetch ids of any products that are related in the many-to-many relation
      * any product that is in the cart, ordering by the frequency of said relation
      * @return array
      */
     function fetch_related_products() {
         /* a SUBSELECT would be nice! */
         $sql = sprintf("SELECT product_id FROM %s
                         WHERE cart_id = %d",
                         $this->_items_table,
                         $this->get_id());
         $my_products = $this->db->getCol($sql);
         if (count($my_products)) {
             $sql = "SELECT related_to, COUNT(cm_products_id) AS relcount
                     FROM cm_products_relations 
                     WHERE cm_products_id IN (". join(',',$my_products) .") 
                     GROUP BY related_to ORDER BY relcount DESC";
             return $this->db->getCol($sql);
         }
     }


    /**
     * get total of all giftcards that are applied to this cart
     * @return float 
     */
    function get_giftcard_total() {
        if (defined('CSHOP_ACCEPT_GIFTCARDS') && CSHOP_ACCEPT_GIFTCARDS) {
            $sql = "SELECT SUM(gc_amt) AS tot FROM cm_giftcards WHERE cart_id = " . $this->get_id();
            return $this->db->getOne($sql);
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
            $sql = "SELECT id, gc_no, gc_amt FROM cm_giftcards WHERE cart_id = " . $this->get_id();
            return $this->db->getAll($sql);
        }
        else {
            return 0;
        }
    }


    /**
     * get the total tax amount for the items currently in cart. 
     * find out which items in this cart are taxable, then get the shipping
     * address, if available, and let cm_Tax figure it out
     *
     * note: saves the resulting tax to the cart in DB
     *
     * @param $shiptotal shipping cost for the order, if any
     * @return @int
     */
    function calculate_tax($shiptotal=0) {
        $amt = $this->get_taxable_amount();

        if (!$amt) {
            return 0;
        }
        else {
            $classname = CSHOP_CLASSES_USER;
            $user = new $classname($this->db);
            $uid = $user->get_auth_id();
            $user->set_id($uid);

            $shipdest = $user->fetchShippingAddr(array('country', 'state'));

            if ($shipdest) {
                $taxclass = CSHOP_CLASSES_TAXCALCULATOR;
                $taxc = new $taxclass();
                $taxc->set_amount($amt);
                $taxc->set_shipping_cost($shiptotal); // let the tax calculator know about any shipping cost we are aware of at this point. It will decide whether or not to include it.
                $taxc->set_destination($shipdest['country'], $shipdest['state']);

                $thetax = $taxc->calculate();
                if ($thetax) {
                    $vals = array('tax_total' => $thetax, 
                                  'tax_method' => $taxc->get_tax_name());
                }
                else {
                    $vals = array('tax_total' => 0, 
                                  'tax_method' => null);
                }
                $this->store($vals);
                return $thetax;
            }
        }
    }


    function do_apply_discount_to_lineitems() {
         $coup = $this->get_coupon_object();
         if ($this->apply_discount_to_line_items) { 
             return true;
         }
         else {
             if ($this->_coupon_code = $this->get_header('coupon_code')) {
                 if ($this->_coupon->validate($this->_coupon_code) && $coup->applies_to_lineitems()) {
                     return true;
                 }
             }
         }
    }

    function generate_order_token() {
        if (!$this->get_id()) return;

        $res = $this->fetch(array('order_token'));
        $tok = $res['order_token'];
        if (!$tok) {
            $order = cmClassFactory::getSingletonOf(CSHOP_CLASSES_ORDER, $this->db);
            $tok = $order->create_order_token();
            try {
                $this->store(array('order_token' => $tok));
            } catch (Exception $e) {
                if ($e->getCode() == DB_ERROR_ALREADY_EXISTS) {
                    $tok = $this->generate_order_token();
                }
                else {
                    throw $e;
                }
            }
        }
        return $tok;
    }




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


    /* provides hook for cmOrder, when copying cart contents to create a new 
     * object. Any colmap array return here will be used to transfer the 
     * indicated columns to the new order */
    function get_colmap_shipping_options() { }


    function get_minicart_values() {
        $subtotal = $this->get_subtotal();
        $mini = $this->fetch(array('ship_total', 'ship_method', 'tax_total', 'tax_method'));
        $mini['giftcard_total'] = $this->get_giftcard_total();
        $mini['discount_amt'] = $this->get_discount($subtotal);
        $mini['subtotal'] = $subtotal;
        return $mini;
    }


    function garbage_old_carts() {
        if (mt_rand(1, 100) <= $this->gc_prob) {
            $sqlwhere = sprintf(" TO_DAYS(NOW()) - TO_DAYS(modified_date) > %d", $this->gc_age);
            $sql1 = "DELETE FROM cm_cart_items WHERE cart_id IN (SELECT id from cm_cart WHERE $sqlwhere)";
            $sql2 = "DELETE FROM cm_cart WHERE $sqlwhere";
            $res = $this->db->query($sql1);
            $res = $this->db->query($sql2);
        }
    }

}



/* lame back-compat */
class circusCart extends cmCart { }





/******************************************************** ********************/
/** handle cart errors, doing a special redirect back to ourselves if its an
 * inventory/stock issue */
function cmCartErrorHandler(&$err_obj) {
    if ($err_obj->getCode() == ERR_CART_NO_STOCK or $err_obj->getCode() == ERR_CART_NO_INV) { 

        $msg = base64_encode($err_obj->getMessage());

        if (isset($_POST['op_add_pid'])) {
            /* totally uncool hack to get the original $pid from the backtrace of
             * the error object rather than just using global() */
            $pid = $err_obj->backtrace[2]['args'][0];
            $url = sprintf('%s?pid=%d&inventoryerror&err=%s', 
                            CSHOP_PRODUCT_DETAIL_PAGE,
                            $pid,
                            $msg);
        }
        else {
            $url = sprintf('%s?inventoryerror&err=%s', 
                            $_SERVER['PHP_SELF'],
                            $msg);
        }
        header("Location: $url\n");
        exit();
    }
    elseif ($err_obj->getCode() != DBCON_ZERO_EFFECT) { //"0 rows were changed"
        pear_error_handler($err_obj);
    }
    exit();
}
