<~ include file="cart_header.tpl" heading="Confirm Order Details" ~>
			  
<div id="cartWrapper">

  <~ include file="float:cartsteps.tpl" step=4 ~>
  <~ if !$cart_itemcount ~>
      <div class="userCartControl" style="text-align: center">
        Your cart is empty.
        <br />
        <a href="/" class='cartlink'>CONTINUE SHOPPING</a>
      </div>
  <~ elseif $payment_error ~>
    <div class="userError">
        Sorry, your payment information was not accepted for processing. 
        <br />
        The result of our attempt to run your payment was : 
        <br />
        <strong><~ $payment_error ~>: <~ $payment_error_msg ~></strong>
        <br />
        <br />
        Please check your payment and personal information. If anything needs to be changed, you can 
            <a href="checkout.php?billing">go back and try again</a>.
        <br />
        <br />
        If you feel this is an error or there is some mistake please let us know.
    </div>
    <br />
  <~ else ~>
    <div class="userNotice">
        Please check all order details and click CONFIRM ORDER to send your order for processing.
    </div>
    <div id="chkConfirmCustInfo">
        <h4>Customer:</h4>
        <div class="custName"><~ $user.cust_name ~> <~ if $user.company ~>&ndash;&nbsp;<~ $user.company ~><~/if~></div> 
        <div class="custEmail">&lt;<~ $user_email ~>&gt; </div>
        <~ if $user.telephone ~><div class="custPhone">Phone:&nbsp;<~ $user.telephone ~></div><~/if~>
    </div>

    <table width="90%">
        <tr>
            <td valign="top" class="checkoutAddress">
                <div class="checkoutFormBox">
                <h4>Billing Address</h4>
                    <~ if $billing ~>
                        <~ include file="cart/address_format.tpl" address=$billing ~>
                        <div class="checkoutConfirmEditAddrLink">
                            [<a href="checkout.php?billing#addr">edit</a>]
                        </div>
                    <~ else ~>
                        N/A
                    <~/if ~>
                </div>
             </td>
            <td valign="top" class="checkoutAddress">
                <div class="checkoutFormBox">
                <h4>Shipping Address</h4>
                    <~ if $shipping ~>
                        <~ include file="cart/address_format.tpl" address=$shipping ~>
                        <div class="checkoutConfirmEditAddrLink">
                            [<a href="checkout.php">edit</a>]
                        </div>
                    <~/if ~>
                </div>
             </td>
        </tr>
    </table>

    <~ if $payment_info ~>
        <div class="checkoutFormBox" id="checkoutConfirmPaymentInfoArea">
          <h4>Payment Information</h4>
          <~ $payment_info.cctype ~> -<~ $payment_info.ccno ~> (Exp: <~ $payment_info.ccexp|date_format:"%b %Y" ~>)
                        <div class="checkoutConfirmEditAddrLink">
                            [<a href="checkout.php?billing">edit</a>]
                        </div>
        </div>
    <~/if~>
      <~ if $giftcards ~>
        <div class="checkoutFormBox">
          <h2 class="checkoutSectionHeader">Giftcards applied to this order:</h2> 
             <table width="80%" cellspacing="1">
                <~ foreach from=$giftcards item=gc ~>
                  <tr style="background-color: #<~ cycle values=eee,ccc ~>">
                    <td><~ $gc.gc_no ~></td>
                    <td align="center"><~ $gc.gc_amt|currency_format ~></td>
                  </tr>
                <~/foreach~>
                  <tr>
                    <th align="right">Total Giftcard Credit</th>
                    <th><~ $gc_total|currency_format ~></th>
                  </tr>
             </table>
         </div>
      <~/if~>
    <~ include file="float:checkout_giftcards.tpl" ~>

    <~ include file="float:cart_contents.tpl" suppress_update=1 ~>

  <~ if $grand_total ~>
    <br />
    <strong>The total amount to be billed is <~ $grand_total|currency_format ~>.</strong> 
    <br />
  <~/if ~>

    <~ include file="float:store_return_policy.tpl" ~>

      <div class="checkoutSubmitButtonArea">
      <form action="<~ $smarty.server.PHP_SELF ~>" method="post">
        <input type="hidden" name="op_confirm" value="1" />
        <span class="formexFieldSubmit">
          <input type="submit" name="opCartQtyUp" id="opCartCheckout" value="CONFIRM ORDER">
        </span>
      </form>
      </div>
  <~ /if ~>

  <~ include file="float:store_owner_address.tpl" ~>

</div>

<~ include file="cart_footer.tpl" ~>
