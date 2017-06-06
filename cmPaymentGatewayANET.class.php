<?php
require_once('cshop/cmPaymentGateway.class.php');

/** the gateway for Authorize.net goes here
 * $Id: cmPaymentGatewayANET.class.php,v 1.9 2008/07/01 19:34:00 sbeam Exp $
 */
class cmPaymentGatewayANET extends cmPaymentGateway {

    /** what does this payment gateway call itself (shows up on order detail pages) */
    var $gateway_name = 'Authorize.net';

    /** are we in test mode? */
    var $testmode = CSHOP_PAYMENT_TESTMODE;

    /** A.net login name
     @protected */
    var $_anet_login = '';

    /** A.net transaction key
     @protected */
    var $_anet_key = '';

    /** A.net MD5 checksum key thing
     @protected */
    var $_anet_md5 = '';

    /** whether we do md5 checks (on the A.net response) or not
     @protected */
    var $_do_md5_check = true;

    /** do we send x_fp_hash and friends to A.net? (I can't get it to work) */
    var $_do_md5_fingerprint = false;


    /** url to send the request to 
     * @protected */
    var $_transact_url = "https://secure.authorize.net/gateway/transact.dll";

    /** url to send test requests to 
      * changed Jul 1 2008, a.net apparently uses the same server now 
     * @protected */
    var $_transact_url_test = "https://secure.authorize.net/gateway/transact.dll";

    /** are we in A.net's password-required mode?  
     * NOTE: this doesnt actually
     * work. We actually get denied (103) every time we use this, whether the
     * A.net console is set to Password-Required Mode or not
     @protected */
    var $_mode_password_required = false;

    /** are we using A.net AIM (Advanced Integration Method) or SIM? */
    var $_mode_AIM = true;

    /* holds the anet password if any. */
    var $_anet_password = '';

    /** version # of this class */
    var $_VERSION = '1.6';

    var $does_AVS = true;

    /* if true, will pretend that all authorization requests succeed. For testing, of course. */
    var $_FAKE_SUCCESS = false;

    /* default type of transaction to run against A.net. Should be AUTH_ONLY or AUTH_CAPTURE */
    var $_default_transaction_type = 'AUTH_ONLY';


    function __construct($user, $pay, $order) {
        $this->_user =& $user;
        $this->_payment =& $pay;
        $this->_order =& $order;
        $this->_self_description = $this->get_self_description();

        if ($this->_anet_login === '' && defined('CSHOP_PAYMENT_CONFIG_FILE')) {
            $this->_autoconfigure_from_file(CSHOP_PAYMENT_CONFIG_FILE);
        }

        if (!isset($this->_default_transaction_type) or $this->_default_transaction_type != 'AUTH_CAPTURE' ) {
            $this->_default_transaction_type = 'AUTH_ONLY';
        }
    }


    function _autoconfigure_from_file($file) {
        if ($fh = @fopen($file, 'r')) {
            while (!feof($fh)) {
                $buf = fgets($fh);
                if (preg_match('/^\s*#/', $buf)) continue;
                $buf = trim($buf);
                if (!empty($buf) && strpos($buf, ':')) {
                    list($k, $v) = preg_split('/\s*:\s*/', $buf);
                    if (isset($this->$k)) {
                        $this->$k = trim($v);
                    }
                }
            }
            fclose($fh);
        }
    }

    /** check all the info at the gateway server
     * @return true on success, PEAR::Error otherwise
     */
    function authorize() {

        $this->set_trans_amount($this->_order->get_total());
        if ($this->_default_transaction_type == 'AUTH_CAPTURE') {
            $req = $this->construct_request('auth_capture');
        }
        else {
            $req = $this->construct_request('authorize');
        }

        if (!empty($this->_FAKE_SUCCESS))
            return true;
        else
            return $this->send($req);
    }

    function auth_capture() {
        $this->set_trans_amount($this->_order->get_total());
        $req = $this->construct_request('auth_capture');
        return $this->send($req);
    }


