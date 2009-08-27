<?php
/**
 * UPS realtime shipping calculator
 *
 * $Id: cmShipMethod_UPS.class.php,v 1.8 2008/01/17 02:43:51 sbeam Exp $
 */
require_once("HTTP/Request.php");
require_once('cshop/cmShipMethod.class.php');
ini_set("soap.wsdl_cache_enabled", "0");

class cmShipMethod_FedEx extends cmShipMethod {

    /** will log API transactions to debug_log file */
    var $debug = false;
    var $debug_log = '/tmp/cmShipMethod_FedEx.log';

    /** live **
    var $fedex_account_number = CSHOP_FEDEX_ACCOUNT_NUMBER;
    var $fedex_meter_number = CSHOP_FEDEX_METER_NUMBER;
    var $fedex_webauth_key = CSHOP_FEDEX_WEBAUTH_KEY;
    var $fedex_webauth_password = CSHOP_FEDEX_WEBAUTH_PASS;
    /** **/

    /** test **/
    var $fedex_account_number = '510087240';
    var $fedex_meter_number = '118502131';
    var $fedex_webauth_key = 'EvvtCGizPC9B0KAQ';
    var $fedex_webauth_password = 'DQyNTGBQymB30HwcEUogzCzmL';
    /** **/

    /** types of shipping this shipper provides, CODE => 'Name' - i.e. '2DA' => '2nd Day Air' */
    var $ship_types = array('FIRST_OVERNIGHT' => 'First Overnight',
                       'PRIORITY_OVERNIGHT' => 'Priority Overnight',
                       'STANDARD_OVERNIGHT' => 'Standard Overnight',
                       'FEDEX_2_DAY' => 'FedEx 2-Day',
                       'FEDEX_EXPRESS_SAVER' => 'FedEx Express Saver',
                    );



    /** keys from the above that we are actually using in this implementation */
    var $allowed_ship_types = array('FIRST_OVERNIGHT', 'PRIORITY_OVERNIGHT', 'STANDARD_OVERNIGHT', 'FEDEX_2_DAY', 'FEDEX_EXPRESS_SAVER');

    // location of FedEx RateService_v7.wsdl file, relative to this file location.
    var $_path_to_wsdl = 'util/RateService_v7.wsdl';

    var $_name = 'FedEx';


    function set_cart(&$cart) {
    }


    /** pulling in quotes from all avail types and putting into a nice array
     */
    function get_all_quotes(cmCart $cart, $adder=0) {
        $res = array();

        $this->_cart =& $cart;

        /* does this look like a PO Box? if so bail, with an error */
        if ($this->_dest['country'] == 'US' && preg_match('/^(P\.?O\.?\s*)?BOX\s+[0-9]+/i', $this->_dest['addr'])) {
            return $this->raiseError('FedEx cannot deliver to P.O. Boxes');
        }

        $allquotes = $this->quote();
        if (!is_array($allquotes)) {
            $msg = $this->_name . ": Shipping calculation error! ";
            $msg .= (PEAR::isError($allquotes))? $allquotes->getMessage() : $allquotes;
            return $this->raiseError($msg);
        }
        asort($allquotes); // sort the quotes by cost
        foreach ($allquotes as $type => $q) {
            if (in_array($type, $this->allowed_ship_types)) {
                $q += $adder;
                $opt = sprintf("%s %s (%.02f)", $this->get_name(), $this->ship_types[$type], $q);
                $res[$opt] = $opt;
            }
        }
        return $res;
    }


    /* get a quote for the given type */
    public function quote() {
        $request = $this->_construct_fedex_request();
        return $this->send_fedex_query($request);
    }


