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
			Doo::loadClassAt('html/DooFormExt','default');

			$infoCache = DOO::cache('php')->get($configKey);
			$info = json_decode($infoCache,true);

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
			            'peiSum' => array('text', array(
			                'label' => '菜品调剂数',
			 				'attributes' => array('class'=>"m-wrap"),
			 				'value' => isset($info['peiSum']) ? $info['peiSum'] : '',
			 				'help' => '根据历史的订单加上这个数量，作为菜品配送数',
			            )),
			            'shopMaster' => array('text', array(
			                'label' => '店长微信openid',
			 				'attributes' => array('class'=>"m-wrap"),			 				
			 				'value' => isset($info['shopMaster']) ? $info['shopMaster'] : '',
			 				'help' => '可以根据店长订餐时绑定的电话号码,从用户列表中查到店长的微信openid',
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