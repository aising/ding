<!DOCTYPE html>

<!--[if IE 8]> <html lang="en" class="ie8"> <![endif]-->

<!--[if IE 9]> <html lang="en" class="ie9"> <![endif]-->

<!--[if !IE]><!--> <html lang="en"> <!--<![endif]-->

<!-- BEGIN HEAD -->

<head>

	<meta charset="utf-8" />

	<title><?php echo Doo::conf()->siteName?></title>

	<meta content="width=device-width, initial-scale=1.0" name="viewport" />

	<meta content="" name="description" />

	<meta content="" name="author" />

	<!-- BEGIN GLOBAL MANDATORY STYLES -->

	<link href="<?php echo Doo::conf()->global?>bootstrap/media/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>

	<link href="<?php echo Doo::conf()->global?>bootstrap/media/css/bootstrap-responsive.min.css" rel="stylesheet" type="text/css"/>

	<link href="<?php echo Doo::conf()->global?>bootstrap/media/css/font-awesome.min.css" rel="stylesheet" type="text/css"/>

	<link href="<?php echo Doo::conf()->global?>bootstrap/media/css/style-metro.css" rel="stylesheet" type="text/css"/>

	<link href="<?php echo Doo::conf()->global?>bootstrap/media/css/style.css" rel="stylesheet" type="text/css"/>

	<link href="<?php echo Doo::conf()->global?>bootstrap/media/css/style-responsive.css" rel="stylesheet" type="text/css"/>

	<link href="<?php echo Doo::conf()->global?>bootstrap/media/css/light.css" rel="stylesheet" type="text/css" id="style_color"/>


	<!-- END GLOBAL MANDATORY STYLES -->

	<link rel="shortcut icon" href="<?php echo Doo::conf()->global?>bootstrap/media/image/favicon.ico" />

</head>

<!-- END HEAD -->

<!-- BEGIN BODY -->

