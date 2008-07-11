Dear <~ $user.cust_name ~>,

<~ if $order.orders_status eq 1 ~>
Your order has been received into our system. For your reference, here are the details:
<~else~>
The status of your order has been updated. The details are listed  below.
<~/if~>

  Order ID: <~ $order_id ~>
  Order Date: <~ $order_date ~>

  Status: <~ $order_status ~>

<~ if $order.orders_status lt 5 ~>
<~ if $cart_totals.grand_total ~>
    ORDER TOTAL: <~ $cart_totals.grand_total|currency_format ~>
<~/if ~>
<~/if~>
<~ if $order.orders_status eq $smarty.const.CM_ORDER_STATUS_SHIPPED ~>
    Ship Date: <~ $order.ship_date ~>
<~ if $order.ship_method ~>
    Ship Method: <~ $order.ship_method ~>
<~/if~>
<~ if $order.ship_total ~>
    Shipping Total: <~ $order.ship_total ~> <~ $order.currency ~>
<~/if~>
<~/if~>
<~ if $order.tracking_no ~>
    Tracking#: <~ $order.tracking_no ~>
<~/if~>
<~ if $order.orders_status gt 3 and $order.delivery_date ~>
    Delivery Date: <~ $orde.delivery_date ~>
<~/if~>

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


