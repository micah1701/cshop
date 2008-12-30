<~ include file="cart_header.tpl" ~>
			  
<div id="cartWrapper">
    <h2 class="productName headline">PASSWORD RECOVERY</h2>

    <~ include file="cart/user_error_report.tpl" ~>

    <br />
      <~ if $BAD_EMAIL ~>
        Sorry, the email address <strong><~ $BAD_EMAIL ~></strong> is not a valid email address.
      <~ elseif $EMAIL_NOT_FOUND ~>
        Sorry, the email address <strong><~ $EMAIL_NOT_FOUND ~></strong> 
        was not found in our records. If you believe this is in error, please contact the site administrator.
      <~ elseif $EMAIL_SENT ~>
        An email has just been sent to you at &#8216;<~ $EMAIL_SENT ~>&#8217;
        <p>
        In this email you will find instructions and a key that will enable you
        to change your password for this site. Please check your email now.
        </p>   
      <~ elseif $KEY_ERROR ~>
          <div class="userError">
          <strong>ERROR!</strong>:<br />
              <div class="error">
              <~ if $KEY_ERROR == 'MASH_INCOMPLETE' ~>
                Sorry, the key you gave is not complete. Please be sure you copied the
                entire link from the email, and that there are no line breaks or other
                non-alphanumeric characters in the link.
              <~ elseif $KEY_ERROR == 'UID_NOT_FOUND' ~>
                Sorry, the key you passed included an invalid user id. Perhaps your account
                has been deleted since the email was generated.
              <~ elseif $KEY_ERROR == 'MASH_NO_MATCH' ~>
                Sorry, the key you passed no longer matches your account. Perhaps your
                account information has been changed since your email was generated. Please
                go back to the <a href="pass.recover.php">&quot;Forgot Password&quot; page</a>
                and begin the process again.
              <~/if~>                           
          </div>
        
      <~ elseif $cform ~>
        If you already have an account with us and have forgotten your password,
        you can use this utility to reset your password. When you enter your email
        or username in the below field, an email will be sent to the email address
        we have on file for you. The email will contain a special link which you
        can then use to come back to this site and reset your password.
      <~/if~>



      <~ if $CHANGE_ERROR ~>
        <div class="error">
        <~ if $CHANGE_ERROR == 'UNIQ_NO_MATCH' ~>
          Sorry, your session is invalid and you may not change this user&#8217;s password in this
          session.
          <br /><br />
          Please go back to the <a href="pass.recover.php">&quot;Forgot Password&quot; page</a>
          and begin the process again.
        <~ elseif $CHANGE_ERROR == 'BAD_PARAM' ~>
          Sorry, the parameter you passed was invalid.
          <br /><br />
          Please go back to the <a href="pass.recover.php">&quot;Forgot Password&quot; page</a>
          and begin the process again.
        <~ elseif $CHANGE_ERROR == 'PASS_NO_MATCH' ~>
          Sorry, the two passwords you entered did not match. Please <a href="<~ $BACK_LINK ~>">go back</a> and re-enter
          your new password in the form.
        <~ elseif $CHANGE_ERROR == 'INVALID_UID' ~>
          Sorry, the user account could not be found!
        <~ elseif $CHANGE_ERROR == 'PASS_TOO_EASY' ~>
          Sorry, the password you entered was too short or did not contain any numeric characters.
          Please <a href="<~ $BACK_LINK ~>">go back</a> and enter a password that is at least 5 
          characters long and contains numerals or digits.
        <~/if~>
        </div>
      <~ elseif $CHANGE_SUCCESS ~>
          <h3>Password Changed</h3>
          Your password was successfully changed. Please <a href="checkout.php">log in</a> using your new credentials.
      <~/if~>        









    <~ if $cform ~>
      <~ $cform.FORM ~>
        <div class="checkoutFormBox">
            <strong>Enter your email address:</strong>
            <br />
            <~ $cform.email.TAG ~>
            <br />
        </div>
        <div class="checkoutFormBox" style="text-align: right">
                <~ $cform.op.TAG ~>
        </div>

          <~ $cform.HIDDENS ~>
          </form>
      <div style="height: 10px">&nbsp;</div>
    <~ elseif $pwform ~>
          Please fill out the below form to update your password. You will then be able to
          log into your account using your new password.  
      <~ $pwform.FORM ~>
        <div class="checkoutFormBox">
            <strong>Please choose a new password:</strong>
            <br />
            <~ $pwform.newpass.TAG ~>
            <br />
            <strong>Please re-enter the password to confirm:</strong>
            <br />
            <~ $pwform.newpass2.TAG ~>
            <br />
        </div>
        <div class="checkoutFormBox" style="text-align: right">
                <~ $pwform.op_send.TAG ~>
        </div>

          <~ $pwform.HIDDENS ~>
          </form>
    <~ /if ~>
    <div style="height: 10px">&nbsp;</div>
</div>

<~ include file="cart_footer.tpl" ~>