    function run_transaction($type, $amt=0) {
        $valid_types = array('credit', 'capture', 'void');
        if (!in_array($type, $valid_types)) {
            throw new Exception("invalid transaction type $type");
        }
        elseif (!is_numeric($amt) && $type != 'void') {
            throw new Exception('transaction amount must be a numeric value');
        }
        else {
            $this->set_trans_amount($amt);
            $req = $this->construct_request($type);

            if (!empty($this->_FAKE_SUCCESS)) {
                $this->_trans_result = 'APPROVED';
                $this->_trans_result_msg = 'Faked - not sent to gateway.';
                return true;
            }
            else {
                return $this->send($req);
            }
        }
    }

    function get_trans_id() {
        return $this->_gate_transid;
    }

    function get_auth_code() {
        return $this->_gate_auth_code;
    }

    function get_trans_type() {
        return $this->_trans_type;
    }

    function get_trans_amount() {
        return $this->_trans_amount;
    }


    function get_captured_amount() {
        if (strtoupper($this->get_trans_type()) == 'AUTH_CAPTURE') {
            return $this->_trans_amount;
        }
    }

    function get_trans_result() {
        return $this->_trans_result;
    }

    function get_trans_result_msg() {
        return $this->_trans_result_msg;
    }


    function get_trans_request($safe=false) {
        if (!$safe) { // !
            return $this->_trans_request;
        }
        else {
            $str = $this->_trans_request;
            $str = preg_replace('/x_card_num=([^&]+)/', 'x_card_num=XXXXXXXXXXXXXXXX', $str);
            $str = preg_replace('/x_card_code=([^&]+)/', 'x_card_code=XXX', $str);
            return $str;
        }
    }

    function get_trans_response() {
        return $this->_trans_response;
    }


    function set_trans_amount($amt) {
        $this->_trans_amount = $amt;
    }

    /** find out what the AVS system said in the response about the given part of the address.
     * @param $type zip,addr,intl,err,unsup
     * @return bool did AVS return true for the given value
     */
    function get_avs_result($type) {
        if ($this->avs_result_flags) {
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
            return (!empty($res));
        }
    }

    /** tell whether or not the CSC code was successfully verified
     * @return bool */
    function get_csc_result() {
        return $this->csc_match;
    }



    /** sends the given request string to the payment processor
     * @param $req str a complete GET query
     * @return the response
     */
    function send($req) {
        $url = ($this->testmode)? $this->_transact_url_test : $this->_transact_url;
        $http = new HTTP_Request($url . '?' . $req);
	$http->setMethod(HTTP_REQUEST_METHOD_POST);
        if (PEAR::isError($http->sendRequest())) {
            return $http;
        }
        $res = $http->getResponseBody();
        return $this->parse_response($res);
    }



    /** should take a reponse direct from the gateway and break it up and
     * report on any errors vie raiseError() or return whaterve is appropriate */
    function parse_response($res) {

        $this->_trans_response = $res;

        $res = substr($res, 1, -1);// rip off beginning and end quotes

		//build array of return values
		$rvals = split('"&"', $res);

        $trans_msg = null;


		// grab our result
        /* 1=APPROVED 2=DECLINED 3=ERROR */
		$trans_response_code = $rvals[0];
		$trans_response_subcode = $rvals[1];
        $trans_reason_code = $rvals[2]; // A code used by the system for internal transaction tracking.
        $trans_msg = $rvals[3]; // text of error/result if any

        $this->_gate_auth_code = $rvals[4];
        $trans_avs_code = $rvals[5];
        $this->_gate_transid = $rvals[6];
        $trans_csc_code = $rvals[38];

		$err = "";
		
		// grab return information
		if ( $trans_response_code == 1 )  {
            $this->_trans_result = 'APPROVED';
        }
		elseif ( $trans_response_code == 2 )  {
            $this->_trans_result = 'DECLINED';
			$err = "The transaction was declined";
        }
		elseif ( $trans_response_code == 3 ) {
            $this->_trans_result = 'ERROR';
			$err = "There was an error ($trans_reason_code): $trans_msg";
        }
		else {
            $this->_trans_result = 'UNKNOWN';
			$err = "Unknown error: $trans_response_code ($trans_msg)";
        }

        $this->_trans_result_msg = $trans_msg;

        
        // make a hash of some s3kr3t stuff and compare it to the MD5 val in the response
        if (!$err and $this->_do_md5_check) {
            $s = join('', array($this->_anet_md5, $this->_anet_login, $this->_gate_transid, number_format($this->get_trans_amount(), 2)));
            $myhash = md5($s);
            if (strcasecmp($myhash, $rvals[37]) != 0) {
                $this->_trans_result = 'ERROR';
                $err = "The transaction failed an internal validation check (MD5)";
            }
        }


        $this->set_avs_result_flags($trans_avs_code);
        $this->set_csc_result_flags($trans_csc_code, $rvals[39]);



        if ($err) {
            return $this->raiseError($err);
        }
        else {
            return true;
        }
    }


