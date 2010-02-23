<html>
<head>
<style>
<!--
a.MailingListLink:link    { font-family: Verdana, Arial, sans-serif; font-size: 12px; color: #0066ff; font-weight: normal; text-decoration: underline; }
a.MailingListLink:visited { font-family: Verdana, Arial, sans-serif; font-size: 12px; color: #0066ff; font-weight: normal; text-decoration: underline; }
a.MailingListLink:active  { font-family: Verdana, Arial, sans-serif; font-size: 12px; color: #ff0000; font-weight: normal; text-decoration: underline; }
a.MailingListLink:hover   { font-family: Verdana, Arial, sans-serif; font-size: 12px; color: #ff0000; font-weight: normal; text-decoration: underline; }
.MailingListBody          { background-color: #FFFFFF; color: #000000; font-family: Verdana, Arial, sans-serif; font-size: 12px; }
-->
</style>
</head>
<body>

<p class="MailingListBody">
Greetings, <~ $recip_name ~>:
</p>

<p class="MailingListBody">
<font size="+1">You have just been sent a discount code from <strong><~ $smarty.const.SITE_TITLE ~></strong>.</font>
</p>

<~ if $coupon_descrip ~>
    <p class="MailingListBody" style="margin-left: 2em">
    Coupon type: &quot;<~ $coupon_descrip ~>&quot;
    </p>
<~/if~>

<hr>
<p class="MailingListBody">
Your discount code is:<br>
<strong><font size="+1"><~ $coupon_code ~></font></strong><br>
</p>
<hr>

<p class="MailingListBody">
To begin shopping at <~ $smarty.const.SITE_DOMAIN_NAME ~>, go to
<a class="MailingListLink" href="http://<~ $smarty.const.SITE_DOMAIN_NAME ~><~ $smarty.const.CSHOP_HOME_PAGE ~>"><~ $smarty.const.SITE_DOMAIN_NAME ~><~ $smarty.const.CSHOP_HOME_PAGE ~></a>.
</p>
<p class="MailingListBody">
When prompted, enter your code exactly as it appears above.
</p>
<p class="MailingListBody">
This email was sent to <strong><~ $recip_name ~> (<~ $recip_email ~>)</strong>. If you are not
the intended recipient, please delete this email. No further action will be taken.
</p>
<p class="MailingListBody">
<~ $smarty.const.SITE_TITLE ~>
</p>
<p class="MailingListBody">
Visit us at <a class="MailingListLink" href="http://<~ $smarty.const.SITE_DOMAIN_NAME ~>"><~ $smarty.const.SITE_DOMAIN_NAME ~></a>. If you have any questions, you may reply to this email. 
</p>
   </body>
</html>
