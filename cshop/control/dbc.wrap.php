<?php
/**
 * generic controller script to handle generation and processing of forms for
 * managing simple CCOM database content. Uses ccomContainer instances to
 * connect to the data store and formex() and mosh_tool() to handle the UI and
 * input checks
 *
 * $Id: dbc.wrap.php,v 1.1 2006/02/24 16:20:48 sbeam Exp $
 */

require_once('onset/formex.class.php');
require_once('onset/mosh_tool.class.php');
require_once('onset/fu_HTML_Table.class.php');
require_once('onset/uploadable.class.php');

$ACTION = 0;
$errs = array();
$msg = '';

/* set of actions we can perform here, bitwise and'ed together */
define ('OP_DO_PROC', 1);
define ('OP_ADD', 2);
define ('OP_EDIT', 4);
define ('OP_KILL', 8);

if (!$page_id = $_REQUEST['page']) {
    trigger_error("No page identifier was given", E_USER_ERROR);
}

$page_parent_id = (isset($_REQUEST['parent_id']))? $_REQUEST['parent_id'] : null;

$dbc = null;
if (@include_once($page_id . '.class.php')) {
    $dbc =& new $page_id($pdb); // will asplode on invalid $page_id, TODO use exceptions
}
if (!$dbc) {
    trigger_error("Resource '$page_id' not found", E_USER_ERROR);
}

$colmap = $dbc->get_colmap(); // the center of everything

// breadcrumbs array (title => href)
$crumbs = array($dbc->page_title => $_SERVER['PHP_SELF'] . "?page=$page_id");

// GET params common to all functions that need to be passed along
$base_get_vars = sprintf("page=%s", $page_id);
if ($page_parent_id) $base_get_vars .= sprintf("&parent_id=%d", $page_parent_id);


/* form submission happened. See what we got */
if (isset($_POST['op'])) {
    $imgcount = 0;

    $ACTION |= OP_DO_PROC;
    $mosh = new mosh_tool();
    $mosh->form_field_prefix = '';

    if (isset($_POST['op_kill'])) {
        $ACTION |= OP_KILL;
    }
    elseif (! ($errs = $mosh->check_form($colmap))) {

        $vals = $mosh->get_form_vals($colmap);
        if ($page_parent_id) $vals[$dbc->parent_id_col] = $page_parent_id; // todo ccomContainer::store() should handle this

        /** if we have an id param, this is an edit not an add */
        if (isset($_POST['id'])) {
            $ACTION |= OP_EDIT;
            $dbc->set_id($_POST['id']);
        }

        $res = $dbc->store($vals);

        if (PEAR::isError($res) and $res->getCode() != DBCON_ZERO_EFFECT) {
            $errs[] = "Storage Error: " . $res->getMessage();
        }
        else {
            $imgcount = $dbc->store_upfiles();
            if (PEAR::isError($imgcount)) {
                $errs[] = "File upload error: " . $imgcount->getMessage();
            }
            else {
                if (isset($vals[$dbc->_table_namecol])) {
                    $entry_title = sprintf("'%s'", $vals[$dbc->_table_namecol]);
                }

                if ($ACTION & OP_EDIT) {
                    $msg .= sprintf("'%s' entry %s was updated.", $dbc->page_title, $entry_title);
                }
                else {
                    $msg .= sprintf("Inserted new '%s' entry %s.", $dbc->page_title, $entry_title);
                }

                // append nice info to $msg
                if ($imgcount) {
                    $msg .= sprintf(" %d image%s loaded.", $imgcount, ($imgcount>1)? 's were':' was');
                }
            }
        }
    }
}

if ($ACTION & OP_KILL) {
    $dbc->set_id($_POST['id']);
    $res = $dbc->kill();
    if (PEAR::isError($res) and $res->getCode() != DBCON_ZERO_EFFECT) {
        $errs[] = "Could not remove: " . $res->getMessage();
    }
    else {
        $msg = "Entry was removed";
    }
}

/** if there was a sucessful POST, do a redirect */
if ($msg and !count($errs) and ($ACTION & OP_DO_PROC)) {
    // send back to self with messageness
    #print $msg;
    header("Location: {$_SERVER['PHP_SELF']}?$base_get_vars&info=" . base64_encode($msg));
    exit();
}


/** we didnt have a post - so set ACTION flags depending on some GET inputs */
if (isset($_GET['op_edit']) and is_numeric($_GET['op_edit'])) {
    $ACTION |= OP_EDIT;
    $dbc->set_id($_GET['op_edit']);
}
elseif (isset($_GET['op_add'])) {
    $ACTION |= OP_ADD;
}



