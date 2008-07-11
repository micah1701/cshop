<?php
/**
 * UPS realtime shipping calculator
 *
 * $Id: cmShipMethod_UPS.class.php,v 1.8 2008/01/17 02:43:51 sbeam Exp $
 */
require_once("HTTP/Request.php");
require_once('circusShop/cmShipMethod.class.php');

class cmShipMethod_UPS extends cmShipMethod {

    /** will log API transactions to debug_log file */
    var $debug = true;
    var $debug_log = '/tmp/cmShipMethod.log';

    /** types of shipping this shipper provides, CODE => 'Name' - i.e. '2DA' => '2nd Day Air' */
    var $ship_types = array('1DM' => 'Next Day Air Early AM',
                       '1DML' => 'Next Day Air Early AM Letter',
                       '1DA' => 'Next Day Air',
                       '1DAL' => 'Next Day Air Letter',
                       '1DAPI' => 'Next Day Air Intra (Puerto Rico)',
                       '1DP' => 'Next Day Air Saver',
                       '1DPL' => 'Next Day Air Saver Letter',
                       '2DM' => '2nd Day Air AM',
                       '2DML' => '2nd Day Air AM Letter',
                       '2DA' => '2nd Day Air',
                       '2DAL' => '2nd Day Air Letter',
                       '3DS' => '3 Day Select',
                       'GND' => 'Ground',
                       'GNDCOM' => 'Ground Commercial',
                       'GNDRES' => 'Ground Residential',
                       'STD' => 'Canada Standard',
                       'XPR' => 'Worldwide Express',
                       'XPRL' => 'Worldwide Express Letter',
                       'XDM' => 'Worldwide Express Plus',
                       'XDML' => 'Worldwide Express Plus Letter',
                       'XPD' => 'Worldwide Expedited',
                       'WXS' => 'Worldwide Saver',
                    );


    /** keys from the above that we are actually using in this implementation */
    var $allowed_ship_types = array('1DA', '2DA', '3DS', 'GND', 'GNDCOM', 'GNDRES', 'XPR', 'XDM', 'XPD', 'STD', 'WXS');
    //var $allowed_ship_types = array('1DM', '1DML', '1DA', '1DAL', '1DAPI', '1DP', '1DPL', '2DM', '2DML', '2DA', '2DAL', '3DS', 'GND', 'GNDCOM', 'GNDRES', 'STD', 'XPR', 'XPRL', 'XDM', 'XDML', 'XPD');

    var $_service_url = 'http://www.ups.com/using/services/rave/qcostcgi.cgi';

    var $_name = 'UPS';



