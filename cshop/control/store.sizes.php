<?php

$tablename = 'cm_sizes'; // name of the table we are editing here
$table_ordercol = 'order_weight'; // default column to order by
$table_title = "Size"; // descrip/name of the table contents, for error reporting
$table_namecol = 'fullname'; // column that should be use as the title/name/text to describe the row, for error reporting

/* form definition arrays suitable for formex() */
$colmap = array('code' =>      array('Code', 'text', null, array('size'=>8), 1),
                'fullname' =>      array('Full Name', 'text'),
                'order_weight' =>      array('Order', 'numeric', null, array('size'=>3)),
                );
require ('./shop.editor.inc.php');
