<div class="headlineW">
    <h2 class="productName headline"><~ $pagetitle|escape ~></h2>
  <a class="printInvoice" href="<~ $smarty.server.PHP_SELF ~>?op_print=<~ $orderinfo.order_token ~>" target="printWin">Print Invoice</a>
</div>


<table border="0" cellspacing="0" cellpadding="0" width="650">
  <tr>
    <td align="center">
<table border="0" cellspacing="0" cellpadding="0" width="650">
  <tr>
    <td valign="top">
    <table  width="320" class="orderDetail"  cellpadding="0" cellspacing="0" >
        <tr>
          <td>
            <strong>Order ID:</strong>
          </td>
          <td>
            <~ $orderinfo.id ~>
          </td>
        </tr>
        <tr>
          <td>
            <strong>Order Number:</strong>
          </td>
          <td>
            <~ $orderinfo.order_token ~>
          </td>
        </tr>
        <tr>
          <td>
            <strong>Submitted on:</strong>
          </td>
          <td>
              <~ $orderinfo.order_create_date|date_format:$smarty.const.CSHOP_DATE_FMT_DISPLAY ~>
          </td>
        </tr>
        <tr>
          <td>
            <strong>Order Status:</strong>
          </td>
          <td>
            <~ $order_status ~><br />
          </td>
        </tr>
        <tr>
            <td valign="top">
                <strong>Customer:</strong>
            </td>
            <td><~ if $user.perms ~><a href="store.users.php?uid=<~ $user.id ~>"><~/if~>
                    <~ if $user.cust_name ~>
                        <~ $user.cust_name ~>
                    <~ else ~>
                        <~ $user.first_name ~> <~ $user.last_name ~>
                    <~/if~></a><br />
                 <~ if $user.company ~><~ $user.company ~><br /><~/if~>
                 &lt;<~ $user_email ~>&gt;
            </td>
         </tr>
        <tr>
            <td valign="top">
                <strong>Number Items:</strong>
            </td>
            <td>
              <~ $numitems ~>
            </td>
         </tr>
  <~ if $orderinfo.orders_status gte 3 and $orderinfo.ship_date ~>
        <tr>
          <td>
            <strong>Ship Method:</strong>
          </td>
          <td>
            <~ $orderinfo.ship_method ~><br />
          </td>
        </tr>
        <tr>
          <td>
            <strong>Tracking#:</strong>
          </td>
          <td>
            <~ $orderinfo.tracking_no ~><br />
          </td>
        </tr>
        <tr>
          <td>
            <strong>Total Shipping:</strong>
          </td>
          <td>
            <~ $orderinfo.ship_total ~>&nbsp;<~ $currency ~><br />
          </td>
        </tr>
        <tr>
          <td>
            <strong>Ship Date:</strong>
          </td>
          <td>
            <~ $orderinfo.ship_date|date_format:$smarty.const.CSHOP_DATE_FMT_DISPLAY  ~><br />
          </td>
        </tr>
  <~ else ~>
        <tr>
          <td>
            <strong>Requested Ship Method:</strong>
          </td>
          <td>
            <~ $orderinfo.ship_method ~><br />
          </td>
        </tr>
  <~/if ~>
  <~ if $orderinfo.orders_status == 4 and $orderinfo.delivery_date ~>
        <tr>
          <td>
            <strong>Delivery Date:</strong>
          </td>
          <td>
            <~ $orderinfo.delivery_date|date_format:$smarty.const.CSHOP_DATE_FMT_DISPLAY  ~>
          </td>
        </tr>
  <~/if ~>
        <tr>
          <td>
            <strong>Quote Amount:</strong>
          </td>
          <td>
            <~ $cart_totals.grand_total ~>&nbsp;<~ $currency ~> <br />
          </td>
        </tr>
      <~ if $orderinfo.discount_descrip ~>
        <tr>
          <td valign="top">
            <strong>Discount:</strong>
          </td>
          <td>
            <~ if $orderinfo.discount_amt ~>
              <~ $orderinfo.discount_amt ~>&nbsp;<~ $currency ~> <br />
            <~/if~>
            [<~ $orderinfo.discount_descrip ~>&nbsp;<~ $orderinfo.coupon_code ~>]
          </td>
        </tr>
      <~/if~>
        <tr>
          <td>
            <strong>Billed to date:</strong>
          </td>
          <td>
            <~ $orderinfo.amt_billed_to_date ~>&nbsp;<~ $currency ~>
          </td>
        </tr>
        <tr>
          <td valign="top">
            <strong>Payment Method:</strong>
          </td>
          <td>
              <~ $orderinfo.payment_method ~> <br />
              <~ if $orderinfo.cc_type ~>
                  <~ $orderinfo.cc_type ~>: <~ $orderinfo.cc_number ~><br />
                  Exp: <~ $orderinfo.cc_expires ~><br />
              <~/if~>
              <~ if $orderinfo.cc_owner ~>
                  Ref: <strong><~ $orderinfo.cc_owner ~></strong><br />
              <~/if~>
          </td>
        </tr>
        <tr>
          <td valign="top">
            <b>Billing to:</b>
          </td>
          <td>
                <~ include file="cart/address_format.tpl" address=$billing ~>
         </td>
       </tr>

        <tr>
          <td valign="top">
            <b>Shipping to:</b>
          </td>
          <td>
                <~ include file="cart/address_format.tpl" address=$shipping ~>
         </td>
       </tr>
     </table>

    </td>
    <td valign="top">
      <div class="history">
        <h3 class="order">ORDER STATUS</h3>

    <table cellspacing="0" cellpadding="2" border="0" width="320">
        <tr>
          <th>Date</th>
          <th>Notify</th>
          <th>Status</th>
        </tr>
      <~ foreach from=$history item=h ~>
        <~ cycle assign=bg values=#e0e0e0,#e8e8e8 ~>
        <tr style="background: <~ $bg ~>">
          <td><~ $h.stamp|date_format:$smarty.const.CSHOP_DATE_FMT_DISPLAY ~></td>
          <td align="center"><~ if $h.user_notify ~><img src="/control/cshop/img/tick.gif" /><~else~><img src="/control/cshop/img/cross.gif" /><~/if~></td>
          <td><~ $h.order_status ~></td>
        </tr>
        <~ if $h.comments ~>
          <tr style="background: <~ $bg ~>">
            <td colspan="3"><strong>Comments:</strong> <~ $h.comments|escape:"html" ~></td>
          </tr>
        <~/if~>
      <~/foreach ~>
    </table>


        <~ $upform.FORM ~>
        <table border="0"  width="320">
          <tr><td>Status:</td><td><~ $upform.orders_status.TAG ~></td></tr>
          <tr><td>Comments:</td><td><~ $upform.comments.TAG ~></td></tr>
        <~ if $orderinfo.orders_status eq $smarty.const.CM_ORDER_STATUS_PROCESSING ~>
          <tr><td>Ship Method:</td><td><~ $upform.ship_method.TAG ~></td></tr>
          <tr><td>Ship Date:</td><td><~ $upform.ship_date.TAG ~></td></tr>
          <tr><td>Tracking#:</td><td><~ $upform.tracking_no.TAG ~></td></tr>
        <~ elseif $orderinfo.orders_status == $smarty.const.CM_ORDER_STATUS_SHIPPED ~>
          <tr><td>Delivery Date:</td><td><~ $upform.delivery_date.TAG ~></td></tr>
        <~/if~>
          <tr><td>Notify Customer?</td><td><~ $upform.do_notify.TAG ~></td></tr>
          <tr><td colspan="2" align="right"><~ $upform.op_update.TAG ~></td></tr>
        </table>
        <~ $upform.HIDDENS ~>
        </form>
      </div>
    </td>
  </tr>
