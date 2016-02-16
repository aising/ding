function hoho2(){};
$(document).ready(function(){

	// datatable 表格删除确认
	$('.js-datatable-del').each(function(i) {
		var href = $(this).attr('href');
		var selfObject = this;
		$(this).attr('href','javascript:void(0)');
		$(this).attr('link',href);
		$(this).attr('data-target','#myModal');
		$(this).attr('data-toggle','modal');
		$(selfObject).click(function(){
			var tableData = $(this).parent().parent().html();
			var tableHead = $(this).parent().parent().parent().parent().find('thead').html();
			var tableContent = '<table class="table">'+tableHead+'<tbody>'+tableData+'</tbody>'+'</table>';
			$('#myModalLabel').html('请你确认');
			$('#myModalBody').html(tableContent);
			//$('#myModalBody > table > thead > tr:last').hide();
			$('#myModalBody > table').find("th:last").remove();
			$('#myModalBody > table').find("td:last").remove();
			$('#modal-button-group').html('<a href="'+href+'" class="btn red">删除</a>');
			$(selfObject).modal({show:false});
		});
	});

	// 分页
	if(typeof pages != "undefined") {
		PPage("page-js-2",curpage,pages,"hoho2.go",true);
        hoho2.go=function(pageNum){
            if(window.location.search.indexOf('page=')!==-1){
                window.location = window.location.href.replace(/page=[0-9]+/,'page='+pageNum);
            }else{
            	//var url = window.location.href;
                window.location.href = window.location.href.indexOf("?") > 0 ? window.location.href+'&page='+pageNum :  window.location.href+'?page='+pageNum;
            }
            PPage("page-js-2",pageNum,pages,"hoho2.go",true);
        }
	}

	// ajax表单
	BASE.ajaxForm({});

	// checkbox
	BASE.checkboxSelect('js-checkbox1');

});