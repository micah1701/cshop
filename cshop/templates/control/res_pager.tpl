    <div align="left">
      <~ $pager->numrows ~> item<~ if $pager->numrows gt 1 ~>s<~/if~> found. 
      Showing <~ $pager->from ~> to <~ $pager->to ~>.<br />
      <~ if $pager->numpages gt 1 ~>
          page: 
          <~ section name=p loop=$pager->numpages start=0 ~>
            <~ if $smarty.section.p.iteration == $pager->current ~>
              <strong><~ $smarty.section.p.iteration ~></strong>&nbsp;
            <~ else ~>
              <a href="<~ $smarty.server.PHP_SELF ~>?page=<~ $smarty.section.p.iteration ~>&<~ $pager->get_params ~>"><~ $smarty.section.p.iteration ~></a>&nbsp;
            <~ /if ~>
        <~ /section ~>
      <~ /if ~>
    </div>
