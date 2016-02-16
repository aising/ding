<?php

/**
 * 用户类
 * @author xinkq
 */
class User{

	//是否验证验证码
	protected $_checkSafecode = TRUE;

	private $userinfo = NULL;
	
	private $db = NULL;
	
	// private $_redis;
	
	private $_rememberKey = '__re__';
	public function __construct($db=''){
		$this->db = $db;
		// Doo::loadClassAt('KVCache','default');
		// $KVCache = new KVCache();
		// $this->_redis = $KVCache->redis();
	}
	
	/**
	 * 判断是否登录
	 * @return bool
	 */
	public function isLogin() {	
		return isset($_SESSION['authory']) ? TRUE : FALSE;
	}

	public static function vSafeCode($safecode) {		

		if(!isset($_SESSION['safe_code'])) {
			return 'captcha is null';
		}

		if(strtolower($_SESSION['safe_code']) != strtolower($safecode)){
           	return 'captcha is error';
		}
	}

	/**
	 * 自动登录
	 * @return boolen
	 */
	public function _____autoLogin() {
		if(isset($_COOKIE[$this->_rememberKey])) {

			$data = decrypt($_COOKIE[$this->_rememberKey],Doo::conf()->KEY_PASSWORD);
			list($ip,$account,$passwd,$lang) = explode('|', $data);
			if($ip != getIp()) {
				return false;
			}
			$_SESSION['safe_code'] = '12345';
			$res = $this->login($account,$passwd,$_SESSION['safe_code'],$lang,$remember = 1);
			return $res['success'];
		}
		return false;
	}

	/**
	 * 用户登录
	 * @param string $account
	 * @param string $passwd
	 * @param string $safecode
	 * @return int
	 */
	public function login($account,$passwd,$safecode = '',$lang = 'zh',$remember = 0){
		Doo::loadHelper('DooValidator');
		$v = new DooValidator;
		$success = TRUE;
		$userinfo = $errors = array();

		$postData = array('username'=>$account,'password'=>$passwd,'captcha'=>$safecode,'lang'=>$lang);
		//D($postData);
		$rules = array(
			'username' => array(
                        array( 'maxlength', 20 ),
                        array( 'notnull' ),
                ),
			'password' => array(
						array( 'maxlength', 20 ),
                        array( 'notnull' ),
				),
			'captcha' => array(
						array( 'notnull' ),
						array( 'custom','User::vSafecode'),
				),
			'lang' => array(
						array( 'notnull' ),
						array( 'inList',array_keys(Doo::conf()->langList)),
				),
		);
		if($this->_checkSafecode == FALSE){
			unset($rules['captcha']);
		}

		if($errors = $v->validate($postData,$rules)) {
			$success = FALSE;
		}
		// 无论登录是否正确都删除验证码
		unset($_SESSION['safe_code']);

		if($success) {
			//根据用户账号查数据库获取用户
			$param = array(
					'username' => $account
			);
			
			$result = DBproxy::getProcedure('Manage')->setDimension(2)->spSysUserSSign($param);
			
			if (!empty($result)) {
				$userinfo = $result;
				$userinfo['uname']  = $userinfo['username']  = $account;
			}else{
				$success = FALSE;
				$errors['username'] = 'username or password is error';
			}
		}

		if($success) {
			if ($userinfo['passwd'] != $this->password($passwd)) {
				$success = FALSE;
				$errors['password'] = 'username or password is error';
			}
		}

		//用户锁定
		if($success) {
			if($userinfo['is_locked'] == 1) {
				$success = FALSE;
				$errors['username'] = 'username is locked';
			}
		}
		//用户验证
		if($success) {
			if($userinfo['is_check'] == 0) {
				$success = FALSE;
				$errors['username'] = ' Did not check validation ';
			}
		}
		if($success) {
			//取所属角色(s)
			$userinfo['roleids'] = $this->get_user_roles($userinfo['uid']);			
			$this->userinfo = $userinfo;
			//最后做是否验证的检查
			if ($userinfo['is_check'] == 1) {
				$_SESSION['userinfo'] = $this->userinfo;
				//写权限到$_SESSION['authory']
				$this->authorityInSession();
				$this->insert_login_log($userinfo['uid']);
			}else{
				$_SESSION['userinfo_tmp'] = $this->userinfo;				
			}
		}

		if($success && $remember) {
			@setcookie($this->_rememberKey,encrypt(getIp().'|'.$account.'|'.$passwd.'|'.$lang,Doo::conf()->KEY_PASSWORD),time() + 30 * 86400,'/');
		}

		return array('success'=>$success,'errors'=>$errors);
	}

