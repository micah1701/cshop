<?php
require_once("HTTP/Request.php");



define('CMPAY_AVS_NOMATCH', 0);
define('CMPAY_AVS_UNSUP', 1);
define('CMPAY_AVS_ERR', 2);
define('CMPAY_AVS_INTL', 4);
define('CMPAY_AVS_ZIP', 8);
define('CMPAY_AVS_ADDR', 16);

define('CMPAY_CSC_NOMATCH', 0);
define('CMPAY_CSC_MATCH', 1);
define('CMPAY_CSC_MISSING', 2);
define('CMPAY_CSC_UNSUP', 4);
define('CMPAY_CSC_ERROR', 8);

/** instantiates a payment gateway class based on the given param. 
 *
 * this really makes no sense until we use PHP5 and autoloading
 *
 * TODO this should be an abstract class. This makes no sense by itself, it needs to be fleshed out.
 *
 * $Id: cmPaymentGateway.class.php,v 1.18 2008/07/01 15:39:07 sbeam Exp $
 */
abstract class cmPaymentGateway extends PEAR {

    /* private class var access via getters */
    var $_trans_type;
    var $_trans_amount;
    var $_trans_result;
    var $_trans_result_msg;
    var $_trans_request;
    var $_trans_response;

    /** holds the transaction id returned by the gateway
     * @private */
    var $_gate_transid = null;

    /** holds the auth/approval code returned by the gateway, needed for CAPTURE only
      * @private */
    var $_gate_auth_code = null;

    var $avs_result_flags = null;
    var $csc_match = null;

    /** url to send the request to 
     * @protected */
    var $_transact_url;

    /** url to send test requests to 
     * @protected */
    var $_transact_url_test;

    /** type of currency we are using here 
     * @protected */
    var $currency_code = 'USD';

    /** just a freeform string passed to gateway to describe the order. we put site and shop info in it */
    var $_self_description = '';

    var $gateway_type = 'Manual';

    /* does this type of gateway have Address Verification,or something similar? */
    var $does_AVS = false;

    /** when the order is stored, it passes the CC# back to us for processing.
     * If true, truncate it to the last 4 digits */
    var $do_truncate_stored_ccno = true;

    /** does this gateway allow transactions based on the original payment info, from the admin console (A.net) */
    var $enable_admin_transactions = true;

    /** keep a log of things in CSHOP_LOG_FILE */
    var $do_logging = false;

    function cmPaymentGateway(&$user, &$pay, &$order) {
        echo 'self!?';
        $this->_user =& $user;
        echo 'self!?';
        $this->_payment =& $pay;
        echo 'self!?';
        $this->_order =& $order;
        echo 'self!?';
        $this->_self_description = $this->get_self_description();
        echo 'self!?';
    }


    function factory($type, &$user, &$pay, &$order) {
        switch ($type) {
            case 'anet':
                return new cmPaymentGatewayANET($user, $pay, $order);
            case 'Manual':
                return new cmPaymentGatewayManual($user, $pay, $order);
            default:
                return $this->raiseError("we dont have an implementation for $type!");
        }
    }

    /** create a string to describe this class and the server it is hosted from
     * @return str */
    function get_self_description() {
        $res = '';
        if (defined('SITE_DOMAIN_NAME')) {
            $res .= SITE_DOMAIN_NAME;
        }
        $res .= sprintf("/%s() %s", get_class($this), $this->_VERSION);
        return $res; 
    }


    function get_trans_id() {
        return $this->_gate_transid;
    }

    function get_auth_code() {
        return $this->_gate_auth_code;
    }
    function set_auth_code($code) {
        $this->_gate_auth_code = $code;
    }

    function get_trans_type() {
        return $this->_trans_type;
    }

    function get_trans_amount() {
        return $this->_trans_amount;
    }

    function get_trans_result() {
        return $this->_trans_result;
    }

    function get_trans_result_msg() {
        return $this->_trans_result_msg;
    }

    function get_trans_response() {
        return $this->_trans_response;
    }


    function set_trans_amount($amt) {
        $this->_trans_amount = $amt;
    }

    function set_trans_id($id) {
        $this->_gate_transid = $id;
    }


    /** tell whether or not the CSC code was successfully verified
     * @return bool */
    function get_csc_result() {
        return $this->csc_match;
    }

    function get_trans_request() {
        return $this->_trans_request;
    }



    /** find out what the AVS system said in the response about the given part of the address.
     * @param $type zip,addr,intl,err,unsup
     * @return bool did AVS return true for the given value
     */
    function get_avs_result($type) {

        if ($this->avs_result_flags == NULL) return NULL;

        switch ($type) {
            case 'zip':
                $res = $this->avs_result_flags & CMPAY_AVS_ZIP;
                break;
            case 'addr':
                $res = $this->avs_result_flags & CMPAY_AVS_ADDR;
                break;
            case 'intl':
                $res = $this->avs_result_flags & CMPAY_AVS_INTL;
                break;
            case 'err':
                $res = $this->avs_result_flags & CMPAY_AVS_ERR;
                break;
            default:
                $res = $this->avs_result_flags & CMPAY_AVS_UNSUP;
                break;
        }
        return $res;
    }

    /** check all the info at the gateway server
     * @return true on success, PEAR::Error otherwise
     */
    function authorize() {
        $this->set_trans_amount($this->_order->get_total());
        $req = $this->construct_request('authorize');
        return $this->send($req);
    }

    function auth_capture() {
        $this->set_trans_amount($this->_order->get_total());
        $req = $this->construct_request('auth_capture');
        return $this->send($req);
    }

    function credit($code, $amount) { 
        $this->set_trans_amount($amount);
        $this->set_trans_id($code);
        $req = $this->construct_request('credit');
        return $this->send($req);
    }

    function void($code) { 
        $this->set_trans_id($code);
        $req = $this->construct_request('void');
        return $this->send($req);
    }

    public abstract function get_captured_amount();

    function send($req) { }

    /** should take a reponse direct from the gateway and break it up and
     * report on any errors vie raiseError() or return whaterve is appropriate */
    /* abstract */ function parse_response($res) { }

    /**
     * create a GET URI for request to gateway server using their API
     */
    /* abstract */ function construct_request($type) { }


    /** truncate the passed str to the last 4 chars, if
     * $this->do_truncate_stored_ccno is set - cmOrder calls this right before
     * an order payement info is stored
     *
     * @param str $ccno a credit card number
     * @return str
     */
    function truncate_ccno($ccno) {
        $ccno = preg_replace('/[^\d]/', '', $ccno); // diggits only

        if ($this->do_truncate_stored_ccno) {
            return substr($ccno, -4);
        }
        else {
            return $ccno;
        }
    }

    function get_transaction_options() { }


    function log($msg, $method='unknown') {
        if ($this->do_logging and defined('CSHOP_LOG_FILE')) {
            $byline = "\n===\n" . get_class($this) . "::$method() - " . date('r') . " [" . $_SERVER['REMOTE_ADDR'] . "]\n";
            error_log($byline . trim($msg) . "\n", 3, CSHOP_LOG_FILE);
        }
    }


}

