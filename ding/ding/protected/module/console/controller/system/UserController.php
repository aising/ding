<?php
Doo::loadController('ApplicationController');
/**
 * 用户增加申请
 * @author xinkq
 */
class UserController extends ApplicationController {

	
	/**
	* 账号是否被锁
	**/
	public static $locked = array(
		0=> '否',
		1=> '是'
	);
	/**
	*  用户列表
	*/
	public static $dataTableUrl = NULL;

	/**
	*  添加用户
	*/
	public static $addUrl = NULL;

	/**
	*  修改用户
	*/
	public  static $modUrl = NULL;

	/**
	*  修改密码
	*/
	public  static $modPasswordUrl = NULL;

	/**
	*  修改筹码
	*/
	public  static $modChips = NULL;

	/**
	* 删除用户
	*/
	public static $delUrl = NULL;

	/**
	* 用户登陆
	*/
	public static $login = NULL;

	/**
	* 获取用户角色权限
	*/
	public static $sGetUserRoleids = NULL;

	public function init() {
		UserController::$dataTableUrl = adminAppUrl('system/user/dataTable');
		UserController::$modUrl = adminAppUrl('system/user/mod?id=');
		UserController::$addUrl = adminAppUrl('system/user/add');
		UserController::$modPasswordUrl = adminAppUrl('system/user/modPassword?id=');

		UserController::$delUrl = adminAppUrl('system/user/del?id=');
		UserController::$login = adminAppUrl('system/user/login?uname=');
		UserController::$sGetUserRoleids = adminAppUrl('system/user/sGetUserRoleids');
	}

	public function dataTable() {
		Doo::loadClassAt('html/DataTable','default');
		Doo::loadClassAt('html/DooFormExt','default');
		$dt = new DataTable();

		function table_button($row,$rowData) {
			//D($rowData);
			$modUrl = UserController::$modUrl.$rowData['uid'];
			$modPasswordUrl = UserController::$modPasswordUrl.$rowData['uid'];
			$delUrl = UserController::$delUrl.$rowData['uid'];
			$login = UserController::$login.$rowData['uname'];

			$a = '<a href="'.$modUrl.'" class="blue-stripe btn mini">修改用户资料及权限</a>';			
			$a .= '<a href="'.$modPasswordUrl.'" class="blue-stripe btn mini">修改密码</a>';
			// $a .= '<a href="'.$delUrl.'" class="red-stripe btn mini js-datatable-del">删除</a>';			
			return $a;
		}

		//账号状态
		function locked($row,$rowData) {
			$a = $rowData['is_locked']==1? '被锁定' : '有效';
			return $a;
		}

		$header = array(			    
				'name' => array('name' => '姓名'),
				'user_roles'  => array('name' => '角色'),
				'uname' => array('name' => '登录账号'),
				'is_locked'  => array('name' => '账号状态', 'callback' => 'locked'),
				'uname' => array('name' => '登录账号'),
				'CasinoChips' => array('name' => '筹码'),
				'mobile' => array('name' => '手机号码'),
				'email' => array('name' => '邮箱'),
				'last_logon_ip' => array('name' => '最后登陆IP'),
				'last_logon_time' => array('name' => '最后登陆时间'),
				'table_button_action' => array('name'=>'操作','callback'=>'table_button')
		);

		$uname = $this->getUrlVar('uname', '');
		$form = new DooFormExt(array(
            'method' => 'get',
            'renderFormat' => 'html',
            'action' => '',
            'attributes'=> array('id'=>'js-get-form','class'=>'form-horizontal'),
            'elements' => array(
                'add' => array('display', array(
	            	'left' => ' ',
	                'hide-label'=> true,
	                'div' => false,
	                'content' => '<a href="'.UserController::$addUrl.'" class="btn green-stripe"><i class="icon-plus"></i>新增用户</a>',
	 				'attributes' => array('class'=>"m-wrap"),
	            )),
                'uname' => array('text', array(
	            	'left' => ' ',
	                'hide-label'=> true,
	                'div' => false,
	                'placeholder' => '根据登陆账号查询',
	 				'attributes' => array('class'=>"m-wrap"),
	 				'value' => $uname
	            )),
                'search' => array('button', array(
                    'div' => false,
                    'label' => '<i class="icon-search"></i>查询',
                    'attributes' => array('class'=>"btn blue"),
                    'value' => 1
                )),
            ))
        );


		Doo::loadClassAt('Role','default');
		$role = new role();
		$roles = $role->get_roles();
		$user_type = '4';//超级用户
		$data = $this->_user->get_list($uname, $this->getCurPage(), Doo::conf()->pagesize, $user_type);
		
		foreach($data['list'] as $k => $v){
			$role_ids = $this->_user->get_user_roles($v['uid']);
			foreach($role_ids as $role_id){
				if(isset($roles[$role_id])) {
					$data['list'][$k]['user_roles'] = $roles[$role_id];
				}
			}
		}

		$content = $dt->setTitle('')
					   ->setAttr(array('class'=>'table table-hover'))
					   ->setHeader($header)
					   ->setData($data['list'])
					   ->setDefaultValue('unkown')
					   ->setTopContent($form->render())
					   ->setBottomContent($this->pager($data['total']))
					   ->render(false);

		$this->contentlayoutRender($content);
	}


