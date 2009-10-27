<?php
require_once('db_container.class.php');
require_once('cshop/cmShipping.class.php');

/**
 * basic container class Product Categories. Simple. provides the colmap of
 * course and a few util functions to select lists of cats and info on indiv.
 * cats. 
 *
 * @changes
 * Feb  5 08:22:36 EST 2008 - sbeam - now it can do arbitrary levels of nesting of categories. 
 * Nice, but expensive and confusing. We provide more methods to try and minimize DB calls 
 * and hide complexity from the calling code. We use the adjacency model because its easier and 
 * we often need to find siblings, not just parents/children 
 *
 * $Id: cmProductCategory.class.php,v 1.21 2008/06/20 22:07:56 sbeam Exp $
 */
class cmProductCategory extends db_container {

    var $_table = 'cm_categories';

    /** next 3 have to be set for use by store.dbcwrap.php. Really they should
     * be abstract in the parent class so we can make sure they exist */
    var $class_descrip = 'Category'; // english name of this class, what it represents
    var $table_orderby_default = 'parent_cat_id,order_weight'; // column to sort listings by, by default
    var $table_name_column = 'name'; // column to get the "name" or description of a single instance from

    /* form definition arrays suitable for formex() */
    var $colmap = array('name' =>          array('Category Name', 'text', null, 1),
                        'urlkey' =>        array('URL Key', 'text', null, 1),
                    'parent_cat_id' =>  array('Parent Category', 'select', array(), array('top_value' => ' -- No Parent Category --'), false),
                    'cat_photo' => array('Category Icon/representative photo', 'image_upload', null,  array('allowed'=>'web_images_nogif',
                      'maxdims' => IMG_MAX_DIMS,
                      'path'=> CSHOP_MEDIA_FULLPATH,
                      'ws_path' => CSHOP_MEDIA_URLPATH,
                      'resize_method'=>'shrink_to_size',
                      'max_w' => '800',
                      'max_h' => '800'), false),
                    'descrip' =>        array('Description', 'textarea', null, false),
                    'ship_class_id' =>  array('Shipping Class', 'select', array(), 1),
                    'is_taxable' =>     array('Taxable?', 'toggle', false),
                    'feature_rank' =>   array('Feature Rank', 'select', array(0,1,2,3,4,5), false),
                    'order_weight' =>   array('Order Weight', 'text', null, array('size'=>4,'maxlength'=>4),null),
                    'is_active' =>      array('Is Active?', 'toggle', false),
                    'is_used_in_bundle' => array('Used in Product Bundles?', 'toggle', false),
                   );

    var $control_header_cols = array('concat_name'=>'Name', 'urlkey' => 'URL key', 'ship_class'=>'Shipping Class', 'is_taxable'=>'Taxable?', 'is_active'=>'Enabled/Active?', 'feature_rank'=>'Feature Rank', 'product_count'=>'# Products', 'order_weight' => 'Order Weight');


    var $colmap_help = array('urlkey' => 'key for links (URLs) to this category. Letters or numbers only, no spaces or punctuation. Max 16 characters. NOTE: changing this value may break existing links.',
                             'cat_photo' => 'Category Image. Max 800x800',
                             'order_weight' => 'value to order this category by against other categories. Numeric value. Does not need to be sequential',
                             'is_active' => 'Show this category in storefront navigation and menus',
                             'is_used_in_bundle' => 'Show this category in bundle configuration tool',
                         );

    function get_colmap()
    {
        if (empty($this->_colmap_filled)) { // remember if we did this already
            $this->colmap['parent_cat_id'][2] = $this->get_categories_for_select();
            $this->colmap['ship_class_id'][2] = cmShipping::fetch_ship_class_opts($this->db);
        }

        if (!defined('CSHOP_USE_BUNDLES') or !CSHOP_USE_BUNDLES)
            unset($this->colmap['is_used_in_bundle']);

        return $this->colmap;
    }


    /* get list of all product categories down to the given level
     * @param $level optional include only category at this category nesting level
     * @param $startwith optional include only cats with this parent_category_id
     * @param $orderby string order results by the specified column name 
     * @param $exclude_empty if true, return only categories that contain something (!)
     * @return array
     */
    function get_all_categories($level=null, $startwith=0, $orderby=null, $exclude_empty=false) {
        trigger_error('get_all_categories() is deprecated, use get_category_tree()', E_USER_WARNING);
        return $this->get_category_tree($startwith, null);
    }