	/**
	* 用户列表登陆
	* @param $account
	*/
	public function accountLogin($account) {
		//根据用户账号查数据库获取用户
		$param = array(
				array($account)
		);
		
		$result = $this->db->execute('sp_sys_user_s_sign', $param,1);
		$userinfo = $result;

		//取所属角色(s)
		$userinfo['roleids'] = $this->get_user_roles($userinfo['uid']);
		
		$this->userinfo = $userinfo;

		$_SESSION['userinfo'] = $this->userinfo;
		//写权限到$_SESSION['authory']
		$this->authorityInSession();
		$this->insert_login_log($userinfo['uid']);
	}
	
	/**
	 * 写成功登录日志
	 * @param int $uid
	 * @return array(status)
	 */
	private function insert_login_log($uid){
		$param = array(
				'uid' => intval($uid)
		);
		$result = DBproxy::getProcedure('Manage')->setDimension(2)->spSysUserUSign($param);		
		return $result;
	}
	
	public function authorityInSession(){
		$authority = array();
		//MAKR 4是超管
		if(!in_array(Doo::conf()->adminRoleId, $this->userinfo['roleids'])){

			//TODO  用户角色外权限
			$tmp_arr = $this->getSpecialAuthority($this->userinfo['uid']);
			if ($tmp_arr){
				foreach ($tmp_arr as  $var){
					$authority[$var['menu_id']] = $var['perm_id'];
				}
			}

			//所在角色权限
			foreach($this->userinfo['roleids'] as $roleid){
				$tmp_arr = $this->getRoleAuthority($roleid);
				if ($tmp_arr){
					foreach ($tmp_arr  as  $var){
						if(isset($authority[$var['menu_id']])){
							//合并同一菜单权限
							$authority[$var['menu_id']] |= $var['perm_id'];
						}else{
							$authority[$var['menu_id']] = $var['perm_id'];
						}
					}
				}
			}
			
			unset($tmp_arr);
		}else{
			$authority = -1;
		}
		$_SESSION['authory'] = $authority;

	}
	
	
	/**
	 * 获取角色菜单权限
	 * @param int $roleid 角色ID
	 * @return array 权限列表（menu_id,perm_id）
	 */
	public function getRoleAuthority($roleid){
		Doo::loadClassAt('Role','default');
		$role = new role();
		$result = $role->get_role_perm($roleid);
		foreach ($result as $key => $value) {
			$res[] = array('menu_id'=>$key,'perm_id'=>$value);
		}
		return $res;
	}
	
	/**
	 * TODO  获取操作员角色外的操作权限
	 * @param int $roleid 用户ID
	 * @return array 权限列表（menu_id,perm_id）
	 */
	public function getSpecialAuthority($uid){
		// $data = array();

		// $param = array(
		// 		 'uid' => $uid
		// );		
		// $result = $this->db->execute('sp_sys_user_perm_s', $param,2);
		// if($result['status'] ==0 ){
		// 	$data = $result;
		// }
		// return $data;
	}
	
	
	/**
	 * 检查是否需要验证手机号码
	 * @param bool
	 */
	public function isCheckMobile() {
		return $this->userinfo['is_check'] == 1 ? TRUE : FALSE;
	}

	// /**
	//  * 手机短信验证
	//  */
	// public function ckeckMobile($num){
	// 	$success = TRUE;
	// 	$errors = array();

	// 	if (empty($_SESSION['userinfo_tmp'])) {
	// 		$success = FALSE;
	// 		$errors['num'] = 'logined';
	// 	}

	// 	if (empty($num)) {
	// 		$success = FALSE;
	// 		$errors['num'] = 'not null';
	// 	}
		
	// 	if($success) {
	// 		$key = XCACHEALIVETIME_MSG.':'.$_SESSION['userinfo_tmp']['uid'];
	// 		$ret = xcache_get($key);
	// 		if ($num == $ret) {
	// 			$_SESSION['userinfo'] = $_SESSION['userinfo_tmp'];
	// 			$this->userinfo = $_SESSION['userinfo'];
	// 			unset($_SESSION['userinfo_tmp']);
	// 			xcache_unset($key);
	// 			//写权限到$_SESSION['authory']
	// 			$this->authorityInSession();
	// 			$this->insert_login_log($_SESSION['userinfo']['uid']);
	// 		}else{
	// 			$success = FALSE;
	// 			$errors['num'] = 'not equal';
	// 		}
	// 	}

