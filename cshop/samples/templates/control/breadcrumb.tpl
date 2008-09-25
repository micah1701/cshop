  <strong>
  <~ foreach from=$breadcrumb key=bc item=link ~>
      <a href="<~ $link ~>"><~ $bc ~></a> &gt;
  <~/foreach ~>
  <~ $breadcrumb_last ~>
  </strong>