    /**
     * create a GET URI for request to a.net server using their API
     */
    function construct_request($type) {

        switch ($type) {
            case 'auth_capture':
                $xaction_type = 'AUTH_CAPTURE';
                break;
            case 'authorize':
                $xaction_type = 'AUTH_ONLY';
                break;
            case 'capture':
                $xaction_type = 'PRIOR_AUTH_CAPTURE';
                break;
            case 'credit':
                $xaction_type = 'CREDIT';
                break;
            case 'void':
                $xaction_type = 'VOID';
                break;
            default:
                return $this->raiseError("request type '$type' is not known to me");
        }
        $this->_trans_type = $xaction_type;
        $this->_trans_time = time();

        $billing = $this->_user->fetchBillingAddr();
        $shipping = $this->_user->fetchShippingAddr();

		$aNetVars = array();
		//common items
		//Merchant Account Information (pg 8)
		$aNetVars["x_login"] 				= $this->_anet_login;

        if ($this->_mode_password_required) { // NOTE: this doesnt actually
                            // work. We actually get denied (103) every time we use this, whether
                            // the A.net console is set to Password-Required Mode or not
            $aNetVars["x_password"] = $this->_anet_password;
        }

        /* AIM mode just uses the x_tran_key. Not sure how "Advanced" that is, but whatever */
        if (!empty($this->_anet_key) and $this->_mode_AIM) {
            $aNetVars["x_tran_key"]	= $this->_anet_key;
        }
        if (!empty($this->_anet_duplicate_window)) {
            $aNetVars["x_duplicate_window"]	= $this->_anet_duplicate_window;
        }

		$aNetVars["x_version"]				=	"3.1";
		$aNetVars["x_test_request"]			=	($this->testmode)? 'TRUE' : 'FALSE';

		//Gateway Response Confirmation (pg 9)
		$aNetVars["x_delim_data"] 			= 	"TRUE";
		$aNetVars["x_relay_response"] 		= 	"FALSE";
		$aNetVars["x_delim_char"]			=	"&";			//fields in response separated by a &
		$aNetVars["x_encap_char"]			=	"\"";			//all fields of response enclosed in "

        /** these apply only to SIM mode in A/net */
        if (!$this->_mode_AIM) {
            // Fingerprint generation params
            $aNetVars['x_fp_timestamp'] = $this->_trans_time;
            $aNetVars['x_fp_sequence'] = $this->_order->get_id();
            $aNetVars['x_fp_hash'] = $this->_calc_fingerprint();
        }

		//Customer Name and Billing Address (pg 10)	
        $billname = $this->_name_split($billing['name']);
		$aNetVars["x_first_name"]			=	$billname[0];
		$aNetVars["x_last_name"]			=	(isset($billname[1]))? $billname[1] : '';
		$aNetVars["x_address"]				=	join(' / ', array($billing['street_addr'], $billing['addr2']));
		$aNetVars["x_city"]					=	$billing['city'];
		$aNetVars["x_state"]				=	$billing['state'];
		$aNetVars["x_zip"]					=	$billing['postcode'];
		$aNetVars["x_country"]				=	$billing['country'];
        if (!empty($billing['phone'])) {
            $billing['phone'] = preg_replace('/[^\d]/','',$billing['phone']);
            $aNetVars["x_phone"]			=	$billing['phone'];
        }

		//Additional Customer Data (pg 11)
		$aNetVars["x_cust_id"]				=	$this->_user->get_id();
		$aNetVars["x_customer_ip"]		    =	$_SERVER['REMOTE_ADDR'];
		
		//Email Settings (pg 11)
		$aNetVars["x_email"]				=	$this->_user->get_email();
		$aNetVars["x_email_customer"]		=	"FALSE"; // ??
		$aNetVars["x_merchant_email"]		=	"";
		
		//Invoice Information (pg 12)
		$aNetVars["x_invoice_num"]			=	$this->_order->fetch_token();
		$aNetVars["x_description"]			=	$this->_self_description;

		//Customer Shipping Address (pg 10)	
        $shipname = $this->_name_split($shipping['name']);
		$aNetVars["x_ship_to_first_name"]			=	$shipname[0];
		$aNetVars["x_ship_to_last_name"]			=	(isset($shipname[1]))? $shipname[1] : '';
		$aNetVars["x_ship_to_address"]				=	join(' / ', array($shipping['street_addr'], $shipping['addr2']));
		$aNetVars["x_ship_to_city"]					=	$shipping['city'];
		$aNetVars["x_ship_to_state"]				=	$shipping['state'];
		$aNetVars["x_ship_to_zip"]					=	$shipping['postcode'];
		$aNetVars["x_ship_to_country"]				=	$shipping['country'];

		//Transaction Data (pg 13)	
		$aNetVars["x_amount"]				=	$this->get_trans_amount();
		$aNetVars["x_currency_code"]		=	$this->currency_code;
		$aNetVars["x_type"]					=	$xaction_type;
		$aNetVars["x_recurring_billing"]	=	"NO"; // ?
		$aNetVars["x_method"]				=	"CC";  // ?

		// credit card information
        if ($type == 'auth_capture' or $type == 'authorize') {
            $aNetVars["x_card_num"]			=	$this->_payment->get_ccno();
            $aNetVars["x_exp_date"]			=	$this->_payment->get_ccexp('m/y');
            $aNetVars["x_card_code"]		=	$this->_payment->get_csc();
        }
        else {
            $aNetVars["x_trans_id"]		    =	$this->_order->fetch_payment_transaction_id();

            if ($type == 'capture') { // anet needs this x_auth_code from the original AUTH_ONLY to process a capture.
                $aNetVars["x_auth_code"]	=	$this->_order->fetch_payment_auth_code();
            }
            if ($type == 'credit') { // anet needs the last 4 of the original cc# to issue a credit
                $aNetVars["x_card_num"]			=	$this->_order->get_header('cc_number');
            }
        }

        $req = '';
		foreach ( $aNetVars as $k=>$v ) {
			if ($v) {
				$req .= urlencode($k) ."=" . urlencode($v) . "&";
            }
        }
        $this->_trans_request = $req;
        return $req;
    }