</table>
</td>
</tr>
  <!--
  <tr>
    <td colspan="2">
    <div style="text-align: left; margin: 10px; border: 1px solid black; padding: 0">
        <iframe src="store.order.transactions.php?oid=<~ $orderinfo.id ~>" height="140" width="100%" border="0" >blurgh!</iframe>
    </div>
    </td>
  </tr>
  -->
<~ if $has_custom_designs ~>
  <tr>
    <td valign="top" colspan="2">
      <div style="text-align: left; border-bottom: 1px solid black">
        <h3 class="order">DEKAL GENERATOR CONTROLLER</h3>
        <div  align="center">
          <iframe src="/control/dekal_genreq_controller.php?oid=<~ $orderinfo.id ~>" style="width: 90%; border: 0;" frameborder="0" ></iframe>
        </div>
    </td>
  </tr>
<~/if~>
  <tr>
    <td colspan="2">
      <div class="orderItems">
        <h3 class="order">ORDER ITEMS</h3>
        <form id="oiform" action="store.orders.php" method="post">
  <table border="0" cellspacing="0" cellpadding="3" class="cart">
      <tr class="cartHeader">
          <th align="center">&nbsp;</th>
          <th colspan="2" align="left">Product</th>
          <th>Quantity</th>
          <th>Backordered</th>
          <th align="right">Price</th>
          <th align="right">Total</th>
      </tr>
    <~ foreach from=$cart item=item name=i ~>
       <tr id="lineItem<~ $item.id ~>" class="<~ cycle values=cartRowOdd,cartRowEven ~> <~ if $item.stock_status ne 1 ~>backordered<~/if~>">
          <td class="oi_id" align="center" valign="top">
              <~ $smarty.foreach.i.iteration ~>
          </td>
          <td valign="top">
              <strong><~ $item.product_descrip ~></strong>
              [<~ $item.product_sku ~>]
          </td>
          <td valign="top">
            <~ include file="float:cart_product_attribs.tpl" ~>
          </td>
          <td align="center" valign="top">
              <span id="dQty_<~ $item.id ~>" class="dQty"><~ $item.qty ~></span>
          </td>
          <td align="center" valign="top">
              <span id="dBackOrder_<~ $item.id ~>" class="dBackOrder"><~ $item.backorder_qty ~></span>
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
  <~ include file="cart/cart_totals.tpl" colspan=6 ~>
      <tr class="cartHeader">
          <td colspan="4">&nbsp;</td>
          <td align="center"><input type="submit" name="op_oiform" class="btn" id="op_oiform" value="UPDATE ORDER" /></td>
          <td colspan="2">&nbsp;</td>
      </tr>
  </table>
    
    <input type="hidden" name="oid" value="<~ $orderinfo.id ~>" />
  </form>
      </div>
    </td>
  </tr>
