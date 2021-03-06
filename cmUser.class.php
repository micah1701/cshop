<?php
require_once('db_container.class.php');
require_once(CONFIG_DIR.'cshop.config.php');
require_once(CSHOP_CLASSES_ADDRESSBOOK.'.class.php');
require_once(CSHOP_CLASSES_PAYMETHOD.'.class.php');
require_once('cshop/mailer.class.php');
require_once('Mail.php');
require_once('Mail/mime.php');


/**
 * basic container class for marquisjet users/sales reps
 *
 */
class cmUser extends db_container {
    var $_sesskey = 'circusUser_id';
    var $_table = 'cm_auth_user';

    /** in account.php, do we require user to provide their address to sign up? */
    var $do_require_address_on_register = false;

    var $colmap = array('cust_name'     => array('Customer Name', 'text', 1),
                        'company'       => array('Company', 'text'),
                        'email'         => array('email', 'email', 1),
                        'telephone'     => array('Telephone', 'text'),
                        'fax'           => array('Fax', 'text'),
                        'username'      => array('User Name', 'text', 1),
                        'perms'         => array('Permissions', 'select', array('ADMIN'=>'ADMIN', 'SALES REP'=>'SALES REP', 'PUBLIC'=>'PUBLIC'), null));

    var $order_confirm_bcc = ERROR_EMAIL_RECIP;

    
    function cmUser(&$db) {
        $this->db = $db;
        // stick an address object on here
        $addr_class = CSHOP_CLASSES_ADDRESSBOOK;
        $this->addr = new $addr_class($db);
    }


    function set_id($id) { // not the best way to go about this?
        $this->_id = $id;
        $this->addr->user_id = $id;
        $_SESSION[$this->_sesskey] = $id;
    }


    function set_auth(&$auth) {
        if (!empty($auth) and is_numeric($auth->auth['uid'])) {
            if (isset($_SESSION[$this->_sesskey])) unset($_SESSION[$this->_sesskey]);
            $id = $auth->auth['uid'];
            $this->set_id($id);
            return $id;
        }
    }


    /* quiky to get a true/false on the given type of permission */
    function has_perm($p) {
        global $perm; // i know.
        return $perm->have_perm($p);
    }

    /**
     * get the id of the current user who called us into existence. 
     * grab from the global $auth object which should already be instantiated
     * @return int a user id
     */
    function get_auth_id() {
        if (isset($GLOBALS['auth']) and is_numeric($GLOBALS['auth']->auth['uid'])) {
            // once they are signed in we need to DELETE the fkg sesskey!
            if (isset($_SESSION[$this->_sesskey])) unset($_SESSION[$this->_sesskey]);
            return $GLOBALS['auth']->auth['uid'];
        }
        else {
            if (empty($_SESSION[$this->_sesskey])) {
                // generate random unique temp user id, that never actually gets saved anyway
                $_SESSION[$this->_sesskey] = 'ANON-'.uniqid(mt_rand(), true);
            }
            return $_SESSION[$this->_sesskey];
        }
    }


    /*
     * create a special anon user account. We use the email for the anon_email
     * column and leave the regular email col blank. also set the is_anon flag. 
     * @param $email string the email addr to use
     * @param $vals array any other vals in cm_users table to store (optional)

     * */
    function create_anon_user($email, $vals=array()) {
        if ($this->_id) {
            $this->_id = null;
        }
        // strip 'anon_' off of anything in vals. 
        // to get around the fact that we had to prefix the fields in anon 
        // user signup with this to avoid naming conflict with the 
        // shipping addr half of the form :/
        foreach ($vals as $k => $v) { 
            $stripped_k = preg_replace('#^anon_#', '', $k);
            unset($vals[$k]);
            $vals[$stripped_k] = $v;
        }
        if ($email)
            $vals['anon_email'] = $email;
        elseif ($vals['email'])
            $vals['anon_email'] = $vals['email'];

        $vals['email'] = null;
        $vals['is_anon'] = true;
        $vals['perms'] = 'ANON';
        return $this->store($vals);
    }


    /**
     * hack to modify colmap to fetch each field prefixed with 'anon_'. This is 
     * to avoid conflicts in field names with the shipping addr form, which 
     * happens to be on the same page in anon checkout :/
     */
    function get_anon_colmap() {
        $anoncolmap = array();
        foreach (array('cust_name','email','telephone','fax') as $k) {
            $anoncolmap['anon_'.$k] = $this->colmap[$k];
        }
        return $anoncolmap;
    }



    /** get all addresses assoc. with this user
     * @return array 
     */
    function fetchAllAddr() {
        return $this->addr->fetchAllByUser($this->get_id());
    }


    /** unset the session var that holds the userid - safety
     */
    function unset_sesskey() {
        $_SESSION[$this->_sesskey] = null;
    }




