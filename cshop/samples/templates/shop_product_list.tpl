<~ include file="shop_category_nav.tpl" ~>
<span style="color: #666; font-style: italic">skeleton template <~ $smarty.template ~></span>
<~ include file="shop_breadcrumb.tpl" ~>
    <p><span class="karhutitle"><~ $category.name ~></span>
    <span style="font-style: italic"><~ $category.descrip ~></span>
    </p>



  <div style="margin-left: 210px">
  <~ foreach from=$products name=p item=prod ~>
     <div style="float: left; width: 150px; border: 1px solid #666">
       <a href="<~ $smarty.server.PHP_SELF ~>?pid=<~ $prod.id ~>&amp;cat=<~ $category.id ~>"><img src="<~ $prod.images[0].system_location ~>/<~ $prod.images[0].filename_thumb ~>" <~ $prod.dims_thumb ~> alt="product image"  title="<~ $prod.title ~>" /></a>
       <br />
       <~ $prod.title|escape ~>
     <~ if $smarty.foreach.p.iteration %3 == 0 and !$smarty.foreach.p.last ~>
     <~/if~>
     </div>
  <~foreachelse~>
    <em>Sorry, no products are found in <strong><~ $category.name ~></strong></em>
  <~/foreach~>
  </div>
  


