Dear <~ $user.cust_name ~>,

<~ if $orderinfo.orders_status eq 1 ~>
Your order has been received into our system. For your reference, here are the details:
<~else~>
The status of your order has been updated. The details are listed  below.
<~/if~>

Order ID: <~ $orderinfo.order_token ~>
Order Date: <~ $orderinfo.order_create_date|date_format:"%b %e, %Y %I:%M %p" ~>

Status: <~ $order_status ~>

Customer: <~ $user.cust_name ~> <~ if $user.company ~>[<~ $user.company ~>]<~/if~> [<~ $user.email ~>]

Number Items: <~ $numitems ~>

<~ if $orderinfo.orders_status lt 5 ~>
<~ if $cart_totals.grand_total ~>
ORDER TOTAL: <~ $cart_totals.grand_total|currency_format ~> <~ $currency ~>
<~/if ~>
<~/if~>

<~ if $orderinfo.orders_status gt 2 ~>
SHIPPING INFORMATION:
  Ship Date: <~ $orderinfo.ship_date ~>
<~ if $orderinfo.ship_method ~>
  Ship Method: <~ $orderinfo.ship_method ~>
<~/if~>
<~ if $orderinfo.ship_total ~>
  Shipping Total: <~ $orderinfo.ship_total|currency_format ~> <~ $currency ~>
<~/if~>
<~/if~>
<~ if $orderinfo.tracking_no ~>
  Tracking#: <~ $orderinfo.tracking_no ~>
<~/if~>
<~ if $orderinfo.orders_status gt 3 and $orderinfo.delivery_date ~>
  Delivery Date: <~ $orde.delivery_date ~>
<~/if~>


PAYMENT METHOD: <~ $orderinfo.payment_method ~> 
<~ if $orderinfo.cc_type ~>
  <~ $orderinfo.cc_type ~>: <~ $orderinfo.cc_number ~>
  Exp: <~ $orderinfo.cc_expires ~>
<~/if~>

BILLING TO:
<~ include file="cart/address_format.txt.tpl" address=$billing ~>

SHIPPING TO:
<~ include file="cart/address_format.txt.tpl" address=$shipping ~>


<~ if $comments ~>
Comments: 
===============================================================================
<~ $comments ~>
===============================================================================
<~/if~>

ORDER ITEMS:

<~ foreach from=$cart item=item ~>
  -----------------------------------------------------------------------------
  <~ $item.product_descrip ~>
<~ if $item.normalized_attribs ~>    <~ foreach from=$item.normalized_attribs key=k item=v ~> <~ $k ~>: <~ $v ~> <~/foreach ~>
<~/if~>
<~ if $item.out_of_stock ~>    <~ $item.stock_msg ~>
<~ /if ~>
<~ if $item.item_options ~>    <~ foreach from=$item.item_options key=k item=option ~><~ if $option.descr ~> <~ $option.descr|escape ~>: <~ $option.value ~> <~/if~><~/foreach~>
<~/if ~>
  QTY: <~ $item.qty ~>
  ITEM PRICE: <~ $item.price|currency_format ~>
  TOTAL: <~ $item.line_price|currency_format ~>
  -----------------------------------------------------------------------------

<~/foreach ~>

 SUBTOTAL <~ $cart_totals.subtotal|currency_format ~>

<~ if $discount_amt ~>
 DISCOUNT [<~ $discount_descrip ~>] : (<~ $discount_amt|currency_format ~>)
<~/if ~>

<~ foreach from=$cart_totals.other item=tot ~>
          <~ $tot.method ~> <~ $tot.amt|currency_format ~>
<~/foreach ~>
<~ if $cart_totals.shipping.method ~>
 SHIPPING : <~ $cart_totals.shipping.method ~> <~ $cart_totals.shipping.amt|currency_format ~>
<~/if ~>
<~ if $cart_totals.tax.method ~>
 TAX : <~ $cart_totals.tax.method ~> <~ $cart_totals.tax.amt|currency_format ~>
<~/if ~>
<~ if $cart_totals.giftcards.total gt 0 ~>
 GIFT CARDS:
<~ foreach from=$cart_totals.giftcards.list item=gc ~>
    <~ $gc.gc_no ~> : <~ $gc.gc_amt|currency_format ~>
<~/foreach ~> (<~ $cart_totals.giftcards.total|currency_format ~>)
<~/if ~>
<~ if $cart_totals.grand_total ~>
 TOTAL: <~ $cart_totals.grand_total|currency_format ~>
<~/if ~>

You may view complete details and status on your order at any time here:
<~ $order_view_link ~>