    function fetchBillingAddr($cols=null) {
        $bid = $this->fetch(array('billing_addr_id'));
        if (!$bid['billing_addr_id']) {
        }
        else {
            $this->addr->set_id($bid['billing_addr_id']);
            return $this->addr->fetch($cols);
        }
    }

    function fetchShippingAddr($cols=null) {
        $sid = $this->fetch(array('shipping_addr_id'));
        if (empty($sid['shipping_addr_id'])) {
        }
        else {
            $this->addr->set_id($sid['shipping_addr_id']);
            return $this->addr->fetch($cols);
        }
    }

    /**
     * set the current billing or shipping addr in the users table to 
     * point to the given address_book id
     * @param $type str billing or shipping
     * @param $addrid int an id from address_book
     * @return success
     */
    function activateAddress($type, $addrid) {
        if ($type != 'billing' and $type != 'shipping') {
            return $this->raiseError("bad param '$type'");
        }
        $this->addr->set_id($addrid); // make sure we don't just override a dbc instance
        if (!$this->get_id()) {
            return $this->raiseError("user id not set");
        }
        $vals[$type.'_addr_id'] = $addrid;
        return $this->store($vals);
    }

    /**
     * store an address and associate it with this user
     * @param $type str billing or shipping
     * @param $addr array assoc address values
     * @param $forcenew force creation of a new cm_address_book entry, defaults to true
     * @see cmAddressBook
     * @return success
     */
    function store_address($type, $addr, $forcenew=true) {
        if (!$this->get_id()) {
            return $this->raiseError("user id not set");
        }
        $addr['user_id'] = $this->get_id();

        if (!$forcenew) { // look for existing addr and update it.
            if ($addrid = $this->get_header($type.'_addr_id')) {
                $this->addr->set_id($addrid);
            }
            else { // not known.
                $this->addr->reset();
            }
        }
        /* use the full country name, not ISO code, whenever possible */
        if (!empty($this->addr->colmap['country'][3]['iso_codes']) && $addr['country'] != 'US' and is_callable(array('formex', 'get_country_opts'))) {
            $addr['country'] = formex::get_country_opts(true, $addr['country']);
        }
        
        $this->addr->store($addr, $forcenew);
        return $this->activateAddress($type, $this->addr->get_id());
    }



    /**
     * reset the token for this user, and set/unset the force_pw_change flag.
     * @param tog bool if false, unset the force flag
     * @return string the new token
     */
    function force_pword_change($tog=true) {
        $tok = $this->create_token();
        $vals = array('force_pword_change'=>$tog, 'token' => $tok);
        return $this->store($vals);
    }




    /** create a 16 diit unique identifier for this user
     * @return string
     */
    function create_token() {
        return substr(md5(uniqid(getmypid() . rand(), true)), 16);
    }


    /**
     * get the user id of someone given the token
     * @return int a user id or false on no match
     */
     function get_id_by_token($token) {
         $sql = sprintf("SELECT %s FROM %s WHERE token = '%s'",
                         $this->_pk_col,
                         $this->get_table_name(),
                         addslashes($token));
         $id = $this->db->getOne($sql);
         if ($id) {
             $this->set_id($id);
             return $id;
         }
     }

   /**
     * get the user id of someone given the email
     * @return int a user id or false on no match
     */
     function get_id_by_email($str) {
         $sql = sprintf("SELECT %s FROM %s WHERE LOWER(email) = '%s'",
                         $this->_pk_col,
                         $this->get_table_name(),
                         strtolower(addslashes($str)));
         $id = $this->db->getOne($sql);
         if ($id) {
             $this->set_id($id);
             return $id;
         }
     }


   /**
    * util function to get email addr of this user
    *
    * if this seems to be an "anonymous" user, this is the only time we
    * care. The email addr will be in the anon_email column, not the
    * normal one.
    */
    function get_email() {
        if (!isset($this->header['email'])) {
            if (defined('CSHOP_ALLOW_ANON_ACCOUNT')) {
                $this->fetch(array('email','anon_email','is_anon'));
            }
            else {
                $this->fetch(array('email'));
            }
        }
        if (defined('CSHOP_ALLOW_ANON_ACCOUNT') and !empty($this->header['is_anon'])) { 
            return $this->header['anon_email'];
        }
        else {
            return $this->header['email'];
        }
    }


    /**
     * change the password for this user, if they are found and the token matches
     * @return success
     */
     function change_pword($new_pass) {
         $vals = array();
         $vals['password'] = crypt($new_pass);
         return $this->store($vals);
     }




