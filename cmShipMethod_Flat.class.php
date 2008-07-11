<?php
/**
 * get shipping rates from a flat rate table
 *
 * NOTE this is just a stub, although it works in the cart there is no control/admin UI
 *
 * $Id: cmShipMethod_Flat.class.php,v 1.6 2006/12/29 20:36:29 sbeam Exp $
 */
require_once('circusShop/cmShipMethod.class.php');

class cmShipMethod_Flat extends cmShipMethod {

    var $_name = 'FLAT RATE';

    var $_table= 'cm_shipmethods_flat_methods';

    var $class_descrip = 'Flat Ship Method'; // english name of this class, what it represents
    var $table_orderby_default = 'cost'; // column to sort listings by, by default
    var $table_name_column = 'name'; // column to get the "name" or description of a single instance from

    var $control_header_cols = array('name'=>'Name', 'cost'=>'Cost');

    /* form definition arrays suitable for formex() */
    var $colmap = array('name' => array('Method Name', 'text', null, array('size'=>16), null, 1),
                        'cost' => array('Cost', 'numeric', null, array('size'=>5), null, 1));


    /** pulling in quotes from all avail types and putting into a nice array
     */
    function get_all_quotes(&$cart, $adder=0) {
        $res = array();

        $allquotes = $this->quote();
        if (!is_array($allquotes)) {
            $msg = $this->_name . ": Shipping calculation error! ";
            $msg .= (PEAR::isError($allquotes))? $allquotes->getMessage() : $allquotes;
            return $this->raiseError($msg);
        }
        asort($allquotes); // sort the quotes by cost
        foreach ($allquotes as $type => $q) {
            $q += $adder;
            $opt = sprintf("%s (%.02f)", $type, $q);
            $res[$opt] = $opt;
        }
        return $res;
    }


    /* get a quote for the given type */
    function quote() {
        global $pdb;

        /** just get them all */
        $sql = "SELECT name, cost FROM " . $this->_table;

        $opts = array();
        $res = $pdb->query($sql);

        if ($res->numRows() == 0) {
            trigger_error("MISCONFIGURATION: no Flat Shipping methods have been added!", E_USER_WARNING);
        }
        while ($row = $res->fetchRow()) {
            $opts[$row['name']] = $row['cost'];
        }
        return $opts;
    }


    /** get list of all methods in the flat table
     * @throws PE
     * @return array */
    function fetch_method_list()
    {
        $db =& $this->dbcontainerSingleton();
        return $this->dbc->fetch_any(array('id','name','cost'), 0, 0, 'order_weight');
    }

}
