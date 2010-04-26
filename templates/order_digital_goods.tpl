    <div class="checkoutFormBox">
        <h2 class="checkoutSectionHeader">Digital Downloads</h2>
        <div class="userIndicator">Your order contains digital goods which can now be
            downloaded. Please click the below links to retrieve your files.</div>
        <ul>
        <~ foreach from=$download_list item=download ~>
            <li><a href="<~ $download.download_url ~>"><~ $download.product_descrip ~></a></li>
        <~/foreach~>
        </ul>
    </div>