	// 	return array('success'=>$success,'errors'=>$errors); 
	// }
	

	// /**
	//  * 获取手机短信
	//  * @return int 验证码
	//  */
	// public function getNewMessage(){
	// 	require_once APP_FUNC_PATH.'func.php';
	// 	if(empty($_SESSION['userinfo_tmp']['uid'])){
	// 		return -1;
	// 	}
		
	// 	$num = rand(100000,999999);
	// 	$key = XCACHEALIVETIME_MSG.':'.$_SESSION['userinfo_tmp']['uid'];
	// 	xcache_set($key, $num, XCACHE_MSG_ALIVETIME);
		
	// 	//发送验证码
	// 	//@TODO
	// 	$msg = sprintf(SMS_MSG_LOGIN,$num);
	// 	sms_send(SMS_MOBILE, $msg);
	// 	return $num;
	// }
	
	 
	// /**
	//  * 获取手机短信
	//  */
	// public function getMessage(){
	// 	return xcache_get(XCACHEALIVETIME_MSG);
	// }
	
	public function logout(){
		session_destroy();
		//退出删除菜单缓存		
		@setcookie($this->_rememberKey,'',time()-10,'/');
		//@unset($_COOKIE[$this->_rememberKey]);
	}
	
	public static function getNowChips(){
		return $holdSum = DBproxy::getProcedure('Manage')->setDimension(3)->getUserChipsSum(user::getUserInfoByUId($_SESSION['userinfo']['uid']));
	}

	public function getUserInfo(){
		if (isset($_SESSION['userinfo'])) {
			return $_SESSION['userinfo'];
		}

		return NULL;
	}

	public function getUsername(){		
		if (isset($_SESSION['userinfo'])) {
			return $_SESSION['userinfo']['name'];
		}else{			
			return NULL;	
		}
	}

	//获取用户角色权限
	public static function sGetUserRoleids(){
		if (isset($_SESSION['userinfo'])) {
			return $_SESSION['userinfo']['roleids'];
		}

		return array();
	}

	public static function getUserInfoByUId($id){
		if (isset($_SESSION['userinfo']['uid']) && $_SESSION['userinfo']['uid'] == $id) {
			return intval($_SESSION['userinfo']['uid']);
		}
		
		return NULL;
	}
	
	public static function getUserInfoByAccount(){
		if (isset($_SESSION['userinfo']['uname']) ) {
			return trim($_SESSION['userinfo']['uname']);
		}
		return NULL;
	}
	
	public static function getUserInfoByName($name){
		if (isset($_SESSION['userinfo']['name']) && $_SESSION['userinfo']['name'] == $name) {
			return $_SESSION['userinfo'];
		}
		return NULL;
	}
	
	/**
	* 检查用户名
	*/
	public static function chechUnameExist($uname){
		$result = DBproxy::getProcedure('Manage')->setDimension(3)->checkUnameExist($uname);
		if($result === true){
			return 'uname is exist!';
		}
	}

	/**
	* 检查总社是否有足够的筹码可以转给总代
	*/
	public static function checkCasinoChips($CasinoChips){
		if($CasinoChips < 0 || $CasinoChips > 10000000000){
			return '筹码只能是大于0';
		}
		//如果当前角色不是管理员才进行筹码是否足够判断
		$currentRole = User::sGetUserRoleids();
		if($currentRole[0] != Doo::conf()->adminRoleId){
			$uid = user::getUserInfoByUId($_SESSION['userinfo']['uid']);
			$holdSum = DBproxy::getProcedure('Manage')->setDimension(3)->getUserChipsSum($uid);
			if($holdSum < $CasinoChips){
				return '你的筹码不够转账了！';
			}
		}
	}

	/**
	 * 取用户分页列表
	 * @param int $page
	 * @param int $psize
	 * @return array
	 */
	public function get_list($uname,$page,$psize, $user_type=0) {		
		$param = array(
				'uid' => -1,
				'uname' => $uname,
				'pagesize' => $page.','.$psize,
				'user_type' => $user_type //用户类型
		);

		return $result = DBproxy::getProcedure('Manage')->setDimension(2)->spSysUserS($param);
	}
	