    /* this is used only for the list in the admin, yeh? */
    function _get_fetch_any_sql($cols, $orderby, $where, $orderdir)
    {

        $cols = array();
        foreach ($this->colmap as $k => $v) {
            if ($v[1] == 'image_upload') { // special addrs for any images. todo something better
                foreach (array('size','dims','mimetype') as $extracol) {
                    $cols[] = 'c.' . $k . "_$extracol";
                }
            }
            $cols[] = 'c.' . $k;
        }

        $sql = "SELECT c.id, " . join(',', $cols) . "
                   , COUNT(pc.cm_products_id) AS product_count
                   , IFNULL(CONCAT(parent.name, ':', c.name), c.name) AS concat_name
                   , sc.name as ship_class
                   , IF(c.is_taxable = 1, 'Y', 'N') AS is_taxable
                   , IF(c.is_active = 1, 'Y', 'N') AS is_active
                FROM ". $this->get_table_name() ." c 
                LEFT JOIN cm_products_categories pc ON (pc.cm_categories_id = c.id)
                LEFT JOIN cm_categories parent ON (parent.id = c.parent_cat_id)
                LEFT JOIN cm_ship_class sc ON (sc.id = c.ship_class_id)";

        if ($where) $sql .= " WHERE $where ";

	if (!$orderby) $orderby = 'concat_name';

        $sql .= " GROUP BY (c.id)
                ORDER BY $orderby $orderdir";
        return $sql; 
    }



    /** get complete info (db row) on any category where Feature Rank is set
     * @return array
     */
    function get_featured_categories($rank=null, $parent=null)
    {
        $w = 'c.is_active = 1 AND c.feature_rank > 0';
        if ($rank) $w .= sprintf(" AND c.feature_rank = %d ", $rank);
        if ($parent) $w .= sprintf(" AND c.parent_cat_id = %d ", $parent);
        $cols = array('c.name', 'c.level', 'c.parent_cat_id', 'c.descrip', 'c.feature_rank');
        return $this->fetch_any($cols, 0, 0, 'c.feature_rank,c.order_weight', $w);
    }

    /** find the parent category, if any, of the given category id. If the
     * given categoruy is top-level, then return it back
     * @param $catid int a category id
     * @return int the parent category id, or itself */
    function get_parent_category($catid=null)
    {
        if (!$catid and $this->_id) {
            $catid = $this->get_id();
        }
        $sql = sprintf("SELECT parent_cat_id FROM %s WHERE id = %d",
                       $this->get_table_name(),
                        $catid);
        $pc = $this->db->getOne($sql);
        return ($pc)? $pc : $catid;
    }


    /**
     * extend parent to clear our little cache 
     */
     function set_id($id) {
        $this->_catpath_cache = null;
        parent::set_id($id);
    }



    /* recursively fetch all ancestors of the given category. This is maybe 
     * expensive so we cleverly(?) cache the results and return the same thing 
     * on subsequent calls
     *
     * @param $catid int optional get parents of this cat. If null, the current 
     * id of this instance will be used
     * @param $cols array list of columns in cm_categories you want returned
     */
    function get_parent_categories($catid=null, $cols=null) {
        if (!isset($this->_sth_parent_categories)) {
            if (!$catid) {
                $catid = $this->get_id();
            }
            if (!is_array($cols)) {
                $cols = array_keys($this->colmap);
            }
            $col_list = join(',', $cols);

            $sql = "SELECT $col_list,parent_cat_id,id FROM ".$this->get_table_name() ." WHERE id = ?";
            $this->_sth_parent_categories = $this->db->prepare($sql);
        }

        if (empty($this->_catpath_cache)) {
            $cats = array();
            $res = $this->db->execute($this->_sth_parent_categories, array($catid));
            if ($cat = $res->fetchRow()) {
                $cats[] = $cat;
                if (!empty($cat['parent_cat_id'])) {
                    $cats = array_merge($this->get_parent_categories($cat['parent_cat_id']), $cats);
                }
            }
            $res->free();
            $this->_catpath_cache = $cats;
        }
        return $this->_catpath_cache;
    }



