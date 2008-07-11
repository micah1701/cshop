<~ if $related ~>
<strong>You may be interested in the following products as well:</strong> 
  <table border="0" cellspacing="0" cellpadding="0">
    <tr>
 <~ foreach from=$related name=pr item=prod ~>
      <td align="center" valign="top" class="card">
        <~ if $prod.images[0] ~>
        <a href="<~ $smarty.const.CSHOP_PRODUCT_DETAIL_PAGE ~>?pid=<~ $prod.id ~>"><img src="<~ $prod.images[0].system_location ~>/<~ $prod.images[0].filename_thumb ~>" border="0" /></a>
        <br />
        <~/if~>
        <a href="<~ $smarty.const.CSHOP_PRODUCT_DETAIL_PAGE ~>?pid=<~ $prod.id ~>"><~ $prod.title ~></a>
      </td>

    <~ if $smarty.foreach.pr.iteration is div by 3 ~>
      </tr>
      <tr>
    <~elseif ! $smarty.foreach.pr.last ~>
      <td>&nbsp;</td>
    <~/if~>
 <~/foreach~>
    </tr>
  </table>
<~/if~>