<?php

$table_title = "Color";
$tablename = 'cm_colorways';
$table_ordercol = 'name'; // default column to order by
$table_namecol = 'name'; // column that should be use as the title/name/text to describe the row, for error reporting

/* form definition arrays suitable for formex() */
$colmap = array('name' =>      array('Color Name', 'text', null, 1),
                'code' =>      array('Code', 'text'),
                'rgb_value' => array('Value', 'colorpicker', null, array('size'=>7,'maxlength'=>7)),
                );
require ('./shop.editor.inc.php');
