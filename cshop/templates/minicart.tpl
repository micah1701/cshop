<~ if $minicart ~>
  <div id="miniCart">
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
          Subtotal&nbsp;&nbsp;<strong><~ $minicart.subtotal|currency_format  ~></strong>
        </div>
      <~ if $minicart.discount_amt ~>
        <div style="text-align: right">DISCOUNT&nbsp;<strong>(<~ $minicart.discount_amt|currency_format ~>)</strong></div>
      <~/if ~>
        <~ if $minicart.ship_total ne 0 ~>
        <div style="text-align: right">
        <~ $minicart.ship_method ~>&nbsp;&nbsp;<strong><~ $minicart.ship_total|currency_format ~></strong>
        </div>
        <~/if~>
        <~ if $minicart.tax_total ne 0 ~>
        <div style="text-align: right">
          Tax: <~ $minicart.tax_method ~>&nbsp;&nbsp;<strong><~ $minicart.tax_total|currency_format  ~></strong> 
        </div>
        <~/if~>
        <~ if $minicart.giftcard_total ~>
          <div style="text-align: right">
            Gift Cards: &nbsp;&nbsp;<strong>(<~ $minicart.giftcard_total|currency_format  ~>)</strong> 
          </div>
        <~/if~>
    </div>
<~/if~>
