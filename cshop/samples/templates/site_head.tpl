<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <title><~ $pagetitle ~> <~ $SITE_DOMAIN_NAME ~></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body id="<~ $page_id ~>">
<strong><~ $SITE_DOMAIN_NAME ~></strong>
<br />
<span style="color: #666; font-style: italic">skeleton template <~ $smarty.template ~></span>
<div style="text-align: right">
    <strong><a href="/cart/cart.php">Your Shopping Cart</a></strong>
    <~ if $cart_itemcount ~>
      <br />
      <~ $cart_itemcount ~> item<~ if $cart_itemcount gt 1 ~>s<~/if~>
      [&nbsp;Subtotal: $<~ $cart_subtotal|string_format:"%.2f" ~>&nbsp;]
    <~/if~>
</div>
<hr />