    /**
     * build array that can be passed to the SoapClient per the API.
     */
    private function _construct_fedex_request() {
        
        $request = array();
        $request['WebAuthenticationDetail'] = array('UserCredential' =>
                                              array('Key' => $this->fedex_webauth_key, 'Password' => $this->fedex_webauth_password));
        $request['ClientDetail'] = array('AccountNumber' => $this->fedex_account_number, 'MeterNumber' => $this->fedex_meter_number);
        $request['TransactionDetail'] = array('CustomerTransactionId' => $this->_cart->get_id());
        $request['Version'] = array('ServiceId' => 'crs', 'Major' => '7', 'Intermediate' => '0', 'Minor' => '0');
        $request['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP'; // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
        // $request['RequestedShipment']['ShipTimestamp'] = date('c');
        // Service Type and Packaging Type are not passed in the request
        $request['RequestedShipment']['Shipper'] = array('Address' => array(
                                                  #'StreetLines' => array('10 Fed Ex Pkwy'), // Origin details
                                                  #'City' => 'Memphis',
                                                  #'StateOrProvinceCode' => 'TN',
                                                  'PostalCode' => CSHOP_SHIPPING_ORIGIN_ZIP,
                                                  'CountryCode' => CSHOP_SHIPPING_ORIGIN_COUNTRY));
        $request['RequestedShipment']['Recipient'] = array('Address' => array (
                                                       'StreetLines' => array($this->_dest['addr']), // Destination details
                                                       #'City' => $this->_dest['addr']'Herndon',
                                                       #'StateOrProvinceCode' => $this->_dest['addr']'VA',
                                                       'PostalCode' => $this->_dest['postcode'],
                                                       'CountryCode' => $this->_dest['country']));
        $request['RequestedShipment']['ShippingChargesPayment'] = array('PaymentType' => 'SENDER',
                                                                'Payor' => array('AccountNumber' => $this->fedex_account_number,
                                                                                 'CountryCode' => 'US'));
        $request['RequestedShipment']['RateRequestTypes'] = 'ACCOUNT'; 
        $request['RequestedShipment']['PackageCount'] = '1';// currently only one occurrence of RequestedPackage is supported
        $request['RequestedShipment']['PackageDetail'] = 'INDIVIDUAL_PACKAGES';
        $request['RequestedShipment']['RequestedPackageLineItems'] = array('0' => array('Weight' => array('Value' => $this->_weight, 'Units' => 'LB')));
        return $request;
    }


    /** sends a SOAP request to a FedEx server. see what happens */
    function send_fedex_query($request) {

        if ($this->debug) {
            $log = "==\n".get_class($this) . "::send_fedex_query()\n" . date('r');
            $log .= "\nIP: " . $_SERVER['REMOTE_ADDR'];
            $log .= "\n". serialize($request);
            error_log($log, 3, $this->debug_log);
        }
 
        $wsdl = dirname(__FILE__) . '/' . $this->_path_to_wsdl;
        $client = new SoapClient($wsdl, array('trace' => 1)); // http://us3.php.net/manual/en/ref.soap.php

        $quotes = array();

        $response = $client->getRates($request);
        if ($this->debug) {
            $log = "\n". serialize($response);
            error_log($log, 3, $this->debug_log);
            error_log("\n{$response->HighestSeverity}\n", 3, $this->debug_log);
        }

        if ($response->HighestSeverity == 'SUCCESS') {
            foreach ($response->RateReplyDetails as $rateReply) {           
                $service = $rateReply->ServiceType;
                foreach ($rateReply->RatedShipmentDetails as $detail) {
                    $last_rate = null;
                    if (isset($detail->ShipmentRateDetail)) {
                        $rate = $detail->ShipmentRateDetail->TotalNetFedExCharge->Amount;
                        /* fedex returns multiple rate detail objects for each method, but they are always identical (maybe) */
                        if (!empty($last_rate) and $rate != $last_rate) {
                            $msg = "got different rates for the same shipping method $service";
                            trigger_error($msg, E_USER_WARNING);
                            if ($this->debug) error_log("$msg\n", 3, $this->debug_log);
                        }
                        $last_rate = $rate;
                    }
                }
                $quotes[$service] = $rate;
            } 
            return $quotes;
        }
        else {
            if (is_object($response->Notifications)) {
                if ($response->Notifications->Code == 556) 
                    $err = "The Address or Postal/ZIP code was not valid.";
                else
                    $err = $response->Notifications->Severity . ': ' .  $response->Notifications->Message . ' ';
            }
            elseif (is_array($response->Notifications)) {
                foreach ($response->Notifications as $notification) {           
                    $err .= $notification->Message . ' ';
                }
            } 
            if ($this->debug) error_log("$err\n", 3, $this->debug_log);
            return $this->raiseError( $err );

        }

    }


}
