<?php
/**
 * 页面权限验证
 * @auth xinkq
 */
class PageAuth {
	const P_NOTAUTH = 0;
	const P_VIEW = 1;
	const P_AUDIT1 = 2;
	const P_AUDIT2 = 4;
	const P_AUDIT3 = 8;
	const P_ADD = 16;
	const P_MOD = 32;
	const P_DEL = 64;

	public $_noCheckAuthorityPage = array('?m=index&c=main','?m=do&c=login','?m=do&c=quit');

	public static function getDefined() {
		$ini_perms = array(
		PageAuth::P_VIEW => '查看',
		PageAuth::P_AUDIT1 => '初审',
		PageAuth::P_AUDIT2 => '二审',
		PageAuth::P_AUDIT3 => '终审',
		PageAuth::P_ADD => '增加',
		PageAuth::P_MOD => '修改',
		PageAuth::P_DEL => '删除'
	    );
	    return $ini_perms;
	}
	/**
	 * 权限检查及跳转到无权限面
	 * @param int $checkvalue 该页面的权限标识
	 */
	public function auth($checkvalue,$m='',$c='') {

		if(PageAuth::P_NOTAUTH === $checkvalue) {
			return TRUE;
		}
		
		if (!isset($_SESSION['authory']) || !$this->checkAuthority($_SESSION['authory'], $checkvalue,$m,$c)) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * 检查页面权限
	 * @param unknown $authority        	
	 * @param unknown $checkvalue        	
	 * @return boolean
	 */
	public function checkAuthority($authority, $checkvalue) {		
		if (empty($authority)) {
			return false;
		}		
		
		//超管直接返回成功
		if($authority == '-1') return true;
		
		Doo::loadClassAt('Menu','default');
		$menu = new menu(DBproxy::getManage());
		$menuList = $menu->getMenuArray();
		foreach ( $menuList as $key => $var ) {
			
			if (empty($var['url'])) {
				continue;
			}

			if (strpos($var['url'], '/') !== 0) {
				continue;
			}
			
			$menuid = -2;			
			if(strpos($var['url'],'/')===0){
		        $first = '';
		    }else{
		        $first = '/';
		    }

		    // var_dump($_SERVER['REDIRECT_URL'] , trim('/'.Doo::conf()->adminRoute.$first.$var['url']),'<br>');
		    //寻找后台菜单URL和当前URL 是否匹配。
			if($_SERVER['REDIRECT_URL'] == trim('/'.Doo::conf()->adminRoute.$first.$var['url'])) {
				$menuid = $var['id'];
				
				// echo '<pre>';var_dump($_SESSION['authory'],isset($authority[$menuid]),$var);
				// var_dump($authority,$menuid,$checkvalue);die;
			}

			if (isset($authority[$menuid])  && (($authority[$menuid] & $checkvalue) == $checkvalue)) {
				return true;
			}
		}
		return false;
	}

	

}