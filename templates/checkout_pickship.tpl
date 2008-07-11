<~ include file="cart_header.tpl" heading="Shipping Method" ~>
<~ include file="float:minicart.tpl" ~>

			  
<div id="cartWrapper">
  <~ include file="float:cartsteps.tpl" step=2 ~>
  
  <div style="clear: left">

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
      <~/if~>
        
      <~ if $cform.ship_method ~>
        <~ $cform.FORM ~>

            <div class="checkoutFormBox" id="chkPickShip">
            <~ if $HAVE_FREE_SHIP ~>
              <strong>You have qualified for free shipping on this order!</strong>
            <~ else ~>
              <strong>Please choose your preferred shipping method.</strong>
              <span class="formReqStar">*</span>
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
  <div style="clear: both">&nbsp;</div>
</div>
<~ include file="cart_footer.tpl" ~>
