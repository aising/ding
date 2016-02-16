<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width; initial-scale=1.0,user-scalable=no"><!--去除放大页面-->
<meta http-equiv="Pragma" content="no-cache"> <!--禁止浏览器缓存-->
<?php
$styleVer = '1.17';
?>
<title><?php echo $data['pageTitle']?> 微微乐 </title>
<link type="text/css" href="<?php echo Doo::conf()->global?>default/css/index.css?v=<?php echo $styleVer;?>" rel="stylesheet" />
<link type="text/css" href="<?php echo Doo::conf()->global?>css/page.css?v=<?php echo $styleVer;?>" rel="stylesheet" />


</head>

<body>

	<?php echo $data['content'];?>	

</body>

</html>
<script type="text/javascript" src="<?php echo Doo::conf()->global?>bootstrap/media/js/jquery-1.10.1.min.js?v=<?php echo $styleVer;?>"></script>
<script type="text/javascript" src="<?php echo Doo::conf()->global?>js/page.js?v=<?php echo $styleVer;?>"></script>

<script>
function ai(){};
$(document).ready(function(){

	// 分页
	if(typeof pages != "undefined") {
		PPage("page-js-2",curpage,pages,"ai.go",true);
        ai.go=function(pageNum){
            if(window.location.search.indexOf('page=')!==-1){
                window.location = window.location.href.replace(/page=[0-9]+/,'page='+pageNum);
            }else{
            	//var url = window.location.href;
                window.location.href = window.location.href.indexOf("?") > 0 ? window.location.href+'&page='+pageNum :  window.location.href+'?page='+pageNum;
            }
            PPage("page-js-2",pageNum,pages,"ai.go",true);
        }
	}

	$('.choose .sub').click(function(){
			var valsub = $(this).next().val();
			if(valsub > 1){
			    $(this).next().val($(this).next().val()-1);
                            var pp =  parseFloat($("#cartPrice").html()) - $(this).attr('price');
                            $("#cartPrice").html( pp.toFixed(2) );
			}
	});
	$('.choose .add').click(function(){
		var valadd = $(this).prev().prev().val();
		$(this).prev().prev().val(++valadd);
                var ppp = parseFloat($("#cartPrice").html()) + parseFloat($(this).attr('price'))
                $("#cartPrice").html(ppp.toFixed(2) );
	});



    //发验证码
    $('#getCheckNo').click(function(){
        var phone = $("#phone").val();
        if( phone == '' ){
            alert('请填写手机号码！');
            return false;
        }
        $('#getCheckNo').attr('disabled','disabled');
        $.ajax({
           type: "POST",
           cache:'false',
           url: "<?php echo appurl('sendSMSCheckNO');?>",
           data: {phone:phone},
           success: function(data){                
                if(data != '发送成功'){
                    alert('提示：'+data);
                    $('#getCheckNo').removeAttr('disabled');
                }else if(data == '发送成功'){
                    alert(data+'，请注意接收验证码。');
                    //显示输入验证码的框
                    $(".check6No").attr("style","display:block;  height: 65px; clear:both;margin-bottom: 50px;    ");
                    $(".btel").attr("style","height: 50px;");
                    $("#phone").attr('readonly','readonly');
					$('#getCheckNo').remove();
					
                }
            }
        }); 

    });
    //验证手机是否本人
    $('#checkNo').click(function(){        
        var phoneCheckNo = $("#phoneCheckNo").val();        
        if( phoneCheckNo == '' ){
            alert('请填写收到的验证码！');
            return false;
        }
        $('#checkNo').attr('disabled','disabled');

        $.ajax({
           type: "POST",
           cache:'false',
           url: "<?php echo appurl('SMSCheckNO');?>",
           data: {phoneCheckNo:phoneCheckNo,phone:$("#phone").val()},
           success: function(data){                
                if(data == 'ok'){
                    //输入框关闭
                    $("#phone").attr('readonly','readonly');
                    $("#getCheckNo").remove();
                    $(".check6No").html('<p style="text-align: center;">手机号码验证成功</p>');
                    //开放提交
                    $(".account").attr('type','submit');
                    $(".account").attr("style","background-color:#fff");
                }else if( data == '验证码已经失效'){
                    alert(data);
                    window.location.reload();
                }else if( data == '验证码不对'){
                    alert(data);
                    $('#checkNo').removeAttr('disabled');
                }
            }
        }); 

    });
    //#checkNo

    //购物车删除
    $('.delCart').click(function(){
        var bookid = $(this).attr('bookId');        
        $.ajax({
           type: "POST",
           cache:'false',
           url: "<?php echo appurl('cartDel');?>",
           data: {id:bookid},
          success: function(data){
                if(data == 1){                    
                    window.location.reload();
                }else{
                    window.location.href="<?php echo appurl('index');?>";
                }
            }
        });
    });
	//!#delCart
	//不到时间的外卖提示
	$('.waimaiNote').click(function(){
		if($(this).attr("note")!='')
                {
                 alert($(this).attr("note"));
                }
	});
});

</script>

