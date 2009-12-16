<?php


/**
 * utility class with static methods to get various selection/menu choices for
 * editing product attribs and stuff. 
 *
 * @DEPRECATED
 * TODO wow this is bad organization of function. shuffle everything over to the appropriate classes, 
 * most of these methods are already duplicated.
 *
 * $Id: store.edit.inc.php,v 1.6 2006/10/05 23:05:26 sbeam Exp $
 */
class cshopUtils {

  function get_all_categories(&$pdb, $add_any = false) {
      $arr = array();
      if ($add_any) {
          $arr[''] = '[ANY]';
      }

      /** select cats and subcats, and do some fancy footwork to make a nested menu **/
      $sql = "SELECT c.id, c.name
                    , IFNULL(CONCAT(cat.name, ':', c.name), c.name) AS concat_name
                    , IF(cat.name IS NOT NULL, CONCAT('&nbsp;--', c.name), c.name) AS subcat
                FROM cm_categories c 
                LEFT JOIN cm_products_categories pc ON (pc.cm_categories_id = c.id)
                LEFT JOIN cm_categories cat ON (cat.id = c.parent_cat_id)
                ORDER BY concat_name";
      $res = $pdb->query($sql);
      while ($row = $res->fetchRow()) {
          $arr[$row['id']] = $row['subcat'];
      }
      return $arr;
  }

    function get_all_parent_categories(&$pdb, $add_any = false) {
        $arr = array();
        if ($add_any) {
            $arr[''] = '[ANY]';
        }
        $sql = "SELECT id, name, level FROM cm_categories WHERE level = 0 ORDER by name";
        $res = $pdb->query($sql);
        while ($row = $res->fetchRow()) {
            $arr[$row['id']] = $row['name'];
        }
        return $arr;
    }


  function get_all_manufacturers(&$pdb, $add_any = false) {
      /** get mfr options **/
      $arr = array();
      if ($add_any) {
          $arr[''] = '[ANY]';
      }
      /** todo there really should be a way to speed this up - just pass
       * tablename and colnames to some utility class (mosh?) and have it make up
       * the options. maybe... */
      $sql = "SELECT id, name FROM cm_manufacturers ORDER by name";
      $res = $pdb->query($sql);
      while ($row = $res->fetchRow()) {
          $arr[$row['id']] = $row['name'];
      }
      return $arr;
  }


  function get_all_ship_classes(&$pdb) {
      /** get mfr options **/
      $arr = array();
      $sql = "SELECT id, name FROM cm_ship_class ORDER by id";
      $res = $pdb->query($sql);
      while ($row = $res->fetchRow()) {
          $arr[$row['id']] = $row['name'];
      }
      return $arr;
  }


  function get_all_sizes(&$pdb) {
      /** get mfr options **/
      $arr = array();
      $sql = "SELECT id, code, fullname FROM cm_sizes ORDER by order_weight";
      $res = $pdb->query($sql);
      while ($row = $res->fetchRow()) {
          $arr[$row['id']] = $row['fullname'] . '(' . $row['code'] .')';
      }
      return $arr;
  }

  function get_all_colors(&$pdb, $add_any=false) {
      /** get mfr options **/
      $arr = array();
      if ($add_any) {
          $arr[''] = '[ANY]';
      }
      $sql = "SELECT id, name, code FROM cm_colorways ORDER by name";
      $res = $pdb->query($sql);
      while ($row = $res->fetchRow()) {
          $arr[$row['id']] = $row['name'] . ' ('.$row['code'] .')';
      }
      return $arr;
  }
}
?>
