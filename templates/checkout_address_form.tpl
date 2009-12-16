          <table class="checkoutAddressForm" width="100%" cellpadding="4" cellspacing="0" border="0">
            <tr>
              <td class="<~ $cform.name.CLASS ~>">
                <span><label for="name" accesskey="n"><~ $cform.name.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.name.TAG|tabindex:11 ~><span class="formReqStar">*</span>
              </td>
              <td class="<~ $cform.city.CLASS ~>">
                <span><label for="city" accesskey="n"><~ $cform.city.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.city.TAG|tabindex:15 ~><span class="formReqStar">*</span>
              </td>
            </tr>

            <tr>
              <td class="<~ $cform.company.CLASS ~>">
                <span><label for="company" accesskey="n"><~ $cform.company.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.company.TAG|tabindex:12 ~>
              </td>
              <td class="<~ $cform.state.CLASS ~>">
                <span><label for="state" accesskey="n"><~ $cform.state.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.state.TAG|tabindex:16 ~><span class="formReqStar">*</span><div class="checkoutHintNotice">(where required) </div>
              </td>
            </tr>

            <tr>
              <td class="<~ $cform.street_addr.CLASS ~>">
                <span><label for="street_addr" accesskey="n"><~ $cform.street_addr.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.street_addr.TAG|tabindex:13 ~><span class="formReqStar">*</span>
              </td>
              <td class="<~ $cform.postcode.CLASS ~>">
                <span><label for="postcode" accesskey="n"><~ $cform.postcode.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.postcode.TAG|tabindex:17 ~><span class="formReqStar">*</span><div class="checkoutHintNotice">(where required) </div>
              </td>
            </tr>

            <tr>
              <td class="<~ $cform.addr2.CLASS ~>">
                <span><label for="addr2" accesskey="n"><~ $cform.addr2.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.addr2.TAG|tabindex:14 ~>
              </td>
              <td class="<~ $cform.country.CLASS ~>">
                <span><label for="country" accesskey="n"><~ $cform.country.LABEL ~></label></span>
              </td>
              <td class="formField">
                <~ $cform.country.TAG|tabindex:18 ~><span class="formReqStar">*</span>
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
                <~ $cform.phone.TAG|tabindex:19 ~><span class="formReqStar">*</span>
              </td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
          <~/if ~>

          <~ if $cform.email ~>
              <tr class="checkoutEmail">
                <td valign="top" class="<~ $cform.email.CLASS ~>">
                  <span><label for="email" accesskey="n">e-mail address:</label></span>
                </td>
                <td class="formField" colspan="4">
                    <~ $cform.email.TAG|tabindex:20 ~>
                </td>
              </tr>
          <~/if~>

          <tr class="checkoutUserComments">
            <td valign="top" class="<~ $cform.user_comments.CLASS ~>">
              <span><label for="user_comments" accesskey="n">Order Comments:</label></span>
            </td>
            <td class="formField" colspan="4">
                <~ $cform.user_comments.TAG|tabindex:21 ~>
                <br />
                <div id="max_comments">Characters remaining: <span>255</span></div>
            </td>
          </tr>

        </table>

        <script type="text/javascript">

            /** check and verify length of comment according to max length found in html */
            var max_comment_length = 2000;
            $(document).ready( function() { 
                    max_comment_length = parseInt($('#max_comments span').text());
                    $('#f_user_comments').trigger('keyup');
           });

            $('#f_user_comments').bind('keyup', function() {
                    var len = $(this).val().length;
                    if (len > max_comment_length) {
                        $('#max_comments').addClass('checkoutCommentError');
                    }
                    else {
                        $('#max_comments').removeClass('checkoutCommentError');
                    }
                    $('#max_comments span').text( max_comment_length - len );
            });

            $('form').bind('submit', function() {
                    var comments = $(this).find('#f_user_comments');
                    if ($(comments).length) {
                        if ($(comments).val().length > max_comment_length) {
                            alert('Comment field cannot exceed ' +max_comment_length+ ' characters. Please edit to proceed');
                            return false;
                        }
                        return true;
                    }
            });

        </script>
