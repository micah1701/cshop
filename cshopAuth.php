<?php


class cshopAuth extends jen_Auth {
    var $debug  = false; 
    var $logfile = '/tmp/authlib.log';
    var $lifetime       =  55;
    var $classname = 'cshopAuth';
    var $allow_bypass = true;            ## If true, a default auth is created...

    var $_sql_fmt_select_user = 'SELECT id, perms, username, fname, lname, company, email, password,
                                             force_pword_change, token
                                 FROM cm_auth_user
                                 WHERE (username = %1$s OR email = %1$s)
                                 AND is_active = 1
                                 AND password IS NOT NULL';

    function auth_validatelogin() {
        global $pdb;
        $this->error_log('called ' . __FUNCTION__ . '()');

        if (!isset($_POST["f_username"])) return;

        if ($username = $_POST["f_username"]) {
            $this->auth["uname"] = $username;
        }

        if (isset($_GET['authtok'])) {
            $sql = sprintf("SELECT id, force_pword_change, token
                               FROM auth_user
                               WHERE token = '%s' OR email = '%s'",
                               addslashes($_GET["token"]));
            if ($uid = $pdb->getOne($sql)) {
                $this->show_pw_change_form($uid, 1);
                $this->auth["forcing_pw_change"] = true;    
            }
            return false;
        }

        $sql = sprintf($this->_sql_fmt_select_user,
                       $pdb->quoteSmart($_POST["f_username"]));

        $res = $pdb->query($sql);

        if ($row = $res->fetchRow()) {
            if ($this->_validate_pass($_POST['f_password'], $row['password'])) {
                if (isset($row["perms"]))
                    $this->auth["perm"] = $row["perms"];
                else
                    $this->auth["perm"] = 1;

                $this->auth["user_full_name"] = $row["fname"] . " " . $row["lname"];
                $this->auth["user_first_name"] = $row["fname"];
                #$this->auth["user_company"] = $row["company"];
                $this->auth["user_email"] = $row["email"];
                $this->auth["auth_id"] = $row["id"];
                $this->set_bypass_flag(false);

                return $row["id"];
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    function _validate_pass($pw, $hash) {
        return (crypt($pw, $hash) == $hash);
    }


    // show a form to allow (or force) a user to change their PW, assuming already logged in
    function show_pw_change_form($uid, $forced) {
        global $sess, $smarty, $fex; 
        $this->error_log('called ' . __FUNCTION__ . '()');

        if (!$fex) {
            include_once("formex.class.php");
            $fex = new formex();
            // try to save any GET params we might have had during timeout
            if ($_SERVER['REQUEST_URI']) {
                $fex->form_action = $_SERVER['REQUEST_URI'];
            }
        }

        $fex->max_size = 25;
        $fex->add_element("pw", array("New Password", "password", 1));
        $fex->add_element("pw_confirm", array("Confirm new password", "password", 1));
        $fex->add_element("op_pw_ch", array("CHANGE PASSWORD", "submit", 1));
        $fex->add_element("uid", array(null, "hidden", $uid));

        $smarty->assign("ERROR", $this->error);
        $smarty->assign("pw_ch_form", $fex->get_struct());
        $smarty->assign(array("self" => $_SERVER['PHP_SELF'], "FAILED" => $failed));
        $smarty->display("pw_change_form.tpl");
    }




    function auth_validate_pw_change() {
        global $db;
        $this->error_log('called ' . __FUNCTION__ . '()');
        if (!isset($_POST['f_pw']) or !isset($_POST['f_pw_confirm'])) {
            //$this->error = "Authentication token was not recieved!";
            $this->show_pw_change_form(true);
            return false;
        }
        else {
            $pw = $_POST['f_pw'];
            $pw2 = $_POST['f_pw_confirm'];

            if ($pw != $pw2) {
                $this->error = "The two passwords you entered did not match";
            }
            elseif (strlen($pw) < $this->auth_pw_min_length) {
                $this->error = "Passwords must be at least " . $this->auth_pw_min_length . " characters.";
            }
            // elseif (!preg_match('/\d+/', $pw) or !preg_match('/\w+/', $pw)) { // all alpha or number
            //    $this->error = "Passwords must contain both letters and numbers.";
            // }
            elseif (strtolower($pw) == strtolower($this->auth['uname'])
                    or strtolower($pw) == strtolower($this->auth['user_email'])) {
                $this->error = "Password must be different than your username or email address";
            }

            if ($this->error) {
                $this->show_pw_change_form(true);
                return false;
            }
            else { // looks ok
                $sql = sprintf("UPDATE auth_cred SET pword = '%s', pword_force_change = 0 WHERE user_id = %d",
                                crypt($pw),
                                $this->auth["auth_id"]);
                if ($this->debug) echo $sql . "<br>";
                $res = $db->query($sql);
                if ($db->affectedRows()) {
                    return true;
                }
            }
        }
    }  

                                    

    function auth_loginform($failed=0) {
        global $sess;
        global $smarty; 
        global $fex;  // see note below
        $this->error_log('called ' . __FUNCTION__ . '()');

        // here we put the entire page using smarty
        $smarty->assign("page_id", 'login');

        require_once("formex.class.php");
        // this is a bad kludge to get uname/pw filled pre-filled-out for users that just
        // registered + confirmed their account - if $fex exists it can be filled out with
        // a $db_row attrib and maybe $FEx, etc. - see activate.php, e.g.
        if (!$fex) { 
            $fex = new formex(); 

            // try to save any GET params we might have had during timeout
            if ($_SERVER['REQUEST_URI']) { 
                $fex->form_action = $_SERVER['REQUEST_URI'];
            }
        }

        $fex->max_size = 16;
        $fex->add_element('username', array('Username/email', 'text', null, array('class'=>'cartLogin'), 1));
        $fex->add_element('password', array('Password', 'password', null, array('class'=>'cartLogin'), 1));
        $fex->add_element("op_login", array("LOGIN", "submit", 1)); 

        $smarty->assign("cform", $fex->get_struct());
        $smarty->assign(array("self" => $_SERVER['PHP_SELF'], "BAD_PASS" => $failed));
        $smarty->display("float:checkout_login.tpl");
    }



    function admin_auth_loginform($failed=0) {
        if ($this->debug) echo "called auth_loginform()<br>\n";
        global $sess;
        global $PHP_SELF;
        global $smarty;

        include_once("FEx.class.php");
        $fex = new FEx();
        $fex->add_element("username", array("Username or Email", "text", 1));
        $fex->add_element("password", array("Password", "password", 1));
        $fex->add_element("op_login",array("Login Now", "submit", 1));
        $smarty->assign("loginform", $fex->display());

        $smarty->assign(array("self" => $PHP_SELF, "FAILED" => $failed));
        $smarty->display("admin_loginform.tpl");
    }



  function auth_preauth() {
      $this->error_log('called ' . __FUNCTION__ . '()');
      if (isset($_POST[$this->auth_bypass_token])) {
          $this->error_log('setting uid = 1');
          $this->set_bypass_flag();
          $uid = 1;
          return $uid;
      }
  }

}





class cshopPerm extends Perm { 
    var $debug = 0;
    var $permissions = array(
        "PUBLIC"    => 1,
        "SALES REP" => 3,
        "ADMIN"     => 7,
    );

    // TODO make a template with a nice message
    function perm_invalid($does_have, $must_have) {
        global $auth;
        echo "<p><b>Permission Denied</b><br>You do not have permission to access this page.</b></p>";
    }
}



