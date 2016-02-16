<?php
Doo::loadClassAt('Menu','default');
Doo::loadClassAt('Role','default');
Doo::loadClassAt('User','default');

$_SERVER['REDIRECT_URL'] = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : '';
$role = new role(DBproxy::getManage());
$user = new user(DBproxy::getManage());
$data['userinfo'] = $userinfo = $user->getUserInfo();

$roles = $role->get_roles();

$data['menu'] = new menu(DBproxy::getManage());

$role_name =  array();

if(!empty($userinfo['roleids'])){
	foreach($userinfo['roleids'] as $roleid){
		$role_names[] = $roles[$roleid];
	}
}

$role_name = $role_name_s = !empty($role_names) ? implode(', ', $role_names) : '';

if(!empty($role_names) && count($role_names) > 1){
	$role_name_s = $role_names[0].'...';
} else {
	$role_name_s = $role_names[0];
}
//顶部导航index
$data['top'] = isset($_GET['top']) ? $_GET['top'] : -1;
if(isset($_GET['top'])) {
	$data['top'] = $_SESSION['menu_top'] = $_GET['top'] ;
} else {
	$data['top'] = isset($_SESSION['menu_top']) ? $_SESSION['menu_top'] : $data['menu']->getTop();
}

$data['role_name_s'] = $role_name_s;
$data['menu']->SetAuthory($_SESSION['authory']);
$pageTitle = $data['menu']->getPageTitle($_SERVER['PATH_INFO']);
$navigator = $data['menu']->getNavigator();

$navigatorText = '';
if(!empty($navigator)) {
	$count = count($navigator);
	foreach ($navigator as $k => $val) {
		$t = $count - 1 > $k ? '<i class="icon-angle-right"></i>' : '';
		$navigatorText .= '<li><a href="javascript::">'.$val['menu_name'].'</a>'.$t.'</li>';
	}
}

?>
<!DOCTYPE html>

<!--[if IE 8]> <html lang="en" class="ie8"> <![endif]-->

<!--[if IE 9]> <html lang="en" class="ie9"> <![endif]-->

<!--[if !IE]><!--> <html lang="en"> <!--<![endif]-->

<!-- BEGIN HEAD -->

<head>

	<meta charset="utf-8" />

	<title><?php echo $data['pageTitle']?></title>

	<meta content="width=device-width, initial-scale=1.0" name="viewport" />

	<meta content="manage" name="description" />

	<meta content="webdev" name="author" />

	<!-- BEGIN GLOBAL MANDATORY STYLES -->
	<?php echo $data['includeJsAndCss'][1];?>
	<!-- END GLOBAL MANDATORY STYLES -->

	<link rel="shortcut icon" href="<?php echo Doo::conf()->global?>bootstrap/media/image/favicon.ico" />
	<script type="text/javascript">
        var BASEURL = "<?php echo Doo::conf()->APP_URL?>";
        var APPURL = "<?php echo adminAppurl('')?>";
    </script>
</head>
 <!-- 翻译工具 
<div id="google_translate_element"></div><script type="text/javascript">
function googleTranslateElementInit() {
   new google.translate.TranslateElement({pageLanguage: 'zh-CN', layout: google.translate.TranslateElement.InlineLayout.SIMPLE, multilanguagePage: true}, 'google_translate_element');
}
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
-->

<!-- END HEAD -->

<!-- BEGIN BODY -->

<!-- <body class="page-header-fixed">
	<div class="navbar navbar-fixed-top yellow">
		<span class="form-loading"></span>
	</div>
    

</body> -->

