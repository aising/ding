<?php
 /**
 * 角色类
 * @package application
 * @author xinkq
 */
 
class Role {
	private $db;
	public function __construct($db='') {
		$this->db = $db;
	}
	
	/**
	 * 取角色分页列表
	 * @param int $page
	 * @param int $psize
	 * @return array
	 */
	public function get_list() {
		return  DBproxy::getProcedure('manage')->spSysRoleS(-1);
	}
	
	/**
	 * 取所有角色键值数组(roleid=>role_name)
	 * @return array
	 */
	public function get_roles() {
		$result = array();
		$rs = $this->get_list();

		if(!empty($rs)){
			foreach($rs as $k=>$v){
				$result[$v['id']] = $v['name'];
			}
		}
		
		return $result;
	}
	
	/**
	 * 取一个角色信息
	 * @param int $id
	 * @return array
	 */
	public function get_one($id) {
		return  DBproxy::getProcedure('manage')->spSysRoleS($id);
	}
	
	/**
	 * 添加角色
	 * @param array $row 角色信息
	 * @return int
	 */
	public function insert($data) {
		$result = DBproxy::getProcedure('manage')->setDimension(1)->spSysRoleIu('i',0,$data['name']);
		$id = 0;
		$status=1;
		if($result['status']==0&&!empty($result['id'])){
			$id = $result['id'];
			
			if($this->perm_iu($id, $data['perm'])!=0){
				$status = 2;
			}else{
				$status = 0;
			}
		}else{
			$status = 3;
		}

		return array('success'=>$status == 0 ? true : false,'errors'=>$id==0 ? '数据库操作出错' : $id);
	}
	
	/**
	 * 更新角色权限
	 * @param int $roleid
	 * @param array $perms
	 * @return number
	 */
	private function perm_iu($roleid,$perms){
		if(empty($perms))return 1;
		$perm = array();
		foreach($perms as $menu_id=>$arr){
			$p = 0;
			foreach($arr as $perm_id=>$val){
				if($val<=0)continue;
				$p |= $perm_id;
			}
			
			$perm[] = sprintf('(x,%d,%d)',$menu_id,$p);
		}

		$permlist = implode(',', $perm);
		
		$result = DBproxy::getProcedure('manage')->setDimension(0)->spSysRolePermIu($roleid,$permlist);

		return $result['status'] ==0 ? 0:2;
	}
	
	/**
	 * 更新角色
	 * @param array $row 角色信息
	 * @return int
	 */
	public function update($data) {
		$status=1;
		$id = $data['id'];
		$result = DBproxy::getProcedure('manage')->setDimension(1)->spSysRoleIu('u',$data['id'],$data['name']);
		if($result['status']==0){			
			if($this->perm_iu($data['id'], $data['perm'])>0){
				$status = 2;
			}else{
				$status = 0;
			}
		}else{
			$status = 3;
		}
		return array('success'=>$status == 0 ? true : false,'errors'=>$data['id']);
	}
	
	/**
	 * 删除角色
	 * @param int $id
	 * @return int
	 */
	public function del($id) {
		$success = true;
		$errors = '';
		$data = $this->get_one($id);
		if(!empty($data)) {
			$result = DBproxy::getProcedure('manage')->setDimension(0)->spSysRoleD($id);

			$success =  $result['status']!=0 ? false : true;
			if(!$success) {
				$errors = '数据删除出错';
			}	
		} else {
			$success = false;
			$errors = '数据不存在';
		}
		return array('success'=>$success,'errors'=>$errors);
	}
	
	/**
	 * 取角色权限
	 * @param int $roleid
	 * @return array
	 */
	public function get_role_perm($roleid){
		$result = DBproxy::getProcedure('manage')->spSysRolePermS($roleid);		
		//db权限和菜单拆分
		$permids = explode('),', $result[0]['perm_id']);
		foreach ($permids as $key => $value) {
			$perms_menu_peridArr = explode(',', $value);
			$perms_menu_perid[] = array('menu_id'=>$perms_menu_peridArr[1],'perm_id'=>$perms_menu_peridArr[2]);
		}

		$perms = array();
		if(!empty($perms_menu_perid)){
			foreach($perms_menu_perid as $k=>$v){
				$perms[$v['menu_id']] = $v['perm_id'];
			}
		}
		return $perms;
	}
}
?>