	/**
	 * 取某用户信息
	 * @param int $uid
	 * @return array
	 */
	public function get_one($uid) {
		$data = array();
		$param = array(
				'uid' => $uid,				
		);
		
		$result = DBproxy::getProcedure('Manage')->setDimension(2)->spSysUserS($param);
		if(!empty($result['list'][0])){
			$data = $result['list'][0];
		}
		// $result = $this->db->execute('sp_sys_user_s', $param);
		return $data;
	}
	
	/**
	* 
	* 密码加密 用户密码经过md5+key处理过写入数据库
	*/
	public function password($password = ''){
		return md5(sha1(Doo::conf()->KEY_PASSWORD).sha1(trim($password)));
	}

	/**
	* 添加用户
	* @param array $data
	* $data['base'] = array(
	*	            'name' => trim($_POST['name']),
	*	            'uname' => trim($_POST['uname']),
	*	            'password' => trim($password),
	*	            'is_locked' => intval($_POST['is_locked']),
	*	            'is_check' => 1,
	*	            'CasinoChips' => trim($_POST['CasinoChips']),
	*				'mobile' => intval($_POST['mobile']),
	*				'roleids'=>$roleids,
	*	            'email' => $_POST['email'],
	*	            'roleType' => $roleids,
	 * @return int
	 */
	public function insert($data) {
		$param = array(
				'name' => $data['base']['name'],
				'uname' => $data['base']['uname'],
				'password' => $this->password($data['base']['password']),
				'mobile' => $data['base']['mobile'],
				'CasinoChips' => isset($data['base']['CasinoChips'])&&is_numeric($data['base']['CasinoChips']) ? floatval($data['base']['CasinoChips']) : '0',
				'is_check' => $data['base']['is_check'],
				'email' => $data['base']['email'],
		);
		$result = DBproxy::getProcedure('Manage')->setDimension(2)->spSysUserIU('i',$param);
		
		$uid = 0;
		$status=0;
		$error = '';

		if($result['status']==0 && !empty($result['id'])){
			$uid = $result['id'];
			//角色处理成数组可以多选 的情况下
			$roles = array($data['base']['roleids']);
			if(!empty($roles)){
				if($this->set_roles($uid, $roles)!=0){
					$status = 2;
				}
			}

			// $perms = isset($data['base']['perms']) ? $data['base']['perms'] : array();
			//额外权限，现不开放
			// if($status==0 && !empty($perms)){
			// 	if($this->set_perms($uid, $data['perms'])!=0){
			// 		$status = 3;
			// 	}
			// }

			//区分会员类型插入，默认没有附加信息
			if($status==0 && isset($data['base']['roleType']) && isset($data['extend'])){
				$data['extend']['uid'] = $uid;
				$ext = $this->addUserExtendInfo($data['base']['roleType'],$data['extend']);
				$status = $ext['status'];
				$error = isset($ext['error']) ? $ext['error'] : '';
			}
			
		}else{
			$status = $result['status'];
		}
		
		$result = array('status'=>$status,'id'=>$uid,'error'=>$error);
		return $result;
	}
		
	/**
	 * 根据会员角色添加更多内容，像总代的更多信息
	 * @param str $role 角色
	 * @param array $data
	 * @return int
	 */
	private function addUserExtendInfo($role,$data,$IU='i',$uid = 0){
		$result = array();
		switch ($role) {
			//总社
			case '1': //header
				$result = DBproxy::getProcedure('Manage')->setDimension(2)->userExTInfoIU($IU,$data,$uid);
				break;
			//总代
			case '2': //masterAgent
				$result = DBproxy::getProcedure('Manage')->setDimension(2)->userExTInfoIU($IU,$data,$uid);
				break;
			//代理商
			case '3': //agent
				$result = DBproxy::getProcedure('Manage')->setDimension(2)->userExTInfoIU($IU,$data,$uid);
				break;
			//超管
			default:
				$result = DBproxy::getProcedure('Manage')->setDimension(2)->userExTInfoIU($IU,$data,$uid);
				break;				
		}

		return $result;
	}
	
