<?php
/**
 * get shipping rates from a Zone rate table matrix based on weight or price
 *
 * $Id: cmShipMethod_Zone.class.php,v 1.9 2008/04/22 20:35:32 sbeam Exp $
 */ require_once('cshop/cmShipMethod.class.php');

class cmShipMethod_Zone extends cmShipMethod {

    /** will log API transactions to debug_log file */
    var $debug_log = '/tmp/cmShipMethod.log';

    var $_name = 'ZONE SHIPPING';

    var $_table_methods = 'cm_shipmethods_zone_methods';
    var $_table_costs = 'cm_shipmethods_zone_costs';

    /** used to create the little form in the control */
    var $colmap = array('name' => array('Method Name', 'text', null, array('size'=>16), null, 1),
                        'basis' => array('Basis', 'select', array('price'=>'price','weight'=>'weight'), null, null, 1),
                        'cm_shipmethods_zone_zones_id' => array('Zone', 'select', null, null, null, 0));

    /** another one for zones only, used for the control form */
    var $colmap_zones = array('zone_name' => array('Zone Name', 'text', null, array('size'=>36), null, 1),
                              'cm_shipmethods_zone_locales' => array('Included Countries', 'select_bicameral', array(), array('size'=>10), 1));


    /* populate the zonemethods with zones */
    function get_colmap() {
        $zones = array();
        if ($zonerows = $this->get_avail_zones()) {
            foreach ($zonerows as $z) {
                $zones[$z['id']] = $z['zone_name'];
            }
        }
        $this->colmap['cm_shipmethods_zone_zones_id'][2] = $zones;
        return $this->colmap;
    }

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

        $wt = $this->_weight;
        $pr = $this->_subtotal;
        $zone = $this->_dest['country'];

        /** query finds any ranges that match by this order's subtotal or weight! */
        $sql = "SELECT meth.name, cost.cost
                FROM ".$this->_table_methods." meth 
                    INNER JOIN cm_shipmethods_zone_zones zone ON (zone.id = meth.cm_shipmethods_zone_zones_id)
                    LEFT JOIN cm_shipmethods_zone_locales loc ON (zone.id = loc.cm_shipmethods_zone_zones_id)
                    LEFT JOIN ".$this->_table_costs." cost ON (cost.cm_shipmethods_zone_methods_id = meth.id)
                WHERE loc.country = '$zone' AND (
                   (meth.basis = 'price'
                     AND cost.basis_min <= '$pr' 
                     AND (cost.basis_max >= '$pr' OR cost.basis_max IS NULL OR cost.basis_max = 0))
                   OR
                   (meth.basis = 'weight'
                     AND cost.basis_min <= '$wt' 
                     AND (cost.basis_max >= '$wt' OR cost.basis_max IS NULL OR cost.basis_max = 0))
                   )";

        $opts = array();
        $res = $pdb->query($sql);

