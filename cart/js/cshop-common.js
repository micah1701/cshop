

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

       $('#opCartEmpty').bind('click', function() {
           return confirm('This will remove all the items from your cart. Are you sure?');
       });


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
           $('#checkoutPaymentCardTypeSelection').css('background', 'inherit').find('select').hide(); // hide <select>
           // move the "star" to the end of the group, duh.
           $('#checkoutPaymentCardTypeSelection span.formReqStar').remove().appendTo('#checkoutPaymentCardTypeSelection'); 
       }


    }

);
