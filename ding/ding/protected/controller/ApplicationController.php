<?php
Doo::loadClassAt('PageAuth','default');
/**
 * 应用类(抽像类)
 * @author xinkq 
 */
abstract Class ApplicationController extends DooController {

	/**
	 * 标题
	 * @var string
	 */
	protected $_pageTitle = NULL;

	/**
	 * 资源后面?q数值
	 * @var string
	 */
	protected $_resourceVersion = '1.01';

	/**
	 * 用户类
	 *@var object
	 */
	protected $_user;

	/**
	 * 当前操作人
	 * @var string 
	 */
	protected $_opname = NULL;
	
	/**
	 * 权限控制
	 * @var object
	 */
	protected $_pageAuth;

	/**
	 * 检查是否登录
	 * @var bool
	 */
	protected $_checkIsLogin =  TRUE;//FALSE;

	/**
	 * 检查页面权限
	 * @var bool
	 */
	protected $_checkPageAuth = TRUE;//

	/**
	 * 访问的action
	 * @var string
	 */
	protected $_action = '';

	/**
	 * 检查方法对应权限列表
	 * @var array
	 */
	protected $_checkActionAuthList = array('add' => PageAuth::P_ADD,
		                                    'del' => PageAuth::P_DEL,
		                                    'dataTable' => PageAuth::P_VIEW,
		                                    'mod' => PageAuth::P_MOD,
		                                    'audit1' => PageAuth::P_AUDIT1,
		                                    'audit2' => PageAuth::P_AUDIT2,
		                                    'audit3' => PageAuth::P_AUDIT3,
		                                    'notauth' => PageAuth::P_NOTAUTH
		                                    );
	/**
	 * 角色列表
	 * @var array
	 */
	protected $_roleList = array('header' => 1,
	                             'masterAgent' => 2,
	                             'agent' => 3,
	                             'admin' => 4,
	                            );
	/**
	* 账号是否被锁
	**/
	public static $locked = array(
		0=> '否',
		1=> '锁定此账号',
	);


    /**
     * 当前访问页面对应权限
     * @var string
     */
    protected $_currentPageAuth = '';

   /**
	 * redis对象
	 */
	protected static $_redis = NULL;


	/**
	 * 引入JS
	 * @var array
	 */
	protected $_includeJsFileList = array(
			'bootstrap/media/js/jquery-1.10.1.min.js',
			'bootstrap/media/js/jquery-migrate-1.2.1.min.js',
			'bootstrap/media/js/jquery-ui-1.10.1.custom.min.js',
			'bootstrap/media/js/bootstrap.min.js',
			array('<!--[if lt IE 9]>'),
			'media/js/excanvas.min.js',
			'media/js/respond.min.js',
			array('<![endif]-->'),
			'bootstrap/media/js/jquery.slimscroll.min.js',
			'bootstrap/media/js/jquery.blockui.min.js',
			'bootstrap/media/js/jquery.cookie.min.js',
			'bootstrap/media/js/jquery.uniform.min.js',
			'bootstrap/media/js/jquery.form.min.js',
			'js/page.js',
			'js/tcaplus-base.js',
			// 'js/highcharts/highcharts.js',
			'js/datepicker/WdatePicker.js',
			'js/app.js',
			'bootstrap/media/js/app.js',
	);

	/**
	 * 引入css
	 * @var array
	 */
	protected $_includeCssFileList = array(
			'bootstrap/media/css/bootstrap.min.css',
			'bootstrap/media/css/bootstrap-responsive.min.css',
			'bootstrap/media/css/font-awesome.min.css',
			'bootstrap/media/css/style-metro.css',
			'bootstrap/media/css/style.css',
			'bootstrap/media/css/style-responsive.css',
			'bootstrap/media/css/light.css',
			'css/page.css',
	);

	/**
	 * 模版子目录
	 * @var string
	 */
	protected $_templateDefault = 'default';

	public function beforeRun($resource, $action) {
		header("Content-type:text/html;charset=utf-8");
		parent::beforeRun($resource, $action);
		Doo::loadClassAt('User','default');

		$this->_user = new User(DBproxy::getManage());
		
		$this->_pageAuth = new PageAuth();
		$this->_action = $action;

		if($this->_checkIsLogin) {
			if(!$this->_user->isLogin()) {
				$this->notLoginPage();
				exit;
			}
		} else {
			$this->_checkPageAuth = TRUE; // 关闭登录验证时，自动关闭页面权限验证
		}

		if($this->_checkPageAuth) {
			$this->_currentPageAuth = isset($this->_checkActionAuthList[$action]) ? $this->_checkActionAuthList[$action] : $this->_checkActionAuthList['notauth'];
			if(!$this->_pageAuth->auth($this->_currentPageAuth)) {
				$this->notAuthPage();
				exit;
			}
		}

		$this->_pageTitle = Doo::conf()->siteName;
		$this->_opname = $this->_user->getUsername();
		$this->init();

	}

	/**
	 * 默认初始方法
	 * @return void
	 */
	public function init() {}

	/**
	 * 后置控制器
	 * @param  [type] $routeResult [description]
	 * @return [type]              [description]
	 */
	public function afterRun($routeResult) {
		parent::afterRun($routeResult);		
		switch ($this->_action) {
			case 'add':
			case 'del':
			case 'mod':
				Doo::logger()->info(' ip:'.getIP().' user:'.$this->_opname,$this->_action);
				break;
			default:
				Doo::logger()->info(' ip:'.getIP().' user:'.$this->_opname);
				break;
		}
		
	}

	/**
	 * 布局
	 * @param string 模版文件
	 * @param array 输入模版数据
	 * @param string layoutFile 布局文件
	 */
	public function layoutRender($filePath,$data = array(),$layoutFile = 'layout') {
		// ob_clean();
		ob_start();
		$data['pageTitle'] = isset($data['pageTitle']) ? $data['pageTitle'] : $this->_pageTitle;
		$data['includeJsAndCss'] = $this->getJsAndCss();
		$data['controller'] = $this;
		$this->renderc($this->_templateDefault.'/'.$filePath,$data);
		$content = ob_get_contents();
		ob_end_clean();
		$data['content'] = $content;
		$this->renderc($this->_templateDefault.'/'.$layoutFile,$data);
	}

	/**
	 * 布局
	 * @param string content 内容
	 * @param array 输入模版数据
	 * @param string layoutFile 布局文件
	 */
	public function contentlayoutRender($content,$data = array(),$layoutFile = 'layout') {
		$data['pageTitle'] = isset($data['pageTitle']) ? $data['pageTitle'] : $this->_pageTitle;
		$data['includeJsAndCss'] = $this->getJsAndCss();
		$data['content'] = $content;
		$this->renderc($this->_templateDefault.'/'.$layoutFile,$data);
	}
	
	/**
	 * ajax提交时固定返回格式
	 * @param boolen success
	 * @param array
	 * @param boolen 是否输出
	 * @return json
	 */
	public function ajaxFormResult($success,$errors,$output = true,$successCustomCallBack = '',$errorCustomCallBack = '') {
		sleep(1);
		$output = array('success' => $success,'errors' => $errors);
		if(!empty($successCustomCallBack)) {
			$output['successCustomCallBack'] = $successCustomCallBack;
		}

		if(!empty($errorCustomCallBack)) {
			$output['errorCustomCallBack'] = $errorCustomCallBack;
		}
		
		$output = json_encode($output);
		if($output) {
			// ob_clean();
			ob_start();
			echo $output;	
		} 

		return $output;
	}

	/**
	 * 显示没有登录的页面
	 * @return string html
	 */
	public function notLoginPage() {
		$a = adminAppUrl('in');
		echo '<!Doctype html><html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content="text/html;charset=utf-8"><title>请登录</title></head>
		<script type="text/javascript">
				function delayURL(url) {
					var delay = document.getElementById("time").innerHTML;
					if(delay > 0) {
						delay--;
						document.getElementById("time").innerHTML = delay;
					} else {
						window.top.location.href = url;
					}
					setTimeout("delayURL(\'" + url + "\')", 1000);
				}
			</script>
			<span id="time"><b>3</b></span>秒钟后自动跳转，如果不跳转，请点击下面的链接<a href="'.$a.'">登录</a>
			<script type="text/javascript">
			delayURL("'.$a.'");
			</script></html>';
	}

	/**
	 * 显示没有权限的页面
	 * @return string
	 */
	public function notAuthPage() {
		echo 'Not permission!';
	}

	/**
	 * 提示
	 * @param string msg
	 * @param string ERRORS\ERROR\WARN\INFO\SUCCESS
	 * @return string html
	 */
	public function alert($msg,$status = 'ERROR',$redirect = true,$redirectUrl = '',$time = 4) {
		
		//echo 'Not permission!';
		$status = strtoupper($status);
		
		if($this->isAjax() && $_POST) {
			return $this->ajaxFormResult(false,$msg);
		}

		$redirectHtml = '';
		if($redirect) {
			if($redirectUrl == '') {
				$redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
			}

			$redirectHtml = '<script type="text/javascript">
				function delayURL(url) {
					var delay = parseInt(document.getElementById("stime").innerHTML);
					if(delay > 0) {
						delay--;
						document.getElementById("stime").innerHTML = delay;
					} else {
						location.href = url;
					}
					setTimeout("delayURL(\'" + url + "\')", 1000);
				}
			</script>
			<div> <span id="stime" class="label">'.$time.'</span>秒钟后自动跳转，如果不跳转，请点击下面的链接<a class="label label-info" href="'.$redirectUrl.'">跳转链接</a></div>
			<script type="text/javascript">
			delayURL("'.$redirectUrl.'");
			</script>';
		}

		switch ($status) {
			case 'ERRORS':
			case 'ERROR':
				$msg = "<div class=\"alert alert-error\"><i class=\"icon-remove\"></i>{$msg}</div>";
				break;
			case 'WARN':
				$msg = "<div class=\"alert alert-warning\"><i class=\"icon-warning-sign\"></i>{$msg}</div>";
				break;
			case 'INFO':
				$msg = "<div class=\"alert alert-info\"><i class=\"icon-info-sign\"></i>{$msg}</div>";
				break;
			case 'SUCCESS':
			default:
				$msg = "<div class=\"alert alert-success\"><i class=\"icon-ok\"></i>{$msg}</div>";
				break;
		}
		$this->contentlayoutRender($msg.$redirectHtml);
	}
	
	/**
	 * php跳转
	 * @param string url地址
	 * @return string
	 */
	public function errors($url) {
		echo header('Location:'.$url);
		exit;
	}

	/**
	 * js跳转
	 * @param string url地址
	 * @return string
	 */
	public function jsRedirect($url) {
		echo '<script>location.href="'.$url.'"</script>';
		exit;
	}


	/**
	 * 生成js css
	 * @return array js,css
	 */
	public function getJsAndCss() {
		$js = $css = '';
		foreach ($this->_includeCssFileList as $val) {
			if(!is_array($val)) {
				$css .= '<link href="'.Doo::conf()->global.$val.'?v='.$this->_resourceVersion.'" rel="stylesheet" type="text/css"/>'."\n";
			} else {
				$css .= current($val);
			}
		}

		foreach ($this->_includeJsFileList as $val) {
			if(!is_array($val)) {
				$js .= '<script src="'.Doo::conf()->global.$val.'?v='.$this->_resourceVersion.'" type="text/javascript" ></script>'."\n";
			} else {
				$js .= current($val);
			}
		}
		return array($js,$css);
	}

	/**
	 * 取url变量
	 * @param  string $key    
	 * @param  mixed $default 默认值
	 */
	public function getUrlVar($key,$default=NULL) {

		if(isset($_GET[$key])) {
			return $_GET[$key];
		}
		if(isset($_POST[$key])) {
			return $_POST[$key];
		}
		if(isset($this->params[$key]) ) {
			return $this->params[$key];
		}
		return $default;
	}
	
	/**
	 * 获取POST参数数据
	 * @param string $key
	 */
	public function post($key) {
		$defval = '';
		
		if(isset($_POST[$key])) {
			$defval = $_POST[$key];
		}
		
		return $defval;
	}
	
	/**
	 * 获取GET参数数据
	 * @param string $key
	 */
	public function get($key) {
		$defval = '';
	
		if(isset($_GET[$key])) {
			$defval = $_GET[$key];
		}
	
		return $defval;
	}

	/**
	 * 设定cache key
	 * @param  string $key    
	 * @param  mixed $default 默认值
	 * @param  string $keyPrefix key字段前缀
	 */
	public function setCacheDataByGetVar($key,$value,$keyPrefix='') {
		$key = 'cache_'.$keyPrefix.$key;
		$_SESSION[$key] = $value;
	}

	/**
	 * 缓存urlkey
	 * @param  string $key     
	 * @param  mixed $default 默认值
	 * @param  string $keyPrefix key字段前缀
	 * @return mixed
	 */
	public function getCacheUrlVar($key,$default=NULL,$keyPrefix='') {
		$var = $this->getUrlVar($key,$default);
		if($default == NULL) {
			if($var != NULL) {
				$this->setCacheDataByGetVar($key,$var,$keyPrefix);
			}
			$key = 'cache_'.$keyPrefix.$key;
			return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
		}
		return $var;
	}

	/**
	 * 取得当前分页值
	 * @return integer
	 */
	public function getCurPage() {
		return isset($_GET['page']) ? (intval($_GET['page'])>=1 ? intval($_GET['page']) : 1) : 1;
	}

	/**
	 * js 分页生成
	 * @param  integer $total  
	 * @param  integer $curpage 当前页
	 * @return string html
	 */
	public function pager($total,$curpage = null) {
		$curpage = $curpage == null ? $this->getCurPage() : $curpage;
		$pages = ceil($total / Doo::conf()->pagesize);
		if($total == 0) {
			return;
		}
		
		//最大页兼容
		if($curpage > $pages) {
			$curpage = $pages;
		}
		
		$output = '<div class="page-wrapper">
        <div class="page-shaper of" style="width:auto;">
            <div class="wrapper inb ml">
                <div class="page mb20 dataTables_paginate paging_bootstrap pagination" id="page-js-2"></div>
                <script type="text/javascript">
					var curpage = '.$curpage.';
					var pages = '.$pages.';
                </script>
            </div>
        </div>
    </div>';
    	return $output;
	}

    //获取当前页面完整URL
    public function curPageURL() {
    	$pageURL = 'http';
    
    	if (isset ( $_SERVER ["HTTPS"] ) && $_SERVER ["HTTPS"] == "on") {
    		$pageURL .= "s";
    	}
    	$pageURL .= "://";
    
    	if (isset ( $_SERVER ["SERVER_PORT"] ) && $_SERVER ["SERVER_PORT"] != "80") {
    		$pageURL .= $_SERVER ["SERVER_NAME"] . ":" . $_SERVER ["SERVER_PORT"] . $_SERVER ["REQUEST_URI"];
    	} else {
    		$pageURL .= $_SERVER ["SERVER_NAME"] . $_SERVER ["REQUEST_URI"];
    	}    	
    	return $pageURL;
    }

    
	/**
	 * 实例化redis
	 * @return void
	 */
	public function getObjRedis() {
		if (!self::$_redis) {
			self::$_redis = new Redis();
			self::$_redis->connect(DOO::conf()->redis[0],DOO::conf()->redis[1]);//'127.0.0.1','6379'
		}
	}


	
	public function allurl(){	
		Doo::loadCore('app/DooSiteMagic');
		DooSiteMagic::showAllUrl();	
	}
	
    public function debug(){
		Doo::loadCore('app/DooSiteMagic');
		DooSiteMagic::showDebug($this->params['filename']);
    }
	
	public function gen_sitemap_controller(){
		//This will replace the routes.conf.php file
		Doo::loadCore('app/DooSiteMagic');
		DooSiteMagic::buildSitemap(true);		
		DooSiteMagic::buildSite();
	}
	
	public function gen_sitemap(){
		//This will write a new file,  routes2.conf.php file
		Doo::loadCore('app/DooSiteMagic');
		DooSiteMagic::buildSitemap();		
	}
	
	public function gen_site(){
		Doo::loadCore('app/DooSiteMagic');
		DooSiteMagic::buildSite();
	}
	
    public function gen_model(){
        Doo::loadCore('db/DooModelGen');
        global $dbconfig;
        foreach ($dbconfig as $key => $value) {
        	$path = Doo::conf()->SITE_PATH . Doo::conf()->PROTECTED_FOLDER . 'model/' .$key;
        	if(!is_dir($path)) {
        		mkdir($path);
        	}
        	$path .= '/';
        	Doo::db()->setDb($dbconfig,$key);
        	Doo::db()->reconnect($key);
        	DooModelGen::genMySQL($comments=true, $vrules=true, 
        		$extends='DooModel', 
        		$createBase=true, 
        		$baseSuffix='Base', 
        		$useAutoload=false,
        		$chmod=null,
        		$path,$dbname = ucfirst($key));
        	//exit;
        }
        
    }
}