	//添加
	public function add() {		
		if($this->isAjax() && $_POST) {
			$v = Doo::loadHelper('DooValidator',true);
			$success = true;
			$errors = array();
			$rules = $this->_getFormRule();
			// 验证数据
			if($errors = $v->validate($_POST,$rules)) {
				$success = false;
			}

			$password = $_POST['password'];
			$password2 = $_POST['password2'];
			if(!isset($password2) || trim($password2)=='') {
				$success = false;
				$errors[] = '再次输入密码不能为空';
			}

			if($password != $password2) {
				$success = false;
				$errors[] = '两次输入的密码不一致，请检查';	
			}

			//额外权限
			// $perms = (isset($_POST['perm']) && isset($_POST['showperm']) && $_POST['showperm'][0]==1) ? $_POST['perm'] : '';//配置角色外权限 
			$roleids = Doo::conf()->adminRoleId;//后台管理员

			// 插入数据库（接口没有验证数据是否重复，需添加者自己注意）
			if($success) {	
				$data['base'] = array(
		            'name' => trim($_POST['name']),
		            'uname' => trim($_POST['uname']),
		            'password' => trim($password),
		            'is_locked' => intval($_POST['is_locked']),
		            'is_check' => 1,
		            'email' => $_POST['email'],
					'mobile' => intval($_POST['mobile']),					
		            'roleids'=>$roleids,
		            'roleType' => $roleids,
				);
				// //用户 额外数据
				// $data['extend'] = array( 
				// 					'operationArea' => trim($_POST['operationArea']),
				// 		            'currency' => trim($_POST['currency']),
				// 		            'parentid' => user::getUserInfoByUId($_SESSION['userinfo']['uid']),
				// 		            'CasinoChips' => trim($_POST['CasinoChips']),
				// 		            'TimeZone' => trim($_POST['TimeZone']),
				// 		            'PreferredLanguage' => trim($_POST['PreferredLanguage']),
			 //            );
				
				$result = $this->_user->insert($data);
				//D($result);
				if($result['status'] != 0) {
					$success = false;
					$errors[] = '插入数据库出错'.$result['error'];
				}
			}

			// 处理返回路径
			if($success) {
				if(isset($_POST['saveAndReutrn'])) {
					$errors = UserController::$dataTableUrl;
				} else if(isset($_POST['saveAndSee'])){
					$errors = UserController::$modUrl.$result['id'];
				} else {
					$errors = UserController::$addUrl;
				}
			}			
			$this->ajaxFormResult($success,$errors);
		} else {
			// 显示生成表单
			Doo::loadClassAt('html/DooFormExt','default');

			$form = new DooFormExt($this->_getFormConfig(true,array(),$_SESSION['userinfo']['roleids']));
			
			$this->contentlayoutRender('<a class="btn green-stripe" href="'.UserController::$dataTableUrl.'"><i class="icon-plus"></i>返回列表</a>'.$form->render());
		}
	}

