<?php

class cmPaymentGatewayANET_SIM extends cmPaymentGatewayANET {

    /** are we in test mode? */
    var $testmode = CSHOP_PAYMENT_TESTMODE;

    var $gateway_name = 'Authorize.net SIM';

    /** A.net login name
     @protected */
    #var $_anet_login = '54PB5egZ'; # test

    /** A.net transaction key
     @protected */
    #var $_anet_key = '48V258vr55AE8tcg'; # test
     
    /** A.net transaction key
     @protected */
    #var $_anet_md5 = ''; # test

    /** whether we do md5 checks or not
     @protected */
    var $_do_md5_check = true;

    var $_mode_AIM = false;

    var $_transact_url = "https://secure.authorize.net/gateway/transact.dll";

    var $_transact_url_test = 'https://test.authorize.net/gateway/transact.dll';  # test

    var $_receipt_link_url = false;
    var $_relay_response_url = '/cart/anet_sim_notify.php';

    function __construct() {
        if ($this->_anet_login === '' && defined('CSHOP_PAYMENT_CONFIG_FILE')) {
            $this->_autoconfigure_from_file(CSHOP_PAYMENT_CONFIG_FILE);
        }
        if (CSHOP_PAYMENT_TESTMODE) {
            $this->_transact_url  = $this->_transact_url_test;
        }
    }

    function attach_cart(cmCart $cart) {
        $this->_cart =& $cart;
    }

    function attach_user(puredarkUser $user) {
        $this->_user =& $user;
    }

    function construct_request($type=null) {

        switch ($type) {
            case 'authorize':
                $this->_trans_type = 'AUTH_ONLY';
                break;
            case 'capture':
                $this->_trans_type = 'PRIOR_AUTH_CAPTURE';
                break;
            case 'credit':
                $this->_trans_type = 'CREDIT';
                break;
            case 'void':
                $this->_trans_type = 'VOID';
                break;
            default:
                $this->_trans_type = 'AUTH_CAPTURE';
        }
        $this->_trans_time = time();

		$aNetVars = array();
		//common items http://developer.authorize.net/guides/SIM/Submitting_Transactions/Requesting_the_Secure_Hosted_Payment_Form.htm
		$aNetVars["x_login"] 				= $this->_anet_login;
		$aNetVars["x_type"]					=	$this->_trans_type;
		$aNetVars["x_amount"]				=	sprintf("%.02f", $this->_cart->get_grandtotal());
		$aNetVars["x_show_form"]		    =	'PAYMENT_FORM';
		$aNetVars["x_version"]		        =	'3.1';
		$aNetVars["x_description"]		    =	SITE_TITLE . ' [' . SITE_DOMAIN_NAME . ']';


		$aNetVars["x_relay_response"] 		= 	"FALSE";

        // Fingerprint generation params
        $aNetVars['x_fp_timestamp'] = $this->_trans_time;
        $aNetVars['x_fp_sequence'] = $this->_cart->get_id();

		$aNetVars["x_customer_ip"]		    =	$_SERVER['REMOTE_ADDR'];
		
		//Invoice Information (pg 12)
		$aNetVars["x_invoice_num"]			=	$this->_cart->generate_order_token();
		#$aNetVars["x_description"]			=	$this->_self_description;

        if ($userinfo = $this->_user->fetch()) {
            $aNetVars["x_cust_id"] =	$this->_user->get_id();
            $aNetVars['x_email'] = $userinfo['email'];
        }
        if ($shipping = $this->_user->fetchShippingAddr()) {
            $aNetVars['x_ship_to_company']    = substr($shipping['company'], 0, 50);
            if (!empty($shipping['addr2'])) {
                $aNetVars['x_ship_to_address'] = substr($shipping['street_addr'] . ' / ' . $shipping['addr2'], 0, 60);
            }
            else {
                $aNetVars['x_ship_to_address'] = substr($shipping['street_addr'], 0, 60);
            }
            $aNetVars['x_ship_to_city']    = substr($shipping['city'], 0, 40);
            $aNetVars['x_ship_to_state']   = substr($shipping['state'], 0, 40);
            $aNetVars['x_ship_to_zip']     = substr($shipping['postcode'], 0, 20);
            $aNetVars['x_ship_to_country'] = substr($shipping['country'], 0, 60);
            if (false !== strpos($shipping['name'], ' ')) {
                list($firstname, $lastname) = split(' ', $shipping['name'], 2);
                $aNetVars['x_ship_to_first_name'] = substr($firstname, 0, 50);
                $aNetVars['x_ship_to_last_name'] = substr($lastname, 0, 50);
            } else {
                $aNetVars['x_ship_to_last_name'] = substr($shipping['name'], 0, 50);
            }
        }
        if ($totals = $this->_cart->fetch_totals()) {
            if (!empty($totals['shipping']['method'])) {
                $line = array('Shipping', $totals['shipping']['method'], $totals['shipping']['amt']);
                $aNetVars['x_freight'] = join('<|>', $line);
            }
        }

        // http://developer.authorize.net/guides/SIM/Additional_API_Fields/Itemized_Order_Information.htm
        if ($cartitems = $this->_cart->fetch_items()) {
            $aNetVars["x_line_item"] = array();
            foreach ($cartitems as $item) {
                if (strlen($item['product_descrip']) > 31) {
                    $this->log('WARNING: cart line item details violate Authorize.net input limits');
                }
                $line = array( substr($item['product_sku'], 0, 31),
                               substr($item['product_descrip'], 0, 31), 
                               substr($item['product_descrip'], 0, 255), 
                               $item['qty'], 
                               $item['price'], 'Y');
                $aNetVars["x_line_item"][] = join('<|>', $line);
            }

        }

        // http://developer.authorize.net/guides/SIM/Receipt_Options/The_Receipt_Page.htm
        if ($this->_receipt_link_url) {
            $aNetVars['x_receipt_link_method'] = 'POST';
            $aNetVars['x_receipt_link_url'] = $this->_receipt_link_url;
        }
        // http://developer.authorize.net/guides/SIM/Receipt_Options/Relay_Response.htm
        elseif ($this->_relay_response_url) {
            $aNetVars['x_relay_response'] = 'TRUE';
            $aNetVars['x_relay_url'] = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $this->_relay_response_url;
        }
        
        //
        $aNetVars['x_fp_hash'] = $this->_calc_fingerprint($aNetVars['x_amount']);
        $vars = $this->vars_to_html($aNetVars);
        $this->log($vars, __METHOD__);
        return $vars;
    }

