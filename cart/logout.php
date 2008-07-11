<?php

/**
 * kills the current auth object and bounces user to their cart again
 *
 * $Id: logout.php,v 1.4 2008/01/11 12:19:14 sbeam Exp $
 */

// init page auth objects
require_once(CONFIG_DIR . 'circusShop.config.php');
page_open(array('sess'=>'jen_Session', 'auth'=>'selfAuth'));

$auth->unauth();

header("Location: checkout.php?w=".uniqid('000'));
