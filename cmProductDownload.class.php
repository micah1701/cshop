<?php

class cmProductDownload extends db_container {

    var $_table = 'cm_products_downloads';

    /** next 3 have to be set for use by store.dbcwrap.php. Really they should
     * be abstract in the parent class so we can make sure they exist */
    var $class_descrip = 'Downloads'; // english name of this class, what it represents
    var $table_orderby_default = 'name'; // column to sort listings by, by default
    var $table_name_column = 'name'; // column to get the "name" or description of a single instance from

    var $control_header_cols = array('name'=>'Name', 'is_active'=>'Active?');

    /* form definition arrays suitable for formex() */
    var $colmap = array('name' =>          array('Name', 'text', null, 1),
                        'is_active' =>  array('Is Active?', 'toggle', 1, null, 0),
                        'url' =>  array('URL', 'text', null, null, 0),
                        'cm_products_id' => array('', 'hidden')
                        );


    function fetch_by_product_id($pid, $cols=null) {
        $w = 'cm_products_id = '.intval($pid);
        return $this->fetch_any($cols, 0, 0, 'name', $w);
    }
}

