<?php
class Menu{

	private $menuList = NULL;
	private $recursArr = NULL;
	private $idRelateMenu = array();
	private $parentid = array();
	private $db;

	private $_redis;
	private $_cacheTime = 86400;

	public function __construct($db=''){
		$this->db = $db;
		Doo::loadClassAt('KVCache','default');
		$KVCache = new KVCache();
		$this->_redis = $KVCache->cfile();

	}
	

	public function get_all() {		
		$sql = 'SELECT * FROM `menu` order by sort_id desc';
		$list = $this->db->execute($sql,'s');
		return $list['data'];
	}
	
	
	/**
	 * 获取菜单原始数组
	 */
	public function getMenuArray(){
		$menuList = $this->_redis->get(Doo::conf()->redisPrefix.':menuList');
		$menuList = json_decode($menuList,true);
		if ($menuList) {
			$this->menuList = $menuList;
			return $this->menuList;
		}else{			
			$this->menuList = $this->get_all();
			usort($this->menuList,array($this,'menuSort'));
			$this->_redis->set(Doo::conf()->redisPrefix.':menuList',$this->_cacheTime,json_encode($this->menuList));
			return $this->menuList;
		}
	}
	
	
	/**
	 * 清楚缓存里面的MenuList
	 */
	public function clearCache(){
		//xcache::del('menuList');
		$this->_redis->flush(Doo::conf()->redisPrefix.':menuList');
	}
	
	
	/**
	 * 将menuList递归成关联数组
	 * @param number $parent
	 * @param unknown $config
	 * @param unknown $arr
	 */
	private function walk($parent=0,&$config,&$arr) {
		foreach($config as $key=>$val){
				
			if($val['parent_id'] == $parent){
				$arr[$val['id']]=array();
				unset($config[$key]);
			}
		}
		if(empty($config))return;
	
		foreach($arr as $key => $val){
			$this->walk($key,$config,$arr[$key]);
		}
		
	}
	
	
	/**
	 * 将menuList的菜单id做为键形成新的以menuid引导的数组
	 * @return multitype:
	 */
	public function idRelateMenuArr(){
		if (empty($this->menuList)) {
			$this->getMenuArray();
		}		
		foreach ($this->menuList as $key => $var){
			$this->idRelateMenu[$var['id']] = $var;
		}
		return $this->idRelateMenu;
	}
	
	
	/**
	 * 公开的获取菜单递归好的数组的方法
	 * @return multitype:
	 */
	public function recursArray(){
		if (empty($this->menuList)) {
			$this->getMenuArray();
		}
		$tmpArr = $this->menuList;
		$outArr = array();
		$this->walk(0,$tmpArr, $outArr);
		$this->recursArr = $outArr;
		return $outArr;
	}
	
	
	/**
	 * 获取父类id的树状结构的数组，selectIndex的作用是把要标记的父类id在下拉框中被选中
	 */
	public function getParentidTree($selectIndex = 0){
		if (empty($this->recursArr)) {
			$this->recursArray();
		}
		if (empty($this->idRelateMenu)) {
			$this->idRelateMenuArr();
		}		
		$str = '';
		$seprate = '';
		$this->combineParentidTreeStr($this->recursArr, $str,$seprate,$selectIndex);
		return $str;
	}
	
	
	/**
	 *将父类id编入数组 
	 */
	public function parentidIntoArray(){
		if (!empty($this->parentid)) {
			return $this->parentid;
		}
		
		if (empty($this->idRelateMenu)) {
			$this->idRelateMenuArr();
		}
		
		//var_dump($this->idRelateMenu);
		
		foreach ($this->idRelateMenu as $key => $var){
			if (isset($this->idRelateMenu[$var['parent_id']]) && ($this->idRelateMenu[$var['parent_id']]['is_show'] == 1)) {
				$this->parentid[$var['parent_id']] = 0;
			}
		}
		
		return $this->parentid;
		
	}
	
	
	/**
	 * 将菜单的父类id递归成树状结构，作为前端的下拉选中框
	 * @param unknown $arr
	 * @param unknown $str
	 * @param string $seprate
	 * @param unknown $selectIndex
	 */
	private function combineParentidTreeStr($arr,&$str,$seprate = '',$selectIndex){		
		if (empty($arr)) {
			return ;
		}
		$seprate .= "&nbsp;&nbsp;&nbsp;&nbsp;";

		foreach ($arr as $key => $var){
			if ($this->idRelateMenu[$key]['type_id'] != 2 || $this->idRelateMenu[$key]['is_show'] != 1) {
				continue;
			}
			if ($key == $selectIndex) {
				$str .= "<option value=\"{$key}\" selected>{$seprate}├{$this->idRelateMenu[$key]['menu_name']}</option>";
			}else{
				$str .= "<option value=\"{$key}\">{$seprate}├{$this->idRelateMenu[$key]['menu_name']}</option>";
			}
				$this->combineParentidTreeStr($var, $str,$seprate,$selectIndex);

		}
	}
	
	
	public function getMenuTreeList(){
		if (empty($this->recursArr)) {
			$this->recursArray();
		}
		if (empty($this->idRelateMenu)) {
			$this->idRelateMenuArr();
		}		
		$marr = array();
		$this->doMenuTreeList($this->recursArr, $marr);
	
		$menuArr = array();
		$i=0;
		foreach($marr as $k=>$v){
			if(!isset($this->idRelateMenu[$k]))continue;
			$i++;
			$menuArr[$i] = $this->idRelateMenu[$k];			
			$menuArr[$i]['_sep'] = $v;
		}
		return $menuArr;
	}
	
