<~ include file="cart_header.tpl" heading="Order Detail" ~>
<div id="cartWrapper" >
			  
    <~ if $new_order ~>
      <~ include file="float:cartsteps.tpl" step=5 ~>
      <div class="userIndicator">
        <strong>THANK YOU FOR YOUR ORDER</strong><br>
        Your order has been entered into our system. A confirmation email is on its way, and you
        may also 
        print or save this page for your records.
      </div>
    <~else~>
      <div class="userIndicator">
        Details on your order are as follows. Please print or save this page for your records.
      </div>
    <~/if~>

    <div id="orderPrintLink">
      <a href="#" onclick="window.print()">PRINT THIS PAGE</a>
    </div>

    
    <div id="chkConfirmCustInfo">
        <h2 class="checkoutSectionHeader">Customer</h2>
        <div class="custName"><~ $user.cust_name ~> <~ if $user.company ~>&ndash;&nbsp;<~ $user.company ~><~/if~></div> 
        <div class="custEmail">&lt;<~ $user_email ~>&gt; </div>
        <~ if $user.telephone ~><div class="custPhone">Phone:&nbsp;<~ $user.telephone ~></div><~/if~>
    </div>

    <div id="chkConfirmOrderInfo">
      <div id="chkConfirmOrderHeader">
        <h2 class="checkoutSectionHeader">Order Information</h2>
        <div class="orderHeader"><span>Order ID:</span> <~ $orderinfo.order_token ~></div>
        <div class="orderHeader"><span>Order Date:</span> <~ $orderinfo.order_create_date|date_format:$smarty.const.CSHOP_DATE_FMT_DISPLAY ~></div>
            <div class="orderHeader"><span>Order Status:</span> <~ $order_status ~></div>
        <~ if $orderinfo.cc_type ~>
          <div class="orderHeader"><span>Payment Method:</span> <~ $orderinfo.cc_type ~> <~ $orderinfo.cc_number ~></div>
        <~/if~>
      </div>
        <table id="checkoutConfirmAddresses">
            <tr>
                <td valign="top" class="checkoutAddress">
                    <h4>Billing Address</h4>
                    <~ if !$billing ~>
                      N/A
                    <~else ~>
                      <~ include file="cart/address_format.tpl" address=$billing ~>
                    <~/if~>
                 </td>
                <td valign="top" class="checkoutAddress">
                    <h4>Shipping Address</h4>
                    <~ if $no_shipping_required ~>
                        <strong>No shipping required.</strong>
                    <~ else ~>
                        <~ include file="cart/address_format.tpl" address=$shipping ~>
                    <~ /if ~>
                 </td>
            </tr>
        </table>
    </div>


    <div class="checkoutFormBox">
        <h2 class="checkoutSectionHeader">Order Items</h2>
        <~ include file="float:cart_contents.tpl" suppress_update=1 ~>
    </div>


    <~ if $history ~>
      <div id="orderHistory">
        <h2 class="checkoutSectionHeader">Order History</h2>

        <div class="orderItems">
            <table cellspacing="0" width="100%">
                <tr>
                  <th align="left">Date</th>
                  <th align="left">Status</th>
                  <th align="left">Comment</th>
                </tr>
              <~ foreach from=$history item=h ~>
                <~ if $h.user_notify ~>
                    <tr>
                      <td><~ $h.stamp|date_format:$smarty.const.CSHOP_DATE_FMT_DISPLAY ~></td>
                      <td><~ $h.order_status ~></td>
                      <td><~ $h.comments|escape:"html" ~>&nbsp;</td>
                    </tr>
                <~/if~>
              <~/foreach ~>
            </table>
        </div>
      </div>
    <~/if~>

</div>

<div id="cmContinueShopping">
<a href="<~ $smarty.const.CSHOP_PRODUCT_DETAIL_PAGE ~>" class="cartlink">RETURN TO STORE</a>
</div>

<~ include file="cart_footer.tpl" SUPPRESS_COLLAGE=true ~>
