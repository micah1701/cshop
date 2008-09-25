<?php
/**
 * init.php
 * sets up constants, paths, DB connection and other stuff as needed
 *
 * loaded via "php_value auto_prepend_file" Directive in httpd.conf
 *
 * THIS IS A generic init.php site for cshop sites. Site-specific config 
 * is in local-init.*.php files and may vary depending if we are on dev, stage 
 * or production server
 *
 * NOTE:
 * If anything is changed in this file, copy the changes back into cshop/samples folder as 
 * well (if applicable to ALL cshop sites 
 */



/** load the really-local local-init.*.php, depending on our dev state */
if (!getenv('ON_LIVE_SERVER')) {
    if ($environ = getenv('DEV_ENVIRONMENT')) {
        require('local-init.'.$environ.'.php');
    }
    else {
        require('local-init.dev.php');
    }
}
else {  /* its the production server! just try and act normal *******************/
    require('local-init.production.php');

    /** redirect to non-SSL page if we don't need it **/
    if ($_SERVER['SERVER_PORT'] == 443 and 
         !preg_match('/^\/control\//', $_SERVER['REQUEST_URI']) and
         !preg_match('/^\/cart\/(?!cart.php).*/', $_SERVER['REQUEST_URI']))  {

       header("Location: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
       header("Connection: close");
       exit();
    }
}
/*********************************************************************************/

                      

define("CONFIG_DIR", SITE_ROOT_DIR . "/config/");  // dir for config files
define("INCL_DIR", SITE_ROOT_DIR . '/local'); // dir for local includes and classes and stuff

define("SITE_MEDIA_URLPATH", '/.library'); // where the images/media for non-product stuff is stored
define("UPLOAD_SAVE_PATH", SITE_ROOT_DIR . '/web' . SITE_MEDIA_URLPATH);

// html template files for all stuff in /cart/
define("CART_TEMPLATE_DIR", SITE_ROOT_DIR . '/web/cart/html');

/** all-important include path info here */
$pathsep = ':';
ini_set("include_path", 
                    INCL_DIR.$pathsep.
                    INCL_DIR.'/onsetlib'.$pathsep.
                    INCL_DIR.'/cshop'.$pathsep.
                    SITE_ROOT_DIR.'/pear'.$pathsep.
                    ini_get('include_path'));


require_once "PEAR.php";





                     


/** bring in PEAR DB which also requires PEAR base for us **/
require_once "DB.php";
require_once "DB/mysql.php";

/*** init the DB **/  
$pdb =& DB::connect(PEAR_DSN);
if (DB::isError($pdb)) {
    die($pdb->getMessage());
}
$pdb->setFetchMode(DB_FETCHMODE_ASSOC);
$pdb->debug = DEBUG;
          


/*** old PHPLIB updated for php4 handles auth, perms and user ***/
define('ONSET_AUTHLIB_DIR', INCL_DIR . '/authlib/');
require_once(INCL_DIR . "/authlib/local.inc");     // load our special extensions to authlib classes
require_once(INCL_DIR . "/authlib.local.php");      // load the really local authlib stuff


/* our special error handling functions */
require_once('error_handlers.php');
error_reporting(E_ALL);




/*** init the Smarty template engine ***/
define("SMARTY_DIR", INCL_DIR . "/Smarty/");             // Smarty likes this
require_once(SMARTY_DIR . "Smarty.class.php");
$smarty =& new Smarty;
$smarty->compile_check = true;
$smarty->debugging = false;
$smarty->template_dir = SITE_ROOT_DIR . "/templates";
$smarty->template_path = array(SITE_ROOT_DIR."/templates", SITE_ROOT_DIR."/templates/cart_custom", SITE_ROOT_DIR."/templates/cart");
$smarty->plugins_dir = SMARTY_DIR . "plugins";
$smarty->config_dir = SITE_ROOT_DIR . "/config";
$smarty->compile_dir = SITE_ROOT_DIR . "/.smarty.templates_c";
$smarty->cache_dir = SITE_ROOT_DIR . "/.smarty.cache";
$smarty->left_delimiter = '<~';
$smarty->right_delimiter = '~>';
//$smarty->caching = true;
//$smarty->force_compile = true;
$smarty->assign('SITE_DOMAIN_NAME', SITE_DOMAIN_NAME);
$smarty->error_reporting = (E_ALL & ~E_NOTICE); // but not notices from smarty templates
require_once('smarty_resource.float.php');
/* Smarty checkouted with
 * $ cvs -d :pserver:cvsread@cvs.php.net:/repository co -r Smarty_2_6_10 -d Smarty smarty/libs       
 */
#          

