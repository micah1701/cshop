<div style="width: 200px; float: left; margin-right: 10px; border-right: 1px solid #666">
<span style="color: #666; font-style: italic">skeleton template <~ $smarty.template ~></span>
<hr />
<strong>FEATURES</strong>
<ul>
 <li><a href="<~ $smarty.server.SCRIPT_NAME ~>/newarrivals">New Arrivals</a></li>
 <li><a href="<~ $smarty.server.SCRIPT_NAME ~>/featured">Featured</a></li>
 <li>Latest News</li>
</ul>

<strong>SHOP BY CATEGORY</strong>
<ul>
<~ foreach from=$product_categories key=id item=cat ~>
   <li><a href="<~ $smarty.server.SCRIPT_NAME ~>?cat=<~ $id ~>"><~ $cat.name ~></a>
   <~ if $cat.sub ~>
     <ul>
       <~ foreach from=$cat.sub key=sub_id item=sub_cat ~>
          <li><a href="<~ $smarty.server.SCRIPT_NAME ~>?cat=<~ $sub_id ~>"><~ $sub_cat ~></a>
       <~/foreach~>
     </ul>
   <~/if~>
   </li>
<~/foreach~>
</ul>

<hr />
</div>
