<~ if $address.company ~>
<~ $address.company ~> 
<~ if $address.name ~>
c/o <~ $address.name ~>
<~/if~>
<~ else ~>
<~ $address.name ~>
<~/if~>
<~ $address.street_addr ~>
<~ if $address.addr2 ~><~ $address.addr2 ~>
<~/if~>
<~ $address.city ~>, <~ $address.state ~> <~ $address.postcode ~>
<~ $address.country ~>
<~ if $address.telephone ~>Phone: <~ $address.telephone ~><~/if~>
<~ if $address.phone ~>Phone: <~ $address.phone ~><~/if~>
<~ if $address.fax ~>FAX: <~ $address.fax ~><~/if~>
<~ if $address.email ~>email: <~ $address.email ~><~/if~>
