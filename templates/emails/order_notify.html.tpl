<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title>Order Confirmation</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style rel="stylesheet" type="text/css">

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
        h3 {
            font-size: 14px;
            font-weight: bold;
        }
        .alignright {
            text-align: right;
        }
        .alignleft {
            text-align: left;
        }
        .aligncenter {
            text-align: center;
        }
</style>
</head>

<body>

    <div>
        <strong>Dear <~ $user.cust_name ~>,</strong>

        <~ if $order_status == 'NEW' ~>
        <p>Your order has been received into our system. For your reference, here are the details:</p>
        <~ else ~>
        <p>Your order has been updated. Please see below for current order details.</p>
        <~/if~>
    </div>



<table width="100%">
  <tr>
    <td valign="top">
    <table>
        <tr>
          <td>
            <strong>Order Number:</strong>
          </td>
          <td>
            <~ $orderinfo.order_token ~>
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
  <~ if $orderinfo.orders_status gte 3 and $orderinfo.ship_date ~>
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
            <~ $orderinfo.ship_total|currency_format ~>&nbsp;<~ $currency ~><br />
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
            <~ $cart_totals.grand_total|currency_format ~>&nbsp;<~ $currency ~> <br />
          </td>
        </tr>
        <tr>
          <td>
            <strong>Billed to date:</strong>
          </td>
          <td>
            <~ $orderinfo.amt_billed_to_date|currency_format ~>&nbsp;<~ $currency ~>
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
                <~ include file="float:address_format.tpl" address=$billing ~>
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
            <~ if !$shipping ~>
                <strong>No shipping required.</strong>
            <~ else ~>
                <~ include file="float:address_format.tpl" address=$shipping ~>
            <~ /if ~>
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
        <~ include file="float:cart_contents.tpl" suppress_update=1 ~>
        </div>
    </div>
    </td>
  </tr>

    <~ if $has_digital_goods and $download_list  ~>
      <tr>
        <td colspan="2">
            <div style="text-align: left; border-bottom: 1px solid #555">
                <~ include file="float:order_digital_goods.tpl" ~>
            </div>
        </td>
       </tr>
    <~/if~>


  <~ if $history ~>
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
   <~/if~>





</table>




<br>

<hr size="1">
<div align="center" style="font-size: 105%">
    <a href="<~ $order_view_link ~>">To view complete details and status on your order click here.</a>
</div>


<hr size="1">
    <~ include file="float:store_return_policy.tpl" ~>
</body>
</html>

