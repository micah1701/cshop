<div id="cmCartListing">
  <table border="0" cellspacing="0" cellpadding="3" class="cart">
      <tr class="cartHeader">
          <th colspan="2" class="alignleft">Product</th>
          <th>&nbsp;</th>
          <th class="aligncenter">Quantity</th>
          <th class="alignright">Price</th>
          <th class="alignright">Total</th>
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
          <td class="aligncenter" valign="top">
            <~ if $suppress_update ~>
              <~ $item.qty ~>
            <~ else ~>
              <input type="text" size="3" value="<~ $item.qty ~>" name="qty<~ $item.id ~>" class="cartQty" />
            <~ /if ~>
          </td>
          <td class="alignright" valign="top">
             <~ if $sale_zero_price_all ~><span class="priceStrikeout"><~/if~>
              <~ $item.price|currency_format ~>
             <~ if $sale_zero_price_all ~></span><~/if~>
          </td>
          <td class="alignright" valign="top">
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
      <td class="alignright" colspan="3">&nbsp;</td>
      <td class="aligncenter">
        <input type="submit" name="opCartQtyUp" id="opCartQtyUp" value="UPDATE">
      </td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
  <~/if ~>
  <~ include file="float:cart_totals.tpl" ~>
  </table>
  <input type="hidden" name="op_update" value="1" />

<~ if !$suppress_update ~> <input id="opCartEmpty" type="submit" name="op_empty" value="empty cart"> <~/if~>

</div>
