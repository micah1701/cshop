<~ include file="cart_header.tpl" heading="View Cart" ~>
			  
<~if $msg ~>
    <div class="userIndicator">
        <~ $msg|escape ~>
    </div>
<~/if ~>

<div id="cartWrapper">
  <~ if !$cart ~>
    <div class="userCartControl">
        Your cart is empty.
        <a href="<~ $product_detail_page ~>" class="cartlink" id="cmContinueShopping">continue shopping</a>
    </div>
      
  <~ else ~>
      <div class="userCartControl">
          <a href="<~ $product_detail_page ~>" class="cartlink" id="cmContinueShopping">continue shopping</a>

          <~ if $currency_opts ~>
          <div id="cartCurrencyConvert">
              prices shown in <strong><~ $currency_opts[$current_currency_display]|escape ~></strong>
              show prices in 
              <~ foreach from=$currency_opts item=currname key=code ~>
              <a href="<~ $smarty.server.PHP_SELF ~>?curr=<~ $code ~>"><~ $code ~></a> 
              <~/foreach~>
          </div>
          <~/if~>

      </div>
      <form id="cartform" name="cartform" action="<~ $smarty.server.PHP_SELF ~>" method='post'>
        <~ include file="float:cart_contents.tpl" ~>

        <~ if !$SUPPRESS_CHECKOUT ~>
          <div class="userCartControl" id="cartControlButtons">
              <input type="submit" name="op_checkout" id="opCartCheckout" value="CHECKOUT NOW" class="fexter">
          </div>

        <~/if~>

        <~ if $do_check_coupons ~>
          <~ include file="float:cart_discount_entry.tpl" ~>
        <~/if~>

      </form>
    <~ include file="float:store_return_policy.tpl" ~>

    <~ include file="float:cart_related_products.tpl" ~>

  <~ include file="float:store_owner_address.tpl" ~>
<~ /if ~>

</div>

<~ include file="cart_footer.tpl" ~>
