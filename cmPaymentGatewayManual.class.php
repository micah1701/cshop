<?php

require_once('circusShop/cmPaymentGateway.class.php');

class cmPaymentGatewayManual extends cmPaymentGateway {

    var $_self_description = 'Merchant Processor'; // B.S.
    var $gateway_name = 'Manual Payment Terminal';
    var $do_truncate_stored_ccno = false;

    var $_VERSION = '1.0';

    function cmPaymentGatewayManual(&$user, &$pay, &$order) {
        $this->_user =& $user;
        $this->_payment =& $pay;
        $this->_order =& $order;
        $this->_self_description = $this->get_self_description();
    }

    function send($req) { }

    function parse_response($res) { }

    function construct_request($type) {
        $this->_trans_type = 'MANUAL';
    }

    # add listener to Order->update_status() = truncate cc# on change

}