/** create a formex() object to make a form */
if ($ACTION) {
    $fex = new formex();
    $fex->field_prefix = '';
    $fex->left_td_style = '';
    $fex->extra_js_src_dir = "/control/formex_js";
    $fex->rte_js_src_dir = "/control/formex_js";
    $fex->add_element($colmap);

    if (($ACTION & OP_EDIT)) {
        $fex->add_element('but', array('EDIT', 'submit'));
        $s = $dbc->fetch_content();
        $vals = array_pop($s);
        $fex->elem_default_vals = $vals;

        if ($vals['id']) {
            $fex->add_element('id', array(null, 'hidden', $dbc->get_id(), null));
        }

        $confirm_msg = 'This will remove this item from the site permanently. Are you sure?';
        $fex->add_element('op_kill', array('REMOVE', 'submit', null, array('class'=>'ccomKillSwitch'), "onclick=\"return confirm('$confirm_msg')\""));
        if (isset($vals[$dbc->_table_namecol])) {
            $crumbs[$vals[$dbc->_table_namecol]] = $_SERVER['PHP_SELF'] . "?$base_get_vars&op_edit=".$vals['id'];
        }

        $action_name = 'edit';
    }
    else { // I had dejå vu when I wrote this code
        $fex->add_element('but', array('ADD', 'submit'));
        $action_name = 'add';
    }
    $crumbs[$action_name] = null;

    $form_underframe = (($ACTION & OP_EDIT) and $dbc->has_dependents)? $dbc->dependent_class : null;

    $fex->add_element('op', array(null, 'hidden', $ACTION, null));
    $fex->add_element('page', array(null, 'hidden', $page_id, null));
    $fex->add_element('parent_id', array(null, 'hidden', $page_parent_id, null));
}
/** list all entries in one big ass dump using HTML_Table **/
// TODO paging and filtering (turned on/off in model)
else {
    $table = new fu_HTML_Table();
    $table->setAutoGrow(true);
    $table->setAutoFill("n/a");
    
    $header_row = array();
    if ($dbc->control_cols) {
        foreach ($dbc->control_cols as $k) {
            $header_row[$k] = (isset($colmap[$k]))? $colmap[$k][0] : '';
        }
    }
    else {
        foreach ($colmap as $k => $v) {
            $header_row[$k] = $v[0];
        }
    }

    $table_ordercol = $dbc->col_orderby_def;
    if (isset($_GET['by'])) {
        $table_ordercol = $_GET['by'];
    }
    if (isset($_GET['dir'])) {
        $order_dir =  ($_GET['dir'] == 'D')? 'DESC' : 'ASC';
    }
    else {
        $order_dir = $dbc->dir_orderby_def;
    }

    if (!$dbc->suppress_control_nav) {
        $table->addSortRow($header_row, $table_ordercol, null, 'TH', $base_get_vars, $order_dir);
    }


    $where = '';
    if ($dbc->get_table_name() == 'ccom_content') {
        $where = "page_id = '$page_id'";
    }
    elseif ($dbc->parent_id_col) {
        $where = $dbc->parent_id_col . "= '$page_parent_id'";
    }

    $cols = array_keys($header_row);
    array_push($cols, 'id');
    if ($rows = $dbc->fetch_any($cols, 0, 0, $table_ordercol, $where, $order_dir)) {

        foreach ($rows as $row) {
            $link = sprintf('%s?%s&op_edit=%d', $_SERVER['PHP_SELF'], $base_get_vars, $row['id']);
            $cells = array();
            foreach (array_keys($header_row) as $k) { 
                $cells[] = (strlen($row[$k])<129)? $row[$k] : substr(htmlspecialchars($row[$k]), 0, 128) . '...';  // TODO html??
            }
            $table->addRow_fu($cells, '', true, $link);
        }
    }

    $numrows = $dbc->numRows;
}


$smarty->assign('pagetitle', join(' :: ', array_keys($crumbs)));
$smarty->assign('breadcrumb', $crumbs);
$smarty->assign('suppress_control_nav', $dbc->suppress_control_nav);


/* #########################################################################
 * display the listing or the form */
$smarty->display('control/header.tpl');
?>

<div style="margin: 10px;">

<? 

if (!$dbc->suppress_control_nav) $smarty->display('control/breadcrumb.tpl'); 

if (isset($_GET['info'])) { 

?>
    <div class="indicator">
      RESULT: <?= htmlentities(base64_decode($_GET['info'])) ?>
    </div>
<? 

}

if ($ACTION) { // form
    if ($errs) {
        $smarty->assign('errors', $errs);
        $smarty->display('control/user_error_report.tpl');
    }

?>
    <div class="container">
      <div class="heading">
          :: <?= $dbc->page_id ?> - <?= $action_name ?> <?= $dbc->page_title ?>
      </div>
      <?  $fex->display(); ?>
    <? if ($form_underframe) { ?>
      <div class="heading"><?= $dbc->page_title ?> - Images</div>
        <iframe src="wrap.php?page=<?= $form_underframe ?>&parent_id=<?= $dbc->get_id() ?>"  height="255" width="100%" frameborder="0" style="border: 0"></iframe>
    <? } ?>
    </div>
<?

}
else {  // list 

?>
  <? if ($numrows) { ?>
      <div style="border: 1px solid black; padding: 0;">
        <div style="text-align: right; padding: 3px">
          <a href="<?= $_SERVER['PHP_SELF'] ?>?<?= $base_get_vars ?>&op_add" class="buttonAddItem">Add New</a>
        </div>
        <br />
            <? echo $table->toHTML() ?>
      </div>
    <? } else { ?>
        <? if (!$dbc->suppress_control_nav) { ?>
            <div class="userError">No matching records found.</div>
            <br />
        <? } ?>
        <a href="<?= $_SERVER['PHP_SELF'] ?>?<?= $base_get_vars ?>&op_add">Add New</a>
    <? } ?>
<? 

}

?>
    </div>
  </div>

<?
$smarty->display('control/footer.tpl');
