var tabController = {
    curr : null,

    displayControlTab : function () {
        $(this).addClass('tabSelected').siblings().removeClass('tabSelected');

        var rel = $(this).attr('rel');
        var tabContent = $('#'+rel);
        if (tabController.curr == rel) {
            // reload the iframe, if there is one.
            $(tabContent).find('iframe').each( function() { $(this).get(0).contentDocument.location = $(this).attr('src'); } );
        }
        else {
            tabController.curr = rel;
            $(tabContent).show().siblings().hide();
        }
    }
}





function delInv(nid){
	if(invframe.curRow=='null'){
		alert('Please choose an item to delete');	
	}else{
		if(confirm("Are you sure you want to delete the selected inventory?")){
		invframe.location.href='store.inventory.php?nid='+nid+'&action=delete&row='+invframe.curRow;	
			
		}
	}
}




$(document).ready( function()  {
    $('#tabContainer > div').bind('click', tabController.displayControlTab);

    //$('div.formWrapper iframe').height( $(window).height() - $('#tabActiveContent').offset().top - 60 );
} );


