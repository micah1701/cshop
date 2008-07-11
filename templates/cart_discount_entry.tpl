          <~ if !$discount_amt ~>
             <div id="cartDiscountEntry">
                  <~ if $discount_error ~>
                      <span class="userError">Sorry, the Discount Code you entered is 
                          invalid or has already been used. Please try again:</span>
                  <~ elseif $discount_cleared ~>
                      <span class="userError">Your discount Code has been removed from 
                          this cart.</span>
                  <~ else ~>
                      <span>If you have a Gift Certificate or Discount Card, please enter 
                            the code here:</span>
                  <~ /if ~>
                  <input type="text" name="discount_code" id="discount_code" />
                  <input type="submit" name="op_disc" id="opDiscount" value="GO" onclick="this.value='Please wait...';" class="fexter">
              </div>
          <~ else ~>
             <div id="cartDiscountEntry">
                  DISCOUNT APPLIED: <~ $discount_descrip ~> <a href="?clearcoupon" style="font-size: 0.9em" onclick="return confirm('Remove this Discount Code from this cart?')">[clear]</a>
             </div>
          <~ /if ~>
