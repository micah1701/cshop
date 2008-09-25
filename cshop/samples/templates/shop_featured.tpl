<~ include file="shop_category_nav.tpl" ~>
<span style="color: #666; font-style: italic">skeleton template <~ $smarty.template ~></span>
    <p><span class="karhutitle"><~ $category.name ~></span>
    <span style="font-style: italic"><~ $category.descrip ~></span>
    </p>
These are "Featured Categories" - where Feature Rank of the category is non-zero



  <div style="margin-left: 210px">
  <~ foreach from=$feat_categories name=p item=cat ~>
     <div style="float: left; width: 150px; border: 1px solid #666">
       <a href="<~ $smarty.server.SCRIPT_NAME ~>?cat=<~ $cat.id ~>"><img src="<~ $smarty.const.CSHOP_MEDIA_URLPATH ~>/<~ $cat.cat_photo ~>" <~ $cat.cat_photo_dims ~> border="0" alt="category icon" title="<~ $cat.name ~>" /></a>
       <br />
       <~ $cat.name|escape ~>
     </div>
  <~/foreach~>
  </div>
  

  <div style="clear: both; height: 10px; background: #eee;">&nbsp;</div>

