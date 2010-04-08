<?php
/**
 * manange product categoriums
 *
 */

require_once('../../../config/cshop.config.php');
require_once('formex.class.php');
require_once('mosh_tool.class.php');
require_once('uploadable.class.php');
require_once("fu_HTML_Table.class.php");      

/** find the class we are editing using the REQUEST param 'class' of course */
if (!isset($_REQUEST['class'])) {
    trigger_error('got no class!', E_USER_WARNING);
}
$class = $_REQUEST['class'];

// constant matching this param should be in cshop.config.php */
$cconst = 'CSHOP_CLASSES_' . strtoupper($class);
if (defined($cconst)) {
    $className = constant($cconst);
}
elseif (@fopen($class . ".class.php", 'r', true)) {
    $className = $class;
}
else {
    trigger_error("Class '$class' is not known to me", E_USER_ERROR);
}

/** this will explode if the class file isn't named correctly */
require_once($className. '.class.php');

/** create an instance of that class, whatever it may be */
$dbc = new $className($pdb);

/** grab useful params out of the class */
$table_title = $dbc->class_descrip;
$tablename = $dbc->get_table_name();
$table_ordercol = $dbc->table_orderby_default;
$table_namecol = $dbc->table_name_column;

/* form definition array suitable for formex() */
$colmap = $dbc->get_colmap();

###########################################################################################

$pagetitle = "Store $table_title - ";

$SHOWFORM = true; // are we showing a form or not?

$ACTION = null;
define ('OP_ADD', 'ADD '. strtoupper($table_title));
define ('OP_EDIT', 'EDIT '. strtoupper($table_title));
define ('OP_KILL', 'REMOVE '. strtoupper($table_title));

$itemid = null;

/** decide on a course of action... **/
if (isset($_POST['op']) and $_POST['op'] == OP_EDIT) {
    $ACTION = OP_EDIT;
    $itemid = $_POST['id'];
}
elseif (isset($_POST['op']) and $_POST['op'] == OP_ADD) {
    $ACTION = OP_ADD;
}
elseif (isset($_POST['id']) and isset($_POST['op_kill'])) {
    $itemid = $_POST['id'];
    $ACTION = OP_KILL;
}
elseif (isset($_GET['op_edit']) and !empty($_GET['op_edit'])) {
    $itemid = $_GET['op_edit'];
    $ACTION = OP_EDIT;
}
elseif (isset($_GET['op_add'])) {
    $ACTION = OP_ADD;
}
else {
    $SHOWFORM = false;
}
/** **/



$errs = array();

