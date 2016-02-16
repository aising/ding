<?php
Doo::loadController('ApplicationController');
/**
 * 代理控制器
 * 2014.3.30
 * @author xinkq
 */
class LoginController extends ApplicationController {

	protected $_checkIsLogin = FALSE;

	protected $_checkPageAuth = FALSE;


	/**
	 * 登录
	 */
	public function in() {		
		if($this->_user->isLogin()) {
			$result = $this->_user->logout();
		}
		
		// 自动登录
		// if($this->_user->autoLogin()) {
		// 	$this->alert('自动登录成功','success');
		// 	return;
		// }

		if($this->isAjax() && $_POST) {
			$result = $this->_user->login($_POST['username'],$_POST['password'],$_POST['safecode'],$_POST['lang'],isset($_POST['remember']) ? 1 : 0);
			
			$this->ajaxFormResult($result['success'],$result['success'] ? adminAppUrl('home') : $result['errors']);
		} else {
			Doo::loadClassAt('html/DooFormExt','default');
			$form = new DooFormExt(array(
			        'method' => 'post',
			        'renderFormat' => 'array',
			        'action' => '',
			        'attributes'=> array('id'=>'js-form','class'=>'form-vertical login-form'),
			        'elements' => array(
			            'username' => array('text', array(
			                'hide-label' => FALSE,
			 				'placeholder'=>'用户名',
			 				'attributes' => array('class'=>"m-wrap placeholder-no-fix")
			            )),
			 			'lang' => array('select', array(
			 				'multioptions' => Doo::conf()->langList,
			 				'hide-label' => FALSE,
			 				'attributes' => array('class'=>"m-wrap small")
			            )),
			            'password' => array('password', array(
			                'hide-label' => FALSE,
			                'placeholder'=>'密码',
			                'attributes' => array('class'=>"m-wrap placeholder-no-fix")
			            )),
			            'remember' => array('checkbox', array(
			                'hide-label' => FALSE,
			                'div' => FALSE,
			                //'placeholder'=>'记住密码',
			                //'label' => '记住密码',
			                //'label-hide' => 1,
			                'attributes' => array('class'=>"")
			            )),
			            'safecode' => array('text', array(
			            	'div' => FALSE,
			                'hide-label' => FALSE,
			                'placeholder'=>'验证码',
			                'attributes' => array('class'=>"m-wrap small")
			            )),
			        ))
			);
		
			$this->renderc($this->_templateDefault.'/login/in',$form->render());
		}
		
	}

	public function checkMobile() {

	}

	public function out() {
		$result = $this->_user->logout();
		$this->jsRedirect(adminAppUrl('/'));
	}

	
} 