    /** utility function to split a full name field like we use into a.net's
     * first,last format that they prefer for whatever reason 
     * @param $sre name to split
     * @return array (first,last)
     * @todo WTF - if we are using A.net then we really need both fields.
    */
    function _name_split($str) {
        if (!strpos($str, " ")) {
            return array("nofirstnameprovided", $str);
        }
		return split(" ", $str, 2);
    }

    /* determine what transactions can be currently run on this order from the order detail page in the control.
     * if total captured amount is less than the authorized amount, allow 
     * capture. Allow Void and Credit, unless one of those has been used 
     * already. In fact, Void and Credit can only be used once - once they are 
     * used, the admin is shut off, for safety/headache reasons.
     *
     * this is called from the order detail page in control, only
     *
     * @return assoc array
     */
    function get_transaction_options() {

        $gate_opts = array();
        //
        // we can only do transactions on a.net
        if (!$this->enable_admin_transactions) {
            return false;
        }

        $amt_authorized = 0;
        $amt_billed = 0;
        $x_open = true;
        if ($transactions = $this->_order->fetch_transaction_summary()) {
            foreach ($transactions as $t) {
                if ($t['trans_type'] == 'AUTH_ONLY' && $t['trans_result'] == 'APPROVED') {
                    $amt_authorized = $t['trans_amount'];
                }
                elseif ($t['trans_type'] == 'PRIOR_AUTH_CAPTURE' && $t['trans_result'] == 'APPROVED') {
                    $amt_billed += $t['trans_amount'];
                }
                elseif (($t['trans_type'] == 'VOID' or $t['trans_type'] == 'CREDIT') && $t['trans_result'] == 'APPROVED') {
                    $x_open = false;
                }
            }
        }
        if ($x_open) {
            if ($amt_billed < $amt_authorized) {
                $gate_opts['capture'] = 'Capture';
            }
            $gate_opts['void'] = 'Void';
            $gate_opts['credit'] = 'Credit';
            return $gate_opts;
        }
    }


