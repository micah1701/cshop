
              <~ if $item.product_attribs.thumbnail_file ~>
                  <a href="<~ $item.product_attribs.design_url ~>" target="_new">
                  <img src="<~ $item.product_attribs.thumbnail_file ~>" /></a><br />
              <~ elseif $item.filename_thumb ~>
                  <img src="<~ $item.system_location ~>/<~ $item.filename_thumb ~>" <~ $item.dims_thumb ~> /><br />
              <~/if~>

              <~ foreach from=$item.normalized_attribs key=k item=v ~>
                  <strong><~ $k ~>:</strong> <~ $v ~><br />
              <~/foreach ~>

              <~ if $item.item_options ~>
                <~ foreach from=$item.item_options key=k item=option ~>
                  <~ if $option.descr ~>
                     <em><~ $option.descr|escape ~>:</em> <~ $option.value|escape ~><br />
                  <~/if~>
                <~/foreach~>
              <~/if ~>
