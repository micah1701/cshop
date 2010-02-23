<?php
/**
 * abstract cmShipMethod class for various ways of shipping items
 *
 * $Id: cmShipMethod.class.php,v 1.5 2008/04/22 20:35:32 sbeam Exp $
 */
/* abstract */ class cmShipMethod extends PEAR {
    /** types of shipping this shipper provides, CODE => 'Name' - i.e. '2DA' => '2nd Day Air' */
    var $ship_types = array();

    /** keys from the above that we are actually using in this implementation */
    var $allowed_ship_types = array();

    /** the weight of the package in whatver units **/
    var $_weight = null;

    /** is the destination a commercial or residential addr? */
    var $_is_residential = false;

    /* does this shipment qualify for free shipping? */
    var $qualifies_freeship = false;

    /** the nice english name of this shipping method **/
    var $_name = '';

    var $_origin = array();
    var $_dest = array();

    /**
     * give the nice english name of this shipping method
     */
    function get_name() {
        return $this->_name;
    }

    /**
     * override this method to provide a simple list of quotes. We don't worry about
     * zones or weight */
    function get_all_quotes(&$cart) {
    }

    function get_quotes(&$cart) {
        $coup = $cart->get_coupon_object();
        $this->has_full_coupon = ($coup->header and $coup->header['percent_off'] == '100');
        $this->qualifies_freeship = ($this->get_freeship_threshold() and $cart->get_subtotal() > $this->get_freeship_threshold());

        if ($this->has_full_coupon or $this->qualifies_freeship) {
            return $this->freeship_token();
        }
        else {
            return $this->get_all_quotes($cart);
        }
    }

    /**
     * @deprecated this has to be moved to cmShipping() so we can be DRY and retrieve it from DB if needed
     */
    function get_freeship_threshold() {
        trigger_error("cmShipMethod::get_freeship_threshold() is deprecated. It is moved to cmShipping.class.php", E_USER_WARNING);
    }


    function set_origin($postcode, $country) {
        $this->_origin['postcode'] = $postcode;
        $this->_origin['country'] = $country;
    }

    function set_destination($postcode, $country, $addr) {
        $this->_dest['postcode'] = $postcode;
        $this->_dest['country'] = $country;
        $this->_dest['addr'] = $addr;
    }

    function set_item_count($num) {
        $this->_num_items = $num;
    }

    function set_subtotal($tot) {
        $this->_subtotal = $tot;
    }

    function set_weight($wt) {
        if (!is_numeric($wt)) {
            trigger_error("weight is not number", E_USER_ERROR);
        }
        $this->_weight = $wt;
    }


    function set_container($str) {
    }

    function set_residential($bool) {
        $this->_is_residential = ($bool)? true : false;
    }


    /**
     * return token to controller indicating free shipping compatible w/ our quote format
     * @protected
     */
    /* protected */ function freeship_token() {
        return array('FREE SHIPPING' => '0');
    }




}
