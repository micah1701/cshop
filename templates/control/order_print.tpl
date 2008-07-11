<html>
  <head>
    <title><~ $SITE_DOMAIN_NAME ~>: Invoice : Order #<~ $orderinfo.id ~></title>
    <style type="text/css">
        body,div,td,th {
          font-family: arial,helvetica,sans-serif;
          font-size: 11px;
          color: #000;
          background-color: none;
        }
        div.orderItems table {
            width:100%;
        }
        div.orderItems td {
            border-bottom: 1px solid #ddd;
        }
        div.orderItems th {
            border-bottom: 1px solid #555;
        }
    </style>
  </head>
<body>
<h2 style="text-align: left; border-bottom: 1px solid #555">INVOICE : <~ $SITE_DOMAIN_NAME ~></h3>
			  
<table width="100%">
  <tr>
    <td valign="top">
    <table>
        <tr>
          <td>
            <strong>Order Number:</strong>
          </td>
          <td>
            <~ $orderinfo.id ~>
          </td>
        </tr>
        <tr>
          <td>
            <strong>Submitted on:</strong>
          </td>
          <td>
            <~ $orderinfo.order_create_date|date_format:"%b %e, %Y %I:%M %p" ~>
          </td>
        </tr>
        <tr>
          <td>
            <strong>Order Status:</strong>
          </td>
          <td>
            <~ $order_status ~><br />
          </td>
        </tr>
        <tr>
            <td valign="top">
                <strong>Customer:</strong>
            </td>
            <td><~ $user.cust_name ~><br />
                 <~ if $user.company ~><~ $user.company ~><br /><~/if~>
                 &lt;<~ $user.email ~>&gt;
            </td>
         </tr>
        <tr>
            <td valign="top">
                <strong>Number Items:</strong>
            </td>
            <td>
              <~ $numitems ~>
            </td>
         </tr>
  <~ if $orderinfo.orders_status gt 2 ~>
        <tr>
          <td>
            <strong>Ship Method:</strong>
          </td>
          <td>
            <~ $orderinfo.ship_method ~><br />
          </td>
        </tr>
        <tr>
          <td>
            <strong>Tracking#:</strong>
          </td>
          <td>
            <~ $orderinfo.tracking_no ~><br />
          </td>
        </tr>
        <tr>
          <td>
            <strong>Total Shipping:</strong>
          </td>
          <td>
            <~ $orderinfo.ship_total ~>&nbsp;<~ $currency ~><br />
          </td>
        </tr>
        <tr>
          <td>
            <strong>Ship Date:</strong>
          </td>
          <td>
            <~ $orderinfo.ship_date|date_format ~><br />
          </td>
        </tr>
  <~/if ~>
  <~ if $orderinfo.orders_status == 4 and $orderinfo.delivery_date ~>
        <tr>
          <td>
            <strong>Delivery Date:</strong>
          </td>
          <td>
            <~ $orderinfo.delivery_date|date_format ~>
          </td>
        </tr>
  <~/if ~>

        <tr>
          <td>
            <strong>Invoice Amount:</strong>
          </td>
          <td>
            <~ $cart_totals.grand_total ~>&nbsp;<~ $currency ~> <br />
          </td>
        </tr>
        <tr>
          <td>
            <strong>Billed to date:</strong>
          </td>
          <td>
            <~ $orderinfo.amt_billed_to_date ~>&nbsp;<~ $currency ~>
          </td>
        </tr>
        <tr>
          <td valign="top">
            <strong>Payment Method:</strong>
          </td>
          <td>
            <~ $orderinfo.payment_method ~> <br />
          <~ if $orderinfo.cc_type ~>
            <~ $orderinfo.cc_type ~>: <~ $orderinfo.cc_number ~><br />
            Exp: <~ $orderinfo.cc_expires ~><br />
          <~/if~>
          </td>
        </tr>
      </table>

    </td>
    <td valign="top">

    <table width="100%" cellspacing="0" cellpadding="2" border="0">
        <tr>
          <td valign="top">
            <b>Billing to:</b>
          </td>
          <td>
                <~ include file="cart/address_format.tpl" address=$billing ~>
         </td>
       </tr>

        <tr>
          <td colspan="2" valign="top">
            &nbsp;
          </td>
       </tr>

        <tr>
          <td valign="top">
            <b>Shipping to:</b>
          </td>
          <td>
                <~ include file="cart/address_format.tpl" address=$shipping ~>
         </td>
       </tr>
     </table>

    </td>
  </tr>
  <tr>
    <td colspan="2">
    <div style="text-align: left; border-bottom: 1px solid #555">
        <h3>ORDER ITEMS</h3>
        <div class="orderItems">
        <~ include file="float:cart_show.tpl" suppress_update=1 ~>
        </div>
    </div>
    </td>
  </tr>


  <tr>
    <td colspan="2">
      <div style="text-align: left; border-bottom: 1px solid #555">
        <h3>ORDER HISTORY</h3>

        <div class="orderItems">
            <table cellspacing="0" cellpadding="2" border="0" width="100%">
                <tr>
                  <th align="left">Date</th>
                  <th align="left">Status</th>
                  <th align="left">Comment</th>
                </tr>
              <~ foreach from=$history item=h ~>
                <~ if $h.user_notify ~>
                    <tr>
                      <td><~ $h.stamp|date_format:"%e %b %Y %I:%M %p" ~></td>
                      <td><~ $h.order_status ~></td>
                      <td><~ $h.comments|escape:"html" ~>&nbsp;</td>
                    </tr>
                <~/if~>
              <~/foreach ~>
            </table>
        </div>
      </div>

    </td>
  </tr>





</table>
    <~ include file="float:store_return_policy.tpl" ~>
</body>
</html>


