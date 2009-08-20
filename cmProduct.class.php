<?php
require_once('db_container.class.php');
require_once(CSHOP_CLASSES_PRODUCT_CATEGORY . '.class.php');
require_once(CSHOP_CLASSES_PRODUCT_OPTION   . '.class.php');
require_once('mailer.class.php');


/**
 * basic container class for marquisjet products
 *
 */
class cmProduct extends db_container {

    var $_table = 'cm_products';
    var $_inventory_table = 'cm_inventory';
    var $_category_table = 'cm_categories';
    var $_category_map_table = 'cm_products_categories';
    var $_related_products_table = 'cm_products_relations';

    /** private - save the category id of this product (though there may be multiple */
    var $_cat_id = null;

    /** next 3 have to be set for use by store.dbcwrap.php. Really they should
     * be abstract in the parent class so we can make sure they exist */
    var $class_descrip = 'Product'; // english name of this class, what it represents
    var $table_orderby_default = 'title'; // column to sort listings by, by default
    var $table_name_column = 'title'; // column to get the "name" or description of a single instance from

    var $use_related_products = CSHOP_USE_RELATED_PRODUCTS;



    /* form definition arrays suitable for formex() */
    var $colmap = array('title' =>         array('Title/Name', 'text', null, 1),
                    'sku' =>           array('SKU', 'text', null, 1),
                    'price' =>         array('Base Price', 'numeric', null, array('size'=>8), 1),
                    //'list_price' =>      array('List Price', 'numeric', null, array('size'=>8)),
                    'weight' =>        array('Weight (lbs)', 'numeric', null, array('size'=>8), 1),
                    #'inv_qty' =>        array('Quantity on hand', 'numeric', null, array('size'=>8), 1),
                    'cm_ship_class_id' => array('Shipping Class', 'select', array(), null, 1),
                    'description' =>         array('Description', 'textarea'),
                    'cm_manufacturers_id' => array('Manufacturer', 'select', array(), null, 1),
                    'is_featured' =>      array('Featured Product?', 'toggle'),
                    'feature_rank' =>      array('Feature Rank', 'numeric', null, array('size'=>4), 0),

                    'is_active' =>          array('Make Live?', 'toggle'),
                    /*
                     * 'imageid' =>    array('Image', 'image_upload', null, array('allowed'=>'web_images',
                     *                                   'maxdims' => '400x600',
                     *                                   'path'=> PRODUCTS_UPLOAD_SAVE_PATH,
                     *                                   'ws_path' => PRODUCTS_MEDIA_URLPATH )),
                     *                                   */

                    );

    var $child_relations = array('cm_products_relations'=> 'related_to',
                                 'cm_products_categories'=> 'cm_categories_id');

    var $control_header_cols = array('title'=>'Title', 'sku'=>'SKU', 'price'=>'Base Price', 'cm_manufacturer' => 'Mfr', 'is_featured' => 'Featured', 'feature_rank' => 'Feature Rank', 'is_active'=>'Active');


    var $colmap_help = array(
                             'sku' => 'SKU must be unique to this product',
                             'is_available' => 'if checked, this product will be available for online e-commerce checkout. Otherwise, customer will have to place an inquiry',
                             'price' => 'Actual price of product available for checkout',
                             'list_price' => 'If greater than the Price, product will be marked as ON SALE in storefront',
                             'is_active' => 'If unchecked, product will be hidden from customers and unavailable for purchase',
                             'feature_rank' => 'If this is a featured product, this field may be used to sort the products in the Featured Product display area. Does not have to be sequential.',
                             'is_featured' => 'If checked, this product may be displayed in the Featured Product display area.',
                             'order_weight' => 'Numeric value used to sort products in all customer display areas, when another sorting criteria has not been selected. Does not have to be sequential. Sorted from HIGH to LOW',
                         );



    /** get just the title for this product
     * @return string
     */
     function get_title() {
         $pro = $this->fetch(array('title'));
         return $pro['title'];
     }


     function fetch($cols='', $kids=false, $get_images=false)
     {
         $info = parent::fetch($cols, $kids);
         if ($get_images) {
             $info['images'] = $this->get_images(null, $get_images);
         }
         return $info;
     }



    /*
     * fillout category options in colmap and return
     */
    function get_colmap()
    {
        /*
         * if (empty($this->_filled_colmap)) {
         *  $this->colmap['cm_products_categories'][2] = $this->get_product_category_options();
         * }
         */
        return $this->colmap;
    }