    /** 
     * set AVS Response code into a set of binary flags via the defines set up above
     * @param $trans_avs_code code returned from a.net api response
     * @return void
     */
    function set_avs_result_flags($trans_avs_code) {
        switch ($trans_avs_code) {
            case 'A':
                $this->avs_result_flags = CMPAY_AVS_ADDR;
                break;
            case 'B':
                $this->avs_result_flags = CMPAY_AVS_ERR | CMPAY_AVS_UNSUP;
                break;
            case 'E':
            case 'R':
                $this->avs_result_flags = CMPAY_AVS_ERR;
                break;
            case 'G':
                $this->avs_result_flags = CMPAY_AVS_INTL;
                break;
            case 'N':
                $this->avs_result_flags = CMPAY_AVS_NOMATCH;
                break;
            case 'S':
            case 'U':
                $this->avs_result_flags = CMPAY_AVS_UNSUP;
                break;
            case 'W':
            case 'Z':
                $this->avs_result_flags = CMPAY_AVS_ZIP;
                break;
            case 'X':
            case 'Y':
                $this->avs_result_flags = CMPAY_AVS_ZIP | CMPAY_AVS_ADDR;
                break;
            case 'P':
                $this->avs_result_flags = null;
        }
    }


    /** set CVV response code */
    function set_csc_result_flags($trans_csc_code, $reason) {
        if ($trans_csc_code == 'M') { // match OK
            $this->csc_match = true;
        }
        else {
            if ($trans_csc_code == 'P') { // "not processed"
                $this->csc_match = true;
            }
            else { // either no match or system couldnt process
                $this->csc_match = null;
            }
            switch ($reason) { // "Cardholder Authentication Verification Value (CAVV) Response Code"
                case '0':
                    $this->csc_result = 'CAVV not validated because erroneous data was submitted';
                    break;
                case '1':
                    $this->csc_result = 'CAVV failed validation';
                    break;
                case '3':
                    $this->csc_result = 'CAVV validation could not be performed; issuer attempt incomplete';
                    break;
                case '4':
                    $this->csc_result = 'CAVV validation could not be performed; issuer system error';
                    break;
                case '7':
                    $this->csc_result = 'CAVV attempt - failed validation - issuer available (U.S.-issued card/non-U.S. acquirer)';
                    break;
                case '8':
                    $this->csc_result = 'CAVV attempt - passed validation - issuer available (U.S.-issued card/non-U.S. acquirer)';
                    break;
                case '9':
                    $this->csc_result = 'CAVV attempt - failed validation - issuer unavailable (U.S.-issued card/non-U.S. acquirer)';
                    break;
                case 'A':
                    $this->csc_result = 'CAVV attempt - passed validation - issuer unavailable (U.S.-issued card/non-U.S. acquirer)';
                    break;
                case 'B':
                    $this->csc_result = 'CAVV passed validation, information only, no liability shift';
                    break;
                default: 
                    $this->csc_result = 'CAVV not validated';
            }
        }
    }


}
