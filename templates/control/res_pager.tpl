    <div align="left">
      <~ $pager->numrows ~> item<~ if $pager->numrows gt 1 ~>s<~/if~> found. 
      Showing <~ $pager->from ~> to <~ $pager->to ~>.<br />

      <~ if $pager->numpages gt 1 ~>
          page: 
          <~ if $pager->cutoff_from and $pager->cutoff_from gt 1 ~>
                <a href="<~ $smarty.server.PHP_SELF ~>?page=1&<~ $pager->get_params ~>">&laquo;</a>...
          <~/if~>
          <~ foreach key=p item=offset from=$pager->pages ~>
            <~ if $p eq $pager->current ~>
              <strong><~ $p ~></strong>&nbsp;
            <~ else ~>
              <a href="<~ $smarty.server.PHP_SELF ~>?page=<~ $p ~>&<~ $pager->get_params ~>"><~ $p ~></a>&nbsp;
            <~ /if ~>
          <~ /foreach ~>
          <~ if $pager->cutoff_to and $pager->cutoff_to lt $pager->numpages ~>
            ...<a href="<~ $smarty.server.PHP_SELF ~>?page=<~ $pager->numpages ~>&<~ $pager->get_params ~>">&raquo;</a>
          <~/if~>

      <~ /if ~>
    </div>
