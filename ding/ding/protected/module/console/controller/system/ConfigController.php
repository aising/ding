<?php
Doo::loadController('ApplicationController');
/**
 * 用户角色管理
 * @author xinkq
 */
class ConfigController extends ApplicationController {

	public static $dataTableUrl = NULL;

	public static $addUrl = NULL;

	public  static $modUrl = NULL;

	public static $delUrl = NULL;

	public function init() {
		ConfigController::$dataTableUrl = adminAppUrl('system/config/dataTable');
		ConfigController::$modUrl = adminAppUrl('system/config/mod');
	}

	public function dataTable() {
		$this->mod();exit;
	}

	public function mod() {
		$configKey = 'settingConfig';
		// $cachePath = Doo::conf()->CACHE_PATH;
		// Doo::conf()->CACHE_PATH = Doo::conf()->SITE_PATH.'protect/confCache/';

		array_push($this->_includeJsFileList,'js/ueditor/ueditor.config.js');
		array_push($this->_includeJsFileList,'js/ueditor/ueditor.all.min.js');		
		array_push($this->_includeJsFileList,'js/ueditor/lang/zh-cn/zh-cn.js');
		array_push($this->_includeJsFileList,'js/ueditor/ueditor.use.js');

		$shopNameList = DBproxy::getProcedure('Manage')->setDimension(2)->getShopName();        
        $shopNameOpt = '<select class="m-wrap" name="shopname" id="city-element">';
        foreach ($shopNameList as $key => $value) {
            $selected = $this->getUrlVar('shopname') == $value['cityid'].','.$value['shopname'] ? 'selected=selected' : '';
            $shopNameOpt .= '<option '.$selected.' value="'.$value['cityid'].','.$value['shopname'].'">'.$value['cityNshopname'].'</option>';
        }
        $shopNameOpt .= '</select>';
		
		if($this->isAjax() && $_POST) {
			$v = Doo::loadHelper('DooValidator',true);
			$success = true;
			$errors = array();
			
			DOO::cache('php')->set($configKey,json_encode($_POST),3600*24*365*100);

			// 处理返回路径
			if($success) {				
				$errors = ConfigController::$dataTableUrl;				
			}

			$this->ajaxFormResult($success,$errors);

		} else {
			$infoCache = DOO::cache('php')->get($configKey);
			$info = json_decode($infoCache,true);
			
			// echo Doo::conf()->CACHE_PATH = $cachePath;

			Doo::loadClassAt('html/DooFormExt','default');
			
			$form = new DooFormExt(array(
			        'method' => 'post',
			        'renderFormat' => 'html',
			        'action' => '',
			        'attributes'=> array('id'=>'js-form','class'=>'form-horizontal'),
			        'elements' => array(
			        	'errors' => array('display', array(
			        		'div' => false,
			                'label' => false,
			 				'content' => '<div id="js-form-errors" class=""></div><div style="clear:both"></div>',
			            )),
			            'peiSwitch' => array('select', array(
                                'label' => L('菜品调剂发送开关:'),
                                'attributes' => array('class' => "m-wrap"),
                                'multioptions' => array(0=>'关',1=>'开'),
                                'value' => isset($info['peiSwitch']) ? $info['peiSwitch'] : '0',
                        )),			            
			     //        'peiPhone' => array('text', array(
			     //            'label' => '配送调度号码',
			 				// 'attributes' => array('class'=>"m-wrap"),
			 				// 'value' => isset($info['peiPhone']) ? $info['peiPhone'] : '',
			 				// 'help' => '接收配送数量的号码',
			     //        )),			            
			            'waimaiSum' => array('text', array(
			                'label' => '外卖起送份数',
			 				'attributes' => array('class'=>"m-wrap"),
			 				'value' => isset($info['waimaiSum']) ? $info['waimaiSum'] : '1',
			            )),
			            'aboutTxt' => array('display', array(
			                'label' => '关于我们',
			 				'attributes' => array('class'=>"m-wrap"),
			 				'content' => '<script id="about" type="text/plain" style="width:1024px;height:500px;">'.$info['editorValue'].'</script>'
			            )),

			            'saveAndSee' => array('button', array(
			            	'div' => false,
			            	'left' => ' ',
			                'label' => '保存&查看<i class="icon-arrow-right"></i>',
			                'attributes' => array('class'=>"btn blue"),
			                'value' => 1
			            )),
			            'cancelAndReturn' => array('display', array(
			            	'div' => false,
			            	'left' => ' ',
			            	'right' => '</div>',
			                'content' => '<a class="btn" href="'.ConfigController::$dataTableUrl.'"><i class="icon-arrow-left"></i>取消&返回</a>',
			            )),
			        ))
			);
			
			$this->contentlayoutRender($form->render());
		}
	}

}
