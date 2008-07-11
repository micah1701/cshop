          <table width="100%" cellpadding="4" cellspacing="0" border="0">
            <tr>
              <td class="<~ $cform.name.CLASS ~>">
                <span><label for="name" accesskey="n"><~ $cform.name.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.name.TAG ~><span class="formReqStar">*</span>
              </td>
              <td class="<~ $cform.city.CLASS ~>">
                <span><label for="city" accesskey="n"><~ $cform.city.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.city.TAG ~><span class="formReqStar">*</span>
              </td>
            </tr>

            <tr>
              <td class="<~ $cform.company.CLASS ~>">
                <span><label for="company" accesskey="n"><~ $cform.company.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.company.TAG ~>
              </td>
              <td class="<~ $cform.state.CLASS ~>">
                <span><label for="state" accesskey="n"><~ $cform.state.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.state.TAG ~><span class="formReqStar">*</span>
              </td>
            </tr>

            <tr>
              <td class="<~ $cform.street_addr.CLASS ~>">
                <span><label for="street_addr" accesskey="n"><~ $cform.street_addr.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.street_addr.TAG ~><span class="formReqStar">*</span>
              </td>
              <td class="<~ $cform.postcode.CLASS ~>">
                <span><label for="postcode" accesskey="n"><~ $cform.postcode.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.postcode.TAG ~><span class="formReqStar">*</span>
              </td>
            </tr>

            <tr>
              <td class="<~ $cform.addr2.CLASS ~>">
                <span><label for="addr2" accesskey="n"><~ $cform.addr2.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.addr2.TAG ~>
              </td>
              <td class="<~ $cform.country.CLASS ~>">
                <span><label for="country" accesskey="n"><~ $cform.country.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.country.TAG ~><span class="formReqStar">*</span>
            <~ if $SUPPRESS_INTL_ORDER ~>
                Sorry, at this time we can only ship orders inside the US.
            <~/if~>
              </td>
            </tr>

          <~ if $cform.phone ~>
            <tr>
              <td class="<~ $cform.phone.CLASS ~>">
                <span><label for="phone" accesskey="n"><~ $cform.phone.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.phone.TAG ~><span class="formReqStar">*</span>
              </td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
          <~/if ~>

          <tr>
            <td valign="top">
              <span><label for="user_comments" accesskey="n">Order <br />Comments:</label></span>
            </td>
            <td colspan="4">
              <div class="checkoutFormBox">
                <~ $cform.user_comments.TAG ~>
              </div>
            </td>
          </tr>
        </table>
