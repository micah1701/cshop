<?php
/** configration file for cshop. defines some image metrics and more
 * importantly, classnames to be used in this implementation. 
 *


/** set local classnames for site-specific cshop extensions 
 * Since we can't use PHP5 autoloading (*sob*) you have to enter the 
 * classname to be used for each important cshop object below. That should
 * do it, the controllers will look in include_path for the class file matching
 * the name.
*/

/*
 * @changelog:
 * 1.5.5 : 2009/10/28 add product bundles (puredark)
 * 1.5.4 : 2009/09/12 puredark changes and additions
 * 1.5.3 : 2009/01/02 
 * 1.5.2 : 2008/06/12 break categories and related products control into their own tabs
 * 1.5.0 : 2008/02/05 new INFINITE CATS (for EXR) - NOT BC
 * 1.4 : 2008/01/15 control product tabs re-done w jQuery; add cmCategory::get_featured_products()
 */
define ('CSHOP_VERSION', '1.5.5');

define ('CSHOP_CLASSES_ADDRESSBOOK', 'cmAddressBook');
define ('CSHOP_CLASSES_USER', 'cmUser');
define ('CSHOP_CLASSES_CART', 'cmCart');
define ('CSHOP_CLASSES_COUPON', 'cmCoupon');
define ('CSHOP_CLASSES_ORDER', 'cmOrder');
define ('CSHOP_CLASSES_PAYMENT', 'cmPaymentGatewayManual');
define ('CSHOP_CLASSES_PAYMETHOD', 'cmPaymentCC');
define ('CSHOP_CLASSES_PRODUCT', 'cmProduct');
define ('CSHOP_CLASSES_PRODUCT_CATEGORY', 'cmProductCategory');
define ('CSHOP_CLASSES_PRODUCT_OPTION', 'cmProductOption');
define ('CSHOP_CLASSES_MANUFACTURER', 'cmManufacturer');
define ('CSHOP_CLASSES_SHIPMETHOD', 'cmShipping');
define ('CSHOP_CLASSES_TAXCALCULATOR', 'cmTaxCalculator');
define ('CSHOP_CLASSES_GIFTCARD', 'cmGiftCard');
define ('CSHOP_CLASSES_BUNDLE', 'cmBundle');
define ('CSHOP_CLASSES_DOWNLOADS', 'cmProductDownload');

/* classes used for auth/perm/session management (old-phplib) */
define ('CSHOP_CLASSES_AUTH_AUTH', 'jen_Auth');
define ('CSHOP_CLASSES_AUTH_PERM', 'jen_Perm');
define ('CSHOP_CLASSES_AUTH_SESSION', 'jen_Session');

// destination for uploaded product and category images and such
define ('CSHOP_MEDIA_URLPATH', '/.uploads/cshop');
define ('CSHOP_MEDIA_FULLPATH', SITE_ROOT_DIR . '/web' . CSHOP_MEDIA_URLPATH);

# cmPaymentGatewayANET* keeps a log here.
define('CSHOP_LOG_FILE', CSHOP_MEDIA_FULLPATH . '/cshop.log');

/** time zone identifier to make sure all timestamps are saved 
 * with this offset built in, adjusted for DST. And PHP to use it for display purposes. */
define ('CSHOP_DISPLAY_TZ', 'US/Eastern');

/* * date format for smarty usage */
define ('CSHOP_DATE_FMT_DISPLAY', '%e %b %Y %R %Z');

/** use ON_LIVE_SERVER to determine if testmode. */
define('CSHOP_PAYMENT_TESTMODE', !(ON_LIVE_SERVER));

/** file that holds our payment gateway configuration */
define('CSHOP_PAYMENT_CONFIG_FILE', CONFIG_DIR . "anet.connection.conf");

/* allow customers to bypass creating a user account and login in anonymously for checkout? */
define('CSHOP_ALLOW_ANON_ACCOUNT', true);

/* default shipping origin ZIP, used for UPS/USPS online calcs */
define('CSHOP_SHIPPING_ORIGIN_ZIP', '03801');

/* default shipping origen country (non-US not even tested yet) */
define('CSHOP_SHIPPING_ORIGIN_COUNTRY', 'US');

/* shipping on any order over this amount will be FREE!!!! */
define('CSHOP_SHIPPING_FREESHIP_THRESHOLD', 0);

/* fetch and show any related products in cart? if non-false, use integer to limit # of results */
define('CSHOP_SHOW_RELATED_PRODUCTS_IN_CART', false);

/* fetch and show thumbnail images and descriptions for all products in the cart view? */
define('CSHOP_SHOW_PRODUCT_THUMBNAILS_IN_CART', false);

/* fetch complete list of categories in cart (for navigation or otherwise) */
define('CSHOP_SHOW_CART_CATEGORIES', false);

/* should the Cart obj check inventory levels before adding items to cart and placing orders? */
define('CSHOP_DO_INVENTORY_CHECK', true);

/* should the Cart reject attempts to add items that are out of stock? or let the orders go through to back-order land? */
define('CSHOP_STOCK_BLOCK', true);

/** do we use any type of coupons/discount codes? */
define('CSHOP_DO_TAKE_COUPONS', false);

/** if non-zero, send a warning when inventory reaches this number for any inv. item */
define('CSHOP_INVENTORY_WARNING_LEVEL', 0);