	//修改用户资料及权限
	public function mod() {
		$id = (int) $this->getUrlVar('id',0);
		if($id == 0) {
			$this->alert('参数错误');
			return;
		}
		//取用户信息
		$row = $this->_user->get_one($id);

		if($this->isAjax() && $_POST) {
			if(empty($row)){
				$this->alert('参数错误');
			}

			$v = Doo::loadHelper('DooValidator',true);
			$success = true;
			$errors = array();
			
			$rules = $this->_getFormRule(false);
			// 验证数据
			if($errors = $v->validate($_POST,$rules)) {
				$success = false;
			}
			
			//配置角色外权限 
			// $perms = '';
			// if( isset($_POST['perm']) && isset($_POST['showperm']) && $_POST['showperm'][0]==1 ) {
			// 	$perms = $_POST['perm'];
			// } 
			$roleids = Doo::conf()->adminRoleId;//后台管理员
			// 插入数据库（接口没有验证数据是否重复，需添加者自己注意）
			if($success) {
				$data['base'] = array(
		            'name' => trim($_POST['name']),
		            'mobile' => intval($_POST['mobile']),		            
		            'is_check' => intval($_POST['is_check']),
		            'is_locked' => intval($_POST['is_locked']),
		            'email' => $_POST['email'],
		            'roleids'=> $roleids,
		            'roleType' => $roleids,
				);
				//用户额外数据				
				$data['extend'] = array(
									'operationArea' => trim($_POST['operationArea']),
						            'currency' => trim($_POST['currency']),						            
						            'TimeZone' => trim($_POST['TimeZone']),
						            'PreferredLanguage' => trim($_POST['PreferredLanguage']),							            
			            );
				
				$result = $this->_user->update($id,$data);
				//D($result);
				if($result['status'] != 0) {
					$success = false;
					$errors[] = '插入数据库出错'.$result['error'];
				}				
			}

			// 处理返回路径
			if($success) {
				if(isset($_POST['saveAndReutrn'])) {
					$errors = UserController::$dataTableUrl;
				} else if(isset($_POST['saveAndSee'])){
					$errors = UserController::$modUrl.$id;
				} else {
					$errors = UserController::$addUrl;
				}
			}

			// 处理表单位提交
			$this->ajaxFormResult($success,$errors);
		} else {
			Doo::loadClassAt('Role','default');			
			if(empty($row)){
				$this->alert('参数错误');
			}			

			$role = new role();
			$roles = $role->get_roles();
			//所属角色
			$user_roles = $this->_user->get_user_roles($id);			
			$row['roleid'] = $user_roles[0];

			//配置角色外权限
			Doo::loadClassAt('Menu','default');
			$menu = new menu(DBproxy::getManage());
			$user_perms = $this->_user->get_user_perms($id);
			$menus = $menu->get_menu_formlists($user_perms,1);
			$row['perm'] = $menus;
			
			// 显示生成表单
			Doo::loadClassAt('html/DooFormExt','default');
			$form = new DooFormExt($this->_getFormConfig(false, $row));			
			$this->contentlayoutRender('<a class="btn green-stripe" href="'.UserController::$dataTableUrl.'"><i class="icon-plus"></i>用户列表</a>'.$form->render());
		}
	}