<body class="page-header-fixed">

	<!-- BEGIN HEADER -->

	<div class="header navbar navbar-inverse ">

		<!-- BEGIN TOP NAVIGATION BAR -->

		<div class="navbar-inner">

			<div class="container-fluid">

				<!-- BEGIN LOGO -->

				<a class="brand" href="<?php echo adminAppurl('/')?>">

				<!--<img src="<?php echo Doo::conf()->global?>bootstrap/media/image/logo.png" alt="logo" />-->
				<span><?php echo Doo::conf()->siteName?></span>

				</a>

				<!-- END LOGO -->

				<!-- BEGIN HORIZANTAL MENU -->

				<div class="navbar hor-menu hidden-phone hidden-tablet">

					<div class="navbar-inner">

						<ul class="nav">
							<?php $topnav = $data['menu']->getCurrent(0);?>			
							<?php foreach($topnav as $key => $val):    ?>
							<?php $data['top'] = $data['top'] == -1 ? $val['id'] : $data['top'];?>
							<li class="<?php echo $data['top'] == $val['id'] ? 'active':''?>">

								<a href="<?php echo adminAppurl($val['url']) . '?m=index&c=main&top='.$val['id']?>">

								<?php echo $pname = $val['menu_name']?>

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
					<li class="dropdown user">

						<a href="#" class="dropdown-toggle" data-toggle="dropdown">
						<!-- <img alt="" src="<?php echo Doo::conf()->global?>bootstrap/media/image/ave.jpg" /> -->
						角色:<?php echo $data['role_name_s'] ?>
						<span class="username"><?php echo $data['userinfo']['username'] ?></span>
						<?php						
						if($data['userinfo']['roleids'][0] != Doo::conf()->adminRoleId){
						?>
							当前筹码：<span class="username"><?php echo User::getNowChips();?></span>
						<?php
						}
						?>
						<i class="icon-angle-down"></i>
						</a>

						<ul class="dropdown-menu">
							<?php $topnav = $data['menu']->getCurrent(0)?>			
							
							<?php foreach($topnav as $key => $val):?>
							<?php $data['top'] = $data['top'] == -1 ? $val['id'] : $data['top']?>
							<li class="<?php echo $data['top'] == $val['id'] ? 'active':''?> visible-phone visible-tablet">
								<a href="<?php echo adminAppurl($val['url']) . '?m=index&c=main&top='.$val['id']?>">
								<?php echo $pname = $val['menu_name']?>
								<span class="<?php echo $data['top'] == $val['id'] ? 'selected':''?>"></span>
								</a>
							</li>
							<?php endforeach;?>

							<li><a href="<?php echo adminAppurl('/system/userModPassword/modPassword?top=2151')?>"><i class="icon-user"></i>修改密码</a></li>

							<li><a href="<?php echo adminAppurl('/login/out'); ?>"><i class="icon-key"></i>注销</a></li>


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
			function pageCreateSubMenuUse($menu,$pid,$pname) {
				$html = '';
				$subData = $menu->getChilds($pid);
				if(!empty($subData)) {
					$html .= '<ul class="sub-menu">';
						foreach($subData as $key => $val) {
							$subsub = '';
							$subsub = pageCreateSubMenuUse($menu,$val['id'],$pname.'_'.$val['menu_name']);
							$isarrow = !empty($subsub) ? '<span class="arrow"></span>' : '';
							$a = adminAppurl(Doo::conf()->routeIndexFile.$val['url']);
							
							$active = $val['url'] == $_SERVER['REDIRECT_URL'] ? ' class="active" ': '';
							$target = empty($val['url']) ? '' : '';
							$title = $val['type_id'] ==1 ? ' title="'.$pname.'_'.$val['menu_name'].'" ': '' ;
							$html .= '<li '.$active.'><a href="'.$a.'" '.$target.' '.$title.'>'.$val['menu_name'].$isarrow.'</a>'.$subsub .'</li>';
						}
					$html .='</ul>';

				}
				return $html;
				
			}
			?>

			
			<ul id="siderbar" class="page-sidebar-menu hidden-phone hidden-tablet">
				
				<?php foreach($data['menu']->getChilds($data['top']) as $k => $v):?>
				<?php 
					$a = adminAppurl(Doo::conf()->routeIndexFile.$v['url']);
					$target = empty($v['url']) ? '' : '';
					$title = $v['type_id'] ==1 ? ' title="'.$pname.'_'.$v['menu_name'].'" ': '' ;
					if(!empty($_SERVER['REDIRECT_URL'])) {
						$active = $v['url'] == $_SERVER['REDIRECT_URL'] ? ' active ': '';
					} else {
						$active = '';
					}
					?>
				<li class="start  <?php echo $active?>">
					<a href="<?php echo $a?>" <?php echo $target?> <?php echo $title?>>

					<i class="icon-cogs"></i> 

					<span class="title"><?php echo $v['menu_name']?></span>

					<?php $subData = $data['menu']->getChilds($v['id']);?>
		
					<?php echo !empty($subData) ? '<span class="arrow open"></span>' : '';?>
					</a>
					<?php echo pageCreateSubMenuUse($data['menu'],$v['id'],$v['menu_name']) ?>
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
					$title = $v['type_id'] ==1 ? ' title="'.$pname.'_'.$v['menu_name'].'" ': '' ;
					?>
					<a href="<?php echo $a?>" <?php echo $target?> <?php echo $title?>>

					<i class="icon-cogs"></i> 

					<span class="title"><?php echo $v['menu_name']?></span>

					<!--<span class="selected "></span>-->
					<?php $subData = $data['menu']->getChilds($v['id']);?>
		
					<?php echo !empty($subData) ? '<span class="arrow open"></span>' : '';?>
					</a>
					<?php echo pageCreateSubMenuUse($data['menu'],$v['id'],$v['menu_name']) ?>
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
							 <?php echo $pageTitle;?>
							 <small></small>

						</h3>

						<ul class="breadcrumb">

							<li>

								<i class="icon-home"></i>

								<a href="javascript:void(0)">Home</a> 

								<i class="icon-angle-right"></i>
								<?php echo $navigatorText?>
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
					<?php echo $data['content']?>
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

		<div class="footer-tools">
			<p class="" style="text-align: right">
				<small><?php echo date("Y",time())?> @ copyright <?php echo Doo::conf()->siteName?> runTime:<b><?php echo round(Doo::benchmark($html=false),3);?> ms </b></small>&nbsp;
			</p>
		</div>
	</div>

	<?php echo $data['includeJsAndCss'][0];?>

	<script>

		jQuery(document).ready(function() {    
		   App.init();
		   	var temp = $('#siderbar').find('.active');
			temp.parent().addClass('open');
			temp.parent().addClass('active');
			temp.parent().show();
			temp.parent().parent().parent().addClass('open');
			temp.parent().parent().parent().show();
		});

	</script>
	<!-- END CORE PLUGINS -->
	<!-- Modal -->
	<div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" >
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="myModalLabel"></h3>
		</div>
		<div class="modal-body" id="myModalBody">
		<p>One fine body…</p>
		</div>
		<div class="modal-footer">
		<span id="modal-button-group"></span> &nbsp;&nbsp;
		<button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
		</div>
	</div>
</html>
