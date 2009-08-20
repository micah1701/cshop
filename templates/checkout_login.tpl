<~ include file="cart_header.tpl" heading="Checkout / sign-up" ~>

<div id="checkoutWrapper">

			  

    <div class="cartLoginFormBox" id="cartLoginForm">
    
      <~ $cform.FORM ~>

      <~ if $BAD_PASS ~>

        <div class="userError">Sorry, the credentials you sent could not be validated.
            <br />
            Please try again or <a href="pass.recover.php">click here if you have forgotten your password</a>.
        </div>
        <div style="clear: both; height: 1px; overflow: hidden">&nbsp;</div>

      <~ else ~>

        <h2 class="flir">RETURNING CUSTOMERS</h2> 

      <~ /if ~>

          <table class="login" border="0">
            <tr>
              <td class="<~ $cform.username.CLASS ~>">
                <label for="username" accesskey="e"><~ $cform.username.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.username.TAG ~>
              </td>
              <td class="<~ $cform.password.CLASS ~>">
                <label for="password" accesskey="e"><~ $cform.password.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.password.TAG ~>
              </td>
              <td style="text-align: right" colspan="2">
                <~ $cform.op_login.TAG ~>
              </td>
            </tr>
          </table>
      </form>
    </div>

    <div class="cartLoginFormBox">
        <strong>I am a new customer.</strong> &raquo;&raquo; <a href="account.php">CREATE ACCOUNT</a>
        <p>
            By creating an account at <~ $SITE_DOMAIN_NAME ~> you will be able to shop faster on your next visit, 
            keep up to date on an orders status, and keep track of the orders you have previously made. Please
            see our <a href="<~ $smarty.const.CSHOP_PRIVACY_POLICY_URL ~>">Privacy Policy</a>.
        </p>
    </div>

    <~ if $smarty.const.CSHOP_ALLOW_ANON_ACCOUNT ~>
    <div class="cartLoginFormBox" id="cartLoginBypass">
      <~ $cform.FORM ~>
        <a href="checkout.php" onclick="document.forms[1].submit(); return false">&raquo;&raquo; CONTINUE TO CHECKOUT WITHOUT REGISTERING</a>
        <input type="hidden" name="auth_cancel_login" value="PROCEED TO CHECKOUT" />
        <input type="hidden" name="auth_bypass" value="1" />
      </form>
    </div>
    <~/if~>

</div>
<~ include file="cart_footer.tpl" ~>
