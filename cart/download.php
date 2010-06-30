<?php


require_once(CONFIG_DIR . 'cshop.config.php');
require_once(CSHOP_CLASSES_DOWNLOADS . '.class.php');
require_once(CSHOP_CLASSES_USER . '.class.php');
require_once(CSHOP_CLASSES_ORDER . '.class.php');

// flag for smarty
$smarty->assign('page_id', 'downloads');
$smarty->assign('pagetitle', 'cShop Download Manager');

$user = cmClassFactory::getInstanceOf(CSHOP_CLASSES_USER, $pdb);
$order = cmClassFactory::getInstanceOf(CSHOP_CLASSES_ORDER, $pdb);

// init page auth objects
page_open(array('sess'=>CSHOP_CLASSES_AUTH_SESSION, 'auth'=>'defaultAuth'));


$auth_uid = $user->get_auth_id();
$user->set_id($auth_uid);

// get download token
if (empty($_GET['tok']) or empty($_GET['file_token'])) {
    trigger_error('required parameter missing', E_USER_ERROR);
}

if (! $order->set_id_by_token($_GET['tok'])) {
    trigger_error('order id not found', E_USER_ERROR);
}

$orderinfo = $order->fetch();

if ($orderinfo['user_id'] != $auth_uid) {
    if ($auth->conditional_login()) { // will show login form if not logged in yet.
        trigger_error("illegal attempt to access order", E_USER_ERROR);
    }
    else {
        trigger_error("order access deferred pending login", E_USER_WARNING);
        exit();
    }
}

// look up product info
$item_info = $order->fetch_downloadable_by_token($_GET['file_token']);

if (!$item_info) {
    trigger_error("unknown download token", E_USER_ERROR);
}
//
// check logged-in user has access to it
if ($item_info['order_id'] != $orderinfo['id']) {
    trigger_error("illegal attempt to access download", E_USER_ERROR);
}

$filename = preg_replace('/[^\w\d._-]+/', '_', $item_info['product_descrip']) . '.zip';
header('Content-Disposition: attachment; filename="'.$filename.'"');

$downlo = cmClassFactory::getInstanceOf(CSHOP_CLASSES_DOWNLOADS, $pdb);
$downlo->digital_download_dumper($item_info['product_id']);