     /**
      * get array describing all product attributes for this product type, and given the inventory_id which is used to look them up
      * @param int ket in inventory table
      * @return array
      * @todo this should be in an inventory class which knows the table names etc.
      * @todo it also needs to know about things other than size + color
      */
      function get_attrib_array($invid) {

          $attr = array();
          $label_size = (defined('CSHOP_PRODUCT_ATTRIB_LABEL_SIZE'))? CSHOP_PRODUCT_ATTRIB_LABEL_SIZE : 'Size';
          $label_color = (defined('CSHOP_PRODUCT_ATTRIB_LABEL_COLOR'))? CSHOP_PRODUCT_ATTRIB_LABEL_COLOR : 'Color';
          $sql = sprintf("SELECT inv.id, qty, sizes_id, colorways_id, cw.name AS color, s.fullname AS size
                          FROM cm_inventory inv
                              LEFT JOIN cm_colorways cw ON (cw.id = inv.colorways_id)
                              LEFT JOIN cm_sizes s ON (s.id = inv.sizes_id)
                          WHERE inv.id = %d",
                          $invid);
          $inv = $this->db->getRow($sql);
          if ($inv['size']) $attr[$label_size] = $inv['size'];
          if ($inv['color']) $attr[$label_color] = $inv['color'];
          return $attr;
      }


    /** set product id by SKU. If SKU is found call set_id() on self using the productid
     * @changes look in inventory table first Wed Aug  9 23:17:21 EDT 2006
     * @param sku string
     * @return the pid or PE on failure to find sku
     */
    function set_id_by_sku($sku)
    {
        $pid = null;
        $sql = sprintf("SELECT product_id FROM %s WHERE sku = %s",
                        $this->_inventory_table, 
                        $this->db->quote($sku));

        if (! ($pid = $this->db->getOne($sql))) {
            $sql = sprintf("SELECT id FROM %s WHERE sku = %s", 
                            $this->get_table_name(), 
                            $this->db->quote($sku));
            $pid = $this->db->getOne($sql);
        }
        if ($pid) {
            $this->set_id($pid);
            return $pid;
        }
        else {
            trigger_error("invalid SKU '$sku'", E_USER_ERROR);
        }
    }


    /** get a list of ids and names for all products (for nav)
     * @return array
     */
    function get_product_list($orderby = null) {
        if (!$orderby) {
            $orderby = 'sku';
        }
        $sql = sprintf("SELECT id, title FROM %s WHERE is_active = 1 ORDER BY %s",
                       $this->_table,
                       $orderby);

        $items = array();
        $res = $this->db->query($sql);
        while ($row = $res->fetchRow()) {
            $items[$row['id']] = $row['title'];
        }
        return $items;
    }

    /* get list of all product categories down to the given level
     * (marquisjets implementation actually returns products, they don't use cats)
     * @param $level optional include only category at this category nesting level
     * @param $parent_cat optional include only cats with this parent_category_id
     * @param $exclude_empty if true, return only categories that contain something (!)
     * @return array
     */
    function get_categories($level=null, $startwith=0, $exclude_empty=false) {
        $pc =& $this->_category_factory();
        return $pc->get_category_tree($startwith);
    }

    /** 
     * util function to get a list of all product categories, id => name 
     * get cats and subcats, and do some footwork to make a nested menu 
     * that is good for <select> lists mostly. So thats about it.
     * @param $add_any bool add a [ANY] option at the top
     * @return array
     */
    function get_product_category_options($add_any=false) {
        $arr = array();
        if ($add_any) {
            $arr[''] = '[ANY]';
        }
        $pcat =& $this->_category_factory();
        $arr = $arr + $pcat->get_categories_for_select();
        return $arr;
    }




    /** get a list of all featured product categories
     * @param $rank int opt feature_rank to look for
     * @return array
     */
    function get_featured_categories($rank=null, $parent=null) {
        $pc =& $this->_category_factory();
        return $pc->get_featured_categories($rank, $parent);
    }

    /** create an instance of cmProductCategory if not already have one 
     */
    function _category_factory()
    {
        if (!isset($this->cmCategory)) {
            $c = CSHOP_CLASSES_PRODUCT_CATEGORY;
            $this->cmCategory =& new $c($this->db);
        }
        return $this->cmCategory;
    }

    /** find the parent category, if any, of the given category id. If the
     * given categoruy is top-level, then return it back
     * @param $catid int a category id
     * @return int the parent category id, or itself */
    function get_parent_category_id($catid) {
        $pc =& $this->_category_factory();
        return $pc->get_parent_category($catid);
    }

