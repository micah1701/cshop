<?php
/**
 * an abstract class to handle a tax calculation for a certain type of tax (US State, VAT, EU, etc?)
 *
 * $Id: cmTaxCalculator.class.php,v 1.6 2008/03/19 22:54:05 sbeam Exp $
 */
/* abstract */ class cmTaxCalculator extends PEAR {

    /** @protected the name of this tax */
    var $_tax_name = 'Generic Tax';

    /** @protected the 'code' that matches this tax */
    var $_tax_code = 'GT';

    /** @protected the rate of the tax as a float, or an array mapping locale codes from the $_states array to floats */
    var $_tax_rate = 1.00;
    /*
     * e.g. var $_tax_rate = array('MI'=>6, 'NJ'=>7, 'TN'=>7, 'AZ'=>5.6);
     */

    /** @protected ISO codes of all countries this tax applies to */
    var $_countries = array();

    /** @protected codes of all states/provinces this tax applies to */
    var $_states = array();

    /* e.g. ----
     * var $_states = array('US'=>array('MI' => array('MI', 'michigan', 'REGEX:^mic'),
     *                                  'NJ' => array('NJ', 'new jersey', 'REGEX:^new\s*j', 'REGEX:^N\.?J'), 
     *                                  'TN' => array('TN', 'tennessee', 'REGEX:^ten'))); 
     */




    /** @private where is the shipment going? */
    var $_dest_country = null;
    var $_dest_state = null;

    /** @private amount we are taxing for this tax type */
    var $_taxable_amt = 0;

    /** @private amount of shipping for the order. Some states need to include this, some don't */
    var $_shipping_amt = 0;

    /* remember key of the expression in $_states that matched */
    var $_matched_state = null;

    /** array of locale codes that should have the shipping included in tax total, or TRUE for all */
    var $include_shipping_cost = null;

    function calculate() {
        if ($this->_should_apply()) {
            $amt = $this->_taxable_amt;
            if ($this->_should_include_shipping()) {
                $amt += $this->_shipping_amt;
            }
            return number_format(($amt * ($this->get_tax_rate()/100)) , 2);
        }
    }


    /**
     * use pattern matching to decide if we need to apply tax to the person residing in this country/state
     */
    function _should_apply() {
        if (!in_array($this->_dest_country, $this->_countries)) {
            return false;
        }

        if (count($this->_states) and isset($this->_states[$this->_dest_country])) {
            foreach ($this->_states[$this->_dest_country] as $k => $expressions) {
                foreach ($expressions as $expr) {
                    if (preg_match('/^REGEX:(.*)/', $expr, $m)) {
                        $regex = '/' . $m[1] . '/i';
                        if (preg_match($regex, $this->_dest_state)) {
                            $this->_matched_state = $k;
                            return true;
                        }
                    }
                    else {
                        if (trim(strtoupper($this->_dest_state)) == trim(strtoupper($expr))) {
                            $this->_matched_state = $k;
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * decide if we should include the shipping cost in the calc. If 
     * $include_shipping_cost is an array, see if the current "state" is in the 
     * arr. Otherwise return true if it is non-empty */
    function _should_include_shipping() {
        if (empty($this->include_shipping_cost)) {
            return false;
        }
        else {
            if (is_array($this->include_shipping_cost)) {
                if (!in_array($this->_matched_state, $this->include_shipping_cost)) {
                    return false;
                }
            }
        }
        return true;
    }


    function set_amount($amt) {
        if (!is_numeric($amt)) {
            return $this->raiseError("taxable amount must be numeric");
        }
        $this->_taxable_amt = $amt;
    }

    function set_destination($country, $state=null) {
        if ($state) {
            $this->_dest_state = strtoupper($state);
        }
        $this->_dest_country = strtoupper($country);
    }

    function set_shipping_cost($amt) {
        if (!is_numeric($amt)) {
            return $this->raiseError("shipping amount must be numeric");
        }
        $this->_shipping_amt = $amt;
    }

    function get_tax_rate() {
        if (is_array($this->_tax_rate)) {
            if (isset($this->_tax_rate[$this->_matched_state])) {
                return $this->_tax_rate[$this->_matched_state];
            }
        }
        elseif (is_numeric($this->_tax_rate)) {
            return $this->_tax_rate;
        }

        return 0;
    }

    function get_tax_name() {
        return $this->_tax_name;
    }

    function get_tax_code() {
        return $this->_tax_code;
    }

}
