<?php

$table_title = "Packaging option";
$tablename = 'marq_packaging_opts';
$table_ordercol = 'order_weight'; // default column to order by
$table_namecol = 'name'; // column that should be use as the title/name/text to describe the row, for error reporting

/* form definition arrays suitable for formex() */
$colmap = array('name' =>      array('Name', 'text', null, 1),
                'order_weight' => array('Order Weight', 'numeric', null, 0)
                );
require ('./shop.editor.inc.php');
