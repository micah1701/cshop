<~ include file="cart_header.tpl" heading="Billing Information" ~>
<~ include file="float:minicart.tpl" ~>

<script type="text/javascript">
    var shipVals = new Object;
    <~ foreach from=$shipaddr key=k item=val ~>
        shipVals.<~ $k ~> = '<~ $val|escape:"quotes" ~>';
    <~/foreach ~>

    function addrAutoFill(tog) {
        for (prop in shipVals) {
            field = document.getElementById('f_'+ prop);
            if (field && shipVals[prop]) {
                field.value = (tog)? shipVals[prop] : '';
            }
        }
    }
</script>
			  
<div id="cartWrapper">
    <~ include file="float:cartsteps.tpl" step=3 ~>
    <div class="userNotice">Please enter payment information for your order below.</div>

    <~ include file="float:user_error_report.tpl" ~>

    <~ if ! $errors ~>
       <~ if $mini_shipping ~>
            <~ if $mini_shipping.ship_total == 0 ~>
              <div class="userNotice">You have qualified for FREE shipping on this order!</div>
            <~else~>
              <div class="userNotice">Your shipping total for this order will be $<~ $mini_shipping.ship_total ~>.</div>        
            <~/if~>
       <~/if~>
    <~/if~>

  <~ if $smarty.const.CSHOP_ACCEPT_GIFTCARDS ~>
    <~ include file="float:checkout_giftcards.tpl" ~>
  <~/if~>


   <div class="checkoutFormBox">

   <~ if ! $PAYMENT_REQUIRED ~>
          <strong class="userIndicator">No additional payment is required.</strong>
      <~ $cform.FORM ~>

           <div style="margin-top: 2em">
             <strong>Comments on your order:</strong><br />
             <div class="checkoutFormBox">
               <~ $cform.user_comments.TAG ~>
             </div>
           </div>

           <div class="checkoutFormBox" style="text-align: right">
              <~ $cform.butt.TAG ~>
           </div>
          <~ $cform.HIDDENS ~>
      </form>

   <~ else ~>
    
      <div class="reqFieldNotice">
        Fields marked with <span class="formReqStar">*</span> are required
      </div>

    <~ if $cform ~>

      <~ $cform.FORM ~>

      <div class="checkoutFormBox" id="checkoutBillingPaymentInfoArea">
          <h2 class="checkoutSectionHeader">Payment information</h2>
          <div class="userInstruction">There is one allowed payment type for this order.</div>

          <div class="paymentType">
              <h3><strong>Payment Type:</strong> Credit Card</h3>
              <div class="userInstruction">Please enter your card information:</div>
              <table>
                <tr>
                  <td class="<~ $cform.cctype.CLASS ~>">
                    <label for="cctype" accesskey="n"><~ $cform.cctype.LABEL ~></label>
                    <div class="userHelpText">(click to select)</div>
                  </td>
                  <td class="formField" id="checkoutPaymentCardTypeSelection">
                    <~ $cform.cctype.TAG ~><span class="formReqStar">*</span>
                    <!-- img src="/cart/images/4-Credit-Card-Logos-Small.gif" alt="cc logos" -->
                  </td>
                </tr>
                <tr>
                  <td class="<~ $cform.ccno.CLASS ~>">
                    <label for="ccno" accesskey="n"><~ $cform.ccno.LABEL ~></label>
                  </td>
                  <td class="formField">
                    <~ $cform.ccno.TAG ~><span class="formReqStar">*</span>
                  </td>
                </tr>
                <tr>
                  <td class="<~ $cform.ccexp.CLASS ~>">
                    <label for="ccexp" accesskey="n"><~ $cform.ccexp.LABEL ~></label>
                  </td>
                  <td class="formField">
                    <~ $cform.ccexp.TAG ~><span class="formReqStar">*</span>
                  </td>
                </tr>
                <tr>
                  <td class="<~ $cform.csc1.CLASS ~>">
                    <label for="csc1" accesskey="n"><~ $cform.csc1.LABEL ~></label>
                  </td>
                  <td class="formField">
                    <~ $cform.csc1.TAG ~>&nbsp;<a href="csc_help.php" onclick="window.open(this.href, 'cscWin', 'resizable,height=440,width=380'); return false;">What is this?</a>
                  </td>
                </tr>
              </table>
          </div>
       </div>

      <a name="addr"> </a>
      <div class="checkoutFormBox" id="checkoutBillingPaymentAddrArea">
          <h2 class="checkoutSectionHeader">Billing Address</h2>
           <div class="userInstruction">Please enter the billing address for the card:</div>

           <label class="formexFieldRadio"><~ $cform.same_as_shipping.TAG ~>&nbsp;<~ $cform.same_as_shipping.LABEL ~></label>

           <div id="checkoutBillingAddr">
               <~ include file=float:checkout_address_form.tpl ~>
           </div>

         </div>


          <div class="checkoutSubmitButtonArea">
                <~ $cform.butt.TAG ~>
       </div>

<~/if~> <~* PAYMENT_REQUIRED *~>

          <~ $cform.HIDDENS ~>
          </form>
      <div style="height: 10px">&nbsp;</div>
    <~ /if ~>
  </div>
</div>
<br><br>
<~ include file="cart_footer.tpl" ~>
