<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title>Order Confirmation</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style rel="stylesheet" type="text/css">
body {
	font-family: Arial, Verdana, Helvetica, sans-serif;
	color: #000000;
	background: #ffffff;
}
.date {
	font-weight: bold
}

a {
	color: #339933;
	font-weight: bold;
	text-decoration: underline
}

a:hover {
	color: #336633;
	font-weight: bold;
	text-decoration: none
}

#oitemList {
    text-align: left; margin: 10px; border: 1px solid #666; padding: 4px;
}
#oitemList table {
    width: 100%;
}
#oitemList table td.Rt {
    text-align: right;
}
#oitemList table td.Ct {
    text-align: center;
}
#oitemList table td.Lt {
    text-align: left;
}
</style>
</head>

<body>

<strong>Dear <~ $user.cust_name ~>,</strong>

<~ if $order_status == 'NEW' ~>
    <p>Your order has been received into our system. For your reference, here are the details:</p>
<~else~>
    <p>Your order has been received into our system. For your reference, here are the details:</p>
<~/if~>
</p>
<table cellpadding="2">
  <tr>
    <td>
      Order ID:
    </td>
    <td bgcolor="#e0e0e0">
      <~ $order_id ~>
    </td>
  </tr>

  <tr>
    <td>
      Order Date:
    </td>
    <td bgcolor="#e0e0e0">
      <~ $order_date ~>
    </td>
  </tr>

  <tr>
    <td>
      Status:
    </td>
    <td bgcolor="#e0e0e0">
      <~ $order_status ~>
    </td>
  </tr>

<~ if $order.orders_status lt 5 ~>
  <tr>
    <td>
      Total:
    </td>
    <td bgcolor="#e0e0e0">
      <~ $order.amt_billed_to_date ~> <~ $order.currency ~>
    </td>
  </tr>
<~/if~>
<~ if $orderinfo.cc_type ~> 
    <tr>
      <td>
        Payment Method:
      </td>
      <td>
        <~ $orderinfo.cc_type ~> <~ $orderinfo.cc_number ~>
      </td>
    </tr>
<~/if~>
<~ if $order.orders_status eq $smarty.const.CM_ORDER_STATUS_SHIPPED ~>
    <tr>
        <td>
            Ship Date:
        </td>
        <td bgcolor="#e0e0e0">
            <~ $order.ship_date ~>
        </td>
    </tr>
    <~ if $order.ship_method ~>
      <tr>
        <td>
          Ship Method:
        </td>
        <td bgcolor="#e0e0e0">
          <~ $order.ship_method ~>
        </td>
      </tr>
    <~/if~>
    <~ if $order.ship_total ~>
      <tr>
        <td>
          Shipping Total:
        </td>
        <td bgcolor="#e0e0e0">
          <~ $order.ship_total ~> <~ $order.currency ~>
        </td>
      </tr>
    <~/if~>
<~/if~>
<~ if $order.tracking_no ~>
  <tr>
    <td>
      Tracking#:
    </td>
    <td bgcolor="#e0e0e0">
      <~ $order.tracking_no ~>
    </td>
  </tr>
<~/if~>
<~ if $order.orders_status gt 3 and $order.delivery_date ~>
  <tr>
    <td>
      Delivery Date:
    </td>
    <td bgcolor="#e0e0e0">
      <~ $order.delivery_date ~>
    </td>
  </tr>
<~/if~>
</table>


<~ if $comments ~>
<br>
<strong>Comments:</strong> <br>
<hr size="1">
<pre><~ $comments ~></pre>
<hr size="1">
<~/if~>


<hr size="1">
<h3>ORDER DETAILS</h3>
<br>
<table width="100%" cellpadding="2">
  <tr>
    <td>
      <strong>SHIPPING TO:</strong><br>
      <~ include file="float:address_format.tpl" address=$shipping ~>
    </td>
    <td>
      <strong>BILLING TO:</strong><br>
      <~ include file="float:address_format.tpl" address=$billing ~>
    </td>
  </tr>
  <tr>
    <td colspan="2">
    <div id="oitemList">
    <h3>ORDER ITEMS</h3>

    <table cellspacing="0" cellpadding="0" class="GenTable" id="cartContents">
        <tr class="Th">
            <th align="left" colspan="3">Product</th>
            <th align="center">Quantity</th>
            <th align="right">Price</th>
            <th align="right">Total</th>
        </tr>
    <~ foreach from=$cart item=item ~>
      <tr class="<~ cycle values=cartRowOdd,cartRowEven ~>">
          <td align="left" valign="top" colspan="2"><strong><~ $item.product_descrip ~></strong>
              <~ if $item.out_of_stock ~><br /><em><~ $item.stock_msg ~></em><~ /if ~>
          </td>
          <td valign="top" class="Lt"><~ include file="float:cart_product_attribs.tpl" ~></td>
          <td align="center" valign="top">
              <~ $item.qty ~>
          </td>
          <td align="right" valign="top">
              <~ $item.price|currency_format ~>
          </td>
          <td align="right" valign="top">
              <~ $item.line_price|currency_format ~>
          </td>
      </tr>
    <~/foreach ~>
    <~ include file="float:cart_totals.tpl" ~>
    </div>
    </td>
  </tr>
</table>
<hr size="1">

<br>
<div align="center">
Please note, you can 
    <a href="<~ $order_view_link ~>">view complete details and status on your order at any time on our site.</a>
</div>


<hr size="1">
    <~ include file="float:store_return_policy.tpl" ~>
<hr size="1">
</body>
</html>

