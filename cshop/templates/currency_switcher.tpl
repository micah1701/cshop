<~ if $currency_opts ~>
  <div id="currencySwitchMenu">
    prices shown in <strong><~ $currency_opts[$current_currency_display]|escape ~></strong>
    <br />
    show prices in 
      <~ foreach from=$currency_opts item=currname key=code ~>
                   <a href="<~ $smarty.server.PHP_SELF ~>?curr=<~ $code ~>"><~ $code ~></a> 
      <~/foreach~>
  </div>
<~/if~>
