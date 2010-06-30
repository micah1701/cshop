<?php

class cmProductDownload extends db_container {

    var $_table = 'cm_products_downloads';

    /** next 3 have to be set for use by store.dbcwrap.php. Really they should
     * be abstract in the parent class so we can make sure they exist */
    var $class_descrip = 'Downloads'; // english name of this class, what it represents
    var $table_orderby_default = 'name'; // column to sort listings by, by default
    var $table_name_column = 'name'; // column to get the "name" or description of a single instance from

    var $control_header_cols = array('name'=>'Name', 'is_active'=>'Active?');

    /* form definition arrays suitable for formex() */
    var $colmap = array('name' =>          array('Name', 'text', null, 1),
                        'is_active' =>  array('Is Active?', 'toggle', 1, null, 0),
                        'url' =>  array('URL', 'text', null, null, 0),
                        'cm_products_id' => array('', 'hidden')
                        );

    var $validations = array('url' => array('validate_url', null, "Invalid URL"));

    var $download_request_headers = array();

    const ERROR_VAL_URL = -701;



    function fetch_by_product_id($pid, $cols=null) {
        $w = 'cm_products_id = '.intval($pid);
        return $this->fetch_any($cols, 0, 0, 'name', $w);
    }


    /**
     * zipcode validator, static for call from formex() validate
     */
    function validate_url($col, $value) {
        $err = null;
        if (empty($value))
            $err = "URL required";
        elseif (!preg_match('#(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)#', $value))
            $err = "Please enter a valid URL";

        if ($err) $this->_push_validation_error($err, self::ERROR_VAL_URL, $col, $value);
    }


    function get_download_request_headers() {
        return $this->download_request_headers;
    }

    /**
     * pull and print a downloadable, using curl functions for passthrough.
     * extend for whatever storage mechanism or service we will be using on this cshop instance
     *
     * meant to be called when $this->_id is set already 
     */
    function digital_download_dumper($product_id) {
        if (empty($product_id)) return;

        $url = null;
        if ($info = $this->fetch_by_product_id($product_id)) {
            $url = $info[0]['url'];
        }
        //  'https://storage5.clouddrive.com/v1/MossoCloudFS_7aa80c20-d06b-4abd-b6c8-5f52139a51a3/test-images/unicornp.jpg';
        if (!$url) {
            trigger_error('Misconfiguration: no URL found for this item', E_USER_ERROR);
        }

        $headers = $this->get_download_request_headers(); 

        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $url);

        /** debugging ##
        * $temp_file = '/tmp/db';
        * $fh = fopen($temp_file, 'w');
        #
        * curl_setopt($ch, CURLOPT_VERBOSE, true);
        * curl_setopt($ch, CURLOPT_NOBODY, true);
        * curl_setopt($ch, CURLOPT_STDERR, $fh);
        * curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        */

        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'cmProductDownload_curlPassHeaders');

        curl_exec($ch);

        $res = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($res != 200) {
            trigger_error("HTTP/curl() result $res", E_USER_WARNING);
        } 
        curl_close($ch);
    }
}

function cmProductDownload_curlPassHeaders($ch, $header) {
    #if (preg_match('/^Content-type:/i', $header)) {
        header($header);
    #}
    return strlen($header);
}




