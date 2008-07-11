<div id="miniCart" class="checkoutFormBox">
    <strong><a href="cart.php">Your Cart</a>:</strong><br />
    <table>
    <~ section name=mc loop=$cartitems ~>
      <tr>
        <td valign="top">
          <~ $cartitems[mc].qty ~>&nbsp;x 
        </td>
        <td>
          <~ $cartitems[mc].product_descrip ~>
        </td>
      </tr>
    <~/section ~>
    </table>
    <div style="text-align: right">
      Subtotal&nbsp;&nbsp;<strong><~ $subtotal|currency_format  ~></strong>
    </div>
  <~ if $mini_discount_amt ~>
    <div style="text-align: right">DISCOUNT&nbsp;<strong>(<~ $mini_discount_amt|currency_format ~>)</strong></div>
  <~/if ~>
    <~ if $mini_shipping ~>
    <div style="text-align: right">
    <~ $mini_shipping.ship_method ~>&nbsp;&nbsp;<strong><~ $mini_shipping.ship_total|currency_format ~></strong>
    </div>
    <~/if~>
    <~ if $mini_tax ~>
    <div style="text-align: right">
      Tax: <~ $mini_tax.tax_method ~>&nbsp;&nbsp;<strong><~ $mini_tax.tax_total|currency_format  ~></strong> 
    </div>
    <~/if~>
    <~ if $mini_gc ~>
      <div style="text-align: right">
        Gift Cards: &nbsp;&nbsp;<strong>(<~ $mini_gc|currency_format  ~>)</strong> 
      </div>
    <~/if~>
    <~ if $cart_grandtotal ~>
      <div style="text-align: right">
        TOTAL: &nbsp;&nbsp;<strong><~ $cart_grandtotal|currency_format  ~></strong> 
      </div>
    <~/if~>
</div>