	private function doMenuTreeList($arr,&$marr,$seprate=''){
		$seprate .= '.';
		foreach ($arr as $key => $var){
			$marr[$key] = $seprate;
			$this->doMenuTreeList($var, $marr, $seprate);
		}
	}
	
	
	/**
	 * 将菜单做成前端需要的  d.add('','','','') 结构
	 * @param unknown $authory
	 * @return string
	 */
	public function getMenuTree($authory){
		if (empty($this->menuList)) {
			$this->getMenuArray();
		}
		$str = '';
		$childids = null;
		foreach ($this->menuList as $key => $var){
			if ($var['is_show'] != 1 ) {
				continue;
			}
			
			// if ($authory!=-1 && (!isset($authory[$var['id']]) || (($authory[$var['id']]&P_VIEW)!=P_VIEW))) {
			// 	continue;
			// }
			
			//非超管附加所有父菜单
			if ($authory!=-1){
				$childids[$var['parent_id']] = $var['id'];
			}
			
			if ((strpos($var['page_url'], '?') !== false) || empty($var['page_url']) ) {
				$str .= "d.add({$var['id']},{$var['parent_id']},\"{$var['name']}\",\"{$var['page_url']}\");";
			}else {
				$str .= "d.add({$var['id']},{$var['parent_id']},\"{$var['name']}\",\"?{$var['page_url']}\");";
			}
		}
		
		if(!empty($childids)){
			$parentmenus = array();
			foreach($childids as $parent_id=>$child_id){
				$arr = $this->getParents($parent_id);
				foreach($arr as $k=>$v){
					$parentmenus[$k] = $v;
				}
			}
			//print_r($parentmenus);exit;
			
			foreach($parentmenus as $key => $var){
				if ((strpos($var['page_url'], '?') !== false) || empty($var['page_url']) ) {
					$str .= "d.add({$var['id']},{$var['parent_id']},\"{$var['menu_name']}\",\"{$var['page_url']}\");";
				}else {
					$str .= "d.add({$var['id']},{$var['parent_id']},\"{$var['menu_name']}\",\"?{$var['page_url']}\");";
				}
			}
		}
		return $str;
	}
	

	/**
	 * 将菜单做成前端需要的  d.add('','','','') 结构
	 * @param unknown $authory
	 * @return string
	 */
	public function setAuthory($authory){
		if (empty($this->menuList)) {
			$this->getMenuArray();
		}

		//超管则所有菜单都可以看到
		if($authory == -1) return;

		$temp = array();
		$childids = array();
		foreach ($this->menuList as $key => $var){			
			// if ($var['is_show'] != 1 ) {
			// 	continue;
			// }
			//检测用户是否有这大类的权限
			if ($authory!=-1 && !isset($authory[$var['id']])) {
				continue;
			}
			
			//非超管附加所有父菜单
			if ($authory!=-1){
				$childids[$var['parent_id']]['is_show'] = 1;
			}
			$temp[] = $var;
		}
		
		if(!empty($childids)){
			$parentmenus = array();
			foreach($childids as $parent_id=>$child_id){
				$arr = $this->getParents($parent_id);
				foreach($arr as $k=>$v){
					$parentmenus[$k] = $v;
				}
			}

			foreach($parentmenus as $key => $var){
				$temp[] = $var;
			}
		}

		$this->menuList = $temp;
		unset($temp);	
	}


	/**
	 * 获取某个父节点的子节点
	 * @param unknown $pid
	 * @return multitype:unknown
	 */
	public function getCurrent($pid){
		$subs = array();	

		if (!empty($this->menuList)) {		
			foreach ($this->menuList as $key => $var){
				if ($var['is_show'] != 1 || $pid!=$var['parent_id']) {
					continue;
				}					
				$subs[] = $var;
			}
		}
		
		//超管把代理商的后台入口屏蔽
		if($_SESSION['authory'] == -1 && $pid == 0 ){			
			foreach ($subs as $key => $value) {
				if(in_array($value['id'], Doo::conf()->hidden)){
					unset($subs[$key]);
				}
			}
		}

		return $subs;
	}

