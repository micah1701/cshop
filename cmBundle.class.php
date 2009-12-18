<?php
/**
 * 
 * Class to represent cmProduct 'bundles', ie build-a-kit, build-a-skate, 'pick-yer-own' etc
 *
 * Each bundle can be configured to be made up of selections from certain categories (cmCategories).
 *
 * If the category is tagged as 'use in bundles' (via admin), it will show up in the admin tool
 * for the bundles. Then the quantity of individual products from that category that are required 
 * can be entered. 
 *
 * If a category is not flagged as 'live', then it shouldn't show up on the 
 * front-end. However these hidden Categories can
 * still be used to provide a range of products for use in Bundle 
 * configuration.
 *
 * Bundles are sort of 'meta' products and therefore are kept in their own table seperate from cm_products.
 *
 * inventory can be managed on a bundle-as-a-whole basis only. This is a simple qty field in the admin for the bundle.
 *
 * User's bundle selections are saved to the cart as a serialized array in cm_cart_items.product_attribs. 
 * This all is handled by cmCart
 *
 * TODO
 *   - make a category 'optional' within a bundle
 *   - allow a bundle colorways or other options, like normal products
 *   - create adders for certain premium product selections over the base cost
 *   - allow sub-selection of only certain products within each category
 *
 * for instance:
 *   "Skate" bundle - 
 *      Component "wheels" - 2 required from category 'Wheels'
 *      Component "deck" - 1 required from category 'Deck'
 *      Component "trucks" - 1 required from category 'Special Trucks'
 *      component "hardware" - 1 optional
 *      component "grip tape" - 1 optional
 *
 *
 *  "Holiday Gift Box 1" bundle
 *      Component "Dried Fruit" - 2 required
 *      Component "Sausages" - 1 required
 *      Component "Cheeze Spread" - 3 required
 *
 */


class cmBundle extends cmProduct {

    var $_table = 'cm_bundles';
    var $_bundles_categories_table = 'cm_bundles_categories';
    var $_related_products_table = 'cm_products_relations';

    var $colmap = array('title' => array('Title', 'text', null, true),
                        'sku' => array('SKU', 'text', null, 1),
                        'weight' => array('Weight (lbs)', 'numeric', null, array('size'=>8), 1),
                        'base_price' => array('Base Price', 'numeric', null, true),
                        'cm_ship_class_id' => array('Shipping Class', 'select', array(), null, 1),
                        'description' => array('Description', 'textarea', null, array('rows'=>2), false),
                        'long_description' => array('Additional Info', 'textarea', null, array('rows'=>8), false),
                        'qty_inventory' => array('Inventory (Qty. on Hand)', 'numeric', null, array('size'=>4), false),
                        'do_inventory' => array('Manage Inventory', 'toggle', true, false),
                        );

    var $colmap_help = array(
                             'weight' => 'Shipping calculation to be based on this weight, not individual items',
                             'qty_inventory' => 'Quantity on hand and available for purchase',
                             'do_inventory' => 'Deduct both bundle and individual product items from inventory when purchased.',
                         );


    /* auto fetch options from subclasses */
    function get_colmap() {
        $colmap = parent::get_colmap();

        if (isset($colmap['cm_ship_class_id']) and !count($colmap['cm_ship_class_id'][2])) {
            $colmap['cm_ship_class_id'][2] = cmShipping::fetch_ship_class_opts($this->db);
        }
        return $colmap;
    }




    /* override to use same db seq as the products table (the parent). This is why sequences are nice sometimes */
    function get_sequence() {
        return 'cm_products';
    }


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
    function fetch($cols = '', $kids=false, $get_images=false) {
        if ($vals = parent::fetch($cols, $kids, $get_images)) {
            $vals['required_cats'] = $this->get_required_cats();
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


    function get_required_cats() {
        $required_cats = array();
        $db_cat_map =& $this->_get_category_map_singleton();
        if ($res = $db_cat_map->fetch_any(array('cm_categories_id', 'required'), 0, 0, null, "cm_bundles_id = " . $this->get_id())) {
            foreach ($res as $row) {
                $required_cats[$row['cm_categories_id']] = $row['required'];
            }
        }
        return $required_cats;
    }

    function store_user_bundle() {
    }

    /**
     * given a list of product ids, go over each and make sure they match up to 
     * the required categories for this bundle
     */
    function validate_product_selection($product_skus) {
        $req_cats = $this->get_required_cats();
        $picked = array();
        $product = cmClassFactory::getSingletonOf(CSHOP_CLASSES_PRODUCT, $this->db);

        foreach ($product_skus as $sku) { 
            $product->set_id_by_sku($sku);
            $pinfo = $product->fetch(array('id', 'title', 'display_weight'));
            foreach ($product->fetch_product_categories(null, false) as $cat) { // list of all cats this product is in, even the inactive ones
                if (!isset($req_cats[$cat['id']])) continue;

                if (!isset($picked[$cat['id']])) $picked[$cat['id']] = array();
                $pinfo['sku'] = $sku;
                $picked[$cat['id']][] = $pinfo;
            }
            $product->reset();
        }
        foreach ($req_cats as $cat_id => $qty) {
            if (!isset($picked[$cat_id]) or count($picked[$cat_id]) != $qty) {
                return false;
            }
        }
        $this->product_selection = $picked;
        // passed validation. has proper number of products from each category.
        return true;
    }


    /** get the baseprice for the product from the DB (no adders from attribs...)
     * @return float */
    function fetch_baseprice() {
        return $this->get_header('base_price');
    }

    /**
     * get the total price for this bundle including any adders or optional items
     * @return float
     *
     * TODO handle adders for certain products and optional items
     */
    function get_price() {
        return $this->fetch_baseprice();
    }


    /**
     * get the most qty from all inventory recs for this product. Used for select qty in storefront I think.
     * @param $pid a productid
     * @return int
     */
    function fetch_max_qty_avail($pid) {
        return $this->get_header('qty_inventory');
    }

    /**
     * Bundle does some acrobatics to pull the inventory for each contained product, as well as itself.
     * @param $skus array of product skus contained in the bundle
     * @param $qty how many to pull (applies to Bundle and all products)
     */
    function pull_inventory($skus, $qty) {
        $do_inventory = $this->get_header('do_inventory');
        if ($do_inventory) {

            $product = cmClassFactory::getSingletonOf(CSHOP_CLASSES_PRODUCT, $this->db);
            $sth = $this->db->prepare("SELECT id FROM {$this->_inventory_table} WHERE sku = ?");

            foreach ($skus as $sku) {
                $res = $this->db->execute($sth, $sku);
                if ($row = $res->fetchRow()) {
                    $res = $product->pull_inventory($row['id'], $qty);
                    if (!$res or PEAR::isError($res)) {
                        trigger_error("No effect when deducting inventory qty '$qty' for sku '$sku' ($res)", E_USER_WARNING);
                    }
                }
                else {
                    trigger_error("Unknown SKU '$sku' found in bundle items", E_USER_WARNING);
                }
            }
            $sql = sprintf("UPDATE cm_bundles SET qty_inventory = (qty_inventory - %d) WHERE id = %d", $qty, $this->get_id());
            return $this->db->query($sql);
        }
    }


}
