<~ include file="cart_header.tpl" heading=$ACTION ~>
<~ include file="float:minicart.tpl" ~>
<div id="cartWrapper">

      <div style="text-align: right">
        <~ if $auth_logged_in ~><a href="/cart/logout.php">Logout</a><~/if~>
      </div>

<~ if $msg ~>
    <div class="userIndicator">
        <~$msg~>
    </div>
<~/if~>
			  
<~ if $userinfo ~>

    <div class="userProfileSection">
    <strong>Your contact information</strong>
      <div class="userProfileSectionControls">
        <a href="?op_prof">edit</a>
      </div>
      <table>
        <tr>
          <td> Your Name </td>
          <td class="formField">
            <~ $userinfo.cust_name ~>
          </td>
        </tr>
      <~ if $userinfo.company ~>
        <tr>
          <td> Company </td>
          <td class="formField">
            <~ $userinfo.company ~>
          </td>
        </tr>
      <~/if~>
        <tr>
          <td > email </td>
          <td class="formField">
            <~ $userinfo.email ~>
          </td>
        </tr>
        <tr>
          <td> Telephone </td>
          <td class="formField">
            <~ $userinfo.telephone ~>
          </td>
        </tr>
      </table>
   </div>

    <div class="userProfileSection">
              <div class="userProfileSectionControls">
                <a href="?op_login">Change password</a>
              </div>
        <strong>Username:</strong> 
            <~ $userinfo.username ~>
    </div>



    <div class="userProfileSection">
  <strong>Your addresses</strong>
  <br />
  We have the following shipping and/or billing addresses for you:
  <br />
  <~ section name=a loop=$addrs ~>
    <div class="userProfileSection">
      <div class="userProfileSectionControls">
        <a href="?op_addr&amp;id=<~ $addrs[a].id ~>">edit</a> | 
        <a href="?op_addr_del&amp;id=<~ $addrs[a].id ~>">delete</a> 
      </div>
      <~ include file=float:address_format.tpl address=$addrs[a] ~>
    </div>
  <~sectionelse~>
   <strong>No addresses recorded yet.</strong> 
  <~/section~>
  </div>
    