/** POST rec'd, check valid, proc. upload and save if OK */
if (isset($_POST['op']) and ($ACTION == OP_ADD or $ACTION == OP_EDIT)) {
    $mosh = new mosh_tool();
    $mosh->form_field_prefix = '';
    $msg = '';

    $vals = array();
    $img_vals = array();
    if ($errs = $mosh->check_form($colmap)) {
        // handled below
    }
    else {
        $vals = $mosh->get_form_vals($colmap);

        /** get list of all file upload fields from colmap **/
        $upfiles = array();
        foreach ($colmap as $k => $params) {
            if ($params[1] == 'image_upload' or $params[1] == 'file') {
                $upfiles[$k] = array();
            }
        }


        /** are there any actual files coming in? if so process *********************************/
        $uplo_count = 0;
        if (count($upfiles)) {
            foreach (array_keys($upfiles) as $upfile) {
                $uplo = new uploadable($upfile);

                if (!$uplo->is_uploaded_file()) {
                    #unset($upfiles[$upfile]);
                }
                else {  // we got one - use $uplo to check it and save it and create DB vals as needed
                    $uplo->preserve_original_name = true;

                    $uplo->setErrorHandling(PEAR_ERROR_RETURN);
                    // tell $uplo what kind of image we expect and where to put it:
                    $uplo->params(array('path'=> CSHOP_MEDIA_FULLPATH,
                                        'ws_path' => CSHOP_MEDIA_URLPATH)
                                        + $colmap[$upfile][3]);

                    // set other possible params for uplo from the colmap attribs section [3]
                    // todo something better */

                    $res = $uplo->save_upload();

                    if (PEAR::isError($res)) {
                        $errs[] = $res->getMessage();
                    }
                    else {
                        // get the name of the new file
                        $newfilename = $uplo->get_newname();
                        // create a thumbnail right here:
                        // vals to be put in DB - will be given the rest of the column names below
                        $upfiles[$upfile] = array('' => $newfilename,
                                                  'mimetype' => $uplo->get_filetype(),
                                                  'size' => $uplo->get_filesize(),
                                                  'dims' => $uplo->get_img_dims());

                        // call resizing or thumbnailing methods as needed ((((()))))))))))))))))))))))
                        if (isset($uplo->params['thumb_method'])) { // todo $uplo should do this too
                            $method = $uplo->params['thumb_method'];
                            $stretch = new imagestretcher(CSHOP_MEDIA_FULLPATH . '/' . $newfilename);
                            if (!method_exists($stretch, $method)) {
                                $errs[] = "imagestretcher::$method() is not a valid method.";
                            }
                            else {
                                $stretch->$method($uplo->params['thumb_w'], $uplo->params['thumb_h']);
                                $thumb_name = $stretch->save_to_file(CSHOP_MEDIA_FULLPATH.'/_th_'.$newfilename, 'png');
                                $upfiles[$upfile]['thumb_name'] = $thumb_name;
                                $upfiles[$upfile]['thumb_dims'] = $stretch->get_thumb_dims();
                            }
                        }

                        if (isset($uplo->params['resize_method'])) { // todo $uplo should do this too
                            $method = $uplo->params['resize_method'];
                            $stretch = new imagestretcher(CSHOP_MEDIA_FULLPATH . '/' . $newfilename);
                            if (!method_exists($stretch, $method)) {
                                $errs[] = "imagestretcher::$method() is not a valid method.";
                            }
                            else {
                                $stretch->$method($uplo->params['max_w'], $uplo->params['max_h']);
                                $name = $stretch->save_to_file(CSHOP_MEDIA_FULLPATH.'/'.$newfilename); // same place!
                                $upfiles[$upfile]['dims'] = $stretch->get_thumb_dims();
                            }
                        }

                        // ((((((((((((((((((((((((((((((((((((((((((((((((((((()))))))))))))))))))))))
                        $uplo_count++;
                    }
                }
            }
        }
        # #####################################
        $imgcount = 0;

        /** add keys and vals for each $upfile into $vals to be inserted/updated below */
        foreach ($upfiles as $upfile => $img_vals) {
            foreach ($img_vals as $k => $v) {
                if ($k != '') $vals[$upfile . '_' . $k] = $v;
                else $vals[$upfile] = $v;
            }
            $imgcount++;
        }

        $oldErrorHandling = PEAR::setErrorHandling(PEAR_ERROR_RETURN);

        /** insert/update as needed **/
        if (!count($errs)) {
            if ($ACTION == OP_EDIT) { // update the row in $tablenameable
                $dbc->set_id($itemid);
                $res = $dbc->store($vals);

                if (PEAR::isError($res) and $res->getCode() != DBCON_ZERO_EFFECT) {
                    trigger_error($res->getMessage(), E_USER_ERROR);
                }
                else {
                    $msg .= sprintf('%s "%s" was updated.', $table_title, $vals[$table_namecol]);
                }

            }
            else { // OP_ADD

                $res = $dbc->store($vals);

                if (PEAR::isError($res) and $res->getCode() != DBCON_ZERO_EFFECT) {
                    if ($res->getCode() == DB_ERROR_ALREADY_EXISTS) {
                        $errs[] = "The database key (code) already exists, please try again.";
                    }
                    else {
                        trigger_error($res->getMessage(), E_USER_ERROR);
                    }
                }
                else {
                    $msg .= sprintf('Inserted new %s "%s".', $table_title, $vals[$table_namecol]);
                }
            }


            // append nice info to $msg
            if ($uplo_count) {
                $msg .= sprintf(" %d image%s loaded.", $uplo_count, ($uplo_count>1)? 's were':' was');
            }

            // send back to self with messageness
            if (!empty($msg) and empty($errs)) {
                header("Location: {$_SERVER['PHP_SELF']}?class=$class&info=" . base64_encode($msg));
            }
        }
    }
}
elseif (isset($_POST['op_kill']) and ($ACTION == OP_KILL)) {

    $dbc->set_id($itemid);
    $res = $dbc->kill();

    if (!PEAR::isError($res)) {
        $msg = "The selected $table_title was totally removed.";
        // send back to self with messageness
        header("Location: {$_SERVER['PHP_SELF']}?class=$class&info=" . base64_encode($msg));
    }
    else {
        $errs[] = "ERROR: could not delete: ". $res->getMessage();
    }
}


