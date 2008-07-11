<?php

$table_title = "Manufacturer";
$tablename = 'cm_manufacturers';
$table_ordercol = 'name'; // default column to order by
$table_namecol = 'name'; // column that should be use as the title/name/text to describe the row, for error reporting

/* form definition arrays suitable for formex() */
$colmap = array('name' =>      array('Mfr. Name', 'text', null, 1),
                'is_active' => array('Is Active?', 'toggle'),
                'url' => array('Web Site', 'text', null, 0),
                'description' => array('Description', 'textarea', null, 0),
                );
require ('./shop.editor.inc.php');
