<?php
/**
 * MainController
 * Feel free to delete the methods and replace them with your own code.
 *
 * @author darkredz
 */
class MainController extends DooController{


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
     * 访问的action
     * @var string
     */
    protected $_action = '';

    public function index(){
		//Just replace these
		Doo::loadCore('app/DooSiteMagic');
		DooSiteMagic::displayHome();
    }

    public function cacheKV(){
        Doo::loadClassAt('KVCache','default');
        $KVCache = new KVCache();
        return $this->_redis = $KVCache->redis();
    }

    protected function kvExTime(){
        return 60;
    }
    
    //验证剩余数量是否足够
    //key 菜品id
    //value 订单信息  $value['sum'] 订的份数
    public function checkCanSum($key,$value){

        $params['id']  = intval($key);
        $book = DBproxy::getProcedure('Manage')->setDimension(2)->getBookList($params);
        
        if( isset($book[0]) && !empty($book[0])){            
            //售卖结束
            if(time() > strtotime($book[0]['endSaleTime'])){
                $this->alert($book[0]['name'].'已经售卖结束','ERROR',true,appurl('index'));
            }
            //预定结束时间
            $endtimeUninx = strtotime($book[0]['endtime']);
            if(time() > $endtimeUninx){
                $todayPreEndtimeBookOrder = DBproxy::getProcedure('Manage')->setDimension(2)->getTodayEndTimeOKBookSum($key,0,$endtimeUninx);
                //已经点了的份数
                $hasBookSum = intval($todayPreEndtimeBookOrder[0]['booksum']);
                $surplus = $book[0]['peiSongSum'] - $hasBookSum;
                //订完的提示
                if($surplus <= 0 ){
                    $this->alert($book[0]['name'].'已经供应完毕，明天请尽早下单~','ERROR',true,appurl('index'));exit;
                }
               
                //剩余份数提示
                if($value['sum'] > $surplus){
                    $this->alert($book[0]['name'].'还剩余'.$surplus.'份','ERROR',true,appurl('index'));exit;
                }
            }
            
        }else{
            $this->alert('菜品已经下架','ERROR',true,appurl('index'));exit;
        }
        return true;        
    }
    //验证剩余数量是否足够
    //key 菜品id
    //value 订单信息  $value['sum'] 订的份数
    public function ___checkCanSum($key,$value){

        $params['id']  = intval($key);
        $book = DBproxy::getProcedure('Manage')->setDimension(2)->getBookList($params);
        
        if( isset($book[0]) && !empty($book[0])){            
            //售卖结束
            if(time() > strtotime($book[0]['endSaleTime'])){
                $this->alert($book[0]['name'].'已经售卖结束');
            }
            //预定结束时间
            $endtimeUninx = strtotime($book[0]['endtime']);
            if(time() > $endtimeUninx){
                $todayPreEndtimeBookOrder = DBproxy::getProcedure('Manage')->setDimension(2)->getTodayEndTimeOKBookSum($key,0,$endtimeUninx);
                $hasBookSum = intval($todayPreEndtimeBookOrder[0]['booksum']);                
                if($value['sum'] > $book[0]['peiSongSum'] - $hasBookSum){
                    $this->alert($book[0]['name'].'已经供应完毕，明天请尽早下单~');exit;
                }
            }
            
        }else{
            $this->alert('菜品已经下架');exit;
        }
        return true;
        //验证剩余数量是否足够
        $todayBookOrder = DBproxy::getProcedure('Manage')->setDimension(2)->getTodayOKBookSum($key,0);
        $yestodayBookOrder = DBproxy::getProcedure('Manage')->setDimension(2)->getTodayOKBookSum($key,1);
        // var_dump($yestodayBookOrder,$todayBookOrder);die;
        $todayCanSum = $todayBookOrder[0]['booksum'];
        $yestodayCanSum = $yestodayBookOrder[0]['booksum']+$yestodayBookOrder[0]['peiSongSum'];
        $hasBookSum = $yestodayCanSum - $todayCanSum;
        if($value['sum'] > $hasBookSum){
            $this->alert($value['name'].'已经供应完毕，明天请尽早下单~');exit;
        }
    }
    
    //检测此菜是否可以预定或者存在
    public function checkBook($id){
        $params['id'] = intval($id);
        $book = DBproxy::getProcedure('Manage')->setDimension(2)->getBook($params);
        
        if( isset( $book['data'] ) 
            && !empty($book['data']) 
            && $book['total'] > 0 
            && isset($book['data'][0]['endSaleTime'])
            && time() < strtotime($book['data'][0]['endSaleTime'])
        )
        {
            return true;
        }else{            
            return false;
        }
    }

    /**
	 * 模版子目录
	 * @var string
	 */
	protected $_templateDefault = 'default';

	public function beforeRun($resource, $action) {
		parent::beforeRun($resource, $action);
        $this->_action = $action;
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
		
		$data['content'] = $content;
		$this->renderc($this->_templateDefault.'/'.$layoutFile,$data);
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
        $this->contentlayoutRender($msg.$redirectHtml);die;
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

}
?>
