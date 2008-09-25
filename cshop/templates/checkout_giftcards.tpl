<~ if $cform ~>
  <~ $cform.FORM ~>
    <div class="checkoutFormBox">
        <h2 class="checkoutSectionHeader">Gift Cards</h2>
          <~ if $cform.op_gc_add.TAG ~>
               You may enter one or more Gift Cards for <~ $smarty.const.SITE_TITLE ~>
               here. Gift Card credit will be automatically verified and deducted from
               your subtotal. Enter the card number, the amount you wish to deduct
               from the card, and press ADD CARD.
          <~elseif $giftcards and $cart_grandtotal eq 0 ~>
            The following giftcards will be applied to your order. Your order total is now zero. Please press
            CONTINUE to charge the indicated gift cards.
          <~/if~>
        <div style="margin-left: 20%">
         <table width="80%" border="0" cellspacing="1">
      <~ if $giftcards ~>
                  <tr>
                    <th colspan="2">Current giftcards applied to this order:</th>
                  </tr>
                  <tr>
                    <th>Number</th>
                    <th>Amount</th>
                    <td>&nbsp;</td>
                  </tr>
                <~ foreach from=$giftcards item=gc ~>
                  <tr style="background-color: #<~ cycle values=eee,ccc ~>">
                    <td><~ $gc.gc_no ~></td>
                    <td align="center"><~ $gc.gc_amt|currency_format ~></td>
                    <td style="background-color: #fff"><a style="font-size: 0.8em" href="?op_gc_del&id=<~ $gc.id ~>" onclick="return confirm('Remove this giftcard from your order?')">[remove]</a></td>
                  </tr>
                <~/foreach~>
                  <tr>
                    <th align="right">Total Giftcard Credit</th>
                    <th><~ $gc_total|currency_format ~></th>
                  </tr>
      <~/if~>
      <~ if $cform.op_gc_add.TAG ~>
        <tr>
           <th style="text-align: left">
             <label for="gc_no"><~ $cform.gc_no.LABEL ~></label>
           </th>
           <th style="text-align: left">
             <label for="gc_amt"><~ $cform.gc_amt.LABEL ~></label>
           </th>
           <td>&nbsp;</td>
        </tr>
        <tr>
          <td class="<~ $cform.gc_no.CLASS ~>">
            <~ $cform.gc_no.TAG ~>
          </td>
          <td class="<~ $cform.gc_amt.CLASS ~>">
            $<~ $cform.gc_amt.TAG ~>
          </td>
          <td>
            <~ $cform.op_gc_add.TAG ~>
          </td>
        </tr>
      <~/if~>
      </table>
     </div>
    </div>
  </form>
<~/if~>
