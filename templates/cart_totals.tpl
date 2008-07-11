    <tr class="carttotal">
      <td align="right" colspan="5">SUBTOTAL</td>
      <td align="right"><~ $cart_totals.subtotal|currency_format ~></td>
    </tr>

    <~ if $discount_amt ~>
      <tr class="carttotal">
        <td align="right" colspan="5">DISCOUNT [<~ $discount_descrip ~>] :</td>
        <td align="right">(<~ $discount_amt|currency_format ~>)</td>
      </tr>
    <~/if ~>

    <~ foreach from=$cart_totals.other item=tot ~>
        <tr class="carttotal">
          <td align="right" colspan="5"><~ $tot.method ~></td>
          <td align="right"><~ $tot.amt|currency_format ~></td>
        </tr>
    <~/foreach ~>
  <~ if $cart_totals.shipping.method ~>
    <tr class="carttotal">
      <td align="right" colspan="5">SHIPPING : <~ $cart_totals.shipping.method ~></td>
      <td align="right"><~ $cart_totals.shipping.amt|currency_format ~></td>
    </tr>
  <~/if ~>
  <~ if $cart_totals.tax.method ~>
    <tr class="carttotal">
      <td align="right" colspan="5">TAX : <~ $cart_totals.tax.method ~></td>
      <td align="right"><~ $cart_totals.tax.amt|currency_format ~></td>
    </tr>
  <~/if ~>
  <~ if $cart_totals.giftcards.total gt 0 ~>
    <tr class="carttotal">
      <td align="right" colspan="5">Gift Cards:<br />
                                <~ foreach from=$cart_totals.giftcards.list item=gc ~>
                                      <~ $gc.gc_no ~> : <~ $gc.gc_amt|currency_format ~>
                                      <br />
                                <~/foreach ~>
      </td>
      <td align="right" valign="top">(<~ $cart_totals.giftcards.total|currency_format ~>)</td>
    </tr>
  <~/if ~>
  <~ if $cart_totals.grand_total ~>
    <tr class="carttotal grandTotal">
      <td align="right" colspan="5">TOTAL:</td>
      <td align="right"><~ $cart_totals.grand_total|currency_format ~></td>
    </tr>
  <~/if ~>