    /** fetch all known info about the category given by cat_id
     * @param int $cat_id
     * @return array k=>v db result
     */
     function fetch_category_info($cat_id = null)
     {
        $pc =& $this->_category_factory();
        $pc->set_id($cat_id);
        return $pc->fetch();
     }

     /**
      * get a list of all categories this product is in 
      * @return array
      */
     function fetch_product_categories($level = null)
     {
         $sql = "SELECT c.id, c.name FROM {$this->_category_map_table} pc, {$this->_category_table} c
                 WHERE pc.cm_categories_id = c.id AND pc.cm_products_id = " . $this->get_id();
         if ($level) $sql .= " AND c.level = $level";
         return $this->db->getAll($sql);
     }


     /**
      * get the id of the primary category for this product, if used in this implementation.
      * @return int or null if not set
      */
     function fetch_primary_category($level = null)
     {
         $sql = "SELECT cm_category_primary FROM " . $this->get_table_name() . "
                 WHERE id = " . $this->get_id();
         return $this->db->getOne($sql);
     }




    /**
     * get all DB results where a product is featured 
     */
    function get_all_featured_products($cols=null)
    {
        if (!is_array($cols)) {
            foreach (array_keys($this->colmap) as $c) {
                if (!in_array($c, array_keys($this->child_relations))) {
                    $cols[] = $c;
                }
            }
            $cols[] = 'id';
        }
        $res = $this->fetch_any($cols, 0, 0, 'feature_rank', 'is_featured > 0');
        for ($i=0; $i<count($res); $i++) {
            $res[$i]['images'] = $this->get_thumb_imgs($res[$i]['id'], 1);
        }
        return $res;
    }




    /**
     * get all DB results having a cm_manufacturers_id matching the param
     */
    function fetch_by_manufacturer($mfrid, $cols=null, $offset=0, $range=0, $get_thumbs=false, $orby='title')
    {

        $w = sprintf(" cm_manufacturers_id = %d ", $mfrid);

        $res = $this->fetch_any($cols, $offset, $range, $orby, $w);

        if ($get_thumbs && $res) {
            for ($i=0; $i<count($res); $i++) {
                $res[$i]['images'] = $this->get_thumb_imgs($res[$i]['id'], 1);
            }
        }
        return $res;
    }


    /**
     * fetch list of products within a certain category and by a certain manufacturer.
     * @params $mfrid int a manufacturer_id
     * @params $catid int a category_id
     * @params $cols string column list
     * @params $get_thumbs bool - include the thumbnail images as sub-arrays?
     * @params $orby str SQL frag for orderby
     * @params $get_inactive_products bool what it says
     * @return array complex struct
     */
    function fetch_by_category_and_mfr($mfrid, $catid, $cols=null, $get_thumbs=false, $orby=null, $get_inactive_products=false)
    {
        if (empty($cols)) {
            $cols = '*';
        }
        $cats = array();
        if (empty($orby)) $orby = $this->table_orderby_default;
        $sql = sprintf("SELECT $cols from cm_categories cat, cm_products_categories pc, cm_products p 
                        WHERE pc.cm_categories_id = cat.id 
                            AND pc.cm_products_id = p.id 
                            AND p.cm_manufacturers_id = %d
                            AND cat.id = %d
                            AND p.is_active = %d
                        ORDER BY $orby"
                        , $mfrid, $catid,
                        (empty($get_inactive_products)? 1:0) );

        $res = $this->db->query($sql);
        while ($row = $res->fetchRow()) {
            if ($get_thumbs) {
                $row['images'] = $this->get_thumb_imgs($row['id'], 1);
            }
            $cats[] = $row;
        }
        return $cats;
    }

    /* simple fetch_any restricted by the given REGEXP on title
     * @param $re str opt regular expression, mysql-specific syntax
     * @return array */
    function fetch_by_product_title($re=null) {
        $w = '';
        if ($re) { $w = " title REGEXP(". $this->db->quote($re) .") "; }
        return $this->fetch_any(array('id','title'), 0, 0, 'title', $w);
    }



    /* function to get the pid of one of the featured products. Gets the count
     * where is_featured = 1 and then randomly selects one row from the
     * resultset
     *
     * @return array or false if there is nothing featured
     */
    function get_random_featured_product() {
        $sql = "SELECT id FROM " . $this->_table . " WHERE is_featured = 1";
        $res = $this->db->query($sql);

        $count = $res->numRows();
        if ($count == 0) {
            return false;
        }
        else {
            $row = $res->fetchRow(DB_FETCHMODE_ORDERED, rand(0,$count-1));
            $id = $row[0];
            $class = get_class($this);
            $pro = new $class($this->db);
            $pro->set_id($id);
            $res->free();
            return $pro;
        }
    }


