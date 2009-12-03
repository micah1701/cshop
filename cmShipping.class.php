<?
/**
 * an abstract class to handle shipping calculations
 *
 * TODO think about this and rewrite. This is so simplified for ASS, we just use ass_shipmethod::calculate()
 *
 * $Id: cmShipping.class.php,v 1.21 2008/05/02 18:17:08 sbeam Exp $
 */
/* abstract */ class cmShipping extends PEAR {

    /** set to the zip code where package is coming from */
    var $_origin_zip = null;

    /** set to the country where package is coming from */
    var $_origin_country = null;

    /** the destination address of the order, should correspond to $user->addr->colmap */
    var $_dest;

    /** list of shipping subclasses we can implement */
    var $available_methods = array();

    /** does this shipment qualify for free shipping? */
    var $qualifies_freeship = false;

    /** do ALL of the items in the cart seems to qualify for free shipping (set as flag on product or a category)? */
    var $all_cartitems_qualify_freeship = false;

    /** did one of the shipper classes give us any beef? */
    var $has_calculation_error = false;

    /** list of calculation errors returned by shipper classes */
    var $calculation_errors = array();

    var $adders = array();

    /** narrow down available shipping methods returned by each api calculator (UPS, Fedex, etc) class=>(type,type2) */
    var $allowed_ship_types = array();

    /**
     * setter for destination addr */
    function set_destination($addr) {
        $this->_dest = $addr;
    }

    /**
     * get available quotes for the user based on their location and the contents of their cart.
     * @static
     */
    /* static */ function get_quotes(&$cart) {
        return $this->get_all_quotes($cart);
    }


    /**
     * fetch a list of ALL the available shipmethods for this order. Finds the
     * available ship methods for each item in the cart. If they all qualify
     * for free shipping, then return the free shipping token. Otherwize return
     * an array of the UNION of all available methods for each product.
     * @param cmCart
     * @return array
     * @todo figure out how to offer the INTERSECTION of all methods
     */
    function get_available_methods(&$cart) {
        $pids = array();
        $methods = array();
        $free = true;
        foreach ($cart->fetch_items() as $item) { // TODO - too expensive.
            $pids[] = $item['product_id'];
        }

        if (count($pids) == 0) return $pids;

        /* get list of ship classes defined for each product in the cart. */
        $sql = sprintf("SELECT ship.id, ship.class_map, ship.is_free, ship.adder 
                        FROM cm_ship_class ship, cm_products p 
                        WHERE p.cm_ship_class_id = ship.id AND p.id in (%s)",
                        join(',', $pids));
        $res = $cart->db->query($sql);

        // TODO should this take place regardless of whether products shipclass matched?
        if ($res->numRows() == 0) { // look in categories table now for ship methods
            $sql = sprintf("SELECT DISTINCT c.id FROM cm_products_categories pc, cm_categories c WHERE c.id = pc.cm_categories_id
                            AND pc.cm_products_id IN (%s)",
                            join(',', $pids));
            $catids = $cart->db->getCol($sql);

            $sql = sprintf("SELECT ship.id, ship.class_map, ship.is_free, ship.adder
                            FROM cm_ship_class ship, cm_categories c 
                            WHERE c.ship_class_id = ship.id AND c.id in (%s)",
                            join(',', $catids));
            $res = $cart->db->query($sql);
        }

        if (!PEAR::isError($res)) {
            while ($row = $res->fetchRow()) {
                if (!$row['is_free']) {
                    $free = false;
                }
                $these = split(',', $row['class_map']); // can be a commasep set of methods?
                foreach ($these as $m) {
                    if (!in_array($m, $methods)) $methods[] = $m;
                    $this->adders[$row['id']] = $row['adder'];
                }
            }
        }


        #if ($free) { return 'FREE'; } // everything in the cart was free!
        if ($free) {
            $this->all_cartitems_qualify_freeship = true;
        }

        foreach ($methods as $m) {
            $this->available_methods[] = sprintf('cmShipMethod_%s', $m);
        }

        return $this->available_methods;
    }

    /**
     * return the origin zip/postal code
     */
    function get_origin_zip() {
        if ($this->_origin_zip) {
            return $this->_origin_zip;
        }
        elseif (defined('CSHOP_SHIPPING_ORIGIN_ZIP')) {
            return CSHOP_SHIPPING_ORIGIN_ZIP;
        }
        else {
            trigger_error("cmShipping::_origin_zip is not defined");
        }
    }

    /**
     * return the origin countrycode
     */
    function get_origin_country() {
        if ($this->_origin_country) {
            return $this->_origin_country;
        }
        elseif (defined('CSHOP_SHIPPING_ORIGIN_COUNTRY')) {
            return CSHOP_SHIPPING_ORIGIN_COUNTRY;
        }
        else {
            trigger_error("cmShipping::_origin_country is not defined");
        }
    }

    /* get a quote for each available shipping method
    * @param $cart ref to a fully loaded cmCart object
    * @return float
    */
    function get_all_quotes(&$cart) {
        $this->quotes = array();
        $shipmethods = $this->get_available_methods($cart);

        /* decide how much to add to the total of each quote returned. Adders should be
         * avail from when get_available_methods() was called above */
        $adder = 0;
        if (count($this->adders)) {
            foreach ($this->adders as $id => $amt) {
                $adder += $amt;
            }
        }


        foreach ($shipmethods as $meth) {
            include_once("$meth.class.php");
            if (class_exists($meth)) {
                /* setup and call shipmethod_$meth class to get one or more
                 * quotes from it, based on the cart contents and the adder we
                 * have */
                $sm = new $meth();
                $sm->set_weight($cart->get_weight());
                $sm->set_item_count($cart->count_item_qty());

                if (isset ($this->allowed_ship_types[$meth])) {
                    $sm->allowed_ship_types = $this->allowed_ship_types[$meth];
                }

                $cart_total = $cart->get_shipping_subtotal();

                $sm->set_subtotal($cart_total);
                $sm->set_destination($this->_dest['postcode'], $this->_dest['country'], $this->_dest['street_addr']);
                $sm->set_origin($this->get_origin_zip(), $this->get_origin_country());

                /* ok $sm, go get some quotes, however you think best */
                $these_quotes = $sm->get_all_quotes($cart, $adder);

                if (PEAR::isError($these_quotes)) {
                    $this->has_calculation_error = true;
                    $this->calculation_errors[] = $these_quotes->getMessage();
                    return $these_quotes;
                }
                else {
                    /** hack to currency format each option value (leaving the key alone) */
                    foreach ($these_quotes as $k => $v) {
                        /* $sm should return quotes as "METHOD NAME (23.99)" format */

                        /* match numeric/. value in ()s */
                        preg_match('/\(([\d.]+)\)/', $v, $m); //better had!

                        $price = $m[1]; // the price in base currency (USD) in 00.00 format

                        // cart knows what currency they want to see and how to convert
                        $price = $cart->currency_format($price);

                        $price = str_replace('$', '\$', $price);// not sure what this is for

                        // replace the value in ()s with the formatted price val
                        $these_quotes[$k] = preg_replace('/\([\d.]+\)/', '('.$price.')', $v);
                    }
                    // merge results into our growing list of quotes from each method
                    $this->quotes = $this->quotes + $these_quotes;
                }
            }
        }
        /* this is strange legacy code from DEKAL era to see if any of the ship quotes 
         * returned at this point seem to be FREE. If so, and if the cartitems 
         * all qualify for free shipping, then this person can have free 
         * shipping as an option. Otherwise, they must pay because one 
         * of the cart items isn't freeship */

        // replace $0.00 in ANY quote with the word 'FREE!' - not real pretty
        foreach ($this->quotes as $k => $v) {
            if ($this->quotes[$k] != ($this->quotes[$k] = preg_replace('/\(\$0\.00\)/', '(FREE!)', $v))) {
                /* there is only free ship if *everything* in the cart is free shipping, right?! */
                if ($this->all_cartitems_qualify_freeship) {
                    $this->qualifies_freeship = true; // set the flag so the user gets a nice message or whatever.
                }
                else { /* otherwise we dont want to show free shipping option bc user might be tempted to use it */
                    unset($this->quotes[$k]);
                }
            }
        }

        /* check if the cart total exceeds the free shipping threshold. If so, add an option for that. */
        if ($this->get_freeship_threshold() and $cart->get_subtotal() >= $this->get_freeship_threshold()) {
            $this->qualifies_freeship = true; // set the flag so the user gets a nice message or whatever.
            $this->quotes = $this->freeship_token() + $this->quotes;
        }

        return $this->quotes;
    }

    /* util function for getting the method and the cost out of one of our
     * little tokens. Used in shipping picker script
     * $sm obj should have returned quotes in "METHOD NAME (23.99)" format
     * @param $str str a string, in "METHOD NAME (23.99)" format
     * @return array
     */
    function parse_shipmethod($str) {
        if (preg_match('/^([^(]+)\(\$?([\d.]+)\)/', $str, $m)) 
            return array($m[1], $m[2]);
    }


    /**
     * return token to controller indicating free shipping compatible w/ our quote format
     * @protected
     */
    /* protected */ function freeship_token() {
        return array('FREE SHIPPING (0)' => 'FREE SHIPPING!');
    }

    /**
     * get list of available shipping classes in the db
     * @static
     */
     /* static */ function fetch_ship_class_opts(&$pdb)
     {
         $opts = array();
         $cols = array('id','name');

         $sql = sprintf("SELECT %s FROM cm_ship_class ORDER BY name",
                        join(',', $cols));
         $res = $pdb->query($sql);

         while ($row = $res->fetchRow()) {
             $opts[$row['id']] = $row['name'];
         }

         return $opts;
     }

     function get_adder($meth) {
         $sql = "SELECT adder FROM cm_ship_class WHERE class_map";
     }


    function get_freeship_threshold() {
        if (!empty($this->freeship_threshold)) {
            return $this->freeship_threshold;
        }
        elseif (defined('CSHOP_SHIPPING_FREESHIP_THRESHOLD')) {
            return CSHOP_SHIPPING_FREESHIP_THRESHOLD;
        }
        else { return 0; }
    }



    /*
     * return a list of country ISO codes that this implementation may ship to. 
     * if we can ship to any country, returns null
     * @return array or null
     */
    function get_avail_countries()
    {
        return null;
    }
}





