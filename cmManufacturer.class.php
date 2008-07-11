<?php
require_once('db_container.class.php');

/**
 * basic container class for occasions table - used from store.dbc_wrap.php
 *
 */
class cmManufacturer extends db_container {

    var $_table = 'cm_manufacturers';

    /** next 3 have to be set for use by store.dbcwrap.php. Really they should
     * be abstract in the parent class so we can make sure they exist */
    var $class_descrip = 'Manufacturer'; // english name of this class, what it represents
    var $table_orderby_default = 'name'; // column to sort listings by, by default
    var $table_name_column = 'name'; // column to get the "name" or description of a single instance from

    var $control_header_cols = array('name'=>'Name', 'is_active'=>'Active?');

    /* form definition arrays suitable for formex() */
    var $colmap = array('name' =>          array('Name', 'text', null, 1),
                        'is_active' =>  array('Is Active?', 'toggle', 1, null, 0),
                        'url' =>  array('URL', 'text', null, null, 0),
                        'logo' =>  array('Logo', 'image_upload', null, array('allowed'=>'web_images_nogif',
                                                                          'maxdims' => IMG_MAX_DIMS,
                                                                          'path'=> CSHOP_MEDIA_FULLPATH,
                                                                          'ws_path' => CSHOP_MEDIA_URLPATH,
                                                                          'resize_method'=>'shrink_to_fit',
                                                                          'max_w' => '120',
                                                                          'max_h' => '120'), null, 0),
                        'description' =>  array('Description', 'textarea', null, 0),
                        );



    /**
     * get list of categories that are available from this manufacturer.
     * This maybe should not be here, because it knows too mych aboiut the category object
     * @params $cols list of columns to fetch (depends on DB naming)
     * @params $orderby str SQL orderby frag
     * @return array - list of fetched categories
     */
    function fetch_avail_categories($cols=array(), $orderby='cat.feature_rank,cat.name') {
        $class = CSHOP_CLASSES_PRODUCT_CATEGORY;
        if (empty($cols)) {
            $cols = array('cat.id', 'cat.name');
        }
        $cols = join(',', $cols);
        $cats = array();
        $sql = "SELECT DISTINCT cat.id FROM cm_categories cat, cm_products_categories pc, cm_products p 
                WHERE pc.cm_categories_id = cat.id 
                    AND pc.cm_products_id = p.id 
                    AND p.cm_manufacturers_id = " . $this->db->quote($this->get_id()) . "
                ORDER BY $orderby";

        $res = $this->db->query($sql);
        while ($row = $res->fetchRow()) {
            $cat = new $class($this->db);
            $cat->set_id($row['id']);
            $cats[] = $cat->fetch($cols);
        }
        return $cats;
    }


    /**
     * find a manufacturer by name, set the id of this instance appropriately, and return the first one matching. 
     * @param $name string
     * @return array fetched row
     */
    function fetch_by_name($name) {
        $sql = "SELECT id FROM {$this->get_table_name()} WHERE name = " . $this->db->quote($name);
        if ($id = $this->db->getOne($sql)) {
            $this->set_id($id);
            return $this->fetch();
        }
    }
}
?>