    /**
     * send an email to this userperson 
     * with an MD5 key that can be used to begin password change process
     * @return success
     * @todo make the format of the email in a template somehow...
     */
    function send_pass_notification($forced = false) {
        $site = SITE_DOMAIN_NAME;
        $user = $this->fetch();
        $mash = $user['token'];
        $recip = sprintf('%s <%s>', $this->get_full_name(), $this->get_email());
        $bcc = $this->order_confirm_bcc;
        $link = sprintf('http://%s'.CSHOP_PASS_RECOVER_LINK_FMT,  
                        $_SERVER['HTTP_HOST'], 
                        $user['id'], 
                        $mash);

        $sender = EMAIL_SENDER;
        $headers = sprintf("From: %s Web <$sender>\n", SITE_DOMAIN_NAME);
        $headers .= "X-Sender: <$sender>\n"; 
        $headers .= "Return-Path: <$sender>\n";  // Return path for errors
        $headers .= "BCC: $bcc\n";

        $msg = "This is an automatic message from $site.";

        if ($forced) {
               $msg .= "
                Account information for you has just been added to added to the $site
                website. In order to login, please click on the link below
                - you will be taken to a page where you can choose a password for your
                account and then login and begin shopping at $site!
                ";
        }
        else {
            $msg .= "
                Someone has requested to change the password for the user account with this
                email address. If you have requested this, please click on the link below -
                you will be taken to a page where you can update your password and other
                account information:
                ";
        }
        $msg .= <<<EOM

         *********************************************************************

         $link

         *********************************************************************

        (If you cannot click on the above link or it does not open a browser
        window, just copy the entire line above, making sure to include all
        characters, into your browser's 'Go to:' or 'Location:' bar.)

        If you did not request this action, simply delete this message. No futher
        action will be taken and your password to the site will not be
        changed.

        This message was sent to "$recip". 
        If you have any questions about or problems with this service, send us
        a message at [$sender]

EOM;
		//'
        $msg = preg_replace("/^ *(.*)/m", "\\1", $msg);  //remove left-side spaces from all the lines in msg
        $subj = sprintf('%s: Account Change Request', SITE_DOMAIN_NAME);

        $cm = new circusMailer();
        return $cm->send($recip, $subj, $msg);
    }                   


    /**
     * send an email to this userperson with info on the order
     * @deprecated
     */
    function send_order_notification(&$order) {
        trigger_error('cmUser::send_order_notification(): this method is deprecated, see cmOrder::send_user_notification()', E_USER_WARNING);
    }


    /**
     * get a summary of all the orders by this user
     * @param $cols opt. array list of columns in cm_orders we want to get
     * @return array struct of the orders, for smarty */
    function fetch_order_history($cols=null) {
        if (empty($cols)) {
            $cols = array('order_token','shipping_name','orders_status','billing_name','amt_billed_to_date','amt_quoted','order_create_date');
        }
        $sql = sprintf("SELECT %s FROM cm_orders WHERE user_id = %d",
                        join(',', $cols),
                        $this->get_id());
        $res = $this->db->query($sql);
        $c = CSHOP_CLASSES_ORDER;
        $order = new $c($this->db);
        $statii = $order->get_statuses();
        $items = array();
        while ($row = $res->fetchRow()) {
            $row['status'] = $statii[$row['orders_status']];
            $items[] = $row;
        }
        return $items;
    }


    /**
      * return the user's full name. 
     */
    function get_full_name() {
        if ($this->_id && empty($this->header['full_name'])) {
            $uservals = $this->fetch();

            if (empty($uservals['cust_name']) and !empty($uservals['fname']))
                $uservals['cust_name'] = $uservals['fname'] . ' ' . $uservals['lname'];
            elseif (empty($uservals['cust_name']) and !empty($uservals['first_name']))
                $uservals['cust_name'] = $uservals['first_name'] . ' ' . $uservals['last_name'];

            $this->header['full_name'] = $uservals['cust_name'];
        }
        return $this->header['full_name'];
    }



    /**
     * check that a given username and password checks out
     *
     * @param $username string
     * @param $password string
     * @return true if found
     */
    function authenticate($username, $pw) {

        if (empty($username) or empty($pw)) return false;

        $sql = sprintf('SELECT id, perms, username, cust_name, company, email, password, force_pword_change, token
                        FROM %s WHERE (username = %s OR email = %s)',
                        $this->get_table_name(),
                        $this->db->quoteSmart($username),
                        $this->db->quoteSmart($username));


        $row = $this->db->getRow($sql);
        if (!empty($row)) {
            if (crypt($pw, $row['password']) == $row['password']) {
                $this->set_id($row['id']);
                return $row;
            }
            else {
                return false;
            }
        }
    }


    function payment_method_factory() {
        return cmClassFactory::getInstanceOf(CSHOP_CLASSES_PAYMETHOD, $this->db);
    }



}

