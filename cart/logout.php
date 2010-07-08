<?php

/**
 * kills the current auth object and bounces user to their cart again
 *
 * $Id: logout.php,v 1.4 2008/01/11 12:19:14 sbeam Exp $
 */

// init page auth objects
require_once(CONFIG_DIR . 'cshop.config.php');
page_open(array('sess'=>CSHOP_CLASSES_AUTH_SESSION, 'auth'=>CSHOP_CLASSES_AUTH_AUTH));

$auth->logout();

header("Location: checkout.php?w=".uniqid('000'));