if ($SHOWFORM) {
    $fex = new formex();
    $fex->field_prefix = '';
    $fex->js_src_inline = true;
    $fex->add_element($colmap); // all those things in $colmap are in the form now

    if (isset($dbc->colmap_help) && is_array($dbc->colmap_help)) {
        foreach ($dbc->colmap_help as $k => $text) {
            $fex->set_elem_helptext($k, $text);
        }
    }

    $fex->add_element('op', array($ACTION, 'submit')); // the button
    $fex->add_element('class', array(null, 'hidden', $class)); // important

    if ($ACTION == OP_EDIT) {
        $dbc->set_id($itemid);

        $fex->elem_vals = $dbc->fetch('', true);

        $fex->add_element('id', array(null, 'hidden', $itemid)); // important

        $cat_name = $fex->elem_vals[$table_namecol];

        $confirm_msg = sprintf('This will remove this %s from the site permanently. Are you sure?', $table_title);
        $fex->add_element('op_kill', array(OP_KILL, 'submit', null, null, 'onclick="return confirm(\''. $confirm_msg . '\')"'));

    }
}
else {
    /** list all cm_colors in one big ass dump using HTML_Table **/
    $table = new fu_HTML_Table(array("width" => "600"));
    $table->setAutoGrow(true);
    $table->setAutoFill("n/a");
    
    $header_row = array();
    if (!isset($dbc->control_header_cols)) {
        foreach ($colmap as $k => $v) {
            $header_row[$k] = $v[0];
        }
    }
    else {
        $header_row =& $dbc->control_header_cols;
    }


    if (isset($_GET['by'])) {
        $orby = $_GET['by'];
        if (in_array($orby, array_keys($header_row))) {
            $table_ordercol = $orby;
        }
    }
    $odir = (!isset($_GET['dir']) or $_GET['dir'] == 'A')? 'A' : 'D';

    $table->addSortRow($header_row, $table_ordercol, null, 'TH', "class=$class", $odir);

    // add the primary key if its not in the cols described by header_row
    $scols = array_keys($header_row);
    if (!in_array($dbc->get_pk_col(), $scols)) $scols[] = $dbc->get_pk_col();

    if ($rows = $dbc->fetch_any($scols, 0, 0, $table_ordercol, '', $odir) ) {
        foreach ($rows as $row) {

            $vals = array();

            foreach ($header_row as $k => $v) {
                /* SELECT clause may need tableid.colid syntax - but the DB
                     dont return the tableid part. */
                if (strpos($k, '.') !== false) {
                    $k = substr($k, strrpos($k, '.')+1);
                }

                /* special hack for colorpickers - show a block of color */
                if (isset($colmap[$k][1]) and $colmap[$k][1] == 'colorpicker') {
                    $row[$k] = sprintf('<span style="width: 12px; height: 12px; border: 2px solid black; background-color: %s">&nbsp;&nbsp;&nbsp;</span>&nbsp;%s', $row[$k], $row[$k]);
                }

                if (isset($row[$k]) or $row[$k] == NULL) {
                    $vals[] = $row[$k]; // 
                }
                else { // some sort of misconfig 
                    $vals[] = '';
                    trigger_error("column '$k' was not found in query result", E_USER_NOTICE);
                }
            }

            // store.edit.php?nid=444
            $link = sprintf('%s?op_edit=%s&class=%s', $_SERVER['PHP_SELF'], urlencode($row[$dbc->get_pk_col()]), $class);
            $table->addRow($vals, '', null, $link);
        }
    }
    $numrows = $dbc->numRows;
}

##############################################################################
# output template
##############################################################################
$smarty->assign('pagetitle', strtolower($ACTION)); // make a nice title
$smarty->display('control/header.tpl');
?>


<div id="controlW1">

<? if ($ACTION) { ?>
    <div style="text-align: left; width: 600px">
    <a href="<?= $_SERVER['PHP_SELF'] ?>?class=<?= $class ?>"><?= $table_title ?></a>
    <? if (isset($cat_name)) { ?>
        &raquo;&nbsp;<a href="<?= $_SERVER['PHP_SELF'] ?>?class=<?= $class ?>&op_edit=<?= $itemid ?>"><?= $cat_name ?></a>
    <? } ?>
        &raquo;&nbsp;<?= ucwords(strtolower($ACTION)) ?>
    </div>
    <br />
    <br />
<? } ?>

<? if (isset($_GET['info'])) { ?>
    <div class="indicator">
      <?= htmlentities(base64_decode($_GET['info'])) ?>
    </div>
    <br />
<? } ?>

<? if ($SHOWFORM) { ?>
    
    <div style="width: 600px" class="container">
      <div class="heading">
          <?= $table_title ?>
      </div>
    <? if (count($errs)) { ?>
       <div class="userError">
       Please correct the following errors:
       <ul>
       <? foreach ($errs as $e) { ?>
         <li><?= htmlentities($e) ?></li>
       <? } ?>
       </ul>
       </div>
    <? } ?>
    <? echo $fex->render_form() ?>	
    </div>
<? } else { ?>
  <div style="width: 600px; border: 1px solid black; padding: 4px">
    <div align="right" style="width: 600px">
      <a href="<?= $_SERVER['PHP_SELF'] ?>?op_add&amp;class=<?= $class ?>" class="buttonAddItem">Add New <?= $table_title ?></a>
    </div>
    <? if (!$numrows) { ?>
	    No records found. [<a href="<?= $_SERVER['PHP_SELF'] ?>?op_add&amp;class=<?= $class ?>">ADD</a>]
    <? } else { ?>
      <br />
      <? echo $table->toHTML() ?>
    <? } ?>
  </div>
<? } ?>
	
</div>
	
<? 
$smarty->display('control/footer.tpl');






