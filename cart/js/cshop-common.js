

$(document).ready(
    function () {

        /* product detail page - switch mainPic src when click on little thumbs below */
        $('div.prImageList > div > a').bind('click', function () {
            $('#mainPic').attr('src', $(this).attr("href"));
            $(this).parent().siblings().children('a').removeClass('sel');
            $(this).addClass('sel').blur();
            return false;
        });

        /* hide UPDATE btn in cart and then make it appear when a Qty field is changed. */
        $('#opCartQtyUp').css('visibility', 'hidden');

        $('input.cartQty').bind('keypress', function() {
            $('#opCartQtyUp').css('visibility', 'visible');
        });

       /* bind magic to UPDATE btn in cart */
       $('#opCartQtyUp, #opCartCheckout').bind('click', function() {
           this.value = 'Please wait...';
           return true;
       });

       /* bind a 'are you sure?' alert to cart emptying */
       $('#opCartEmpty').bind('click', function() {
           return confirm('This will remove all the items from your cart. Are you sure?');
       });


       /* show/hide the billing address form as the "same as shipping" checkbox is toggled */
       $('#same_as_shipping').each(function () {
           if (this.checked == true) {
               $('#checkoutBillingAddr').hide();
           }
           $(this).bind('click', function() {
                   $('#checkoutBillingAddr').slideToggle('slow');
           });
       });


       /* important! check inventory on change of size or color selects, for immediate feedback. */
       $('select.prAttrInvChecker').bind('change', doCheckInventorySelection);
       initAttribSelectors();


       /* crazy jquery madness to make the <select> tag for CC opts turn into a
        * group of clickable credit card company logos. The funny part is, it
        * makes NO difference what card type they select, it just makes the
        * customer feel better */
       var has_cc_images = $('#checkoutPaymentCardTypeSelection').css('background-image'); // only run if this is set in CSS
       if (has_cc_images) {
           // turn <option>s into images 
           $('#checkoutPaymentCardTypeSelection select option').each( function() {
                   var ccname = this.value.toLowerCase();
                   $('#checkoutPaymentCardTypeSelection').append('<img name="'+ccname+'" src="/cart/images/cc_' + ccname + '.gif">');
           });
           // bind click on images makes a selection
           $('#checkoutPaymentCardTypeSelection img').bind('click', function () {
               var req = this.name.toUpperCase();
               $(this).parent().find('select option').each( function() { 
                   this.selected = (this.value == req);
               });
               $(this).addClass('sel').siblings('img').removeClass('sel');
           });
           $('#checkoutPaymentCardTypeSelection').find('select').hide(); // hide <select>
           $('#checkoutPaymentCardTypeSelection').css('background-image', 'none'); // rid of old BG img

           // move the "star" to the end of the group, duh.
           $('#checkoutPaymentCardTypeSelection span.formReqStar').remove().appendTo('#checkoutPaymentCardTypeSelection'); 
       }


    }

);



/* check inventory from the allinv JSO by simple brane-ded iteration, based on
 * the current selections of size/color dropdowns
 *
 * todo: maybe more attribs than size/color (but YAGNI for now)
*/
function doCheckInventorySelection() {
    var size = $('#sizes').val();
    var color = $('#colorid').val();
    var msg;
    if (size && color) {
        for (var i in allinv) {
            if (allinv[i].sizes_id == size && allinv[i].colorways_id == color) {
                if (allinv[i].qty > 0) {
                    $('#invErrMsg').hide();
                    $('#opAddCartW').show();
                    return true;
                }
                else {
                    msg = MSG_INV_INSUFF;
                    break;
                }
            }
        }
        if (!msg) msg = MSG_INV_UNKNOWN;
    }
    if (msg) $('#invErrMsg').text(msg).show();
    $('#opAddCartW').hide();
}


/* iterate thru allinv JSO and find the first inv item that has a qty > 0.
 * Change the size/color selects to reflect that. Done only to avoid the
 * embarrassment of having an error message show up on page load */
function initAttribSelectors() {
    if (typeof(allinv) != 'undefined') {
        // if the first option in either select has empty value, skip this. There is a placeholder option.
        if ($('#sizes').get(0).options[0].value != '' && $('#colorid').get(0).options[0].value != '') {
            for (var i in allinv) {
                if (allinv[i].qty > 0) {
                    $('#sizes').val(allinv[i].sizes_id);
                    $('#colorid').val(allinv[i].colorways_id);
                }
            }
        }
        doCheckInventorySelection();
    }
}


