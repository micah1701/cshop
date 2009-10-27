<?php
require_once('db_container.class.php');
require_once(CSHOP_CLASSES_PRODUCT. '.class.php');
require_once(CSHOP_CLASSES_PRODUCT_CATEGORY . '.class.php');


/**
 * 
 * Class to represent cmProduct 'bundles', ie build-a-kit, build-a-skate, 'pick-yer-own' etc
 *
 * Each bundle contains one or more components. Each component may be composed of one or more products, and may or may not be required.
 *
 * for instance:
 *   "Skate" bundle - 
 *      Component "wheels" - 2 required from products X, Y or Z 
 *      Component "deck" - 1 required
 *      Component "trucks" - 1 required
 *      component "hardware" - 1 optional
 *      component "grip tape" - 1 optional
 *
 *
 *  "Holiday Gift Box 1" bundle
 *      Component "Dried Fruit" - 2 required
 *      Component "Sausages" - 1 required
 *      Component "Cheeze Spread" - 3 required
 *
 * components can be created within cm_bundle_components table
 *
 * valid products from each category can be dropped in via admin.
 *
 */
class cmBundle extends cmProduct {

    var $_table = 'cm_bundles';
    var $_bundles_categories_table = 'cm_bundles_categories';
    var $_related_products_table = 'cm_products_relations';

    var $colmap = array('title' => array('Title', 'text', null, true),
                        'base_price' => array('Base Price', 'numeric', null, true),
                        'description' => array('Description', 'textarea', null, array('rows'=>4), false),
                        );


    // normal store() but reset and then store bundle category info
    function store(&$vals) {
        $required_cats = array();
        if (!empty($vals['required']) and is_array($vals['required'])) {
            $required_cats = $vals['required'];
            unset($vals['required']);
        }
        if ($res = parent::store($vals)) {
            $this->_clear_category_map();
            $db_cat_map =& $this->_get_category_map_singleton();
            $this_id = $this->get_id();
            foreach ($required_cats as $cat_id => $qty) {
                if (is_numeric($qty) and $qty > 0) {
                    $res = $db_cat_map->store(array('cm_categories_id' => $cat_id, 'cm_bundles_id' => $this_id, 'required' => $qty));
                    $db_cat_map->reset();
                }
            }
            return $res;
        }
    }



    /* normal fetch but get bundle categories afterwards and append */
    function fetch($cols = '', $kids=false) {
        if ($vals = parent::fetch($cols, $kids)) {
            $vals['required_cats'] = array();
            $db_cat_map =& $this->_get_category_map_singleton();
            if ($res = $db_cat_map->fetch_any(array('cm_categories_id', 'required'), 0, 0, null, "cm_bundles_id = " . $this->get_id())) {
                foreach ($res as $row) {
                    $vals['required_cats'][$row['cm_categories_id']] = $row['required'];
                }
            }
            return $vals;
        }
        
    }

    private function _get_category_map_singleton() {
        if (!isset($this->db_cat_map))
            $this->db_cat_map =& $this->factory($this->db, $this->_bundles_categories_table);

        return $this->db_cat_map;
    }

    private function _clear_category_map() {
        $sql = "DELETE FROM {$this->_bundles_categories_table} WHERE cm_bundles_id = " . $this->db->quoteSmart($this->get_id());
        return $this->db->query($sql);
    }

    function create_bundle() {
    }

    function get_categories() {
    }

    function get_products() {
    }

    function store_user_bundle() {
    }


}
