<?php

// autoloader here for all classes - now that we are on PHP5, cshop requires 
// it, so we make sure we have one.
// http://us2.php.net/manual/en/language.oop5.autoload.php
if (!function_exists('__autoload')) {
    function __autoload($class_name) {
        echo "$class_name:";
        include_once "$class_name.class.php";
        if (!class_exists($class_name, false)) {
            @include_once $class_name . '.php';
            if (!class_exists($class_name, false)) {
                trigger_error("Unable to load class: $class_name", E_USER_NOTICE);
            }
        }
    }
}

/** magically cause all PHP date functions to default to the given timezone. 
 * but will work only in php >= 5.1
 *
 * cause mysql to store all dates with the current UTC offset built-in, as 
 * well. So
 * when we get them out later, PHP can just display whatever it is, and put the 
 * DST offset (EST/EDT or +4:00/+5:00, according to the format needed) for the 
 * given date, which should be correct
 */
if (defined('CSHOP_DISPLAY_TZ') && function_exists('date_default_timezone_set')) {
    date_default_timezone_set (CSHOP_DISPLAY_TZ);
    #$DEFAULT_TZ = new DateTimeZone(CSHOP_DISPLAY_TZ);

    $pdb->query("SET time_zone = " . $pdb->quote(date('P')));
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
 
  public static function getSingletonOf($c, &$db) {
      if (empty(self::$instances[$c])) self::$instances[$c] = new $c($db);
      return self::$instances[$c];
  }

  public static function getInstanceOf($c, $db) {
      return new $c($db);
  }

 
}

