<?php
require_once('circusShop/cmPaymentGateway.class.php');

/** the gateway for CyberSource goes here
 * $Id: cmPaymentGatewayCyberSource.class.php,v 1.4 2008/07/01 15:39:07 sbeam Exp $
 */
class cmPaymentGatewayCyberSource extends cmPaymentGateway {

    /** what does this payment gateway call itself (shows up on order detail pages) */
    var $gateway_name = 'CyberSource';

    /** are we in test mode? */
    var $testmode = true;

    /** cybso login name
     @protected */
    var $_cybs_login = 'assmunch';

    /** cybs .ini file, rel to SITE_ROOT
     @protected */
    var $_cybs_ini = 'config/cybs.ini';

    /** url to send the request to 
     * @protected */
    #var $_transact_url = "https://secure.authorize.net/gateway/transact.dll";

    /** url to send test requests to 
     * @protected */
    #var $_transact_url_test = "https://ics2wstest.ic3.com/commerce/1.x/transactionProcessor";

    /** type of currency we are using here 
     * @protected */
    var $currency_code = 'USD';

    /** holds the transaction id returned by A.net
     * @private */
    var $_cybs_transid = null;

    /** holds the auth/approval code returned by A/net, needed for CAPTURE only
      * @private */
    var $_cybs_auth_code = null;

    /** just a freeform string passed to A.net to describe the order. we put site and shop info in it */
    var $_self_description = '';

    /** are we in A.net's password-required mode?
     @protected */
    var $_mode_password_required = true;

    /** are we using A.net AIM (Advanced Integration Method) or SIM? */
    var $_mode_advanced_itegration = true;

    /** version # of this class */
    var $_VERSION = '1.0';

    var $does_AVS = true;

    function cmPaymentGatewayCyberSource(&$user, &$pay, &$order) {
        $this->_user =& $user;
        $this->_payment =& $pay;
        $this->_order =& $order;
        $this->_self_description = $this->get_self_description();
    }




    function get_trans_request($safe=false) {
        if (!$safe) { // !
            return serialize($this->_trans_request);
        }
        else {
            $a = $this->_trans_request;
            $a['card_accountNumber']      = ':REMOVED:';
            $a['card_cvNumber']           = ':REMOVED:';
            return serialize($a);
        }
    }


    function get_trans_response() {
        return serialize($this->_trans_response);
    }






    /** sends the given request string to the payment processor
     * @param $req str a complete GET query
     * @return the response from Mr. Gateway
     */
    function send($request) {
        $config = cybs_load_config( SITE_ROOT_DIR . '/' . $this->_cybs_ini );
        $reply = array();
        $status = cybs_run_transaction( $config, $request, $reply );

        return $this->parse_response($status, $reply);
    }




