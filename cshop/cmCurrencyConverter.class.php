<?php

/** static class to convert the monetary units and provide some formatting help
 * 
 * @todo cache the DB results for 24 hours in some kind of shmem space?
 *
 * $Id: cmCurrencyConverter.class.php,v 1.1 2006/02/27 00:15:03 sbeam Exp $
 *
 */

/* static */ class cmCurrencyConverter
{

    /** lookup table for rates, CODE => rate */
    var $rates = array();

    /**
     * insane singleton method that saves itself in the SESSION, if available,
     * or in the GLOBALS otherwise. 
     * should always be called as a ref in php4, ie 
     *        $cconv =& cmCurrencyConverter::getSingleton();
     */
    function getSingleton()
    {
        $class = __CLASS__;
        $inst_name = "_INSTANCE_" . strtoupper($class);
        if (isset($_SESSION)) {
            if (!isset($_SESSION[$inst_name])) {
                $_SESSION[$inst_name] =& new $class();
            }
            return $_SESSION[$inst_name];
        }
        else {
            if (!isset($GLOBALS[$inst_name])) {
                $GLOBALS[$inst_name] =& new $class();
            }
            return $GLOBALS[$inst_name];
        }
    }


   /**
    * fetch rates for the specified currency and return the converted value
    *
    * @param $amt float the amount to convert
    * @param $code str a currency code
    * @return float
    */
    /* static */ function convert($amt, $code)
    {
        if (!isset($this->rates[$code])) { // we might have it already
            global $pdb;
            $sql = sprintf("SELECT frate FROM currency_rates WHERE scode = '%s'",
                    addslashes($code));
            $this->rates[$code] = $pdb->getOne($sql);
        }
        if (!isset($this->rates[$code]) or !is_numeric($this->rates[$code])) {
            trigger_error("code $code is unknown", E_USER_WARNING);
        }
        else {
            return sprintf('%.02f', ($amt * $this->rates[$code]));
        }
    }

   /** return the proper symbol for the given currency. 
    * @param $code str currency code
    * @param $charset str a charset
    * @return str 
    * @todo I dont know how well the htmlentities actually work.
    *       Plus we should return actual codes in case the recip isn't speaking HTMLish 
    */
   /* static */ function symbol($code, $charset='UTF-8')
   {
       switch ($code) {
           case 'GBP':
               return '&pound;';
           case 'JPY':
               return '&yen;';
           case 'EUR':
               return '&euro;';
           case 'CAD':
               return '$';
           default: // USD
               return '$';
       }
   }
}
