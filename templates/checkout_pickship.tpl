<~ include file="cart_header.tpl" heading="Shipping Method" ~>
<~ include file="float:minicart.tpl" ~>

			  
<div id="cartWrapper">
  <~ include file="float:cartsteps.tpl" step=2 ~>
  
      <~ if $errors ~>

        <div class="userIndicator">
          Please note, special shipping conditions:
          <ul class="userError">
          <~ foreach from=$errors item=e ~>
              <li><~ $e|escape ~></li>
          <~/foreach~>
          </ul>
          You may <a href="checkout.php">go back to the Shipping information page</a> 
          and change your values.
        </div>
      <~else~>
        <div class="userNotice">Please choose your preferred shipping method.</div>
      <~/if~>
        
      <~ if $cform.ship_method ~>
        <~ $cform.FORM ~>

            <div class="checkoutFormBox" id="chkPickShip">
                <h2 class="checkoutSectionHeader">Select Shipping Method</h2>
            <~ if $HAVE_FREE_SHIP ~>
              <strong>You have qualified for free shipping on this order!</strong>
            <~ else ~>
            <div class="userInstruction">The following shipping methods are available for this order. Please select one.
              <span class="formReqStar">*</span>
            </div>

            <~ /if ~>
                <table cellpadding="4" cellspacing="0" border="0">
                  <tr>
                    <td class="formField">
                      <~ $cform.ship_method.TAG ~>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <div class="checkoutFormBox" style="text-align: right">
                         <~ $cform.butt.TAG ~>
                      </div>
                    </td>
                  </tr>
                </table>
            </div>
            <~ $cform.HIDDENS ~>
          </form>
      <~/if~>
</div>
<~ include file="cart_footer.tpl" ~>
