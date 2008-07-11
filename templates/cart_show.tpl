<div id="cmCartListing">
  <table border="0" cellspacing="0" cellpadding="3" class="cart">
      <tr class="cartHeader">
          <th colspan="2" align="left">Product</th>
          <th>&nbsp;</th>
          <th>Quantity</th>
          <th align="right">Price</th>
          <th align="right">Total</th>
      </tr>
    <~ foreach from=$cart item=item ~>
      <tr class="<~ cycle values=cartRowOdd,cartRowEven ~>">
          <td valign="top">
              <strong><~ $item.product_descrip ~></strong>
          </td>
          <td valign="top">
            <~ include file="float:cart_product_attribs.tpl" ~>
          </td>
          <td valign="top">
            <!-- <~ $item.product_sku ~> -->
          </td>
          <td align="center" valign="top">
            <~ if $suppress_update ~>
              <~ $item.qty ~>
            <~ else ~>
              <input type="text" size="3" value="<~ $item.qty ~>" name="qty<~ $item.id ~>" class="cartQty" />
            <~ /if ~>
          </td>
          <td align="right" valign="top">
             <~ if $sale_zero_price_all ~><span class="priceStrikeout"><~/if~>
              <~ $item.price|currency_format ~>
             <~ if $sale_zero_price_all ~></span><~/if~>
          </td>
          <td align="right" valign="top">
             <~ if $sale_zero_price_all ~>
              $0.00
             <~ else ~>
              <~ $item.line_price|currency_format ~>
             <~ /if ~>
          </td>
      </tr>
    <~/foreach ~>
  <~ if !$suppress_update ~>
    <tr class="cartUpdateButton">
      <td align="right" colspan="3">&nbsp;</td>
      <td align="center">
        <input type="submit" name="opCartQtyUp" id="opCartQtyUp" value="UPDATE"  onclick="this.value='Please wait...'; this.disabled=true; this.form.submit();" />
      </td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
  <~/if ~>
  <~ include file="float:cart_totals.tpl" ~>
  </table>
  <input type="hidden" name="op_update" value="1" />

<~ if !$suppress_update ~>
  <div id="opCartEmpty">
    <a href="<~ $smarty.server.PHP_SELF ~>?empty" onclick="return confirm('This will remove all the items from your cart. Are you sure?')"><span>empty cart</span></a>
  </div>
  <div id="opCartEmptyNote">* note: set Quantity to 0 to remove an item from your cart.</div>

  <script type="text/javascript">
  <!--
      var updateEnabled = false;
      function enableQtyUpdate() {
          if (document.getElementById('opCartQtyUp') && !updateEnabled) {
              document.getElementById('opCartQtyUp').style.visibility = 'visible';
              updateEnabled = true;
          }
      }

      if (document.getElementById('opCartQtyUp')) {
          document.getElementById('opCartQtyUp').style.visibility = 'hidden';
      }

      inpColl = document.getElementsByTagName('INPUT');
      for (i=0; i<inpColl.length; i++) {
          if (inpColl[i].className == 'cartQty') {
              inpColl[i].onkeypress = enableQtyUpdate;
          }
      }

  -->
  </script>
<~/if~>
</div>