	/**
	 * 获取某个父节点的子节点
	 * @param unknown $pid
	 * @return multitype:unknown
	 */
	public function getChilds($pid){
		$subs = array();
		if($pid==0)return $subs;
		foreach ($this->menuList as $key => $var){
			if ($var['is_show'] != 1 || $pid!=$var['parent_id']) {
				continue;
			}				
			$subs[] = $var;
		}
	
		return $subs;
	}
	
	public function getParents($parent_id){
		if (empty($this->menuList)) {
			$this->getMenuArray();
		}
		
		$pids = array();
		//最多循环10次-即10级
		for($i=0;$i<10;$i++){
			foreach ($this->menuList as $key => $var){
				if($var['id'] == $parent_id){
					$pids[$var['id']] = $var;
					$parent_id = $var['parent_id'];
					break;
				}
			}
			
			if($parent_id==0)break;
		}
		
		return $pids;
	}
	
	
	/**
	 * 
	 * @param string $perms
	 * @return string
	 */
	public function get_menu_formlists($perms='',$ext=0){
		if (empty($this->menuList)) {
			$this->getMenuArray();
		}
		
		$root_id = 0;
		$root_ids = array();
		foreach($this->menuList as $k=>$v){
			if($v['parent_id']==0){
				$root_id = $v['id'];
				$root_ids[$v['id']] = $v['menu_name'];
				//break;
			}
		}
		
		$str = '';
		foreach($root_ids as $root_id=>$root_name){
			$str.= '<ul><li><span class="label">'.$root_name.'';
			$this->get_menu_formlist($root_id,$perms,$str,$ext);
			$str.= '</li></ul>';
		}
		return $str;
	}
	
	
	/**
	 * 
	 * @param unknown $pid
	 * @param unknown $perms
	 * @param unknown $str
	 * @return number
	 */
	public function get_menu_formlist($pid,&$perms,&$str,$ext=0){
		if (empty($this->menuList)) {
			$this->getMenuArray();
		}
		$menus = array();
		foreach($this->menuList as $k=>$v){
			if($v['parent_id']!=$pid)continue;
			$menus[$k] = $v;
		}
		
		if(count($menus)>0){
			$str .=  '<ul>';
			foreach($this->menuList as $k=>$v){
				if($v['parent_id']!=$pid)continue;
				$str.= '<li><span class="label">'.$v['menu_name'].' ';
				
				$c_n = $this->get_menu_formlist($v['id'], $perms, $str,$ext);
				
				if($c_n==0){
					//$str.= '<span>';
					$str.= ':';
					$str.= '</span>';
					//if($ext)
					//	$str.= '<input type="checkbox" class="checkbox" name="perm_none['.$v['id'].'][0]" value="0"  /><span class="label">禁用,</span>';
					foreach(PageAuth::getDefined() as $p=>$pname){
						if($v['perm_id'] & $p){
							$checkstr = '';
							if(isset($perms[$v['id']]) && ($perms[$v['id']] & $p)){
								$checkstr = ' checked';
							}
							$str.= '<input type="checkbox" class="checkbox" name="perm['.$v['id'].']['.$p.']" value="'.$p.'" '.$checkstr.' /><span class="label">'.$pname.'</span>';
						}
					}
				}
				$str.= '</span>';
				
				$str.= '</li>';
			}
			$str .=  '</ul>';
		}
		
		//if($n==0)$str=str_replace('<ul></ul>', '', $str);
		return count($menus);
	}
	
	
	/**
	 * 添加菜单
	 * @param array $data
	 * @return boolean
	 */
	public function menuAdd($data){
		$param = array(
				'menu_name' => $data['menu_name'],
				'sort_id' => $data['sort_id'],
				'type_id' => $data['type_id'],
				'perm_id' => $data['perm_id'],
				'is_show' => $data['is_show'],
				'url' => $data['url'],
				'parent_id' => $data['parent_id'],
		);
		
		$key = '`'.implode(array_keys($param),'`,`').'`'; //转key为表字段
		$val = "'".implode($param,"','")."'"; //转值为插入的内容

		$sql = "INSERT INTO `menu` (`id`,".$key.") 
				VALUES (NULL, ".$val.")";
		$result = $this->db->execute($sql,'i');

		$this->clearCache();
		return $result;
	}
	
	
	/**
	 * 修改菜单
	 * @param int $menuid
	 * @param array $data
	 * @return boolean
	 */
	public function menuEdit($menuid,$data){

		$param = array(
				'menu_name' => $data['menu_name'],
				'sort_id' => $data['sort_id'],
				'type_id' => $data['type_id'],
				'perm_id' => $data['perm_id'],
				'is_show' => $data['is_show'],
				'url' => $data['url'],
				'parent_id' => $data['parent_id'],
		);
		
		$key = '`'.implode(array_keys($param),'`,`').'`'; //转key为表字段
		$val = "'".implode($param,"','")."'"; //转值为插入的内容

		$sql = "UPDATE `menu` SET   `menu_name` = '" . $data['menu_name'] . "' ,
									`sort_id` = '" . $data['sort_id'] . "' ,
									`type_id` = '" . $data['type_id'] . "' ,
									`perm_id` = '" . $data['perm_id'] . "' ,
									`is_show` = '" . $data['is_show'] . "' ,
									`url` = '" . $data['url'] . "' ,
									`parent_id` = '" . $data['parent_id'] . "' 

				WHERE `menu`.`id` = '" . $menuid . "'";
		// echo $sql;die;
		$result = $this->db->execute($sql,'u');


		// $param = array(
		// 		array('u')
		// 		,array($menuid)
		// 		,array($data['name'])
		// 		,array($data['sort_id'])
		// 		,array($data['type_id'])
		// 		,array($data['perm_id'])
		// 		,array($data['is_show'])
		// 		,array($data['url'])
		// 		,array($data['parent_id'])
		// );
		
		// $result = $this->db->execute('sp_sys_menu_iu', $param,1);
		$this->clearCache();
		return $result;
	}
	
	
	/**
	 * 删除菜单
	 * @param unknown $menuid
	 * @return boolean
	 */
	public function menuDel($menuid){		
		$sql = "DELETE FROM `menu` WHERE `menu`.`id` = '$menuid'";
		$result = $this->db->execute($sql,'d');
		$this->clearCache();
		return true;
	}
	
