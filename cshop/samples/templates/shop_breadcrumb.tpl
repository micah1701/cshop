<div id="shopBreadcrumb">
<~ if $breadcrumbs ~>
  <strong>
  <~ foreach from=$breadcrumbs key=bc item=link ~>
      <~ if $bc == "general" ~>
      <~ elseif $link ~>
          <a href="<~ $link ~>"><~ $bc ~></a> &gt;
      <~else~>
          <~ $bc ~>
      <~/if~>
  <~/foreach ~>
  </strong>
  <br >
<~/if~>
</div>

