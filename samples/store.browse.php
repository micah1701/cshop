<?php
require_once('../config/cshop.config.php');
require_once("res_pager.class.php");      
require_once(CSHOP_CLASSES_PRODUCT . '.class.php');
require_once(CSHOP_CLASSES_CART.'.class.php');

page_open(array('sess'=>'jen_Session', 'auth'=>'defaultAuth'));

$smarty->assign('page_id', 'storebrowse');

$c = CSHOP_CLASSES_PRODUCT;
$pc = new $c($pdb);

$c = CSHOP_CLASSES_PRODUCT_CATEGORY;
$pcats = new $c($pdb);

$c = CSHOP_CLASSES_USER;
$user = new $c($pdb);
$user->set_auth($auth);


/** number of products/page and where to start **/
$range = 6;
$offset = (isset($_GET['page']))? (($_GET['page']-1) * $range) : 0;

/* get all categories and sub-categories at once, in a nested array */
$smarty->assign('category_tree', $pc->get_categories());


/* show an indiv. product detail page */
if (isset($_GET['pid'])) {
    $pc->set_id($_GET['pid']);
    $product = $pc->fetch();

    if (isset($_GET['cat'])) {
        $pc->set_cat_id($_GET['cat']);
    }

    $product['images'] = $pc->get_images();
    $product['colorways'] = $pc->get_colorways();
    $product['sizes'] = $pc->get_sizes();
    $smarty->assign('product', $product);

    /**
     * impl. custom logic for images presentation:  (mantis #1009)
     * 2) for a product allow colorway levels of "any" and order weights "0, 1, 2" 
     *    each of the three need to be specific to:
     *      2= homepage callout image *** Sean will take care of displaying correct images on home page (callouts)
     *      1= product main image
     *      0= thumbnail
     *
     *      --> if no callout or thumbnail is present, needs to display (0) 
     *          product main image" as thumb and main image
     */
    $colorless_images = array();
    foreach ($product['images'] as $pi) {
        if (empty($pi['colorways_id'])) { // there is no colorway, this is one of thumb, main, callout
            $colorless_images[] = $pi;
        }
        else {
            $smarty->append('colorway_images', $pi); // just use for colorways and forget it
        }
    }
    if (count($colorless_images) == 1) {
        $smarty->assign('detail_image', $colorless_images[0]); // there is only one, so use it.
    }
    elseif (count($colorless_images) > 1) {
        $smarty->assign('detail_image', $colorless_images[1]); // there are many, use #2
    }
    else {
        $smarty->assign('detail_image', null); // should be a generic product image
    }
    /** */



    $inv = $pc->fetch_inventory_item($pc->get_id());
    if ($inv['qty']) {
        $smarty->assign('max_qty', ($inv['qty'] > 10)? 10 : $inv['qty']);
    }

    $pc->views_incr(); // increment the view count
    $pid = $pc->get_id();

    $mycat = array_pop($pc->fetch_product_categories()); // grab the first cat this product is in (should be only 1)
    $smarty->assign('catinfo', $pc->fetch_category_info($mycat['id']));


    $smarty->assign('products', $pc->selectByCategory($mycat['id'], array('title'), false, false, false));

    $smarty->assign('pagetitle', $pc->get_title() . ' : Details : ');
    $tpl = 'shop_product_detail.tpl';
}

/* show the product listing page for some category (top level category or below) */
else {

    $catid = null;
    if (isset($_GET['cat']) && is_numeric($_GET['cat'])) {
        $catid = $_GET['cat'];
    }
    elseif (isset($_GET['cn'])) {
        $catid = $pcats->lookup_cat_by_name($_GET['cn']);
    }

    if (!$catid) {
        if ($default_category = $pc->get_featured_categories(1)) {
            $catid = $default_category[0]['id'];
        }
        else {
            $catid = 1;
        }
    }


    $smarty->assign('parent_cat', $pc->get_parent_category_id($catid));

    /* determine how we are supposed to order the results */
    if (!isset($_GET['top'])) { 
        $sort_by_opts = array('name', 'color', 'price'); // index 0 is default
        $smarty->assign('sort_by_opts', $sort_by_opts);

        $sort_by = null;
        if (isset($_GET['by']) and in_array($_GET['by'], $sort_by_opts)) {
            $sort_by = $_GET['by'];
        }
        $smarty->assign('sorting_by', $sort_by);
        if ($sort_by == 'name') $sort_by = 'title'; // mosh for db col match :/
    }

    $pc->set_resultset_limits($offset, $range);

    /* should be a top-level cat, get only featured prods */
    if (isset($_GET['top'])) { 
        $products = $pc->selectByCategory($catid, array('title','price'), false, 1, true, true);
        #$products = $pc->get_all_featured_products();
    }
    /* we are using special fetch by colorway function */
    elseif (isset($_GET['by']) and $_GET['by'] == 'color') {
        $products = $pc->fetch_by_colorway(null, $catid, null);
        $numrows = $pc->numRows;
    }
    /* should be a sub-cat, get all the products within it */
    else { 
        $products = $pc->selectByCategory($catid, array('title','price'), false, 1, false, false, $sort_by);
        $numrows = $pc->numRows;
    }

    $smarty->assign('products', $products);

    /* also fetch ALL the products under the given category, for the nav - no limits! */
    $pc->set_resultset_limits(0,0);
    $smarty->assign('products_nav', $pc->selectByCategory($catid, array('title',), false, false, false));

    $catinfo = $pc->fetch_category_info($catid);

    $pc->set_cat_id($catid);

    $smarty->assign('pagetitle', $catinfo['name']);
    $smarty->assign('category', $catinfo);

    if (!empty($numrows)) {
        $pager = new res_pager($offset, $range, $numrows);
        $smarty->assign('pager', $pager);
    }

    $tpl = 'shop_product_list.tpl';
}

$smarty->assign('inventoryerror', isset($_GET['inventoryerror']));


$c = CSHOP_CLASSES_CART;
$cart = new $c($pdb);
$smarty->assign('cart_itemcount', $cart->count_items());
$smarty->assign('cart_subtotal', $cart->get_subtotal());

/** setup smarty with a method from the $cart object to convery currencies */
$smarty->register_modifier('currency_format', array(&$cart, 'currency_format'));

$smarty->assign('breadcrumbs', $pc->get_breadcrumbs());

$smarty->display('site_head.tpl');
$smarty->display($tpl);
$smarty->display('site_foot.tpl');


