<?php
require_once('db_container.class.php');

/**
 * basic container class for occasions table - used from store.dbc_wrap.php
 *
 */
class cmProductOption extends db_container {

    var $_table = 'cm_products_options';

    /** next 3 have to be set for use by store.dbcwrap.php. Really they should
     * be abstract in the parent class so we can make sure they exist */
    var $class_descrip = 'Product Option'; // english name of this class, what it represents
    var $table_orderby_default = 'order_weight'; // column to sort listings by, by default
    var $table_name_column = 'optkey'; // column to get the "name" or description of a single instance from

    var $control_header_cols = array('optkey'=>'Key', 'opt_value'=>'Value', 'adder'=>'Adder');

    /* form definition arrays suitable for formex() */
    var $colmap = array('optkey' =>     array('Option Key', 'select_or', array(), 1),
                        'opt_value' =>  array('Option Value', 'text', null, null, 0),
                        'opt_descr' =>  array('Descr.', 'text', '', null, 0),
                        'adder' =>  array('Adder (+/-)', 'numeric', "0.00", null, 1),
                        'order_weight' =>  array('order weight', 'numeric', "0", null, 1),
                        );
    var $_cm_product_id;

    function cmProductOption(&$db, $product_id=null) {
        if (!$db) trigger_error('db needed', E_USER_ERROR);
        $this->db = $db;

        if ($product_id) {
            $this->set_cm_product_id($product_id);
        }
    }


    function get_colmap()
    {
        if (!isset($this->_populated_colmap)) {
            $sql = "SELECT DISTINCT optkey, optkey FROM " . $this->get_table_name() .
                   " ORDER BY optkey";
            $opts = $this->db->getAssoc($sql);
            $this->colmap['optkey'][2] = $opts;
            $this->_populated_colmap = true;
        }
        return $this->colmap;
    }

    function set_cm_product_id($product_id) {
        $this->_cm_product_id = $product_id;
    }

    function fetch_any($cols=null, $offset=0, $range=0, $orderby=null, $where='', $orderdir='ASC') {
        if (!empty($where)) $where .= " AND";
        $where .= " cm_products_id = " . $this->db->quote($this->_cm_product_id);
        if (!$orderby) $orderby = 'order_weight,optkey';
        return parent::fetch_any($cols, $offset, $range, $orderby, $where, $orderdir);
    }
}
