<~ include file="cart_header.tpl" heading="View Cart" ~>
			  
<~if $msg ~>
    <div class="userIndicator">
        <~ $msg|escape ~>
    </div>
<~/if ~>

<div id="cartWrapper">
  <~ if !$cart ~>
      <div class="userCartControl" style="text-align: center">
        Your cart is empty.
        <br />
        <br />
        <a href="<~ $product_detail_page ~>" class="cartlink" id="cmContinueShopping">CONTINUE SHOPPING</a>
      </div>
      
  <~ else ~>
      <div class="userCartControl" style="text-align: right">
        <a href="<~ $product_detail_page ~>" class="cartlink" id="cmContinueShopping">CONTINUE SHOPPING</a>
        <br />
        <~ if $currency_opts ~>
        prices shown in <strong><~ $currency_opts[$current_currency_display]|escape ~></strong>
        <br />
        show prices in 
          <~ foreach from=$currency_opts item=currname key=code ~>
                       <a href="<~ $smarty.server.PHP_SELF ~>?curr=<~ $code ~>"><~ $code ~></a> 
          <~/foreach~>
        <~/if~>
      </div>
      <form name="cartform" action="<~ $smarty.server.PHP_SELF ~>" method='post'>
        <~ include file="float:cart_show.tpl" ~>

        <~ if !$SUPPRESS_CHECKOUT ~>
          <div class="userCartControl" id="cartControlButtons">

              <input type="submit" name="op_checkout" id="opCartCheckout" value="CHECKOUT NOW"  onclick="this.value='Please wait...';" class="fexter">
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