    /** pulling in quotes from all avail types and putting into a nice array
     */
    function get_all_quotes(&$cart, $adder=0) {
        $res = array();

        /* does this look like a PO Box? if so bail, with an error */
        if ($this->_dest['country'] == 'US' && preg_match('/^(P\.?O\.?\s*)?BOX\s+[0-9]+/i', $this->_dest['addr'])) {
            return $this->raiseError('UPS cannot deliver to P.O. Boxes');
        }

        $allquotes = $this->quote('GND');
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
    function quote($type) {
        return $this->send_ups_query($type);
    }



    function _upsDest($postcode, $country){
      $postcode = str_replace(' ', '', $postcode);

      if ($country == 'US') {
        $this->_upsDestPostalCode = substr($postcode, 0, 5);
      } else {
        $this->_upsDestPostalCode = $postcode;
      }

      $this->_upsDestCountryCode = $country;
      return $this->_upsDestCountryCode;
    }

    function get_upsRate($foo) {
      switch ($foo) {
        case 'RDP':
          $this->_upsRateCode = 'Regular+Daily+Pickup';
          break;
        case 'OCA':
          $this->_upsRateCode = 'On+Call+Air';
          break;
        case 'OTP':
          $this->_upsRateCode = 'One+Time+Pickup';
          break;
        case 'LC':
          $this->_upsRateCode = 'Letter+Center';
          break;
        case 'CC':
          $this->_upsRateCode = 'Customer+Counter';
          break;
      }
      return $this->_upsRateCode;
    }

    function get_upsContainerCode($foo) {
      switch ($foo) {
        case 'CP': // Customer Packaging
          $this->_upsContainerCode = '00';
          break;
        case 'ULE': // UPS Letter Envelope
          $this->_upsContainerCode = '01';
          break;
        case 'UT': // UPS Tube
          $this->_upsContainerCode = '03';
          break;
        case 'UEB': // UPS Express Box
          $this->_upsContainerCode = '21';
          break;
        case 'UW25': // UPS Worldwide 25 kilo
          $this->_upsContainerCode = '24';
          break;
        case 'UW10': // UPS Worldwide 10 kilo
          $this->_upsContainerCode = '25';
          break;
      }
      return $this->_upsContainerCode ;
    }

    function get_upsRescom($foo) {
      switch ($foo) {
        case 'RES': // Residential Address
          $this->_upsResComCode = '1';
          break;
        case 'COM': // Commercial Address
          $this->_upsResComCode = '2';
          break;
      }
      return $this->_upsResComCode ;
    }

    function _upsAction($action) {
      /* 3 - Single Quote
         4 - All Available Quotes */

      $this->_upsActionCode = $action;
    }

    /** sends a GET request to a UPS server. see what happends */
    function send_ups_query($product) {
        if ($this->debug) {
            $log = "==\n".get_class($this) . "::send_ups_query()\n" . date('r');
            $log .= "\nIP: " . $_SERVER['REMOTE_ADDR'];
            error_log($log, 3, $this->debug_log);
        }
        if (!isset($this->_upsActionCode)) $this->_upsActionCode = '4';

        $rescom = (!empty($this->_dest['company']) && !preg_match('/^(n\?a|none)$/i', $this->_dest['company']))? 'COM':'RES';

        $params = array('accept_UPS_license_agreement' => 'yes',
                        '10_action' => '4',
                        '13_product' => $product,
                        '14_origCountry' => $this->_origin['country'],
                        '15_origPostal' => $this->_origin['postcode'],
                        '19_destPostal' => $this->_dest['postcode'],
                        '22_destCountry' => $this->_dest['country'],
                        '23_weight' => $this->_weight,
                        '47_rate_chart' => $this->get_upsRate('RDP'), // todo
                        '48_container' => $this->get_upsContainerCode('CP'), // todo
                        '49_residential' => $this->get_upsRescom($rescom),
                        );
        $request = '';
        foreach ($params as $k => $v) {
            $request .= urlencode($k) . '=' . urlencode($v) . '&';
        }
        if ($this->debug) {
            error_log("\n== REQUEST to {$this->_service_url} ==\n$request", 3, $this->debug_log);
        }

          $http =& new HTTP_Request($this->_service_url . '?' . $request);

          if (PEAR::isError($http->sendRequest())) {
              return $http;
          }
          $res = $http->getResponseBody();
          return $this->parse_response($res);
    }


    function parse_response($body) {
            /*
            UPSOnLine4%1DA%03801%US%99587%US%124%6%52.31%0.00%52.31%End of Day%
            4%2DA%03801%US%99587%US%224%6%30.43%0.00%30.43%End of Day%
            4%GND%03801%US%99587%US%044%6%23.84%0.00%23.84%End of Day%
            */
        if ($this->debug) {
            error_log("\n== RESPONSE ==\n$body", 3, $this->debug_log);
        }

        $body_array = explode("\n", $body);

        $quotes = array();
        $err = null;

        $n = sizeof($body_array);
        for ($i=0; $i<$n; $i++) {
            $result = explode('%', $body_array[$i]);
            $errcode = substr($result[0], -1);
            switch ($errcode) {
                case 3:
                case 4:
                    $quotes[$result[1]] = $result[8];
                    break;
                case 5:
                    $err = $result[1];
                    break;
                case 6:
                    $quotes[$result[3]] = $result[10]; // wha hoppin?!
                    break;
            }
        }
        if (empty($quotes)) {
            $msg = ($err)? $err : "Could not parse UPS Server response";
            return $this->raiseError($msg);
        }

        if ($this->debug) {
            $log = '';
            foreach ($quotes as $k => $v) {
                $log .= "$k => $v\n";
            }
            error_log("\n== QUOTES ==\n$log", 3, $this->debug_log);
        }

        return $quotes;
    }
}
?>
