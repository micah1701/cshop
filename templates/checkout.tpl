<~ include file="cart_header.tpl" heading="Shipping Address" ~>
<~ include file="float:minicart.tpl" ~>

<div id="cartWrapper">

  <~ include file="float:cartsteps.tpl" step=1 ~>
  <div class="userNotice">Please enter shipping information for your order below.</div>

  <~ if $is_new_user ~>
    <div class="userAcctNotice">
      <p><strong>New Account</strong><br />
      Your new account has been created and is
      ready to use. You should receive a email in a few minutes with your account
      details for future reference. Please continue through checkout process below:
      </p>
    </div>
  <~ elseif $user ~>
    <div class="userAcctNotice">
        <span class="checkoutSection">Your personal details</span>
        <br />
        <strong><~ $user.cust_name ~> <~ if $user.company ~>&mdash; <~ $user.company ~><~/if~></strong>
        <br />
        <~ $user.email ~>
        <div class="cartYouAreNot">
          (If you are not <strong><~ $user.cust_name ~></strong>, <a href="logout.php">click here</a>)
        </div>
    </div>
  <~ /if ~>

  <div style="height: 1px">
    &nbsp;
  </div>
<br />
    <~ include file="cart/user_error_report.tpl" ~>
    <~ if $cform ~>
      <~ $cform.FORM ~>

      <~ if !$user ~>

      <div class="reqFieldNotice">
         Fields marked with <span class="formReqStar">*</span> are required
      </div>
      <div class="checkoutFormBox">
      <span class="checkoutSection">Your personal details:</span>
          <table cellpadding="4" cellspacing="0" border="0">
            <tr>
              <td>
                <label for="cust_name" >Your Name</label>
              </td>
              <td class="formField">
                <~ $cform.cust_name.TAG ~> <span class="formReqStar">*</span>
              </td>
            </tr>
            <tr>
              <td class="<~ $cform.email.CLASS ~>">
                <label for="email" ><~ $cform.email.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.email.TAG ~><span class="formReqStar">*</span>
              </td>
            </tr>
            <tr>
              <td class="<~ $cform.telephone.CLASS ~>">
                <label for="telephone" ><~ $cform.telephone.LABEL ~></label>
              </td>
              <td class="formField">
                <~ $cform.telephone.TAG ~>
              </td>
            </tr>
          </table>
        </div>
        <br />
        <br />
      <~/if~>





      <div class="checkoutFormBox" id="checkoutShippingAddrArea">
          <span class="checkoutSection">Shipping Address</span>
          <div class="userIndicator">
              <~ if $has_shipping ~>
                  Please edit your shipping address as needed.
                  <br />
                  [<a href="<~ $smarty.server.PHP_SELF ~>?op_add_ship">Add a new shipping address</a>]
              <~else~>
                  Please enter the shipping address for your order. 
              <~/if~>
          </div>
       </div>

       <~ include file=float:checkout_address_form.tpl ~>



          <div class="checkoutSubmitButtonArea">
           <~ $cform.butt.TAG ~>
          </div>
         <~ $cform.HIDDENS ~>
       </form>
    <~ /if ~>
</div>

<div class="clear">&nbsp;</div>
<~ include file="cart_footer.tpl" ~>
