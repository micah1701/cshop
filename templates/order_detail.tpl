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

    
    <h4 id="chkConfirmCustInfo">
        <span class="header">Customer:</span>
                  <span class="cust_name"><~ $user.cust_name ~> &mdash; <~ $user.company ~></span> 
                  <span class="user_email">&lt;<~ $user_email ~>&gt;</span>
                  <~ if $user.telephone ~><span class="phone">Phone:&nbsp;<~ $user.telephone ~></span><~/if~></h4>

    <div id="chkConfirmOrderInfo">
      <div id="chkConfirmOrderHeader">
        <div class="orderHeader"><span>Order ID:</span> <~ $orderinfo.id ~></div>
        <div class="orderHeader"><span>Order Date:</span> <~ $orderinfo.order_create_date|date_format:"%A, %B %e, %Y %I:%M %p" ~>
        <~ if $orderinfo.cc_type ~></div>
          <div class="orderHeader"><span>Payment Method:</span> <~ $orderinfo.cc_type ~> <~ $orderinfo.cc_number ~></div>
        <~/if~>
      </div>
        <table width="90%">
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
                    <~ include file="cart/address_format.tpl" address=$shipping ~>
                 </td>
            </tr>
        </table>
    </div>


  <~ include file="float:cart_show.tpl" suppress_update=1 ~>

</div>
<div style="height: 8px"></div>

<a href="<~ $smarty.const.CSHOP_PRODUCT_DETAIL_PAGE ~>" class="cartlink" id="cmContinueShopping">RETURN TO STORE</a>

<~ include file="cart_footer.tpl" SUPPRESS_COLLAGE=true ~>
