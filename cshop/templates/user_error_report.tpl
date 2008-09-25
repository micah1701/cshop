    <~ if $errors ~>
      <div class="userError">
        Please correct the following errors to proceed:
        <ul class="userError">
        <~ foreach from=$errors item=e ~>
            <li><~ $e ~></li>
        <~/foreach~>
        </ul>
      </div>
      <div style="clear: both; height: 1px"></div>
    <~/if~>

    <~ if $NOTICE ~>
      <div class="userIndicator">
       <~ $NOTICE ~>
      </div>
    <~/if~>
