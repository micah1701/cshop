<~ include file="shop_category_nav.tpl" ~>
<span style="color: #666; font-style: italic">skeleton template <~ $smarty.template ~></span>


<~ include file="shop_breadcrumb.tpl" ~>

<div style="float: left; border: 1px solid #6c6">
  <~ if $product.images[0] ~>
    <img id="mainPic" src="<~ $product.images[0].system_location ~>/<~ $product.images[0].filename_large ~>" <~ $product.images[0].dims_large ~> alt="detail image" />
  <~ else ~>
    <img src="/images/product_no_image.jpg" />
  <~/if~>
</div>

<div style="float: right; width: 200px; padding-left: 5px; border-left: 1px solid #666">
<strong><~ $product.title ~></strong>
<br />
PRICE $<~ $product.price ~>
<p>
<~ $product.description ~>
</p>

<~ if $max_qty ~>
    <form name="prG" action="/cart/cart.php" method="post">
    <span class="ProductQty">QTY&nbsp;&nbsp;&nbsp;&nbsp;</span>
    <select name="qty" class="sby">
    <~ section name=i loop=$max_qty ~> 
        <option><~ $smarty.section.i.iteration ~></option>
    <~/section~>
    </select>

    <br />
    <br />
    <~ if $inventoryerror ~>
     <div class="userIndicator">
         Sorry we do not have that combination of size and color in stock.
     </div>
    <~/if~>
    <~ if $product.sizes ~>
        <select name="sizes" id="sizesSel" class="archive">
        <option value="#" selected>Select a Size</option>
        <~ foreach from=$product.sizes item=size ~>
            <option value="<~ $size.id ~>"><~ $size.fullname ~></option>
        <~/foreach ~>
        </select>
    <~/if~>
    <br />

    <~ if $product.colorways ~>
      <select name="colorid" id="colorwaySel" onchange="switchProductPic(this.options[this.selectedIndex].value, null, 'mainPic')" class="archive">
      <option value="#" selected>Select a Color</option>
      <~ foreach from=$product.colorways item=color ~>
          <option value="<~ $color.id ~>"><~ $color.name ~></option>
      <~/foreach ~>
      </select>
    <~/if~>

    <input type="submit" width="105" height="17" value="add to shopping cart" name="op_add" />
    <input type="hidden" name="op_add_pid" value="<~ $product.id ~>" /></p>
    <~ if $product.colorways ~>
    <span class="ProductQty">ADDITIONAL IMAGES:</span>
      <br />
      <~ foreach from=$product.images key=k item=img ~>
      <div style="height: 34px; width: 34px; margin-right: 3px; margin-top: 3px; float: left; cursor: pointer; background-image: url(<~ $img.system_location ~>/<~ $img.filename_thumb ~>); background-position: center center;" onclick="switchProductPic(null, '<~ $k ~>', 'mainPic')">
         &nbsp; 
        </div>
      <~/foreach ~>
      </select>
    <~/if~>
    </form>







<~ else ~>
    <em>Sorry, this item is currently out of stock.</em>
<~/if~>
</div>





<script type="text/javascript">
<!-- 

var cwpics = new Array();
var opics = new Array();

<~ foreach from=$product.images key=k item=img ~>
    <~ if $img.colorways_id ~>
    cwpics[<~ $img.colorways_id ~>] = { src : '<~ $img.system_location ~>/<~ $img.filename_large ~>', zoom : '<~ $img.filename_zoom ~>' };
    <~/if~>
    opics[<~ $k ~>] = { src : '<~ $img.system_location ~>/<~ $img.filename_large ~>', zoom : '<~ $img.filename_zoom ~>' };
<~/foreach ~>



    function switchPic(newsrc, targetId) {
        if (document.getElementById(targetId)) {
            document.getElementById(targetId).src = newsrc;
        }
    }

    function switchProductPic(cwayId, imgIter, targetId) {
        //console.log("%d %d", cwayId, imgIter);
        if (document.getElementById(targetId)) {
            srcId = cwpics[cwayId];
            if (!srcId && opics[imgIter]) {
                srcId = opics[imgIter];
            }
            if (srcId) {
                document.getElementById(targetId).src = srcId.src; 
                document.getElementById(targetId).parentNode.href = srcId.zoom;
            }
        }
    }




  window.onload = "switchProductPic(document.prG.colorwaySel.options[0].value, 'mainPic');";
// -->
</script>

