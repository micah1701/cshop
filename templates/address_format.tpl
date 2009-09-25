<div class="customerAddress">
<~ if $address.company ~>
  <span class="company"><~ $address.company ~></span><br />
  <~ if $address.name ~>
    &nbsp;&nbsp;c/o <span class="name"><~ $address.name ~></span><br />
  <~/if~>
<~ else ~>
  <span class="name"><~ $address.name ~></span><br />
<~/if~>
<span class="street_addr"><~ $address.street_addr ~></span><br />
<~ if $address.addr2 ~><span class="addr2"><~ $address.addr2 ~></span><br /><~/if~>
<span class="city"><~ $address.city ~></span> <span class="state"><~ $address.state ~></span> <span class="postcode"><~ $address.postcode ~></span><br />
<span class="country"><~ $address.country ~></span>

<~ if $address.telephone ~><br />Phone: <span class="telephone"><~ $address.telephone ~></span><~/if~>
<~ if $address.phone ~><br />Phone: <span class="telephone"><~ $address.phone ~></span><~/if~>
<~ if $address.fax ~><br />FAX: <span class="fax"><~ $address.fax ~></span><~/if~>
<~ if $address.email ~><br />email: <span class="email"><~ $address.email ~></span><~/if~>
</div>
