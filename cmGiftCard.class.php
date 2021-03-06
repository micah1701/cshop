<?php

require_once('HTTP/Request.php');
require_once('db_container.class.php');

/**
 * interface for SmartTransactionSystems (STS) API for creating and using gift cards
 */
class cmGiftCard extends db_container {

    var $colmap = array('gc_no' => array('Gift Card Number', 'text', null, array('size'=>14), 1),
                        'gc_amt' => array('Amount to use', 'text', null, array('size'=>6), 1));

    var $_table = 'cm_giftcards';

    /** the giftcard number */
    var $gc_number = '';

    /** how much the giftcard is being used for */
    var $gc_amount = 0;

    var $do_log = true;


    /* s/getters */
    function set_number($gc_no) {
        $this->gc_number = $gc_no;
    }
    function set_amount($gc_amt) {
        $this->gc_amount = number_format($gc_amt, 2);
    }

    function get_number() {
        return $this->gc_number;
    }
    function get_amount() {
        return $this->gc_amount;
    }

    function find_by_card_number($card_no) {
        $w = "gc_no = " . $this->db->quote($card_no);
        return $this->fetch_any(null, 0, 0, null, $w);
    }


    /** 
     * inquire as to how much is left on this card.
     *
     * giftcard number is gotten from a class property $gc_number
     *
     * uses other class methods to communicate with the giftcard server.
     *
     * @return PEAR_Error on any failure, or true on success
     */
    function get_balance() {
        if (empty($this->gc_number)) {
            return $this->raiseError("giftcard number is not set");
        }

        $this->log("get_balance(): gc_number={$this->gc_number}");
        $xmlstr = $this->_build_balance_inquiry_request($this->gc_number);
        $xmlresp = $this->_send_request($xmlstr);
        if (PEAR::isError($xmlresp)) { return $xmlresp; }
        elseif (empty($xmlresp)) {  return $this->raiseError("No response from giftcard gateway"); }

        $dom =& $this->_parse_xml_response($xmlresp);
        if (PEAR::isError($dom)) return $dom;

        if ( $node_balance =& $this->_dom_get_node($dom, 'Amount_Balance') ) {
            $bal = floatval($this->_dom_get_node_content($node_balance));
            if ($bal > 0) { 
                return $bal;
                // foster 19 nov 2009 - no it is not in cents, it is in real dollars
                // sprintf("%.02f", $bal/100);// Amount_Balance is in "cents"
            }
        }
    }

    /**
     * activate an existing physical GC for the given merch
     */
    function activate(&$order, $merch_id, $amount, $card_no) {
        $this->log("activate(): order.id={$order->get_id()} amount={$amount}, card={$card_no}");
        $xmlstr = $this->_build_activation_request($merch_id, $card_no, $amount);
        $xmlresp = $this->_send_request($xmlstr);
        if (PEAR::isError($xmlresp)) { return $xmlresp; }
        elseif (empty($xmlresp)) {  return $this->raiseError("No response from giftcard gateway"); }

        $dom =& $this->_parse_xml_response($xmlresp);
        if (PEAR::isError($dom)) return $dom;

        $sxml = new SimpleXMLElement($xmlresp);

        if (empty($sxml->Card_Number)) return $this->raiseError("Gateway did not return a Card Number.");

        $vals = array('gc_amt' => $sxml->Amount_Balance,
                      'order_id' => $order->get_id(),
                      'gc_no' => $sxml->Card_Number,
                      'transaction_id' => $sxml->Transaction_ID,
                      'auth_reference' => $sxml->Auth_Reference);

        return $this->store($vals);
    }


    /**
     * create a new giftcard in the STS system, saving the new GC number and associating with
     * a complete order
     */
    function create(&$order, $merch_id, $amount) {
        $this->log("create(): order.id={$order->get_id()} amount={$amount}");
        $xmlstr = $this->_build_creation_request($merch_id, $amount);
        $xmlresp = $this->_send_request($xmlstr);
        if (PEAR::isError($xmlresp)) { return $xmlresp; }
        elseif (empty($xmlresp)) {  return $this->raiseError("No response from giftcard gateway"); }

        $dom =& $this->_parse_xml_response($xmlresp);
        if (PEAR::isError($dom)) return $dom;

        $sxml = new SimpleXMLElement($xmlresp);

        if (empty($sxml->Card_Number)) return $this->raiseError("Gateway did not return a Card Number.");

        $vals = array('gc_amt' => $sxml->Amount_Balance,
                      'order_id' => $order->get_id(),
                      'gc_no' => $sxml->Card_Number,
                      'transaction_id' => $sxml->Transaction_ID,
                      'auth_reference' => $sxml->Auth_Reference);

        return $this->store($vals);
    }


