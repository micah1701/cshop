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
<strong>regular Nav:</strong>
<ul>
<~ foreach from=$category_tree item=cat ~>
   <li><a href="<~ $smarty.server.SCRIPT_NAME ~>?cat=<~ $cat.id ~>"><~ $cat.name ~></a>
   <~ if $cat.children ~>
     <ul>
       <~ foreach from=$cat.children item=subcat ~>
          <li><a href="<~ $smarty.server.SCRIPT_NAME ~>?cat=<~ $subcat.id ~>"><~ $subcat.name ~></a>
       <~/foreach~>
     </ul>
   <~/if~>
   </li>
<~/foreach~>
</ul>

<strong>Nav with SEF URLs:</strong>
 <ul class="Top">
   <~ foreach from=$category_tree item=cat ~>
     <li><a href="/browse/<~ $cat.urlkey ~>" class="<~ if $category.id eq $cat.id  ~>sel<~/if~>"><~ $cat.name|escape ~></a>
           <~ if $cat.children ~>
             <ul class="Sub1">
             <~ foreach from=$cat.children item=subcat ~>
             <li><a href="/browse/<~ $cat.urlkey ~>/<~ $subcat.urlkey ~>" class="<~ if $category.id eq $subcat.id ~>sel<~/if~>">
                 <~ $subcat.name|escape ~></a></li>
                 <~ if $subcat.children ~>
                     <ul class="Sub2">
                     <~ foreach from=$subcat.children item=subsubcat ~>
                       <li><a href="/browse/<~ $cat.urlkey ~>/<~ $subcat.urlkey ~>/<~ $subsubcat.urlkey ~>" class="<~ if $category.id eq $subsubcat.id ~>sel<~/if~>"><~ $subsubcat.name|escape ~></a></li>
                     <~/foreach~>
                     </ul>
                 <~/if~>
             <~/foreach~>
             </ul>
           <~/if~>
     </li>
   <~/foreach ~>
 </ul>


<hr />
</div>