    /** should take a reponse direct from the gateway and break it up and
     * report on any errors vie raiseError() or return whaterve is appropriate */
    function parse_response($status, $reply) {

        $this->_trans_response = $reply;
        $trans_msg = null;
        $err = "";

        // grab our result
        $trans_response_code = $status;

        // grab return information
        if ( $trans_response_code != CYBS_S_OK )  {
            $this->_trans_result = 'ERROR';
            switch ($trans_response_code) {
                case CYBS_S_PHP_PARAM_ERROR:
                    $err = "CYBS: There were incorrect or missing parameters in cybs*() call";
                    break;
                case CYBS_S_PRE_SEND_ERROR:
                    $err = "CYBS: There is a configuration error: " . $reply[CYBS_SK_ERROR_INFO];
                    break;
                case CYBS_S_SEND_ERROR:
                    $err = "CYBS: There is a SEND error: " . $reply[CYBS_SK_ERROR_INFO];
                    break;
                default:
                    $err = "CYBS: CRITICAL error: " . $reply[CYBS_SK_ERROR_INFO] . serialize($reply);
            }
        }
        else {
            if ( $reply['decision'] == 'ACCEPT' )  {
                $this->_trans_result = 'APPROVED';
            }
            elseif ( $reply['decision'] == 'REJECT' )  {
                $this->_trans_result = 'DECLINED';
                $err = "The transaction was declined";
            }
            elseif (  $reply['decision'] == 'ERROR' )  {
                $this->_trans_result = 'ERROR';
                $err = "There was an error ($trans_reason_code): $trans_msg";
            }
            else {
                $this->_trans_result = 'UNKNOWN';
                $err = "Unknown error: $trans_response_code ({$reply['decision']})";
            }
        }

        if (!$err) {
            switch ($reply['reasonCode']) {

                case '100': // Successful transaction.
                    $trans_msg = sprintf("Request ID: %s\nAuthorizedAmount: %s\nAuthorization Code: %s",
                                         $reply['requestID'], $reply['ccAuthReply_amount'],
                                         $reply['ccAuthReply_authorizationCode'] ) ;
                    $this->_gate_transid = $reply['requestID'];
                    break;


                case '203': // General decline of the card. No other information provided by the issuing bank.
                case '204': // Insufficient funds in the account.
                case '205': // Stolen or lost card.
                case '208': // Inactive card or card not authorized for card-not-present transactions.
                case '210': // The card has reached the credit limit.
                case '221': // The customer matched an entry on the processor?s negative file.
                case '233': // General decline by the processor.
                    $err = sprintf("Your card was declined by the payment processor. Please use a different
                                            card or select another form of payment." );
                    $trans_msg = $err;
                    break;

                case '201': // The issuing bank has questions about the request.
                case '202': // Expired card. You might also receive this if the expiration date you provided does not match the date
                case '211': // Invalid card verification number.
                case '231': // Invalid account number.
                case '232': // The card type is not accepted by the payment processor.
                    $err = sprintf("Your payment information was invalid or rejected by the payment processor. Please use a different
                                            card or select another form of payment." );
                    $trans_msg = $err;
                    break;


                case '101': // The request is missing one or more required fields.
                case '102': // One or more fields in the request contains invalid data.
                case '104': // duplicate merchantReferenceCode 
                case '150': // Error: General system failure.
                case '151': // Error: The request was received but there was a server timeout.
                case '152': // Error: The request was received, but a service did not finish running in time.
                case '207': // Issuing bank unavailable.
                case '234': // There is a problem with your CyberSource merchant configuration.
                case '235': // The requested amount exceeds the originally authorized amount.
                case '236': // Processor failure.
                case '238': // The authorization has already been captured.
                case '239': // The requested transaction amount must match the previous transaction amount.
                case '240': // The card type sent is invalid or does not correlate with the credit card number.
                case '241': // The request ID is invalid.
                case '242': // You requested a capture through the API, but there is no corresponding, unused authorization record.
                case '250': // Error: The request was received, but there was a timeout at the payment processor.
                case '520': // The authorization request was approved by the issuing bank but declined by CyberSource
                    $err = "There has been an internal system error with processing your payment. System administrators have been notified. Please try your request again later.";
                    $trans_msg = $err;
                    break;
                default: // wtf!
                    $err = "There has been an UNKNOWN error with processing your payment. System administrators have been notified. Please try your request again later.";
                    $trans_msg = $err;
                    break;
            }

            $this->_trans_result_msg = $trans_msg;

            
            $this->avs_result_flags = $this->decode_avs_response($reply['ccAuthReply_avsCode']);

            $this->csc_result = $this->decode_csc_response($reply['ccAuthReply_cvCode']);
            $this->csc_match = ($this->csc_result & CMPAY_CSC_MATCH);
        }

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

		$request = array();

        // set triggers for the request based on action type
        switch ($type) {
            case 'auth_capture': // 
                $request['ccAuthService_run'] = 'true';
                $request['ccCaptureService_run'] = 'true';
                $this->_trans_type = 'AUTH_CAPTURE';
                break;
            case 'authorize': // normal
                $request['ccAuthService_run'] = 'true';
                $this->_trans_type = 'AUTH_ONLY';
                break;
            case 'capture': // not used really, wont work
                $request['ccCaptureService_run'] = 'true';
                $this->_trans_type = 'PRIOR_AUTH_CAPTURE';
                break;
            default:
                return $this->raiseError("request type '$type' is not known to me");
        }
        $this->_trans_time = time();

        $billing = $this->_user->fetchBillingAddr();
        $shipping = $this->_user->fetchShippingAddr();

        // the login
        $request['merchantID'] = $this->_cybs_login;

		// credit card information
        list($exp_m, $exp_y) = split('/', $this->_payment->get_ccexp('m/Y'));
        $request['card_accountNumber']      = $this->_payment->get_ccno();
        $request['card_cvNumber']           = $this->_payment->get_csc();
		$request["card_expirationMonth"]   = $exp_m;
		$request["card_expirationYear"]    = $exp_y;

		//Customer Name and Billing Address (pg 10)	
        $billname = $this->_name_split($billing['name']);
        $request['billTo_firstName'] = $billname[0];
        $request['billTo_lastName'] = (isset($billname[1]))? $billname[1] : '';
		$request["billTo_street1"]		    =	$billing['street_addr'];
		$request["billTo_street2"]		    =	$billing['addr2'];
		$request["billTo_city"]					=	$billing['city'];
		$request["billTo_state"]			=	$billing['state'];
		$request["billTo_postalCode"]		=	$billing['postcode'];
		$request["billTo_country"]			=	$billing['country'];
		$request["billTo_customerID"]		=	$this->_user->get_id();
		$request["billTo_email"]			=	$this->_user->get_email();

		//Invoice Information
        $request['merchantReferenceCode']   = $this->_order->get_id();
		$request["comments"]			    = $this->_self_description;

		//Customer Shipping Address (pg 10)	
        $shipname = $this->_name_split($shipping['name']);
		$request["shipTo_firstName"]			=	$shipname[0];
		$request["shipTo_lastName"]			    =	(isset($shipname[1]))? $shipname[1] : '';
		$request["shipto_street1"]		        =	$shipping['street_addr'];
		$request["shipto_street2"]		        =	$shipping['addr2'];
		$request["shipTo_city"]					=	$shipping['city'];
		$request["shipTo_state"]				=	$shipping['state'];
		$request["shipTo_postalCode"]		    =	$shipping['postcode'];
		$request["shipTo_country"]				=	$shipping['country'];

		//Transaction Data (pg 13)	
		$request["purchaseTotals_grandTotalAmount"]	=	$this->get_trans_amount();
		$request["purchaseTotals_currency"]		    =	$this->currency_code;

        $this->_trans_request = $request;
        return $request;
    }


    /** utility function to split a full name field like we use into a.net's
     * first,last format that they prefer for whatever reason 
     * @param $sre name to split
     * @return array (first,last)
    */
    function _name_split($str) {
		return split(" ", $str, 2);
    }



    /** set AVS Response code into a set of binary flags via the defines set up above. based on Gateway API
     * @param $code the AVS response code from the gateway
     * return int binary flags indicating the nature of the result */
    function decode_avs_response($code) {
        $res = null;
        switch ($code) {
            case 'A': // Partial match Street address matches, but both 5-digit ZIP code and 9-digit ZIP code do not match.
                $res = CMPAY_AVS_ADDR;
                break;
            case 'B': // Partial match Street address matches, but postal code not verified. Returned only for non-U.S.-issued Visa cards.
                $res = CMPAY_AVS_ADDR;
                break;
            case 'C': // Not verified Street address and postal code not verified. Returned only for non-U.S.-issued Visa cards.
                $res = CMPAY_AVS_UNSUP | CMPAY_AVS_INTL;
                break;
            case 'D': // Match Street address and postal code both match. Returned only for non-U.S.-issued Visa cards.
            case 'X': // Match Exact match. Street address and 9-digit ZIP code both match.
            case 'Y': // Match Street address and 5-digit ZIP code both match.
                $res = CMPAY_AVS_ZIP | CMPAY_AVS_ADDR;
                break;
            case 'M': // Match Street address and postal code both match. Returned only for non-U.S.-issued Visa cards.
                $res = CMPAY_AVS_INTL | CMPAY_AVS_ZIP | CMPAY_AVS_ADDR;
                break;
            case 'E': // Invalid AVS data is invalid.
            case 'R': // System unavailable System unavailable.
            case 'U': // System unavailable Address information unavailable. Returned if the U.S. bank does not support non-U.S. AVS or if the AVS in a U.S. bank is not functioning properly.
                $res = CMPAY_AVS_ERR;
                break;
            case 'G': // Not supported Non-U.S. issuing bank does not support AVS.
            case 'I': // Not verified Address information not verified. Returned only for non-U.S.-issued Visa cards.
                $res = CMPAY_AVS_INTL | CMPAY_AVS_UNSUP;
                break;
            case 'N': // No match Street address, 5-digit ZIP code, and 9-digit ZIP code all do not match.
                $res = CMPAY_AVS_NOMATCH;
                break;
            case 'P': // Partial match Postal code matches, but street address not verified. Returned only for non-U.S.-issued Visa cards.
                $res = CMPAY_AVS_INTL | CMPAY_AVS_ADDR;
                break;
            case 'S': // Not supported U.S. issuing bank does not support AVS.
                $res = CMPAY_AVS_UNSUP;
                break;
            case 'W': // Partial match Street address does not match, but 9-digit ZIP code matches.
            case 'Z': // Partial Match Street address does not match, but 5-digit ZIP code matches.
                $res = CMPAY_AVS_ZIP;
                break;
            case '1': // Not supported CyberSource AVS code. AVS is not supported for this processor or card type.
            case '2': // Invalid CyberSource AVS code. The processor returned an unrecognized value for the AVS response.
                $res = CMPAY_AVS_UNSUP;
                break;
        }
        return $res;
    }

    function decode_csc_response($code) {
        $res = null;
        switch ($code) { // "Cardholder Authentication Verification Value (CAVV) Response Code"
            case 'D': // The issuing bank determined that the transaction is suspicious.
            case 'I': // Card verification number failed the processor's data validation check.
            case 'N': // Card verification number not matched.
                $res = CMPAY_CSC_NOMATCH;
                break;
            case 'M': // Card verification number matched.
                $res = CMPAY_CSC_MATCH;
                break;
            case 'P': // The processor did not process the card verification number for an unspecified reason.
            case '2': // The processor returned an unrecognized value for the card verification response.
            case '3': // The processor did not return a card verification result code.
                $res = CMPAY_CSC_ERROR;
                break;
            case 'S': // Card verification number is on the card but was not included in the request.
                $res = CMPAY_CSC_MISSING;
                break;
            case 'U': // Card verification is not supported by the issuing bank.
            case 'X': // Card verification is not supported by the card association.
            case '1': // Card verification is not supported for this processor or card type.
                $res = CMPAY_CSC_UNSUP;
                break;
        }
        return $res;
    }

}