    /** calculates the MD5 fingerprint hash for this transaction per A.net API
     * http://developer.authorize.net/guides/SIM/Submitting_Transactions/Custom_Transaction_Fingerprint_Code.htm
     * @return string */
    function _calc_fingerprint($total) {
        $vals = array($this->_anet_login, $this->_cart->get_id(), $this->_trans_time, $total);

        $str = join('^', $vals);
        $str .= '^';

        if (function_exists('hash_hmac'))
            $h = hash_hmac("md5", $str, $this->_anet_key);
        else
            $h = bin2hex(mhash(MHASH_MD5, $str, $this->_anet_key));

        return $h;
    }


    function vars_to_html($vars) {
        $res = '';
        foreach ($vars as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $subv) {
                    $res .= '<input type="hidden" name="'. htmlspecialchars($k) .'" value="'. htmlspecialchars($subv) .'" />';
                }
            }
            else {
                $res .= '<input type="hidden" name="'. htmlspecialchars($k) .'" value="'. htmlspecialchars($v) .'" />';
            }
        }
        return $res;
    }


    /**
     * using n/v pairs from POSTed receipt from a.net, set the various flags 
     * and properties that we will expect down the line
     */
    function parse_response($res) {

        if ($this->do_logging) {
            $msg = "received in parse_response():\n";
            foreach ($res as $k => $v) {
                $msg .= "\t$k=>$v\n";
            }
            $this->log($msg, __METHOD__);
        }
        if ($res['x_response_code'] == "1") {
            $this->_trans_result = 'APPROVED';
            $this->set_trans_id($res['x_trans_id']);
            $this->set_auth_code($res['x_auth_code']);
            $this->_trans_type = $res['x_type'];
            $this->_trans_amount = $res['x_amount'];
        }
        elseif ($res['x_response_code'] == "2") {
            $this->_trans_result = 'DECLINED';
        }
        elseif ($res['x_response_code'] == "3") {
            $this->_trans_result = 'ERROR';
        }
        elseif ($res['x_response_code'] == "4") {
            $this->_trans_result = 'HELD FOR REVIEW';
        }
        $this->_trans_response = serialize($res);
        if (!empty($res['x_avs_code'])) $this->set_avs_result_flags($res['x_avs_code']);
        if (!empty($res['x_cvv2_resp_code'])) $this->set_csc_result_flags($res['x_cvv2_resp_code'], $res['x_cavv_response']);
        $this->_trans_result_msg = $res['x_response_reason_text'];

        return ($res['x_response_code'] == "1");
    }


    function verify_response_receipt($res) {
        if (!$this->_do_md5_check) { 
            return true;
        }
        else {
            if ($res['x_MD5_Hash']) {
                $str = $this->_anet_md5 . $this->_anet_login . $res['x_trans_id']  . number_format($res['x_amount'], 2);
                $hash = md5($str);
                $this->log("MD5 check: $str / $hash", __METHOD__);
                return strtolower($hash) == strtolower($res['x_MD5_Hash']);

            }

        }
    }

}