    /**
     * offically deduct the requested amount from the giftcard. 
     * 
     * Giftcard numbers and amounts are retreived from the DB where they should
     * have been stored previously. This instance should be a RowRecord.
     *
     * uses other class methods to communicate with the giftcard server.
     *
     * @param $order cmOrder object
     * @return PEAR_Error on any failure, or true on success
     */
    function redeem(&$order) {
        if (!$this->get_id()) {
            return $this->raiseError('giftcard ID was not set');
        }
        if (! ($myvals = $this->fetch())) {
            return $this->raiseError('could not find the identified giftcard record');
        }
        $this->log("redeem(): order.id={$order->get_id()} gc_no={$myvals['gc_no']}, gc_amt={$myvals['gc_amt']}");
        $xmlstr = $this->_build_redemption_request($myvals['gc_no'], $myvals['gc_amt']);
        $xmlresp = $this->_send_request($xmlstr);
        if (PEAR::isError($xmlresp)) { return $xmlresp; }
        elseif (empty($xmlresp)) {  return $this->raiseError("No response from giftcard gateway"); }

        $dom =& $this->_parse_xml_response($xmlresp);
        if (PEAR::isError($dom)) return $dom;

        $node_ref =& $this->_dom_get_node($dom, 'Auth_Reference');
        $ref_code = $this->_dom_get_node_content($node_ref);

        $vals = array('redeemed_amt' => $myvals['gc_amt'],
                      'order_id' => $order->get_id(),
                      'auth_reference' => $ref_code);

        return $this->store($vals);
    }


    /**
     * basic common parsing of STS gateway response XML, checking for errors in Response_Code
     * and return a PEAR::Error if found. Otherwise returns a DOM object
     */
    function _parse_xml_response($xmlresp) {

        $dom =& $this->_create_dom_obj($xmlresp);
        if (!is_object($dom)) { return $this->raiseError("Could not parse gateway response"); }

        if (! ($node_code =& $this->_dom_get_node($dom, 'Response_Code'))) {
            return $this->raiseError('Could not understand giftcard gateway response');
        }
        else {
            if ($this->_dom_get_node_content($node_code) != '00') { // that means its bad
                $node_res =& $this->_dom_get_node($dom, 'Response_Text');
                return $this->raiseError('Gift Card Transaction API Error: '. $this->_dom_get_node_content($node_res));
            }
            else {
                return $dom;
            }
        }
    }


    /**
     * log something somewhere
     */
    function log($msg) {
        if ($this->do_log) { 
            error_log(date('r')." - cmGiftCard \n".$msg."\n\n", 3, CSHOP_LOG_FILE); 
        }
    }


    /**
     * send the given XML to the server as configured in
     * CSHOP_GIFTCARD_POST_URL. Remove some cruft from the response and return
     * a XML string
     *
     * @private
     * @param $xmlstr str some stuff to send
     * @return str
     */
    function _send_request($xmlstr) {

        $this->log("send_request(): $xmlstr");
        $hr = new HTTP_Request(CSHOP_GIFTCARD_POST_URL);
        $hr->setMethod(HTTP_REQUEST_METHOD_POST);
        $hr->addPostData('Auth_Request', $xmlstr, true);
        $req =& $hr->sendRequest();

        if (PEAR::isError($req)) {
            return $this->raiseError("Could not send request to giftcard processor gateway: ". $req->getMessage());
        }
        else {
            $res = $hr->getResponseBody();
            $this->log("response: $res");

            /* STS sends the XML wrapped in some HTML and control chars and other nonsense
             * remove the cruft: */
            preg_match("/" . chr(2) . "(.*)" . chr(3) . "/", $res, $m);
            return $m[1];
        }
    }


    /**
     * create an XML string suitable for getting Balance Inquiries from our gateway
     * @private
     * @param $card_no str the card number
     * @return str
     */
    function _build_balance_inquiry_request($card_no) {
        $vals = array('Merchant_Number' => CSHOP_GIFTCARD_MERCHANTNUMBER,
                      'Terminal_ID' => CSHOP_GIFTCARD_TERMINALID,
                      'Trans_Type' => 'N',
                      #'Transaction_Amount' => '0',
                      'POS_Entry_Mode' => 'M',
                      'Action_Code' => '05',
                      'Card_Number' => $card_no);
        return $this->_build_simple_xml_req($vals); 
    }