    /**
     * get all images pertaining to this product. Return full info on large
     * images, thumbs and dimensions of each
     * @return array
     */
    function get_images($pid=null, $img_class=null) {
        if (!$pid) $pid = $this->get_id();

        $sql = sprintf("SELECT colorways_id, system_location, class
                            , filename_large, dims_large, filename_thumb, dims_thumb, filename_zoom
                        FROM cm_product_images
                        WHERE cm_products_id = %d", $pid);

        if ($img_class)
            $sql .= " AND class = " . $this->db->quote($img_class);

        $sql .= " ORDER BY order_weight";
        $res = $this->db->query($sql);
        $items = array();
        while ($row = $res->fetchRow()) {
            $items[] = $row;
        }
        return $items;
    }


    /** get all the available colorways for the dgiven product
     * @return array
     */
     function get_colorways() {
         $sql = sprintf("SELECT DISTINCT(colorways_id)
                         FROM %s 
                         WHERE product_id = %d AND colorways_id IS NOT NULL",
                         $this->_inventory_table,
                         $this->get_id());
         $cids = $this->db->getCol($sql);
         if (count($cids)) {
             return $this->get_all_colorways($cids);
         }
     }


     /*
      * get all available colorways for all products. 
      * @param color_ids opt array limit to the given set of ids
      */
     function get_all_colorways($color_ids=null)
     {
         $items = array();
         $sql = "SELECT id, name, rgb_value, code FROM cm_colorways ";
         if (is_array($color_ids)) {
             $sql .= sprintf(" WHERE id IN (%s) ", join(',', $color_ids));
         }
         $sql .= " ORDER BY name";
         $res = $this->db->query($sql);
         while ($row = $res->fetchRow()) {
             $items[$row['id']] = $row;
         }
         return $items;
     }



    /** get all the available sizes for the dgiven product
     * @return array
     */
     function get_sizes() {
         $sql = sprintf("SELECT DISTINCT(sizes_id)
                         FROM %s 
                         WHERE product_id = %d AND sizes_id IS NOT NULL",
                         $this->_inventory_table,
                         $this->get_id());
         $cids = $this->db->getCol($sql);
         if (count($cids)) {
             return $this->get_all_sizes($cids);
         }
     }

     /*
      * get all available sizes for all products. 
      * @param size_ids opt array limit to the given set of ids
      */
     function get_all_sizes($size_ids=null) {
         $items = array();
         $sql = "SELECT id, code, fullname FROM cm_sizes ";
         if (is_array($size_ids)) {
             $sql .= sprintf(" WHERE id IN (%s) ", join(',', $size_ids));
         }
         $sql .= " ORDER BY order_weight";
         $res = $this->db->query($sql);
         while ($row = $res->fetchRow()) {
             $items[$row['id']] = $row;
         }
         return $items;
     }



     /**
      * fetch a list of products where a certain colorway is in stock, or fetch all ordered by colorway name
      * @param int opt colorway id, fetch only products with this color in stock
      * @param int opt cat_id only products within this category
      * @param str opt order by this columns
      */
     function fetch_by_colorway($colorways_id=null, $cat_id=null, $orby=null)
     {
         $sql = "SELECT p.id, p.title, p.price, cw.name AS colorname, i.colorways_id 
                 FROM cm_inventory i
                   INNER JOIN cm_products p ON (p.id = i.product_id)
                   INNER JOIN cm_colorways cw ON (cw.id = i.colorways_id)
                   LEFT JOIN cm_products_categories pc ON (pc.cm_products_id = i.product_id)
                 WHERE i.qty > 0 ";

        if ($cat_id) {
            $sql .= sprintf(" AND pc.cm_categories_id = %d ", $cat_id);
        }
        if ($colorways_id) {
            $sql .= sprintf(" AND i.colorways_id = %d ", $colorways_id);
        }
        $sql .= " GROUP BY i.product_id, i.colorways_id
                  ORDER BY ";
        $sql .= ($orby)? $orby : 'colorname';


        $get_thumb_imgs_sth = $this->db->prepare("SELECT 
                                     filename_thumb, dims_thumb, system_location
                                           FROM cm_product_images
                                           WHERE cm_products_id = ? AND colorways_id = ?
                                           ORDER BY order_weight");

        $chunks = array();

        $res = $this->db->query($sql);
        $this->numRows = $res->numRows();

        for ($ptr = $this->_offset; ($this->_range == 0) or (($this->_offset + $this->_range) > $ptr); $ptr++) { 
            if (! ($row = $res->fetchRow(DB_FETCHMODE_ASSOC, $ptr))) break;

            $img_res = $this->db->execute($get_thumb_imgs_sth, array($row['id'], $row['colorways_id']));
            $row['images'] = array();
            $row['images'][] = $img_res->fetchRow();
            $img_res->free();

            $chunks[] = $row;
        }
        $res->free();

        return $chunks;
     }


    /**
     * fetch a array of product id's=>titles for each product belonging to the given cat id
     * @param $catid int a category id
     * @param $cols arr optional list of product fields to retrieve, default (id, title)
     * @param $get_inactive bool fetch products marked as inactive too?
     * @param $get_thumbs int how many product thumbnails to get for each product
     * @param $only_featured bool get only products that have an is_featured flag set
     * @param $descendants bool get products in this cat AND any child cats
     * @param $orby string order by the given column/alias (hopefull it exist)
     * @return array
     */
    function selectByCategory($catids, $cols=null, $get_inactive=false, $get_thumbs=false, $only_featured=false, $descendants=false, $orby=null) {
        if (!$cols) {
            $cols = array('title');
        }
        if (!is_array($catids)) {
            $catids = array($catids);
        }
        $catids = array_filter($catids, 'is_numeric');
        if (empty($catids)) {
            return $this->raiseError("cannot select null category id");
        }

        if ($descendants) {
            $sql = sprintf("SELECT id FROM cm_categories WHERE parent_cat_id IN (%s)",
                            join(',', $catids));
            $kid_cats = $this->db->getCol($sql);
        }
        $items = array();
        $sql = sprintf("SELECT DISTINCT p.id, %s 
                        FROM %s p 
                           , %s pc
                        WHERE p.id = pc.cm_products_id
                        %s %s
                        AND (cm_categories_id IN (%s) ",
                        join(',', $cols),
                        $this->get_table_name(),
                        $this->_category_map_table,
                        (!$get_inactive)? ' AND is_active = 1 ' : '',
                        ($only_featured)? ' AND p.is_featured = 1 ' : '',
                        join(',',$catids));
        if ($descendants and !empty($kid_cats)) {
            $sql .= " OR cm_categories_id IN (". join(',', $kid_cats) .") ";
        }

        if (!$orby) {
            $orby = "p.feature_rank, p.title, p.id";
        }

        $sql .= "\n) ORDER BY $orby";
        $res = $this->db->query($sql);

        $this->numRows = $res->numRows();

        for ($ptr = $this->_offset; 
             ($this->_range == 0) or (($this->_offset + $this->_range) > $ptr);
             $ptr++) { 
            if (! $row = $res->fetchRow(DB_FETCHMODE_ASSOC, $ptr)) break;

            if ($get_thumbs) {
                if ($get_thumbs === true) { // - this is for back-compat
                    $thumbs = $this->get_thumb_imgs($row['id']);
                    foreach ($thumbs as $k => $v) {
                        $row[$k] = $v;
                    }
                }
                elseif (is_integer($get_thumbs)) {
                    $row['images'] = $this->get_thumb_imgs($row['id'], $get_thumbs);
                }
                else { 
                    $row['images'] = $this->get_thumb_imgs($row['id'], 1, $get_thumbs);
                }
            }

            $items[] = $row;
        }
        return $items;
    }


    /**
     * util function to execute SQL to get thumbnail image info for the given
     * product_id
     * @param $pid int productid to grab thumbnails for
     * @param $limit int limit to this many recors
     * @return array
     * @change Tue Jan 15 17:02:31 EST 2008 - changed function name to remove the leading _ to make it "public" haha
     */
    function get_thumb_imgs($pid, $limit=1, $class=null)
    {
        if (!isset($this->_get_thumb_imgs_sth)) {
            $sql = "SELECT filename_thumb, dims_thumb, filename_large, dims_large,  system_location
                                           FROM cm_product_images
                                           WHERE cm_products_id = ?";
            if (!empty($class)) $sql .=  " AND class = ? ";
            $sql .=                      " ORDER BY order_weight";
            $this->_get_thumb_imgs_sth = $this->db->prepare($sql);
        }
        if (!empty($class)) {
            $img_res = $this->db->execute($this->_get_thumb_imgs_sth, array($pid, $class));
        }
        else {
            $img_res = $this->db->execute($this->_get_thumb_imgs_sth, $pid);
        }
        $chunks = array();
        for ($ptr=0; $ptr<$limit; $ptr++) {
            if (! ($img_row = $img_res->fetchRow(DB_FETCHMODE_ASSOC, $ptr))) break;

            $chunks[] = $img_row;
        }
        $img_res->free();
        return $chunks;
    }



    /**
     * fetchs all combinations of inventory for this product, with qtys. For control.
     * @return array
     */
    function fetch_all_inventory() {

        $items = array();
        $sql = sprintf("SELECT inv.id, qty, sizes_id, colorways_id, inv.sku, cw.name AS color, s.fullname AS size
                              , adder, IFNULL((p.price + adder), p.price) AS total_price
                        FROM cm_products p, %s inv
                          LEFT JOIN cm_colorways cw ON ( cw.id = inv.colorways_id )
                          LEFT JOIN cm_sizes s ON ( s.id = inv.sizes_id )        
                        WHERE p.id = inv.product_id AND inv.product_id = %d
                        ORDER BY s.order_weight, color",
                        $this->_inventory_table,
                        $this->get_id());

        $res = $this->db->query($sql);
        while ($row = $res->fetchRow()) {
            $items[] = $row;
        }
        return $items;
    }


    /**
     * try to get info from a particular row in the inventory for this product.
     * Matches against the productid and any attribs that are set
     * @param $pid int a productid
     * @param $attribs arr mapping colname => value, e.g. sizes_id => 93
     * @return array db->getRow() result */
    function fetch_inventory_item($pid, $attribs=array()) {

         $sql = sprintf("SELECT id, qty, sku FROM %s WHERE product_id = %d",
                        $this->_inventory_table,
                        $pid);

         if (count($attribs)) {
             $sql .= ' AND ';

             $where = array();
             foreach ($attribs as $k => $v) {
                 $where[] = sprintf("%s = '%s'", $k, addslashes($v));
             }
             $sql .= join(' AND ', $where);
         }
         return $this->db->getRow($sql);
    }

    /**
     * get the most qty from all inventory recs for this product. Used for select qty in storefront I think.
     * @param $pid a productid
     * @return int
     */
    function fetch_max_qty_avail($pid) {
        $sql = sprintf("SELECT MAX(qty) FROM %s WHERE product_id = %d", 
                        $this->_inventory_table,
                        $pid);
        return $this->db->getOne($sql);
    }



    /**
     * get info on the inventory record by inv id
     * @param $invid int the inventory id row id
     * @return array(product_id, qty, sku)
     */
    function fetch_inventory($inv_id)
    {
         $sql = sprintf("SELECT id, product_id, qty, sku FROM %s WHERE id = %d",
                        $this->_inventory_table,
                        $inv_id);
         return $this->db->getRow($sql);
    }

    /** 
     * determine if there is ANY inventory at all for this product, in any
     * attribute combination.
     * @param $pid product_id
     * @returns int total of inventory records
     */
    function has_positive_inventory() {
         $sql = sprintf("SELECT SUM(qty) FROM %s WHERE product_id = %d",
                        $this->_inventory_table,
                        $this->get_id());
         return $this->db->getOne($sql);
    }


    /**
     * change the quantity of an inventory item id'd by invid
     * @param $invid int the inventory id row id
     * @param $qty int new quantity
     */
    function update_inventory($invid, $qty)
    {
        $sql = sprintf("UPDATE {$this->_inventory_table} SET qty = %d
                        WHERE id = %d",
                        $qty, $invid);
        $res = $this->db->query($sql);
        return $this->db->affectedRows();
    }

    /**
     * increment the views_count field. happens each time the product detail page is loaded
     */
    function views_incr() {
        $sql = sprintf("UPDATE %s SET view_count = (view_count+1) WHERE id = %d",
                        $this->get_table_name(),
                        $this->get_id());
        return $this->db->query($sql);
    }


    /**
     * get the id of a random product whose feature_rank = 1
     * @param $cat - if set, limit to within this category id
     */
    function get_rand_featured($cat=null) {
        if (!$cat) {
            $sql = sprintf("SELECT id FROM %s WHERE is_featured = 1",
                            $this->get_table_name());
        }
        else {
            $sql = sprintf("SELECT p.id FROM %s p, cm_products_categories pc 
                            WHERE p.id = pc.cm_products_id AND pc.cm_categories_id = %d AND p.is_featured = 1",
                            $this->get_table_name(),
                            $cat);
        }
        $res = $this->db->query($sql);
        $num = $res->numRows();
        if ($num) {
            $rownum = ($num > 1)? rand(0, $num-1) : 0;
            $row = $res->fetchRow(DB_FETCHMODE_ASSOC, $rownum);
            $res->free();
            return $row['id'];
        }
    }

    /** get the baseprice for the product from the DB (no adders from attribs...)
     * @return float */
    function fetch_baseprice() {
        if ($h = $this->fetch(array('price'))) {
            return $h['price'];
        }
    }

    /** get the baseprice for the product from the DB (no adders from attribs...)
     * @return float */
    function fetch_listprice() {
        if ($h = $this->fetch(array('list_price'))) {
            return $h['list_price'];
        }
    }


    /** get the net price per item for this product, including adders based on 
     * the inventory item or the passed option array.
     * @param $invid opt inventory id from cm_inventory
     * @param $options opt array optkey => option.id (from cm_products_options, usually)
     * @return float
     */
    function get_price($invid=null, $options=null)
    {
        $base = $this->fetch_baseprice();

        /* we might have an adder in the inventory table, check there */
        if (defined('CSHOP_INVENTORY_ALLOW_ADDER') && CSHOP_INVENTORY_ALLOW_ADDER && is_numeric($invid)) { 
            $sql = sprintf("SELECT i.adder FROM %s p, %s i WHERE p.id=i.product_id AND i.id = %d",
                            $this->get_table_name(),
                            $this->_inventory_table,
                            $invid);
            if ($inv_price = $this->db->getOne($sql)) {
                $base += $inv_price;

            }
        }

        /* with no options return the price we already have */
        if (!is_array($options) or !count($options)) {
            return $base;
        }
        /* sum adders for all applicable options */
        else {
            $cpo =& $this->_product_option_factory();
            $col = array("SUM(adder) AS diff");
            $w = array();
            foreach ($options as $optkey => $optid) {
                $w[] = sprintf("(id = %d AND optkey = %s)", $optid, $this->db->quote($optkey));
            }
            $where = "(" . join(' OR ', $w) . ")";
            $res = $cpo->fetch_any($col, 0, 0, null, $where);
            if (empty($res) or empty($res[0]['diff'])) {
                return $base;
            }
            else {
                return $base + $res[0]['diff'];
            }
        }
    }


    /** fetch all product options for this instance and re-arrange to be indexed by optkey.      * key => option_id => option values
     * @return array or false if no options.
     */ 
    function fetch_product_options() {
        $cpo =& $this->_product_option_factory();
        $opts = $this->cmOptions->fetch_any();
        $base = $this->fetch_baseprice();
        $list = $this->fetch_listprice();

        if (empty($opts)) {
            return false;
        }
        else {
            $popts = array();
            foreach ($opts as $o) {
                $k = $o['optkey'];
                $option_id = $o['id'];
                if (!isset($popts[$k])) $popts[$k] = array();
                $o['price'] = $o['adder'] + $base;
                $o['list_price'] = $o['adder'] + $list;
                $popts[$k][$option_id] = $o;
            }
            return $popts;
        }
    }


    /** create an instance of cmProductOption if not already have one 
     */
    function _product_option_factory()
    {
        if (!isset($this->cmOptions)) {
            $c = CSHOP_CLASSES_PRODUCT_OPTION;
            $this->cmOptions = new $c($this->db, $this->get_id());
        }
        return $this->cmOptions;
    }

    /** setter for the -cat_id */
    function set_cat_id($cid) {
        $this->_cat_id = $cid;
    }
    /** getter for the -cat_id */
    function get_cat_id() {
        return $this->_cat_id;
    }

    /** fetch an array that can be used by a template to build a breadcrumb
     * list drilling down to product detail level trhough 1 or 2 category
     * levels */
    function get_breadcrumbs() {
        $bc = array();
        if ($cat = $this->get_cat_id()) {
            $sql = sprintf("SELECT c1.id, c1.name, c2.name AS parent_name, c2.id AS parent_id
                            FROM cm_categories c1 LEFT JOIN cm_categories c2 ON c1.parent_cat_id = c2.id
                            WHERE c1.id = %d",
                            $cat);
            if ($row = $this->db->getRow($sql)) {
                if ($row['parent_name']) {
                    $bc[$row['parent_name']] = $_SERVER['PHP_SELF'];
                }
                $bc[$row['name']] = $_SERVER['PHP_SELF'] . "?cat=". $row['id'];
            }
        }
        if ($this->get_id()) {
            $bc[$this->get_header('title')] = '';
        }
        return $bc;
    }



    /** fetch a simple report showing quantities for each inventory item (SKU)
     * can be limited by several factors
     * @param int $pid product_id to limit the report to
     * @param str $orby column/column alias to order the results by
     * @param str $orderdir either DESC or ASC
     * @param int $offset rownum to begin results w/
     * @param int $range number of rows to get
     * @return array
     */
    function fetch_inventory_report($pid = null, $orby = null, $ordir = null, $offset=0, $range=0, $cat_id=null)
    {

        if (!$orby) $orby = 'title';
        $ordir = (!$ordir or $ordir=='DESC')? 'DESC' : 'ASC';
        if (!$range) $range = 50;
        $items = array();


        $sql = "SELECT inv.id, inv.qty, inv.sku, p.id AS product_id, p.title
                       , CONCAT(color.name, ' [', color.code, ']') AS color
                       , color.name as color_name
                       , size.code AS size_code
                       , size.id AS size_id
                       , color.id AS color_id
                FROM cm_inventory inv
                    LEFT JOIN cm_colorways color ON (color.id = inv.colorways_id) 
                    LEFT JOIN cm_sizes size ON (size.id = inv.sizes_id)
                    JOIN cm_products p ON (inv.product_id = p.id)";
        if ($cat_id) {
            $sql .= " JOIN cm_products_categories pc ON (pc.cm_products_id = p.id) ";
        }
                         
        if ($pid) {
            $sql .= sprintf(" AND p.id = %d", $pid);
        }
        if ($cat_id) {
            $sql .= sprintf(" AND pc.cm_categories_id = %d", $cat_id);
        }
        $sql .= " ORDER BY %s %s";

        $res = $this->db->query(sprintf($sql, $orby, $ordir));

        $numrows = $res->numRows();
        $this->numRows = $numrows; 

        $ptrlimit = (($range + $offset) < $numrows)? ($range + $offset) : $numrows;

        for ($ptr=$offset; $ptr<$ptrlimit; $ptr++) { 
            if (! $row = $res->fetchRow(DB_FETCHMODE_ASSOC, $ptr)) break;

            $items[] = $row;
        }
        return $items;
    }




    /**
     * fetch ids of any products that are related in the many-to-many relation to each other
     * @return array
     */
    function fetch_related_products() {
        $sql = "SELECT related_to FROM " . $this->_related_products_table .
               " WHERE cm_products_id = " . $this->get_id();
        return $this->db->getCol($sql);
    }




    /**
     * check the amount of inventory left for the given inventory item. If it
     * is less than CSHOP_INVENTORY_WARNING_LEVEL, send a notice to the
     * configured email addr.
     * TODO product is overloaded with stuff. Give inventory its own class 
     *
     * @param $inv_id int inventory id
     */
    function check_inventory_level($inv_id) {
        if (defined('CSHOP_INVENTORY_WARNING_LEVEL') && CSHOP_INVENTORY_WARNING_LEVEL > 0) {
            $sql = sprintf("SELECT product_id, qty FROM %s WHERE id = %d",
                    $this->_inventory_table,
                    $inv_id);
            list($pid, $left) = $this->db->getRow($sql, DB_FETCHMODE_ORDERED);
            if (CSHOP_INVENTORY_WARNING_LEVEL >= $left) {
                $this->set_id($pid);
                $this->_send_inventory_warning_notice($inv_id, $left);
            }
        }
    }


    /**
     * send a notice to somebody
     */
    function _send_inventory_warning_notice($inv_id, $qty) {
        if (defined('CSHOP_INVENTORY_WARNING_RECIP')) {
            $recip = CSHOP_INVENTORY_WARNING_RECIP;

            $cmail = new circusMailer();

            $attr = $this->get_attrib_array($inv_id);
            $product = $this->fetch(array('title','sku'));

            $subj = SITE_DOMAIN_NAME . " - Inventory Warning";

            $msg = "Inventory levels are at or below the configured warning level for the following item:\n";
            $msg .= "\tProduct name:" . $product['title'] . "\n";
            $msg .= "\tSKU:" . $product['sku'] . "\n";
            foreach ($attr as $k => $v) {
                $msg .= "\t$k: $v\n";
            }
            $msg .= "\tQuantity on hand: $qty\n";

            return $cmail->send($recip, $subj, $msg);
        }
    }


}

/* lame back-compat */
class product_container extends cmProduct { }

