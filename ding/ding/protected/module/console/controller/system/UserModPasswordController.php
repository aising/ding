<?php
Doo::loadController('ApplicationController');
/**
 * 修改密码
 * @author xinkq
 */
class UserModPasswordController extends ApplicationController {

	/**
	*  修改密码
	*/
	public  static $modPasswordUrl = NULL;


	public function init() {
		UserModPasswordController::$modPasswordUrl = adminAppUrl('system/userModPassword/modPassword?id=');
	}


	//修改密码
	public function modPassword() {
		$userInfo = $this->_user->getUserInfo();
		//D($userInfo);
		$uid = $userInfo['uid'];
		if($uid == 0 && isset($uid)) {
			$this->alert('参数错误');
			return;
		}

		if($this->isAjax() && $_POST) {
			$v = Doo::loadHelper('DooValidator',true);
			$success = true;
			$errors = array();

			$password = $_POST['password'];
			$password1 = $_POST['password1'];
			$password2 = $_POST['password2'];

			$pwd = $this->_user->password($password);//md5(KEY_PASSWORD.$password);
			if($userInfo['passwd'] != $pwd) {
				$success = false;
				$errors[] = '密码不正确';
			}

			if(!isset($password1)) {
				$success = false;
				$errors[] = '新登陆密码不能为空';
			}

			if(!isset($password2)) {
				$success = false;
				$errors[] = '再次输入密码不能为空';
			}

			if($password1 != $password2) {
				$success = false;
				$errors[] = '两次输入的密码不一致，请检查';	
			}

			// 插入数据库（接口没有验证数据是否重复，需添加者自己注意）
			if($success) {
				$result = $this->_user->update_pwd(1,$uid,$password1);
				if(isset($result) && $result != 0 ) {
					$success = false;
					$errors[] = '插入数据库出错,不可连续修改两次密码且不可与原密码相同';
				}
			}

			// 处理返回路径
			if($success) {
				if(isset($_POST['saveAndReutrn'])){
					$errors = Doo::conf()->APP_URL.'index.php/in';
				}
			}

			// 处理表单位提交
			$this->ajaxFormResult($success,$errors,true,'loginOut');
		} else {
			//取某用户信息
			$row = $this->_user->get_one($uid);

			//D($row);
			// 显示生成表单
			Doo::loadClassAt('html/DooFormExt','default');
			$form = new DooFormExt($this->_getPasswordFormConfig(false, $row));
			$this->contentlayoutRender($form->render());
		}
	}

	/**
	 * 取得修改密码表单配置
	 * @param  boolean $isInsert 1 是插入表单配置,0 是修改表单
	 * @param  array   $data    修改表单时传入数组
	 * @return array
	 */
	protected function _getPasswordFormConfig($isInsert = true,$data = array()) {
		Doo::loadClassAt('DataExt','default');
		$dataExt = new DataExt();

		$insertForm = array(
			        'method' => 'post',
			        'renderFormat' => 'html',
			        'action' => '',
			        'attributes'=> array('id'=>'js-form','class'=>'form-horizontal'),
			        'elements' => array(
			        	'errors' => array('display', array(
			        		'div' => false,
			                'label-hide' => true,
			 				'content' => '<div id="js-form-errors" class=""></div><div style="clear:both"></div>',
			            )),
			            'name' => array('text', array(
			                'label' => '操作员姓名',
			                'attributes' => array('class'=>"m-wrap small"),
			                'value' => '',
			 				//'help' => '<span class="label label-warning"> *最长为30个字符</span>'
			            )),
			            'uname' => array('text', array(
			                'label' => '登陆账号',
			                'attributes' => array('class'=>"m-wrap small"),
			                'value' => '',
			            )),
			            'password' => array('password', array(
			                'label' => '原登陆密码',
			                'attributes' => array('class'=>"m-wrap small"),
			                'value' => '',
			            )),
			             'password1' => array('password', array(
			                'label' => '新登陆密码',
			                'attributes' => array('class'=>"m-wrap small"),
			                'value' => '',
			            )),
			            'password2' => array('password', array(
			                'label' => '再次输入密码',
			                'attributes' => array('class'=>"m-wrap small"),
			                'value' => '',
			            )),
			            'saveAndReutrn' => array('button', array(
			            	'div' => false,
			            	'left' => '<div class="form-actions js-submitButton">',
			                'label' => '<i class="icon-arrow-left"></i>保存',
			                'attributes' => array('class'=>"btn blue"),
			                'value' => 1
			            )),
			            'cancel' => array('display', array(
			            	'div' => false,
			            	'left' => ' ',
			                'content' => '<a class="btn" href="'.$_SERVER['REQUEST_URI'].'"><i class="icon-undo"></i>取消</a>',
			            )),
			        ));

		if($isInsert) {
			return $insertForm;
		} else {

			//编辑时登录账户不需要修改
			$insertForm['elements']['name'][0] = 'display';
			$insertForm['elements']['name'][1]['content'] = '<label class="m-wrap text">' . $data['name'] . '<input type="hidden" name="name" id="name-element" value="' . $data['name'] . '"> </label>';

			//编辑时登录账户不需要修改
			$insertForm['elements']['uname'][0] = 'display';
			$insertForm['elements']['uname'][1]['content'] = '<label class="m-wrap text">' . $data['uname'] . '<input type="hidden" name="uname" id="uname-element" value="' . $data['uname'] . '"> </label>';


			// 将数据写入表单
			foreach ($data as $key => $val) {
				if(isset($insertForm['elements'][$key])) {
					$insertForm['elements'][$key][1]['value'] = $val;
				}
			}

			return $insertForm;
		}
	}
}