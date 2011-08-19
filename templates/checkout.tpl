<~ include file="cart_header.tpl" heading="Shipping Address" ~>
<~ include file="float:minicart.tpl" ~>

<div id="cartWrapper">

    <~ include file="float:cartsteps.tpl" step=1 ~>
    <div class="checkoutPageHeader"><strong>Shipping Information</strong>
        Please enter shipping information for your order below.</div>

    <~ if $is_new_user ~>
    <div class="userAcctNotice">
        <p><strong>New Account</strong><br />
        Your new account has been created and is
        ready to use. You should receive a email in a few minutes with your account
        details for future reference. Please continue through checkout process below:
        </p>
    </div>
    <~ elseif $user ~>
    <div class="userAcctNotice">
        <h2 class="checkoutSectionHeader">Your contact details</h2>
        <strong><~ $user.cust_name ~> <~ if $user.company ~>&mdash; <~ $user.company ~><~/if~></strong>
        &lt;<~ $user.email ~>&gt;
        <~ if $user.phone ~>Phone:<~ $user.phone ~><~/if~>
        <div class="cartYouAreNot">
            (<a href="logout.php">Not <strong><~ $user.cust_name ~></strong>?</a>)
        </div>
    </div>
    <~ /if ~>

    <div style="height: 1px">
        &nbsp;
    </div>
    <br />
    <~ include file="cart/user_error_report.tpl" ~>
    <~ if $cform ~>
    <~ $cform.FORM ~>

    <~ if !$user ~>

    <div class="reqFieldNotice">
        Fields marked with <span class="formReqStar">*</span> are required.
    </div>
    <div class="checkoutFormBox">
        <h2 class="checkoutSectionHeader">Your contact details</h2>
        <div class="userInstruction">
            We will use this information only to contact you in regard to your order.
        </div>
        <table cellpadding="4" cellspacing="0" border="0">
            <tr>
                <td>
                    <label for="cust_name" >Your Name</label>
                </td>
                <td class="formField">
                    <~ $cform.anon_cust_name.TAG ~> <span class="formReqStar">*</span>
                </td>
            </tr>
            <tr>
                <td class="<~ $cform.anon_email.CLASS ~>">
                    <label for="anon_email" ><~ $cform.anon_email.LABEL ~></label>
                </td>
                <td class="formField">
                    <~ $cform.anon_email.TAG ~><span class="formReqStar">*</span>
                </td>
            </tr>
            <tr>
                <td class="<~ $cform.anon_telephone.CLASS ~>">
                    <label for="anon_telephone" ><~ $cform.anon_telephone.LABEL ~></label>
                </td>
                <td class="formField">
                    <~ $cform.anon_telephone.TAG ~>
                </td>
            </tr>
        </table>
    </div>
    <~/if~>





    <div class="checkoutFormBox" id="checkoutShippingAddrArea">
        <h2 class="checkoutSectionHeader">Shipping Address</h2>
        <~ if $skip_shipping_addr ~>
           <div class="userInstruction">Shipping address not required for this order.</div>
        <~ else ~>
            <div class="userInstruction">
                <~ if $has_shipping ~>
                Please edit your shipping address as needed.
                <br />
                [<a href="<~ $smarty.server.PHP_SELF ~>?op_add_ship">Add a new shipping address</a>]
                <~else~>
                Please enter the shipping address for your order. 
                <~/if~>
            </div>
        <~/if~>

        <~ include file=float:checkout_address_form.tpl ~>
    </div>



    <div class="checkoutSubmitButtonArea">
        <~ $cform.butt.TAG|tabindex:100 ~>
    </div>
    <~ $cform.HIDDENS ~>
</form>
<~ /if ~>
</div>

<div class="clear">&nbsp;</div>
<~ include file="cart_footer.tpl" ~>
