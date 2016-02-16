$(document).ready(function(){
	$("#commission-0").click(function (){
		$("#commissionInput-element").attr("disabled",'');
		$("#commissionInput-element").val('');

		$("#commission2-0").attr("disabled",'');
		$("#commission2Input-element").attr("disabled",'');
		$("#commission2Input-element").val('');
		$("#commission2-1").attr("disabled",'');

		$("#commission2Input-element").val('');
		$("#commission2-0").attr("checked",false);
		$("#commission2-1").attr("checked",false);
		//gamelist
		$("#gameListFee").find("input").attr("readonly",true);		
		$("#gameListFee").find("input").attr("key",function (){
			if($(this).attr("key")=="role_incom"){
				$(this).val('0.01');
			}
			if($(this).attr("key")=="effective_incom"){
				$(this).val('100');
			}
		});
		
	});

	$("#commission-1").click(function (){
		$("#commissionInput-element").removeAttr("disabled");
		$("#commission2-1").attr("disabled",'');
		$("#commission2-0").attr("disabled",'');
		$("#commission2Input-element").attr("disabled",'');

		$("#commission2Input-element").val('');
		$("#commission2-0").attr("checked",false);
		$("#commission2-1").attr("checked",false);

		//gamelist
		$("#gameListFee").find("input").attr("readonly",true);		
		$("#gameListFee").find("input").attr("key",function (){
			if($(this).attr("key")=="role_incom"){
				$(this).val('0.01');
			}
			if($(this).attr("key")=="effective_incom"){
				$(this).val('100');
			}
		});
	});


	$("#commission-2").click(function (){		
		$("#commission2-0").removeAttr("disabled");
		$("#commission2Input-element").removeAttr("disabled");
		$("#commission2-1").removeAttr("disabled");
		$("#commissionInput-element").val('');
	});


	$("#gameFeeEdit").click(function (){
		//选中才可以编辑游戏中的值
		if($("#commission2-1").attr("checked")=="checked"){
			$("#gameListFee").find("input").removeAttr("readonly").removeAttr("style");
		}
	});
	

});

function putRoleDifference (argument) {
	
}
function putEffectiveDifference (argument) {
	
}