     /* get list of lists, each category with a sub-array containing the subcategories
      * just a random data structure, not a object heirarchy or anything fancy.
     * @param $cat_ids list of category ids to limit ourselves to (presumably parent cats)
     * @return array
     * @deprecated. user get_category_tree() instead.
     */
    function get_nested_categories($cat_ids=null) {
        trigger_error('get_nested_categories() is deprecated, use get_category_tree()', E_USER_WARNING);
        return $this->get_category_tree(0, null, $cat_ids);
    }


    /**
     * recursively get a list of lists of lists ad-infinitum to get a representation of the whole category hierarchy.
     * @param $startwith int a category id to use as the root. by default will start at the top.
     * @param $cols array list of column names from cm_categories table to fetch. optional
     * @param $prune_to array list of category ids identifying a branch on the 
     *                        tree. Any nodes that are not siblings or children of nodes on this path will be ignored - 
     *                        OR if TRUE, will prune to the branch containing the current id of this instance.
     * @return a complex array struct
     */
    function get_category_tree($startwith=0, $cols=null, $prune_to=null) {
        $sth =& $this->_sth_category_tree_singleton($cols);

        $res = $this->db->execute($sth, array($startwith));

        if (!is_array($prune_to) && $prune_to === true) {
            $prune_to = $this->get_parent_id_list();
        }

        $sibs = array();
        while ($row = $res->fetchRow()) {
            if (!$prune_to or in_array($row['id'], $prune_to)) {
                if ($kids = $this->get_category_tree($row['id'], null, $prune_to)) {
                    $row['children'] = $kids;
                }
            }
            $sibs[] = $row;
        }
        $res->free();
        if (count($sibs)) return $sibs;
    }



    /**
     * get a PDB statment handle for fetching a parent of a category
     * Constructs only one instance of the sth, which will be a problem if you 
     * change your mind about what columns you want between calls. So don't do that.
     *
     * @private
     * @param $cols array list of columns in cm_categories you want returned
     * @param $orderby str expression for ORDER BY
     */
    function _sth_category_tree_singleton($cols=null, $orderby=null) {
        if (empty($this->_sth_category_tree)) {
            if (!is_array($cols)) {
                $cols = array_keys($this->colmap);
            }
            if (!$orderby) $orderby = 'order_weight';

            $col_list = join(',', $cols);

            $sql = "SELECT $col_list,parent_cat_id,id FROM ".$this->get_table_name()."
                    WHERE parent_cat_id = ? AND is_active = 1 ORDER BY $orderby, name";
            $this->_sth_category_tree = $this->db->prepare($sql);
        }
        return $this->_sth_category_tree;
    }


    /**
     * get the whole category heirarchy as a list of key->value pairs suitable for a formex-style select box.
     * each key is the catid and the value is the catname, indented by a certain amount to indicate the nesting level and parentage 
     * @param $startwith int optional node to use as the root
     * @param $currlevel int the current nesting level - for recursion, don't set this.
     * @return array k=>v pairs
     */
    function get_categories_for_select($startwith=0, $currlevel=0) {
        $cols = array('name','urlkey');
        $sth =& $this->_sth_category_tree_singleton($cols, 'parent_cat_id,order_weight');

        $res = $this->db->execute($sth, array($startwith));

        $options = array();
        while ($row = $res->fetchRow()) {
            $options[$row['id']] = str_repeat('---', $currlevel) . $row['name'];
            if (!empty($row['urlkey'])) $options[$row['id']] .= sprintf('[%s]', $row['urlkey']);
            if ($kids = $this->get_categories_for_select($row['id'], $currlevel+1)) {
                $options = $options + $kids;
            }
        }
        $res->free();
        if (count($options)) return $options;
    }