	//修改密码
	public function modPassword() {
		$id = (int) $this->getUrlVar('id',0);
		if($id == 0) {
			$this->alert('参数错误');
			return;
		}

		if($this->isAjax() && $_POST) {
			$v = Doo::loadHelper('DooValidator',true);
			$success = true;
			$errors = array();

			$password = $_POST['password'];
			$password2 = $_POST['password2'];
			if(!isset($password2)) {
				$success = false;
				$errors[] = '再次输入密码不能为空';
			}

			if($password != $password2) {
				$success = false;
				$errors[] = '两次输入的密码不一致，请检查';	
			}

			// 插入数据库（接口没有验证数据是否重复，需添加者自己注意）
			if($success) {
				$result = $this->_user->update_pwd(1,$id,$password);
				if($result != 0) {
					$success = false;
					$errors[] = '插入数据库出错,不可连续修改两次密码且不可与原密码相同';
				}
			}

			// 处理返回路径
			if($success) {
				if(isset($_POST['saveAndReutrn'])) {
					$errors = UserController::$dataTableUrl;
				} else if(isset($_POST['saveAndSee'])){
					$errors = UserController::$modUrl.$id;
				} else {
					$errors = UserController::$addUrl;
				}
			}

			// 处理表单位提交
			$this->ajaxFormResult($success,$errors);
		} else {
			//取某用户信息
			$row = $this->_user->get_one($id);

			// 显示生成表单
			Doo::loadClassAt('html/DooFormExt','default');
			$form = new DooFormExt($this->_getPasswordFormConfig(false, $row));
			array_push($this->_includeJsFileList,'js/default/user.js');
			$this->contentlayoutRender('<a class="btn green-stripe" href="'.UserController::$dataTableUrl.'"><i class="icon-plus"></i>用户列表</a>'.$form->render());
		}
	}

	//删除
	public function del() {
		$id = (int) $this->getUrlVar('id',0);
		// 删除数据
		$result = $this->_user->del($id);

		$this->alert('删除成功', 'success',true, $this->getCacheUrlVar('user'));
	}

	//返回后台角色列表
	static public function getRoleList(){		
		Doo::loadClassAt('Role','default');
		$role = new role();
		$roles = $role->get_roles();
		return array(Doo::conf()->adminRoleId=>$roles[Doo::conf()->adminRoleId]);
		
	}