	/**
	 * 更新用户
	 * @param array $data
	 * @return int
	 */
	public function update($uid,$data) {
		$param = array(
				'name' => $data['base']['name'],
				'mobile' => $data['base']['mobile'],
				'email' => $data['base']['email'],			
				'is_check' => $data['base']['is_check'],
				'is_locked' => $data['base']['is_locked'],				
		);
		
		$result = DBproxy::getProcedure('Manage')->setDimension(2)->spSysUserIU('u',$param,intval($uid));
		
		$status=0;
		if($result['status'] == 0){
			
			$roles = $data['base']['roleids'];			
			//角色处理成数组可以多选 的情况下
			$roles = array($data['base']['roleids']);
			if(!empty($roles)){
				if($this->set_roles($uid, $roles,'u')!=0){
					$status = 2;
				}
			}		

			//更新扩展信息
			if(isset($data['base']['roleType']) && isset($data['extend'])){
				$data['extend']['uid'] = $uid;
				$ext = $this->addUserExtendInfo($data['base']['roleType'],$data['extend'],'u',$uid);
				$status = $ext['status'];
			}

			// //额外权限，现不开放
			// $perms = $data['perms'];
			// if($status==0 && !empty($perms)){
			// 	if($this->set_perms($uid, $data['perms'])!=0){
			// 		$status = 3;
			// 	}
			// }

		}else{
			$status = $result['status'];
		}
		
		return $status;
	}
	
	/**
	 * 修改用户密码
	 * @param int $uid
	 * @param int $type  1 修改自己的密码，2修改下属用户的密码
	 * @param string $pwd
	 * @return int 0成功 非0失败
	 */
	public function update_pwd($type,$uid,$pwd) {		
		$param = array(
					'uid' => intval($uid),
					'pwd' => $this->password($pwd),
				);
		$result = DBproxy::getProcedure('Manage')->setDimension(2)->sysUserUPass($type,$param);
		
		return isset($result['status']) ? $result['status'] : 1;
	}
	
	/**
	 * 删除用户
	 * @param int $id
	 * @return int
	 */
	public function del($uid = 0) {			
		$result = DBproxy::getProcedure('Manage')->setDimension(2)->delUser(intval($uid),1);
		return $result ? 0 : 1;
	}
	
	
	/**
	 * 更新用户角色
	 * @param int $uid
	 * @param array $roles
	 * @return int
	 */
	public function set_roles($uid,$roles,$iFlag='i') {
		
		$arr=array();
		foreach($roles as $k=>$v){
			$arr[] = sprintf('(x,%d)',$v);
		}
		$rolelist = implode(',', $arr);
		
		$param = array(
				'uid' => $uid,
				'rolelist' => $rolelist
		);

		$result = DBproxy::getProcedure('Manage')->setDimension(2)->spSysUserRoleIu($iFlag,$param);
		return $result['status']==0 ? 0 : 1;
	}
	
	/**
	 * 更新用户权限
	 * @param int $uid
	 * @param array $perms
	 * @return int
	 */
	public function set_perms($uid,$perms) {
		// if(empty($perms))return 1;
		// $perm = array();
		// foreach($perms as $menu_id=>$arr){
		// 	$p = 0;
		// 	foreach($arr as $perm_id=>$val){
		// 		if($val<=0)continue;
		// 		$p |= $perm_id;
		// 	}
				
		// 	$perm[] = sprintf('(x,%d,%d)',$menu_id,$p);
		// }
		// $permlist = implode(',', $perm);
		// $param = array(
		// 		'uid' => $uid,
		// 		'permlist' => $permlist
		// );
		
		// $result = DBproxy::getProcedure('Manage')->setDimension(2)->spSysUserPermIu('i',$param);
		// return $result['status']==0 ? 0 : 1;
	}
	
	/**
	 * 取用户所属角色
	 * @param int $uid
	 * @return array
	 */
	public function get_user_roles($uid){
		$param = array(
				'uid' => $uid
		);
		// $result = $this->db->execute('sp_sys_user_role_s', $param);
		$result = DBproxy::getProcedure('Manage')->setDimension(2)->spSysUserRoleS($param);

		$roleids = array();
		if(!empty($result)){
			foreach($result as $v){
				$roleids[] = $v['role_id'];
			}
		}
		return $roleids;
	}
	
	/**
	 * 取用户菜单权限
	 * @param int $uid
	 * @return array
	 */
	public function get_user_perms($uid){
		$param = array(
				array($uid)
		);
		$result = $this->db->execute('sp_sys_user_perm_s', $param,2);
		$perms = array();
		if(!empty($result)){
			foreach($result as $v){
				$perms[$v['menu_id']] = $v['perm_id'];
			}
		}
		return $perms;
	}
	
	public function __destruct(){
		unset($this->userinfo);
	}
	
}