    /**
     * find the category id for the first category that has the given name, or find based on a path of urlkeys
     * @param $name str 
     * @return int a category id
     */
    function lookup_cat_by_name($name) {
        if (!strstr($name, '/')) { // there is no slash. Hope the urlkey is unique in this case!
            $sql = sprintf("SELECT id FROM %s WHERE urlkey = %s", $this->get_table_name(), $this->db->quote(strtolower($name)));
            return $this->db->getOne($sql);
        }
        else { // yikes. we need to create a ad-hoc SQL that joins the cm_categories table to itself N times, where N='# nodes in path'
            $mypath = split('/', $name);
            $sql_FROM = array(); $sql_WHERE = array();
            $table = $this->get_table_name();
            for ($i=0; $i<count($mypath); $i++) { // build FROM and WHERE sql pieces for each node
                $sql_FROM[] = "$table c$i";
                $wha = " (c$i.urlkey = ?";
                if ($i > 0) $wha .= " AND c$i.parent_cat_id = c".($i-1).".id";
                $wha .= ") ";
                $sql_WHERE[] = $wha; 
            }
            $sql = sprintf("SELECT c%d.id FROM %s WHERE %s", 
                            ($i-1),
                            join(', ', $sql_FROM),
                            join(' AND ', $sql_WHERE));
            $sth = $this->db->prepare($sql); // eg, SELECT c2.id FROM cm_categories c0 ,cm_categories c1 ,cm_categories c2  
                                             //     WHERE  (c0.urlkey = 'apparel')  AND  (c1.urlkey = 'womens' AND c1.parent_cat_id = c0.id)
                                             //     AND  (c2.urlkey = 'vests' AND c2.parent_cat_id = c1.id); */
            $res = $this->db->execute($sth, $mypath);
            if ($row = $res->fetchRow()) {
                return $row['id'];
            }
        }
    }

    /**
     * get all featured products within this category, ordered by their feature_rank attribute.
     * @return array of product objects */
    function get_featured_products() {
        if ($cat = $this->get_id()) {
            $products = array();
            $sql = sprintf("SELECT p.* FROM cm_products p, cm_products_categories pc 
                            WHERE p.id = pc.cm_products_id AND pc.cm_categories_id = %d AND p.feature_rank > 0
                            ORDER BY p.feature_rank ",
                            $cat);
            $res = $this->db->query($sql);
            while ($row = $res->fetchRow()) {
                array_push($products, $row);
            }
            $res->free();
            return $products;
        }
    }

    /**
     * get a list of only top-level categories (have no parents) - useful for navigation etc
     * @return array of category objects */
    function get_toplevel_categories($cols=null, $orderby=null) {
        if (!is_array($cols)) {
            $cols = array_keys($this->colmap);
        }
        if (!$orderby) $orderby = 'order_weight';

        $col_list = join(',', $cols);

        $sql = "SELECT $col_list,parent_cat_id,id FROM ".$this->get_table_name()."
                WHERE (parent_cat_id = 0 OR parent_cat_id IS NULL) AND is_active = 1 ORDER BY $orderby, name";
        $res = $this->db->query($sql);
        $items = array();
        while ($row = $res->fetchRow()) {
            array_push($items, $row);
        }
        return $items;
    }


    /**
     * get a list of category urlkeys suitable for direct use in a link, eg 'apparel/shoes/hitops/dope'
     * @return string
     */
    function get_urlpath() {
        $catpath = $this->get_parent_categories();
        $urlpath = array();
        foreach($catpath as $cat) {
            if (isset($cat['urlkey'])) array_push($urlpath, $cat['urlkey']);
        }
        return join('/', $urlpath);
    }

    /**
     * get list of catids for this cat and all direct ancestors of it - for 
     * flagging navigation or making breadcrumbs or whatnot
     * @return array list of catids
     */
    function get_parent_id_list() {
        $catpath = $this->get_parent_categories();
        $parent_cat_ids = array();
        foreach($catpath as $cat) {
            array_push($parent_cat_ids, $cat['id']);
        }
        return $parent_cat_ids;
    }


    /**
     * get list of all categories which can be used in bundles
     * @return array of category objects */
    function get_categories_for_bundles($cols=null, $orderby=null) {
        if (!is_array($cols)) {
            $cols = array_keys($this->colmap);
        }
        if (!$orderby) $orderby = 'order_weight';

        $col_list = join(',', $cols);

        $sql = "SELECT $col_list,parent_cat_id,id FROM ".$this->get_table_name()."
                WHERE is_used_in_bundle = 1 ORDER BY $orderby, name";
        $res = $this->db->query($sql);
        $items = array();
        while ($row = $res->fetchRow()) {
            array_push($items, $row);
        }
        return $items;
    }

}
