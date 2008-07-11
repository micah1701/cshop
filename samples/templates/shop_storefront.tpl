<~ include file="shop_category_nav.tpl" ~>
<span style="color: #666; font-style: italic">skeleton template <~ $smarty.template ~></span>


<div style="float: left; border: 1px solid #6c6">
    <strong>FOCAL IMAGE PLACEHOLDER</strong>
</div>



<div style="float: right; width: 200px; padding-left: 5px; border-left: 1px solid #666">
    <div style="background: #333; color: white; margin: 10px 0 10px 0">
    FEATURED
    </div>
    <div style="background: #333; color: white; margin: 10px 0 10px 0">
    <a style="color: #6c6" href="<~ $smarty.server.SCRIPT_NAME ~>?cat=<~ $feat_categories[0].id ~>"><~ $feat_categories[0].name|upper ~></a>
    </div>
</div>
  


