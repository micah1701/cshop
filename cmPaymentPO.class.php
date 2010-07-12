<?php

class cmPaymentPO extends db_container {

    var $_table = 'cm_payment_po';
    var $colmap = array('po_no' => array('Purchase Order Number', 'text', null, null, true));

    /** what we call this type of payment usually */
    var $method_name = 'Purchase Order'; 

    function get_cctype() { }
    function get_ccexp() { }
    function get_ccno() { }
    function set_csc() { }

    function check_values($vals) {
        if (empty($vals['po_no']))
            return array('Please enter a purchase order number to complete the order.');
    }
}