	/**
	 * 取得表单配置
	 * @param  boolean $isInsert 1 是插入表单配置,0 是修改表单
	 * @param  array   $data    修改表单时传入数组
	 * @param  array   $nowUserRole    角色数组，根据此参数生成要提交的表单
	 * @return array
	 */
	protected function _getFormConfig($isInsert = true,$data = array(),$nowUserRole = array()) {
		Doo::loadClassAt('DataExt','default');
		$dataExt = new DataExt();

		Doo::loadClassAt('Menu','default');
		$menu = new Menu(DBproxy::getManage());
		$perms = array();
		$menus = $menu->get_menu_formlists($perms);
		if(isset($data['perm'])) {
			$menus = $data['perm'];
		}

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
			 				'help' => '<span class="label label-warning"> *最长为30个字符</span>'
			            )),
			            'uname' => array('text', array(
			                'label' => '登陆账号',
			                'attributes' => array('class'=>"m-wrap small"),
			                'value' => '',
			            )),
			            'password' => array('password', array(
			                'label' => '登陆密码',
			                'attributes' => array('class'=>"m-wrap small"),
			                'value' => '',
			            )),
			            'password2' => array('password', array(
			                'label' => '确认密码',
			                'attributes' => array('class'=>"m-wrap small"),
			                'value' => '',
			            )),			            
			            'roleid' => array('select', array(
			            	'label' => '所属角色',
			            	'multioptions' => UserController::getRoleList(),
			            	'value' => Doo::conf()->adminRoleId,
			            )),
			            'is_locked' => array('select', array(
			            	'label' => '账号是否被锁',
			            	'attributes' => array('class' => 'm-wrap small'),
			            	'multioptions' => ApplicationController::$locked,
			            	'value' => 0,
			            )),
			            'is_check' => array('select', array(
			            	'label' => '账号是否通过验证',
			            	'attributes' => array('class' => 'm-wrap small'),
			            	'multioptions' => array(0=>'未通过验证',1=>'验证通过'),
			            	'value' => 0,
			            )),
			            'mobile' => array('text', array(
			            	'label' => '手机号码',
			            	'attributes' => array('class' => 'm-wrap small'),
			            	'value' => '',
			            	'help' => ' 注：请输入包括国家/地区代码的手机号码。'
			            )),
			            'email' => array('text', array(
			            	'label' => '邮箱账号',
			            	'attributes' => array('class' => 'm-wrap small'),
			            	'value' => '',
			            )),

			        ));
			$btnArr = $this->_btnForm();
			
			$insertForm['elements'] = array_merge($insertForm['elements'], $btnArr);
			
		if($isInsert) {			
			unset($insertForm['elements']['is_check']);
			return $insertForm;
		} else {			

			// //编辑时筹码不需要修改
			// $insertForm['elements']['CasinoChips'][0] = 'display';
			// $insertForm['elements']['CasinoChips'][1]['content'] = '<label class="m-wrap text">' . $data['CasinoChips'] . '</label>';

			//编辑时登录账户不需要修改
			$insertForm['elements']['uname'][0] = 'display';
			$insertForm['elements']['uname'][1]['content'] = '<label class="m-wrap text">' . $data['uname'] . '<input type="hidden" name="uname" id="uname-element" value="' . $data['uname'] . '"> </label>';


			//修改时，不需要显示密码
			unset($insertForm['elements']['password']);
			unset($insertForm['elements']['password2']);

			// 将数据写入表单
			foreach ($data as $key => $val) {
				if(isset($insertForm['elements'][$key])) {
					$insertForm['elements'][$key][1]['value'] = $val;
				}
			}
			return $insertForm;
		}
	}


	/**
	 * 根据当前用户，添加用户的时间，取得表单配置	 
	 * @return array
	 */
	protected function _btnForm()
	{
		$formArr = array('saveAndReutrn' => array('button', array(
			            	'div' => false,
			            	'left' => '<div class="form-actions js-submitButton">',
			                'label' => '<i class="icon-arrow-left"></i>保存&返回',
			                'attributes' => array('class'=>"btn blue"),
			                'value' => 1
			            )),
			            'saveAndAdd' => array('button', array(
			            	'div' => false,
			            	'left' => ' ',
			                'label' => '<i class="icon-plus"></i>保存&新增',
			                'attributes' => array('class'=>"btn blue"),
			                'value' => 1
			            )),

			            'cancel' => array('display', array(
			            	'div' => false,
			            	'left' => ' ',
			                'content' => '<a class="btn" href="'.$_SERVER['REQUEST_URI'].'"><i class="icon-undo"></i>取消</a>',
			            )),
			            'cancelAndReturn' => array('display', array(
			            	'div' => false,
			            	'left' => ' ',
			            	'right' => '</div>',
			                'content' => '<a class="btn" href="'.UserController::$dataTableUrl.'"><i class="icon-arrow-left"></i>取消&返回</a>',
			            ))
			        );


		// if(in_array('1', $nowUserRole)){
		// 	$formArr = array_merge($this->_role1($isInsert,$data,$nowUserRole),$formArr);
		// }
		
		return $formArr;
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
			 				'help' => '<span class="label label-warning"> *最长为30个字符</span>'
			            )),
			            'uname' => array('text', array(
			                'label' => '登陆账号',
			                'attributes' => array('class'=>"m-wrap small"),
			                'value' => '',
			            )),
			            'password' => array('password', array(
			                'label' => '登陆密码',
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
			                'label' => '<i class="icon-arrow-left"></i>保存&返回',
			                'attributes' => array('class'=>"btn blue"),
			                'value' => 1
			            )),
			            'saveAndAdd' => array('button', array(
			            	'div' => false,
			            	'left' => ' ',
			                'label' => '<i class="icon-plus"></i>保存&新增',
			                'attributes' => array('class'=>"btn blue"),
			                'value' => 1
			            )),

			            'cancel' => array('display', array(
			            	'div' => false,
			            	'left' => ' ',
			                'content' => '<a class="btn" href="'.$_SERVER['REQUEST_URI'].'"><i class="icon-undo"></i>取消</a>',
			            )),
			            'cancelAndReturn' => array('display', array(
			            	'div' => false,
			            	'left' => ' ',
			            	'right' => '</div>',
			                'content' => '<a class="btn" href="'.UserController::$dataTableUrl.'"><i class="icon-arrow-left"></i>取消&返回</a>',
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

	/**
	 * 取得表单验证规则
	 * @param  boolean $isInsert 1 是插入表单规则,0 是修改规则
	 * @return array
	 */
	protected function _getFormRule($isInsert = true) {
		$rule = array(
				'name' =>  array(
	                        array('required',"请填写角色名"),
					        array('minlength',2,"角色名最少长度不允许少于2个字节"),
	                        array('maxlength',8,"角色名最大长度不允许大于8个字节"),
	                ),
				'uname' => array(
						array( 'notnull' ),
						array('required',"请填写昵称"),
						array( 'custom','User::chechUnameExist'),
				),
				'password' => array(
	                        array('required',"请填写密码"),
	                        array('maxlength',30,"密码最大值不能超过30"),
	                ),
				
				'email' => array(
	                        array('required',"请填写邮箱"),
	                        array('maxlength',25,"邮箱最大值不能超过25"),
	                ),
				'roleid' => array(
	                        array('required',"请选择角色"),
	                ),
			);
		//修改不验证登录名和密码
		if($isInsert == false){
			unset($rule['uname']);
			unset($rule['password']);
		}
		return $rule;
	}


	//用户登陆
	public function login() {

		Doo::loadClassAt('html/DataTable','default');
        Doo::loadClassAt('html/DooFormExt','default');
        $dt = new DataTable ();

		$form = new DooFormExt(array(
            'method' => 'get',
            'renderFormat' => 'html',
            'action' => '',
            'attributes'=> array('id'=>'js-get-form','class'=>'form-horizontal'),
            'elements' => array(
                'code' => array('text', array(
	            	'left' => ' ',
	                'hide-label'=> true,
	                'div' => false,
	                'placeholder' => '登陆验证码',
	 				'attributes' => array('class'=>"m-wrap"),
	 				'value' => ''
	            )),
                'search' => array('button', array(
                    'div' => false,
                    'label' => '<i class="icon-search"></i>提交',
                    'attributes' => array('class'=>"btn blue"),
                    'value' => 1
                )),
            ))
        );

		$uname = $this->getCacheUrlVar('uname');//用户账号
		$code =  trim($this->getUrlVar('code',''));//登陆验证码
		$codeConf = Doo::conf()->code;//登陆验证码（配置文件）
		if( !empty($code) ) {
			if( $code == $codeConf ) {
				$this->_user->accountLogin($uname);
			} else {
				$this->alert('验证码错误','ERROR',false);
			}
		}

		$content = $dt->setTitle('')
					   ->setAttr(array('class'=>'table'))
					   ->setDefaultValue('unkown')
					   ->setTopContent('<a href="'.UserController::$addUrl.'" class="btn green-stripe"><i class="icon-plus"></i>返回列表</a>'.$form->render())
					   ->render(false);

		$this->contentlayoutRender($content);
	}

	/**
	 * 取得表单配置
	 * @param  boolean $isInsert 1 是插入表单配置,0 是修改表单
	 * @param  array   $data    修改表单时传入数组
	 * @return array
	 */
	protected function _getLoginFormConfig($isInsert = true) {
		Doo::loadClass('DataExt');
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
			                'label' => '登陆码',
			                'attributes' => array('class'=>"m-wrap small"),
			                'value' => ''
			            )),
			            'saveAndReutrn' => array('button', array(
			            	'div' => false,
			            	'left' => '<div class="form-actions js-submitButton">',
			                'label' => '<i class="icon-arrow-left"></i>提交',
			                'attributes' => array('class'=>"btn blue"),
			                'value' => 1
			            )),
			        ));

		
		return $insertForm;
	}

}
