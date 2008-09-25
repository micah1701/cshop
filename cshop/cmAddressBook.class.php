<?php
require_once('db_container.class.php');

/**
 * basic container class for addresses - should belong to users
 *
 * $Id: cmAddressBook.class.php,v 1.5 2006/08/08 22:17:01 sbeam Exp $
 */
class cmAddressBook extends db_container {
    var $_table = 'cm_address_book';

    /** next 3 have to be set for use by store.dbcwrap.php. Really they should
     * be abstract in the parent class so we can make sure they exist */
    var $class_descrip = 'Address'; // english name of this class, what it represents
    var $table_orderby_default = 'name'; // column to sort listings by, by default
    var $table_name_column = 'name'; // column to get the "name" or description of a single instance from

    var $user_id;
    var $colmap = array('name'          => array('Name', 'text', 1),
                        'company'       => array('Company', 'text'),
                        'street_addr'   => array('Street Address', 'text', 1),
                        'addr2'         => array('Address line 2', 'text'),
                        'city'          => array('City', 'text', 1),
                        'state'         => array('State/Province', 'state_abbr'),
                        'postcode'      => array('Postal Code/ZIP', 'text', null, array('size'=>6)),
                        'country'       => array('Country', 'country_select', null, array('iso_codes'=>2), 1));
    
    var $control_header_cols = array('name'=>'Name', 'street_addr'=>'Address');

    function fetchAllByUser($userId) {
        $items = array();
        $sql = sprintf("SELECT id,%s FROM %s WHERE user_id = %d",
                        join(',', array_keys($this->colmap)),
                        $this->get_table_name(),
                        $userId);
        $res = $this->db->query($sql);
        while ($row = $res->fetchRow()) {
            $items[] = $row;
        }
        return $items;
    }
}


/* lame back-compat */
class address_container extends cmAddressBook {
}
