<?php
/** instantiates a payment class based on the given param. 
 *
 * this really makes no sense until we use PHP5 and autoloading
 *
 * $Id: cmPaymentCC.class.php,v 1.7 2007/05/10 16:26:07 sbeam Exp $
 */

class cmPaymentCC extends db_container {

    var $_table = 'cm_paymentcc';
    var $colmap = array('cctype' => array('Card Type', 'select', array(), 1),
                        'ccno' => array('Card Number', 'text', null, array('size'=>22, 'maxlength'=>32), 1),
                        'ccexp' => array('Expiry Date', 'date', 12, array('year_end'=>'+12', 'suppress_day'=>true), 1));

    // ref to a cmUser object
    var $user;

    /** what we call this type of payment usually */
    var $method_name = 'Credit Card'; 

    /** we hold the CSC code for the card only in the _SESSION by this key - named something random for (sec|obsc)urity */
    var $_csc_sesskey = '_cm_HbT';
    
    /* kinds of CCs we can put up wit */
    var $cc_types = array('VISA' => 'Visa',
                           'MC'   => 'MasterCard',
                           'AMEX' => 'American Express',
                           'DISC' => 'Discover',
                            );

    function get_colmap() {
        /* add a field for the CSC - this is not really saved anywhere so it is not in the colmap */
        $this->colmap['csc1'] = array('Card Security Code', 'text', null, array('size'=>4), true);
        $this->colmap['cctype'][2] = $this->get_payment_types();
        return $this->colmap;
    }

    /** function to get avail payment types (cards accepted). If CSHOP_CC_ACCEPTED is defined,
     * it is used as a comma-sep list of the keys - the values are still looked up in this->cc_types
     * @return array
     */
    function get_payment_types()
    {
        if (defined('CSHOP_CC_ACCEPTED')) {
            $cards = preg_split('/\s*,\s*/', CSHOP_CC_ACCEPTED);
            foreach ($this->cc_types as $k => $v) {
                if (!in_array($k, $cards)) {
                    unset($this->cc_types[$k]);
                }
            }
        }
        return $this->cc_types;
    }


    /**
     * util function to retrieve ccno
     * @return str
     */
    function get_ccno() {
        return $this->get_header('ccno');
    }


    /**
     * util function to retrieve ccno
     * @return str
     */
    function get_cctype() {
        return $this->get_header('cctype');
    }

    /**
     * util function to retrieve ccexp, with formatting option
     * @param $fmt string optional - php::date() format for returned date
     * @return str
     */
    function get_ccexp($fmt='m/Y') {
        $exp = $this->get_header('ccexp');
        if ($fmt) {
            $exp = date($fmt, strtotime($exp));
        }
        return $exp;
    }


    /**
     * util function to get the CSC value. This should only ever live in the
     * _SESSION for security reasons. Technically, it should not even be stored
     * there (because it is never supposed to be written to disk, period) but
     * we are going to be very well behaved and erase it soon as the payment is
     * approved or rejected
     * @return str
     */
    function get_csc() {
        if (!empty($_SESSION[$this->_csc_sesskey])) {
            return $_SESSION[$this->_csc_sesskey];
        }
    }

    /**
     * util function to set the CSC value which just saves it to session
     * @param str the value from user
     */
    function set_csc($csc) {
        $_SESSION[$this->_csc_sesskey] = $csc;
    }


    /**
     * check cc#, expiry in vals and return errors if there seems to be a
     * problem
     * @param $vals ccno, ccexp
     * @return array one or more errors
     */
    function check_values(&$vals) {

        $errs = array();

        if (!empty($vals['ccno']))
            $vals['ccno'] = $this->clean_ccno($vals['ccno']);

        // TODO return error codes, not strings
        if (empty($vals['ccno']) or empty($vals['ccexp'])) {
            $this->colmap['ccno'][5] = 2;
            $this->colmap['ccexp'][5] = 2;
            $errs[] = 'Credit card or expiry date was missing';
        }
        if (!$this->_ccno_validate($vals['ccno'])) {
            $this->colmap['ccno'][5] = 2; // set ccno field to show error
            $errs[] = 'The card number is invalid. Please check the number and try again';
        }
        if (!$this->_check_expiry($vals['ccexp'])) {
            $this->colmap['ccexp'][5] = 2; // set ccno field to show error
            $errs[] = 'The card had expired. Please check your expiration date';
        }

        if (count($errs)) {
            return $errs;
        }
    }

    /**
     * run the special ccno validation algo on the val
     * @param string  a cc no
     * @return bool
     */
    function _ccno_validate($ccno) {

        $sum = 0;
        $digits = '';

        // Reverse and clean the number
        $ccno = strrev ($this->clean_ccno($ccno));

        // VALIDATION ALGORITHM
        // Loop through the number one digit at a time
        // Double the value of every second digit (starting from the right)
        // Concatenate the new values with the unaffected digits
        for ($i = 0; $i < strlen ($ccno); ++$i) {
            $digits .= ($i % 2) ? $ccno[$i] * 2 : $ccno[$i];
        }
        // Add all of the single digits together
        for ($i = 0; $i < strlen ($digits); ++$i) {
            $sum += $digits[$i];
        }

        // Valid card numbers will be transformed into a multiple of 10
        $res = ($sum % 10 == 0);
        return $res;
    }

    /** remove all non-numeric from string
     * @param string
     * @return string
     */
    function clean_ccno($ccno) {
        return preg_replace('/[^\d]/', '', $ccno);
    }


	/**
	 * make sure expire date of the card is in the future
     * @param $exp str expiry date in MMYY or YYYY-MM-DD or YYYY-MM formats
	 * @return bool
	 */
	function _check_expiry($exp) {
        if (preg_match('/^\d{2}\d{2}$/', $exp, $m)) {
            $y = $m[1];
            $m = $m[2];
        }
        elseif (preg_match('/^(\d{4})-(\d{2})/', $exp, $m)) {
            $y = $m[1];
            $m = $m[2];
        }
        else {
            return $this->raiseError("invalid expiry format $exp");
        }

        if ($m > 11) { $m = 0; $y++; } // add one to the month
        $m++;
        $exptime = mktime(0,0,0,$m,0,$y); // gets the last day of the previous month!

        return ($exptime >= time());
	}
}