<body class="page-header-fixed">

	<!-- BEGIN HEADER -->

	<div class="header navbar navbar-inverse navbar-fixed-top">

		<!-- BEGIN TOP NAVIGATION BAR -->

		<div class="navbar-inner">

			<div class="container-fluid">

				<!-- BEGIN LOGO -->

				<a class="brand" href="<?php echo $_SERVER['PHP_SELF']?>">

				<!--<img src="<?php echo Doo::conf()->global?>bootstrap/media/image/logo.png" alt="logo" />-->
				<span><?php echo Doo::conf()->siteName?></span>

				</a>

				<!-- END LOGO -->

				<!-- BEGIN HORIZANTAL MENU -->

				<div class="navbar hor-menu hidden-phone hidden-tablet">

					<div class="navbar-inner">

						<ul class="nav">
							<?php $topnav = $data['menu']->getCurrent(0)?>			
							<?php foreach($topnav as $key => $val):?>
							<?php $data['top'] = $data['top'] == -1 ? $val['id'] : $data['top'];?>
							<li class="<?php echo $data['top'] == $val['id'] ? 'active':''?>">

								<a href="<?php echo $_SERVER['PHP_SELF'].'?m=index&c=main&top='.$val['id']?>">

								<?php echo $pname = $val['name']?>

								<span class="<?php echo $data['top'] == $val['id'] ? 'selected':''?>"></span>

								</a>

							</li>
													
							<?php endforeach;?>
						</ul>

					</div>

				</div>



				<!-- END HORIZANTAL MENU -->

				<!-- BEGIN RESPONSIVE MENU TOGGLER -->

				<a href="javascript:;" class="btn-navbar collapsed" data-toggle="collapse" data-target=".nav-collapse">

				<img src="<?php echo Doo::conf()->global?>bootstrap/media/image/menu-toggler.png" alt="" />

				</a>          

				<!-- END RESPONSIVE MENU TOGGLER -->            

				<!-- BEGIN TOP NAVIGATION MENU -->              

				<ul class="nav pull-right">

					<!-- BEGIN USER LOGIN DROPDOWN -->
					<li><a href="/admin.php?m=prop_reissue&c=search&isajax=1" target="mainFrame">检索用户</a></li>
					<li class="dropdown user">

						<a href="#" class="dropdown-toggle" data-toggle="dropdown">

						<img alt="" src="<?php echo Doo::conf()->global?>bootstrap/media/image/ave.jpg" />

						<span class="username"><?php echo $data['userinfo']['name'] ?></span>

						<i class="icon-angle-down"></i>

						</a>

						<ul class="dropdown-menu">
							<?php $topnav = $data['menu']->getCurrent(0)?>			
							<?php foreach($topnav as $key => $val):?>
							<?php $data['top'] = $data['top'] == -1 ? $val['id'] : $data['top']?>
							<li class="<?php echo $data['top'] == $val['id'] ? 'active':''?> visible-phone visible-tablet">

								<a href="<?php echo $_SERVER['PHP_SELF'].'?m=index&c=main&top='.$val['id']?>">

								<?php echo $pname = $val['name']?>

								<span class="<?php echo $data['top'] == $val['id'] ? 'selected':''?>"></span>

								</a>

							</li>
													
							<?php endforeach;?>

							<li><a href="">角色:<?php echo $data['role_name_s'] ?></a></li>
							<!--<li><a href="">上次登录时间:<?php echo $data['userinfo']['last_logon_time'] ?></a></li>
							<li><a href="extra_lock.html"><i class="icon-lock"></i>锁屏</a></li>-->

							<li><a href="<?php echo Doo::conf()->APP_URL.Doo::conf()->routeIndexFile.'/login/out'?>"><i class="icon-key"></i>注销</a></li>


						</ul>
					</li>

					<!-- END USER LOGIN DROPDOWN -->

				</ul>

				<!-- END TOP NAVIGATION MENU --> 

			</div>

		</div>

		<!-- END TOP NAVIGATION BAR -->

	</div>

	<!-- END HEADER -->

	<!-- BEGIN CONTAINER -->   

	<div class="page-container row-fluid" >

		<!-- BEGIN HORIZONTAL MENU PAGE SIDEBAR1 -->

		<div class="page-sidebar nav-collapse collapse">

			<!-- BEGIN SIDEBAR TOGGLER BUTTON -->

			<div class="sidebar-toggler hidden-phone" style="margin-bottom:20px;"></div>

			<!-- BEGIN SIDEBAR TOGGLER BUTTON -->
			<?php
			function createSubMenu($menu,$pid,$pname) {
				$html = '';
				$subData = $menu->getChilds($pid);
				if(!empty($subData)) {
					$html .= '<ul class="sub-menu">';
						foreach($subData as $key => $val) {
							$subsub = '';
							$subsub = createSubMenu($menu,$val['id'],$pname.'_'.$val['name']);
							$target = empty($val['page_url']) ? '' : 'target="mainFrame"';
							$isarrow = !empty($subsub) ? '<span class="arrow"></span>' : '';
							if(empty($val['page_url'])) {
								$a = 'javascript::';
							} else {
								if((strpos($val['page_url'],'/')) === 0) { // 跳转到新的框架代码
									$a = Doo::conf()->APP_URL.Doo::conf()->routeIndexFile.$val['page_url'];
								} else if((strpos($val['page_url'],':')) === 0) { // 跳转到新页面
									$a = Doo::conf()->APP_URL.Doo::conf()->routeIndexFile.substr($val['page_url'],1,strlen($val['page_url']));
									$target = empty($val['page_url']) ? '' : 'target="_blank"';
								} else { // 跳转到旧代码去
									$a = '/admin.php'.'?'.$val['page_url'];
								}
							}
							// $a = empty($val['page_url']) ? 'javascript::' : (strpos($val['page_url'],'/') === 0 ? 
							// 										 Doo::conf()->APP_URL.Doo::conf()->routeIndexFile.$val['page_url'] : 
							// 										'/admin.php'.'?'.$val['page_url']);
							//$a = '';
							
							$title = $val['type_id'] ==1 ? ' title="'.$pname.'_'.$val['name'].'" ': '' ;
							$html .= '<li><a href="'.$a.'" '.$target.' '.$title.'">'.$val['name'].$isarrow.'</a>'.$subsub .'</li>';
						}
					$html .='</ul>';

				}
				return $html;
				
			}
			?>

			
			<ul class="page-sidebar-menu hidden-phone hidden-tablet">
				
				<?php foreach($data['menu']->getChilds($data['top']) as $k => $v):?>
				<li class="start">
					<?php 
					$a = empty($v['page_url']) ? 'javascript::' : (strpos($v['page_url'],'/') === 0 ? 
																	 Doo::conf()->APP_URL.Doo::conf()->routeIndexFile.$v['page_url'] : 
																	'/admin.php'.'?'.$v['page_url']);
					$target = empty($v['page_url']) ? '' : 'target="mainFrame"';
					$title = $v['type_id'] ==1 ? ' title="'.$pname.'_'.$v['name'].'" ': '' ;
					?>
					<a href="<?php echo $a?>" <?php echo $target?> <?php echo $title?>>

					<i class="icon-cogs"></i> 

					<span class="title"><?php echo $v['name']?></span>

					<!--<span class="selected "></span>-->
					<?php $subData = $data['menu']->getChilds($v['id']);?>
		
					<?php echo !empty($subData) ? '<span class="arrow open"></span>' : '';?>
					</a>
					<?php echo createSubMenu($data['menu'],$v['id'],$v['name']) ?>
				</li>
				<?php endforeach?>
			</ul>

			<!--HORIZONTAL AND SIDEBAR MENU FOR MOBILE & TABLETS-->

			<ul class="page-sidebar-menu visible-phone visible-tablet">


				<?php foreach($data['menu']->getChilds($data['top']) as $k => $v):?>
				<li class="start">
					<?php 
					$a = empty($v['page_url']) ? 'javascript::' : $_SERVER['PHP_SELF'].'?'.$v['page_url'];
					$target = empty($v['page_url']) ? '' : 'target="mainFrame"';
					$title = $v['type_id'] ==1 ? ' title="'.$pname.'_'.$v['name'].'" ': '' ;
					?>
					<a href="<?php echo $a?>" <?php echo $target?> <?php echo $title?>>

					<i class="icon-cogs"></i> 

					<span class="title"><?php echo $v['name']?></span>

					<!--<span class="selected "></span>-->
					<?php $subData = $data['menu']->getChilds($v['id']);?>
		
					<?php echo !empty($subData) ? '<span class="arrow open"></span>' : '';?>
					</a>
					<?php echo createSubMenu($data['menu'],$v['id'],$v['name']) ?>
				</li>
				<?php endforeach?>

			</ul>

		</div>

		<!-- END BEGIN HORIZONTAL MENU PAGE SIDEBAR -->

		<!-- BEGIN PAGE -->

		<div class="page-content">

			<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->

			<div id="portlet-config" class="modal hide">

				<div class="modal-header">

					<button data-dismiss="modal" class="close" type="button"></button>

					<h3></h3>

				</div>

				<div class="modal-body">

					<p></p>

				</div>

			</div>

			<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->

			<!-- BEGIN PAGE CONTAINER-->

			<div class="container-fluid">

				<!-- BEGIN PAGE HEADER-->

				<div class="row-fluid">

					<div class="span12">						

						<!-- BEGIN PAGE TITLE & BREADCRUMB-->

						<h3 class="page-title">

							 <small></small>

						</h3>

						<ul class="breadcrumb">

							<li>

								<i class="icon-home"></i>

								<a href="javascript:void(0)">Home</a> 

								<i class="icon-angle-right"></i>

							</li>

							<li>

								<a href="javascript:void(0)"></a>

								<!--<i class="icon-angle-right"></i>-->

							</li>

							<li><a href="javascript:void(0)"></a></li>

						</ul>

						<!-- END PAGE TITLE & BREADCRUMB-->

					</div>

				</div>

				<!-- END PAGE HEADER-->

				<!-- BEGIN PAGE CONTENT-->

				<div class="row-fluid margin-bottom-20">
					<iframe id="mainFrame" name="mainFrame" onload="javascript:iframeOnload(this)" width="100%" height="100%" scrolling="no" src="<?php echo $data['welcome']?>" frameborder="0"></iframe>
				</div>

				<!-- END PAGE CONTENT-->

			</div>

			<!-- END PAGE CONTAINER--> 

		</div>

		<!-- END PAGE -->    

	</div>

	<!-- END CONTAINER -->

	<!-- BEGIN FOOTER -->

	<div class="footer">

		<div class="footer-inner">
			<!--copyright-->

		</div>

		<div class="footer-tools">

			<span class="go-top">

			<i class="icon-angle-up"></i>

			</span>

		</div>

	</div>

	<!-- END FOOTER -->

	<!-- BEGIN JAVASCRIPTS(Load javascripts at bottom, this will reduce page load time) -->

	<!-- BEGIN CORE PLUGINS -->

	<script src="<?php echo Doo::conf()->global?>bootstrap/media/js/jquery-1.10.1.min.js" type="text/javascript"></script>

	<script src="<?php echo Doo::conf()->global?>bootstrap/media/js/jquery-migrate-1.2.1.min.js" type="text/javascript"></script>

	<!-- IMPORTANT! Load jquery-ui-1.10.1.custom.min.js before bootstrap.min.js to fix bootstrap tooltip conflict with jquery ui tooltip -->

	<script src="<?php echo Doo::conf()->global?>bootstrap/media/js/jquery-ui-1.10.1.custom.min.js" type="text/javascript"></script>      

	<script src="<?php echo Doo::conf()->global?>bootstrap/media/js/bootstrap.min.js" type="text/javascript"></script>

	<!--[if lt IE 9]>

	<script src="media/js/excanvas.min.js"></script>

	<script src="media/js/respond.min.js"></script>  

	<![endif]-->   

	<script src="<?php echo Doo::conf()->global?>bootstrap/media/js/jquery.slimscroll.min.js" type="text/javascript"></script>

	<script src="<?php echo Doo::conf()->global?>bootstrap/media/js/jquery.blockui.min.js" type="text/javascript"></script>  

	<script src="<?php echo Doo::conf()->global?>bootstrap/media/js/jquery.cookie.min.js" type="text/javascript"></script>

	<script src="<?php echo Doo::conf()->global?>bootstrap/media/js/jquery.uniform.min.js" type="text/javascript" ></script>

	<!-- END CORE PLUGINS -->

	<script src="<?php echo Doo::conf()->global?>bootstrap/media/js/app.js"></script>      

	<script>
		var interval = 0;
		var iframeObj = null;
		function autoHeight(){
			if(mainFrame.document.body && mainFrame.document.body.scrollHeight){
				iframeObj.height = mainFrame.document.body.scrollHeight + 350;
				$(mainFrame.document.body).scrollTop(0);
			} else {
				iframeObj.height = "1200px";
			}
		}
		function iframeOnload(_this){
			iframeObj = _this;
			clearInterval(interval);
			autoHeight();
			interval = window.setTimeout(function(){
				autoHeight();
			},500);
		}

	/*
		function iFrameHeight() {   
			var ifm= document.getElementById("mainFrame");   
			var subWeb = document.frames ? document.frames["mainFrame"].document : ifm.contentDocument;   
			if(ifm != null && subWeb != null) {
			   ifm.height = subWeb.body.scrollHeight;
			}   
		}  

	*/

		jQuery(document).ready(function() {    
		   App.init();
		});

	</script>

	<!-- END JAVASCRIPTS -->

<!-- END BODY -->

</html>