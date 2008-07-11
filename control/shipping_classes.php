<?php

$table_title = "Shipping Class";
$tablename = 'cm_ship_class';
$table_ordercol = 'name'; // default column to order by
$table_namecol = 'name'; // column that should be use as the title/name/text to describe the row, for error reporting

$class_opts = array('Flat'=>'Flat',
                    'Zone'=>'Zone',
                    'UPS'=> 'UPS Auto-calc',
                    'USPS'=> 'USPS Auto-calc');

/* form definition arrays suitable for formex() */
$colmap = array('name' =>      array('Ship Class', 'text', null, 1),
                'adder' => array('Adder ($)', 'numeric', null, 0),
                'is_free' => array('Free Ship?', 'toggle'),
                'class_map' => array('Use Calculator', 'select', $class_opts, null, 1),
                'descrip' => array('Description', 'text', null, 0),
                );
require ('./shop.editor.inc.php');