    /**
     * create an XML string suitable for offically redeeming the given amount from the GC
     * @private
     * @param $card_no str the card number
     * @param $amt float how much to take
     * @return str
     */
    function _build_redemption_request($card_no, $amt) {
        $vals = array('Merchant_Number' => CSHOP_GIFTCARD_MERCHANTNUMBER,
                      'Terminal_ID' => CSHOP_GIFTCARD_TERMINALID,
                      'Trans_Type' => 'N',
                      'POS_Entry_Mode' => 'M',
                      'Action_Code' => '01',
                      'Card_Number' => $card_no,
                      'Transaction_Amount' => $amt);
        return $this->_build_simple_xml_req($vals); 
    }


    function _build_activation_request($merch_id, $card_no, $amt) {
        $vals = array('Merchant_Number' => (ON_LIVE_SERVER)? $merch_id : CSHOP_GIFTCARD_TEST_MERCHANT_ACCOUNT_ID,
                      'Terminal_ID' => CSHOP_GIFTCARD_TERMINALID,
                      'Trans_Type' => 'N',
                      'POS_Entry_Mode' => 'M',
                      'Action_Code' => '06',
                      'Card_Number' => $card_no,
                      'Transaction_Amount' => $amt);
        return $this->_build_simple_xml_req($vals);
    }


    function _build_creation_request($merch_id, $amt) {
        $vals = array('Merchant_Number' => (ON_LIVE_SERVER)? $merch_id : CSHOP_GIFTCARD_TEST_MERCHANT_ACCOUNT_ID,
                      'Terminal_ID' => CSHOP_GIFTCARD_TERMINALID,
                      'Trans_Type' => 'N',
                      'POS_Entry_Mode' => 'M',
                      'Action_Code' => '06',
                      'Card_Number' => 'NewAccountRQ', #special card# for new card requests, per Ray's email
                      'Transaction_Amount' => $amt);
        return $this->_build_simple_xml_req($vals); 
    }

    /**
     * create a simple, one-level XML string from the given assoc array. Then
     * add some control chars required by STS API 
     * 
     * @private
     * @param $vals array assoc array
     * @return str
     */
    function _build_simple_xml_req(&$vals) {
        $res = chr(2) . '<Request>'; // API requires a 00000010 as a "Start Transmission" frame
        foreach ($vals as $k => $v) {
            $res .= sprintf('<%s>%s</%s>', $k, $v, $k);
        }
        $res .= '</Request>' . chr(3); // API requires a 00000011 as a "End Transmission" frame
        return $res;
    }


    /** 
     * utility to create some sort of DOM object. On PHP4 gives back a dom_xml
     * object, on PHP5+ a DOMDocument
     *
     * 13 Nov 2009 // foster
     * According to docs, PHP4 gives back a DomDocument. Differs from PHP5 by the 
     * casing.
     *
     * @private
     * @return dom_xml or DOMDocument object
     */
    function _create_dom_obj($str) {
        if (function_exists('domxml_open_mem')) {
            $dom =& domxml_open_mem($str);
            return $dom;
        }
        else {
            $dom = new DOMDocument();
            $dom->loadXML($str);
            return $dom;
        }
    }


    /** utility dom function to return the contents of the first Node whose tag name is $tag
     * @private
     * @param $dom a domxml object
     * @param $tag str
     * @return string the value of node, or false if not found
     */
    function _dom_get_node(&$dom, $tag) {
        if (get_class($dom) == 'DomDocument') {     // php 4 DomDocument
            $coll = $dom->get_elements_by_tagname($tag);
            if (!empty($coll)) return $coll[0];
        }
        else {                                      // php 5 DOMDocument
            $coll = $dom->getElementsByTagName($tag);
            if ($coll->length > 1) {
                trigger_error("found multiple nodes named $tag", E_USER_NOTICE);
            }
            if ($coll->length > 0) {
                return $coll->item(0);
            }
        }
        trigger_error("no node named $tag", E_USER_WARNING);
        return "";
    }

    /** utility dom function to return the first child of $node, which is hopefully some text
     * @private
     * @param $dom a domxml object
     * @param $tag str
     * @return string the value of node, or false if not found
     */
     function _dom_get_node_content(&$node) {
        if (get_class($node) == 'DomElement') {     // php 4 DomElement
            return $node->get_content();
        }
        else {                                      // php 5 DOMElement
            $kid = $node->firstChild;
            if ($kid->nodeType == XML_TEXT_NODE) {
                return $kid->nodeValue;
            }
            else {
                trigger_error("child node is not a text node", E_USER_NOTICE);
            }
        }
    }

}