<~else~>

    <h2>Create Customer Account</h2>
   <~ if $ACTION eq $smarty.const.OP_NEW_USER ~>
        <div class="userNotice">
            Please fill in the following form to create a new customer account with
            us. By using your login information in the future, you will be able to
            check on the status of your orders and place repeat orders with us. We
            value your privacy and will not use or distribute your information
            without your consent (please see our <a href="<~ $smarty.const.CSHOP_PRIVACY_POLICY_URL ~>">Privacy Policy</a> for
            more information). If you do not wish to create an account at this
            time, you may <a href="checkout.php">go back and checkout anonymously</a>.  
        </div>

        <div class="accountNote">NOTE: If you already have an account with us, please <a href="checkout.php">login here</a>.</div>
    <~ /if ~>
 
    <~ if $errors ~>
        <~ include file="cart/user_error_report.tpl" ~>
    <~ elseif $DUPE_EMAIL ~>
        <div class="userError">
        The email address or username you gave already exists in our system. If you have already created an
        account, please <a href="checkout.php">login here</a>.</div>

        <p>
        If you have forgotten your password to this account, you can also <a href="profile.php?op_login">reset it here</a>.
        </p>
    <~ /if ~>

    <div class="formReqNotice">Fields marked with  <span class="formReqStar">*</span> are required</div>
    <~ $cform.FORM ~>


  <~ if $ACTION eq $smarty.const.OP_NEW_USER or $ACTION eq $smarty.const.OP_EDIT_PROFILE ~>
      <div class="checkoutFormBox">
        <h2 class="checkoutSectionHeader">Your contact information</h2>
        <div class="userInstruction">
            We will use this information only to contact you in regard to your order.
        </div>
          <table>
            <tr>
              <td class="<~ $cform.cust_name.CLASS ~>">
                <label for="cust_name" accesskey="e">Your Name</label>
              </td>
              <td class="formField">
                <~ $cform.cust_name.TAG ~><span class="formReqStar">*</span>
              </td>
            </tr>
            <tr>
              <td class="<~ $cform.company.CLASS ~>">
                <label for="company" accesskey="e"><~ $cform.company.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.company.TAG ~>
              </td>
            </tr>
            <tr>
              <td class="<~ $cform.email.CLASS ~>">
                <label for="email" accesskey="e"><~ $cform.email.LABEL ~></label>
              </td>
              <td class="formField <~ $cform.email.CLASS ~>">
                <~ $cform.email.TAG ~><span class="formReqStar">*</span>
              </td>
            </tr>
            <tr>
              <td class="<~ $cform.telephone.CLASS ~>">
                <label for="telephone" accesskey="e"><~ $cform.telephone.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.telephone.TAG ~>
              </td>
            </tr>
          </table>
       </div>
  <~/if~>

  <~ if $ACTION eq $smarty.const.OP_NEW_USER or $ACTION eq $smarty.const.OP_EDIT_ADDR ~>
   <~ if $ADDRESS_REQUIRED ~>
      <div class="checkoutFormBox">
          <h2 class="checkoutSectionHeader">Your address</h2>
          <table>
        <~ if $cform.name ~>
            <tr>
              <td class="<~ $cform.name.CLASS ~>">
                <label for="name" accesskey="e"><~ $cform.name.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.name.TAG ~><span class="formReqStar">*</span>
              </td>
            </tr>
        <~/if~>
        <~ if $cform.company ~>
            <tr>
              <td class="<~ $cform.company.CLASS ~>">
                <label for="company" accesskey="e"><~ $cform.company.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.company.TAG ~><span class="formReqStar">*</span>
              </td>
            </tr>
        <~/if~>
            <tr>
              <td class="<~ $cform.street_addr.CLASS ~>">
                <label for="street_addr" accesskey="e"><~ $cform.street_addr.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.street_addr.TAG ~><span class="formReqStar">*</span>
              </td>
            </tr>
            <tr>
              <td class="<~ $cform.addr2.CLASS ~>">
                <label for="addr2" accesskey="e"><~ $cform.addr2.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.addr2.TAG ~>
              </td>
            </tr>
            <tr>
              <td class="<~ $cform.city.CLASS ~>">
                <label for="city" accesskey="e"><~ $cform.city.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.city.TAG ~><span class="formReqStar">*</span>
              </td>
            </tr>
            <tr>
              <td class="<~ $cform.state.CLASS ~>">
                <label for="state" accesskey="e"><~ $cform.state.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.state.TAG ~>
              </td>
            </tr>
            <tr>
              <td class="<~ $cform.postcode.CLASS ~>">
                <label for="postcode" accesskey="e"><~ $cform.postcode.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.postcode.TAG ~>
              </td>
            </tr>
            <tr>
              <td class="<~ $cform.country.CLASS ~>">
                <label for="country" accesskey="e"><~ $cform.country.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.country.TAG ~><span class="formReqStar">*</span>
              </td>
            </tr>
          </table>
       </div>
     <~/if~>
  <~/if~>

  <~ if $ACTION eq $smarty.const.OP_NEW_USER or $ACTION eq $smarty.const.OP_EDIT_LOGIN ~>
      <div class="checkoutFormBox" id="checkoutAccountCredentialPicker">
        <h2 class="checkoutSectionHeader">Your sign-on information</h2>
        <table>
          <~ if $cform.username ~>
            <tr>
              <td class="<~ $cform.username.CLASS ~>">
                <label for="username" accesskey="e"><~ $cform.username.LABEL ~></label>
              </td>
              <td  class="formField">
                <~ $cform.username.TAG ~><span class="formReqStar">*</span>
              </td>
              <td>
                <div class="checkoutHintNotice">
                  Please choose a username to identify your account.
                </div>
              </td>
            </tr>
          <~ elseif $username ~>
            <tr>
              <td> <strong>Your Username</strong>: </td>
              <td class="formField"> <~ $username ~> </td>
            </tr>
            <tr>
              <td colspan="2">You may change your password below. Please type it twice for confirmation.</td>
            </tr>
          <~/if~>
            <tr>
              <td class="<~ $cform.password.CLASS ~>">
                <label for="password" accesskey="e"><~ $cform.password.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.password.TAG ~><span class="formReqStar">*</span>
              </td>
              <td>
                <div class="checkoutHintNotice">
                      Passwords must be at least 6 characters long, and cannot be the same as your username. Please keep your password secure!
                </div>
              </td>
            </tr>
            <tr>
              <td class="<~ $cform.password2.CLASS ~>">
                <label for="password2" accesskey="e"><~ $cform.password2.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.password2.TAG ~><span class="formReqStar">*</span>
              </td>
              <td>
                <div class="checkoutHintNotice">
                      Please enter the password again for confirmation.
                </div>
              </td>
            </tr>
          </table>
       </div>
  <~/if~>
<~/if~>

<~ if $order_history ~>
    <~ include file=float:order_list.tpl ~>
<~/if~>

       <div id="checkoutSubmitButtonArea">   
                <~ $cform.op.TAG ~>
                <~ $cform.HIDDENS ~>
       </div>
     </form>


     <div style="clear: both">&nbsp;</div>
 </div>
<~ include file="cart_footer.tpl" ~>
