<?php

$table_title = "Shipping Method";
$tablename = 'cm_shipmethods_flat_methods';
$table_ordercol = 'name'; // default column to order by
$table_namecol = 'name'; // column that should be use as the title/name/text to describe the row, for error reporting

/* form definition arrays suitable for formex() */
$colmap = array('name' =>      array('Name', 'text', null, 1),
                'cost' =>      array('Cost', 'numeric')
                );
require ('./shop.editor.inc.php');