	/**
	 * 菜单排序回调
	 * @param  $a
	 * @param  $b
	 * @return number
	 */
	private function menuSort($a,$b){		
		if ($a['sort_id'] == $b['sort_id']) {
			return 0;
		}
		return ($a['sort_id'] < $b['sort_id']) ? -1 : 1;
	}
	
	/**
	 * 菜单权限
	 * @param unknown $permId
	 */
	public function getMenuList($permId){
	  $permList = array();
		if($permId & PageAuth::P_VIEW){
			$permList[] = PageAuth::P_VIEW; 
		}
		if ($permId & PageAuth::P_AUDIT1) {
			$permList[] = PageAuth::P_AUDIT1;
		}
		if ($permId & PageAuth::P_AUDIT2) {
			$permList[] = PageAuth::P_AUDIT2;
		}
		if ($permId & PageAuth::P_AUDIT3) {
			$permList[] = PageAuth::P_AUDIT3;
		}
		if ($permId & PageAuth::P_ADD) {
			$permList[] = PageAuth::P_ADD;
		}
		if ($permId & PageAuth::P_MOD) {
			$permList[] = PageAuth::P_MOD;
		}
		if ($permId & PageAuth::P_DEL) {
			$permList[] = PageAuth::P_DEL;
		}
		return $permList;
	}
	
	
	protected $_curMenuData = array();

	protected $_navigatorData = array();

	public function getPageTitle($url) {		
		$url = str_replace('/'.Doo::conf()->adminRoute, '', $url);

		if($url == '/' || $url == '/home') {
			return '控制面板';
		}

		foreach ($this->menuList as $val) {
			if(trim($val['url']) == trim($url)) {
				$this->_curMenuData = $val;
				return $val['menu_name'];
			}
		}
		return 'unknown title';
	}

	public function getNavigator() {
		if(empty($this->_curMenuData)) {
			return '';
		}

		$this->_navigatorData = $this->_getNavigator($this->_curMenuData['parent_id']);
		$this->_navigatorData = array_reverse($this->_navigatorData);
		$this->_navigatorData[] = $this->_curMenuData;

		return $this->_navigatorData;
	}

	public function _getNavigator($pid) {
		$name = array();
		foreach ($this->menuList as $val) {
			if($val['id'] == $pid) {
				$name[] = $val;
				if($val['parent_id'] > 0) {
					$name = array_merge($name , $this->_getNavigator($val['parent_id']));
				}
			}
		}
		return $name;
	}

	public function getTop() {
		if(empty($this->_navigatorData)) {
			return -1;
		}		
		$cur = current($this->_navigatorData);
		return $cur['id'];
	}

	public function __destruct(){
		unset($this->menuList);
	}
}