<~ if $transactions ~>
  <tr>
    <td colspan="2">
      <div class="orderItems">
        <h3 class="order">ORDER TRANSACTIONS</h3>

        <~ if $xform ~>
            <div class="orderTransactForm" align="right">
                <~ $xform.FORM ~>
                <table border="0">
                    <tr>
                        <td><strong>Run Transaction:</strong></td>
                        <td><~ $xform.xtype.TAG ~></td>
                        <td>amount: <~ $xform.xamt.TAG ~></td>
                        <td><~ $xform.op_xaction.TAG ~></td>
                    </tr>
                </table>
                <~ $xform.HIDDENS ~>
                </form>
            </div>
        <~/if~>

  <table border="0" cellspacing="0" cellpadding="3" class="cart">
      <tr class="cartHeader">
          <th align="left">Type</th>
          <th align="left">Date</th>
          <th align="left">TransID</th>
          <th align="left">&nbsp;</th>
          <th align="left">Result</th>
          <th align="left">Amount</th>
          <th align="left">verified</th>
      </tr>
    <~ foreach from=$transactions item=trans name=i ~>
       <tr id="lineItem<~ $item.id ~>" class="<~ cycle values=cartRowOdd,cartRowEven ~>">
          <td valign="top">
              <~ $trans.trans_type ~>
          </td>
          <td valign="top">
              <~ $trans.stamp|date_format:$smarty.const.CSHOP_DATE_FMT_DISPLAY ~>
          </td>
          <td valign="top">
              <~ $trans.trans_id ~>
          </td>
          <td valign="top">
              <~ if $trans.trans_result eq 'APPROVED' ~>
                <img src="/control/cshop/img/tick.gif">
              <~ elseif $trans.trans_result ne '' ~>
                <img src="/control/cshop/img/cross.gif">
              <~/if~>
          </td>
          <td valign="top">
              <~ $trans.trans_result ~>
              <~ $trans.trans_result_msg ~>
          </td>
          <td valign="top">
              <~ $trans.trans_amount ~>
          </td>
          <td valign="top">
              <~ if !$trans.has_avs_result ~>
                &ndash;
              <~ else ~>
              <table cellpadding="0" cellspacing="0" border="0">
                  <tr>
                      <td align="center">Addr</td>
                      <td align="center">ZIP</td>
                      <td align="center">Int'l</td>
                      <td align="center">CSC</td>
                  </tr>
                  <tr>
                      <td align="center"><~ if $trans.verify_addr eq 1 ~>Y<~ elseif $trans.verify_addr eq NULL ~>&ndash;<~else~>N<~/if~></td>
                      <td align="center"><~ if $trans.verify_zip eq 1 ~>Y<~ elseif $trans.verify_zip eq NULL  ~>&ndash;<~else~>N<~/if~></td>
                      <td align="center"><~ if $trans.verify_international eq 1 ~>Y<~ elseif $trans.verify_international eq NULL  ~>&ndash;<~else~>N<~/if~></td>
                      <td align="center"><~ if $trans.verify_csc eq 1 ~>Y<~ elseif $trans.verify_csc eq NULL  ~>&ndash;<~else~>N<~/if~></td>
                  </tr>
               </table>
              <~/if~>
          </td>
       </tr>
    <~/foreach~>
  </table>
    </td>
  </tr>
<~/if~>
</table>


<script type="text/javascript">
<!--

var oiHasUp = false;

$(document).ready(
        function() {
            $('table.cart tr.backordered span.dBackOrder').bind('click', function() {
                var td = $(this).hide().parent().append('<input type="text" name="'+$(this).attr('id')+'" size="2" value="'+$(this).text()+'" />')
                                                .find('input').focus();
                if (!oiHasUp) {
                    $('#op_oiform').show();
                    oiHasUp = true;
                }
            } );
        }
);



// -->
</script>