/** recipient of the inventory warning emails, if any */
define('CSHOP_INVENTORY_WARNING_RECIP', 'debug@circusmedia.com');

/** comma-separated list of Credit Card types we accept - default = VISA,MC,AMEX,DISC */
define('CSHOP_CC_ACCEPTED', 'VISA,MC,AMEX,DISC');

/** use the Bundle/Custom/Build-a-kit functionality? */
define('CSHOP_USE_BUNDLES', false);

// max dims allowed for product images. Larger than this will be resized to fit
define('MAX_W', 480);
define('MAX_H', 480);

// define next two to also allow a "ZOOM" version of product images that can be
// put into a popup window or something
#define('ZOOM_MAX_W', 800);
#define('ZOOM_MAX_H', 600);

// max dims for thumbnails. defining these will cause image shrinkage until
// they fit within the bounds
#define('THUMB_MAX_W', 120);
#define('THUMB_MAX_H', 120);

// exact dims. All thumbs will be resized exactly so (proportionately so extra
// material is cropped)
define('THUMB_EXACT_W', 120);
define('THUMB_EXACT_H', 120);

// max image size that can be uploaded in control. Saves us from memory issues maybe.
define('IMG_MAX_DIMS', '2600x2600');

// URL to use to get to the store front
define('CSHOP_HOME_PAGE', '/store.browse.php');
// URL for a product detail page (GET params will be appended as needed)
define('CSHOP_PRODUCT_DETAIL_PAGE', '/store.browse.php');
// URL for the order detail page
define('CSHOP_ORDER_DETAIL_PAGE_FMT', '/store.browse.php?ord=%s'); // sprintf() format w/ order token
// URL for the password recovery page
define('CSHOP_PASS_RECOVER_LINK_FMT', '/cart/pass.recover.php?u=%d&m=%s'); // sprintf() format w/ order token

// string to be used in <title> tag within cart area
define('CSHOP_CART_PAGETITLE', 'Your Shopping Cart');

/* if true, international orders won't happen (only US in the country selects) */
define('CSHOP_SUPPRESS_INTL_ORDER', true);

/** should we truncate any ccno stored in the DB when order status becomes
 * non-NEW? (really only needed for manual processing. Other Payment Gateway
 * classes do this automatically) */
define('CSHOP_CCNO_TRUNCATE', false);

/* do we accept any gift cards? */
define('CSHOP_ACCEPT_GIFTCARDS', false);

/* URL to post giftcard operations to */
define('CSHOP_GIFTCARD_POST_URL', "https://www.smart-transactions.com/gateway.php");

/* giftcard gateway Smart Transaction Systems, Inc. - our Merchant # */ 
define('CSHOP_GIFTCARD_MERCHANTNUMBER', "99999");

/* giftcard gateway Smart Transaction Systems, Inc. - our Terminal ID */  
define('CSHOP_GIFTCARD_TERMINALID', "001");

/** enable the "options" tab in product detail admin. Only needed in some implementations */
define('CSHOP_USE_PRODUCT_OPTION_TAB', false);

/* use the related products feature? (this will only enable the form element in control) */
define('CSHOP_USE_RELATED_PRODUCTS', false);

/* enable the tab for digital downloads in control? */
define('CSHOP_ENABLE_DIGITAL_DOWNLOADS', false);

/** have fields for "adders" in each row of inventory, and look there for pricing? */
define('CSHOP_INVENTORY_ALLOW_ADDER', false);

/** text label for "Color" attribute of products as shown to users (could be Style or Model, eg) */
define('CSHOP_PRODUCT_ATTRIB_LABEL_COLOR', 'Style');
/** same thing but for the Size attrib */
define('CSHOP_PRODUCT_ATTRIB_LABEL_SIZE', 'Size');

/** can the coupons be limited to work only on certain product categories? or do they all apply to all products equally? (note: 
    * if a coupon does in fact belong to one or more productCategories, then it 
    * should automatically apply itself per-lineitem in the cart */
define('CSHOP_COUPONS_HAVE_CATEGORIES', true);
    
/** whether we are ALWAYS applying coupons A) true, to each individual lineitem or B) 
 * false, as a chunk after the subtotal. Really, it makes no difference except 
 * that clients seem to prefer it different ways.*/
define('CSHOP_APPLY_DISCOUNT_TO_LINE_ITEMS', false);

/** show a list of Merchant Gateway transactions in the control? */
define('CSHOP_CONTROL_SHOW_TRANSACTIONS', true);

/** show controls to allow admin to place A/net transaction from the order detail page? */
define('CSHOP_CONTROL_SHOW_TRANSACTION_CONTROLLER', false);

/** enable use of wholesale/dealer pricing in storefront and cart? */
define('CSHOP_ENABLE_WHOLESALE_PRICING', true);

/* make formex use old-style uppercase field keys like TAG, CLASS, etc */
define('FORMEX_BACK_COMPAT_UC_FIELD_KEYS', true);


/** choices for "Class" under media tab in products admin. Max 16chars each */
$CSHOP_MEDIA_CLASSES = array('listing'=>'Listing',
                             'featured'=>'Feature Callout',
                             'main'=>'Main Detail',
                             'additional'=>'Additional',
                             'colorway'=>'Colorway');

/* here some essential require()s go at the bottom of the page, for variety I guess. */
require_once('cmClassFactory.class.php');
require_once('cshop_smarty_plugins.php');
