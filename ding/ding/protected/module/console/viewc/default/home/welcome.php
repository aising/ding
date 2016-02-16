

<div class="">
	<div class="well">
    	<div id="curr-time">当前时间:<span></span></div>
    	<div id="curr-user"><?php echo $data['userinfo']['name']?>,欢迎进入管理中心!</div>
    </div>

</div>
<div class="line">&nbsp;</div>
<div class="main">
	<div class="content well">
		<ul>
    	<li><span class="label label-important">登录帐号:</span><span> <?php echo $data['userinfo']['username']?></span></li>
    	<li><span class="label label-important">真实姓名:</span><span> <?php echo $data['userinfo']['name']?></span></li>
    	<li><span class="label label-important">登录次数:</span><span> <?php echo $data['userinfo']['logonTimes']?></span></li>
    	<li><span class="label label-important">上次登录IP:</span><span> <?php echo $data['userinfo']['lastLoginIP']?></span></li>
    	<li><span class="label label-important">上次登录时间:</span><span> <?php echo date("Y-m-d H:i",$data['userinfo']['lastLoginTime'])?></span></li>
    	</ul>
    </div>

</div>


<script>
window.onload = function() {
		function getDateStr(){
			var d = new Date();
			var year = d.getFullYear();
			var month = d.getMonth() + 1;
			month = month < 10?"0"+month:month;
			var date = d.getDate();
			date = date < 10?"0"+date:date;
			var hour = d.getHours();
			hour = hour < 10?"0"+hour:hour;
			var minute = d.getMinutes();
			minute = minute < 10?"0"+minute:minute;
			var second = d.getSeconds();
			second = second < 10?"0"+second:second;
			return year+"-"+month+"-"+date+" "+hour+":"+minute+":"+second;
		}
		var $obj = $("#curr-time span");
		$obj.text(getDateStr());
		setInterval(function(){
			$obj.text(getDateStr());				 
		},1000);
			   
}
</script>