        if (PEAR::isError($res)) {
            return $res;
        }
        elseif ($res->numRows() == 0) {
            return $this->raiseError("MISCONFIGURATION: no ship methods are available for this order: zone=$zone, pr=$pr, wt=$wt");
        }
        while ($row = $res->fetchRow()) {
            $opts[$row['name']] = $row['cost'];
        }
        return $opts;
    }



    /* set of hacky functions to use db_container to manage the ship method and ship ranges */
    /** singleton method for a DBC instance tuned to the cm_shipmethod_zone_methods table */
    function dbcontainerSingleton()
    {
        global $pdb;
        if (!isset($this->dbc)) {
            $this->dbc = new db_container($pdb);
            $this->dbc->set_table($this->_table_methods);
        }
        return $this->dbc;
    }


    /** get list of all methods in the zone_methods table
     * @throws PE
     * @return array */
    function fetch_method_list()
    {
        global $pdb;
        $sql = "SELECT meth.id, name, basis, zone_name 
                FROM cm_shipmethods_zone_methods meth, cm_shipmethods_zone_zones zone 
                WHERE zone.id = meth.cm_shipmethods_zone_zones_id
                ORDER BY meth.order_weight, zone.zone_name";
        $methods = $pdb->getAll($sql); // todo limits
        return $methods;

    }


    /**
     * get list of all basises and costs for the selected method
     * @param $meth_id int the ship_method id
     * @throws PE
     * @return array */
    function fetch_basises($meth_id)
    {
        global $pdb;
        $sql = sprintf("SELECT id, basis_min, basis_max, cost FROM cm_shipmethods_zone_costs
                        WHERE cm_shipmethods_zone_methods_id = %d
                        ORDER BY basis_min",
                        $meth_id);
        return $pdb->getAll($sql);
    }


    /**
     * clear out all basises. used presumably before insertning new ones
     * @throws PE
     */
    function clear_basises($meth_id)
    {
        global $pdb;
        $sql = sprintf("DELETE FROM cm_shipmethods_zone_costs
                        WHERE cm_shipmethods_zone_methods_id = %d",
                        $meth_id);
        return $pdb->query($sql);
    }

    /**
     * store a new basis for the given method
     * @param $meth_id int the ship_method id
     * @param $min int minimum of the range
     * @param $max int max of the range
     * @param $cost int how much it will cost ya
     * @throws PE
     */
    function store_basis($methid, $min, $max, $cost)
    {
        global $pdb;
        $dbc = new db_container($pdb);
        $dbc->set_table('cm_shipmethods_zone_costs');
        $vals = array('cm_shipmethods_zone_methods_id' => $methid,
                         'basis_min' => $min,
                         'basis_max' => $max,
                         'cost' => $cost);
        return $dbc->store($vals, true);
    }


    /**
     * get list of all basises and costs for the selected method
     * @param $meth_id int the ship_method id
     * @throws PE
     * @return array */
    function remove_range($rid)
    {
        global $pdb;
        $sql = sprintf("DELETE FROM cm_shipmethods_zone_costs
                        WHERE id = %d",
                        $rid);
        return $pdb->query($sql);
    }


    /* set of hacky functions to use db_container to manage the ship method and ship ranges */
    /** singleton method for a DBC instance tuned to the cm_shipmethod_zone_methods table */
    function _zoneDBContainerSingleton()
    {
        global $pdb;
        if (!isset($this->_zone_dbc)) {
            $this->_zone_dbc = new db_container($pdb);
            $this->_zone_dbc->set_table('cm_shipmethods_zone_zones');
            $this->_zone_dbc->child_relations = array('cm_shipmethods_zone_locales' => 'country');
        }
        return $this->_zone_dbc;
    }



    /* find out which countries we can ship to and return ISO codes as a list.
     * should only find countries where a shipping zone has been defined in control
     * @return array
     */
    function get_avail_countries()
    {
        global $pdb;
        $sql = "SELECT DISTINCT country FROM cm_shipmethods_zone_locales ORDER BY country";
        return $pdb->getCol($sql);
    }


    /**
     * get names and ids of all defined Shipping Zones
     */
    function get_avail_zones()
    {
        $zdbc =& $this->_zoneDBContainerSingleton();
        return $zdbc->fetch_any();
    }

    /**
     * store shipping zone name and list of assoc. countries
     */
    function store_zone($vals, $zone_id=null)
    {
        $zdbc =& $this->_zoneDBContainerSingleton();
        if ($zone_id) {
            $zdbc->set_id($zone_id);
        }
        return $zdbc->store($vals);
    }

    /**
     * fetch shipping zone name and list of assoc. countries
     * @param int a zone id
     * @return array(zone_id, zone_name, array(country_list))
     */
    function fetch_zone($zone_id)
    {
        $zdbc =& $this->_zoneDBContainerSingleton();
        $zdbc->set_id($zone_id);
        return $zdbc->fetch('', true);
    }

    function remove_zone($zone_id)
    {
        $zdbc =& $this->_zoneDBContainerSingleton();
        $zdbc->set_id($zone_id);
        return $zdbc->kill();
    }
}
