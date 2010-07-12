
<h4>Your order history</h4>
<br />
    <~ if $order_history ~>
        Following are your pending and completed orders. Click on the MORE INFO link to view details on your order.
        <table border="0" width="100%">
            <tr>
              <th>
                Order#
              </th>
              <th>
                Ship to
              </th>
              <th>
                Bill to
              </th>
              <th>
                Status
              </th>
              <th>
                Amount
              </th>
              <th>
                &nbsp;
              </th>
            </tr>
        <~ foreach from=$order_history item=o ~>
            <tr style="background-color: #<~ cycle values=eee,dedede ~>">
              <td>
                <~ $o.order_token ~>
              </td>
              <td>
                <~ $o.shipping_name ~>
              </td>
              <td>
                <~ $o.billing_name ~>
              </td>
              <td>
                <~ $o.status ~>
              </td>
              <td>
                $<~ $o.amt_quoted ~>
              </td>
              <td>
                <a href="/cart/order_detail.php?tok=<~ $o.order_token ~>">MORE INFO &raquo;&raquo;</a>
              </td>
            </tr>
        <~/foreach ~>
        </table>
    <~ else ~>
        <br />
        <span class="lite">You have not placed any orders yet.</span>
        <br>
    <~/if~>

