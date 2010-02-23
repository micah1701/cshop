<?php

// autoloader here for all classes - now that we are on PHP5, cshop requires 
// it, so we make sure we have one.
// http://us2.php.net/manual/en/language.oop5.autoload.php
if (!function_exists('__autoload')) {
    function __autoload($class_name) {
        @include_once "$class_name.class.php";
        if (!class_exists($class_name, false)) {
            @include_once $class_name . '.php';
            if (!class_exists($class_name, false)) {
                trigger_error("Unable to load class: $class_name", E_USER_NOTICE);
            }
        }
    }
}

/**
 * a class factory ;)
 *
 * use cmClassFactory::getSingletonOf(CSHOP_CLASSES_PRODUCT, $pdb) to 
 * get a single global instance of an object without it having to be a singleton itself
 *
 * otherwise use getInstanceOf() to get a brand new instance.
 */
class cmClassFactory {
  private static $instances = array();
 
  private function __construct() {}
 
  public static function getSingletonOf($c, $db) {
      if (empty(self::$instances[$c])) self::$instances[$c] = new $c($db);
      return self::$instances[$c];
  }

  public static function getInstanceOf($c, $db) {
      return new $c($db);
  }

 
}

