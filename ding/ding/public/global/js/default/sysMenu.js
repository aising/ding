$(document).ready(function() {
	
	$('#page_url-element').bind('change',function(){
		var tValUrl = $(this).val();
		var open = 1;
		tValUrl = tValUrl.split('/');

		var urlEndStr = tValUrl[tValUrl.length - 1];

		// 1查看 2初审 3二审 4终审 5增加 6修改 7删除
		if(open) {
			$("input[name='permission[]']").removeAttr('checked');
			switch(urlEndStr) {
				case 'add':
					$("input[name='permission[]']").eq(5-1).attr('checked','checked');
				break;

				case 'mod':
					$("input[name='permission[]']").eq(6-1).attr('checked','checked');
				break;

				case 'del':
					$("input[name='permission[]']").eq(7-1).attr('checked','checked');
				break;

				case 'datatable':
				default:
					$("input[name='permission[]']").eq(1-1).attr('checked','checked');
				break;
			}
		}
	});


});