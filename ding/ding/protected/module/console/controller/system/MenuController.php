<?php
Doo::loadController('ApplicationController');
/**
 * 菜单管理
 * @author xinkq
 */

class MenuController extends ApplicationController {
	
	public static $dataTableUrl = NULL;

	public static $addUrl = NULL;

	public  static $modUrl = NULL;

	public static $delUrl = NULL;

	public function init() {
		MenuController::$dataTableUrl = adminAppUrl('system/menu/dataTable');
		MenuController::$addUrl = adminAppUrl('system/menu/add');
		MenuController::$modUrl = adminAppUrl('system/menu/mod?id=');
		MenuController::$delUrl = adminAppUrl('system/menu/del?id=');
	}

	public static $data;

	public function dataTable() {
        Doo::loadClassAt('html/DataTable','default');
		Doo::loadClassAt('Menu','default');

		$menu = new menu(DBproxy::getManage());
		$dt = new DataTable();
		MenuController::$data = $data = $menu->getMenuTreeList();

		// D($data);
	    function table_name($row,$rowData,$val) {
    		$sep = substr($rowData['_sep'],1);
			if($sep!=''){
				$sep = str_replace('.','&nbsp;&nbsp;&nbsp;&nbsp;',$sep);
				$dot = '└─';
				if(isset(MenuController::$data[$row+1])){
					if(MenuController::$data[$row+1]['parent_id']==$rowData['parent_id']){
						$dot = '├─';
					}
				}
				$sep = $sep.$dot;
			}
			return '<div style="left">'.$sep.$val.'</div>';
	    }

	    function table_type($row,$rowData,$val) {
	    	return $val == 2 ? '分类' : '页面';
	    }

	    function table_show($row,$rowData,$val) {
	    	return $val == 1 ? '<span class="label label-success">是</span>' : '<span class="label">否</span>';
	    }

		// 表格按钮
		function table_button($row,$rowData,$val) {
			$modUrl =MenuController::$modUrl.$rowData['id'].'&menu_name='.$rowData['menu_name'].'&sort_id='.$rowData['sort_id']
			.'&type_id='.$rowData['type_id'].'&perm_id='.$rowData['perm_id'].'&is_show='.$rowData['is_show']
			.'&page_url='.urlencode($rowData['url'] ).'&parent_id='.$rowData['parent_id'].'&_sep='.$rowData['_sep'];
			$delUrl = MenuController::$delUrl.$rowData['id'];
				
			$a = '<a href="'.$modUrl.'" class="blue-stripe btn mini">编辑/查看</a>';
			$a .= '  <a href="'.$delUrl.'" class="red-stripe btn mini js-datatable-del">删除</a>';
			return $a;
		}

	    // 表头
        $header = array(
            'menu_name' => array('name' => '菜单名称','callback' => 'table_name'),
            'id' => array('name' => 'ID'),
            'parent_id' => array('name' => '所属父ID'),
            'type_id' => array('name' => '类型','callback' => 'table_type','headerAttr'=>'filterType="enum"'),
            'perm_id' => array('name' => '菜单权限'),
            'url' => array('name' => '菜单URL'),
            'is_show' => array('name' => '是否显示','callback' => 'table_show','headerAttr'=>'filterType="enum"'),
            'sort_id' => array('name' => '排序'),
            'action' => array('name' => '操作','callback' => 'table_button'),
        );

        // 生产表格
        $content = $dt->setTitle('')
                ->setAttr(array('class' => 'table table-hover','id' => 'js-queryTable'))
                ->setHeader($header)
                ->setData($data)
                ->setTopContent('<a href="'.MenuController::$addUrl.'" class="btn green-stripe"><i class="icon-plus"></i>添加菜单</a>'.'<p id="js-queryTable-filter">Filter</p>')
                ->setDefaultValue('unkown')
                ->render(false);

        $this->_includeJsFileList[] = 'js/default/menu.js';
        // 显示模版
        $this->contentlayoutRender($content);
	}
	
	//添加菜单
	public function add() {
		Doo::loadClassAt('Menu','default');
		$menu = new menu(DBproxy::getManage());
		if ($this->isAjax() && $_POST) {
		
			$v = Doo::loadHelper('DooValidator', true);
			$success = true;
			$errors = array();
			$rules = $this->_getFormRule();
			// 验证数据
			if ($errors = $v->validate($_POST, $rules)) {
				$success = false;
			}
			//验证权限
			$permission = isset($_POST['permission']) ? array_sum($_POST['permission']) : 0;
			if ($permission == 0) {
				$success = false;
				$errors = '请选择用户权限';
			}

			// 插入数据库
			if ($success) {
                $param = array(
                			'menu_name' => trim($this->getUrlVar('menu_name'))
                			,'sort_id' => $this->getUrlVar('sort_id')
                			,'type_id' => $this->getUrlVar('type_id')
                			,'perm_id' => $permission
                		    ,'is_show' => $this->getUrlVar('is_show')
                		    ,'url' => $this->getUrlVar('page_url')
	                		,'parent_id' => $this->getUrlVar('parent_id')
                		
                );

				$res = $menu->menuAdd($param) ;
				if ($res['status'] == 0) {
					$id = $res['id'];
				} else {
					$success = false;
					$errors[] = '插入数据库出错';
				}
			}
		
			// 处理返回路径
			if ($success) {

				if (isset($_POST['saveAndReutrn'])) {
					$errors = MenuController::$dataTableUrl;
				}else {
					$errors = MenuController::$addUrl;
				}
			}
		
			// 处理表单位提交
			$this->ajaxFormResult($success, $errors);
		} else {
			//$data为菜单列表
			$data =  $menu->getMenuTreeList();
			// 显示生成表单
			Doo::loadClassAt('html/DooFormExt','default');
			$form = new DooFormExt($this->_getFormConfig(false,$data));
			array_push($this->_includeJsFileList,'js/default/sysMenu.js');
			$this->contentlayoutRender('<a class="btn green-stripe" href="'.MenuController::$dataTableUrl.'"><i class="icon-backward"> </i>菜单列表</a>'.$form->render());
		}
		
	}
	
	//修改菜单
	public function mod() {
		$id = $_GET['id'];
		$info = $_GET;
		$page_url = urldecode($_GET['page_url']);
		$info['page_url'] =  $page_url;
		Doo::loadClassAt('Menu','default');
		$menu = new menu(DBproxy::getManage());
		
		//通过url传递id值查询数据库
		
		if ($this->isAjax() && $_POST) {
		
			$v = Doo::loadHelper('DooValidator', true);
			$success = true;
			$errors = array();
			$rules = $this->_getFormRule();
			// 验证数据
			if ($errors = $v->validate($_POST, $rules)) {
				$success = false;
			}
			//验证权限
			$permission = isset($_POST['permission']) ? array_sum($_POST['permission']) : 0;
			if ($permission == 0) {
				$success = false;
				$errors = '请选择用户权限';
			}
		
			// 插入数据库
			if ($success) {
				$param = array(
						'menu_name' => trim($_POST['menu_name'])
						,'sort_id' => $_POST['sort_id']
						,'type_id' => $_POST['type_id']
						,'perm_id' => $permission
						,'is_show' => $_POST['is_show']
						,'url' => $_POST['page_url']
						,'parent_id' => $_POST['parent_id']
		
				);
				 
				$res = $menu->menuEdit($id,$param);
				if ($res['status'] == 0) {

					//$id = $res['id'];


				} else {
					$success = false;
					$errors[] = '插入数据库出错';
				}
			}
		
			// 处理返回路径
			if ($success) {
				if (isset($_POST['saveAndReutrn'])) {
					$errors = MenuController::$dataTableUrl;
				}else {
					$errors = MenuController::$addUrl;
				}
			}
		
			// 处理表单位提交
			$this->ajaxFormResult($success, $errors);
		} else {
			$data =  $menu->getMenuTreeList();
			//D($data);
			// 显示生成表单
			Doo::loadClassAt('html/DooFormExt','default');
			$form = new DooFormExt($this->_getFormConfig(false,$data,$info));
			array_push($this->_includeJsFileList,'js/default/sysMenu.js');
			$this->contentlayoutRender('<a class="btn blue-stripe" href="'.MenuController::$dataTableUrl.'"><i class="icon-plus"></i>添加菜单</a>'.$form->render());
		}
		
		
	}
	
	//删除菜单
	public function del() {
		$success = true;
		$errors = '删除成功';
		$menuId = (int) $this->getUrlVar('id', 0);
		//删除前查询一下数据是否存在
		Doo::loadClassAt('Menu','default');
		$menu = new menu(DBproxy::getManage());
		$return = $menu -> menuDel($menuId);
		if ($return == false) {
			$errors = '操作失败';
		}
		$this->alert($errors, $success ? 'success' : 'error');
	}
	
	/**
	 * 取得表单配置
	 * @param  boolean $isInsert 1 是插入表单配置,0 是修改表单
	 * @param  array   $data    修改表单时传入数组
	 * @return array
	 */
	protected function _getFormConfig($isInsert = true, $data = array(), $info = array()) {
		Doo::loadClassAt('DataExt','default');
		$dataExt = new DataExt();
		Doo::loadClassAt('Menu','default');
		$menu = new menu(DBproxy::getManage());
// 		D($info);
		$name = isset($info['menu_name']) ? $info['menu_name'] : ''; 
		$typeId = isset($info['type_id']) ? $info['type_id'] : 0;
		$sortId = isset($info['sort_id']) ? $info['sort_id'] : 0;
		$permId = isset($info['perm_id']) ? $info['perm_id'] : 0;
		//权限换算
		$permList = $menu->getMenuList($permId);
		$isShow = isset($info['is_show']) ? $info['is_show'] : 1;
		$pageUrl = isset($info['page_url']) ? $info['page_url'] : '';
		$parentId = isset($info['parent_id']) ? $info['parent_id'] : 0;
		$sep = isset($info['_sep']) ? $info['_sep'] : '';
		$menuRecursStr = $menu->getParentidTree(0);
		$list = array();
		$list[0] = '顶层菜单';
		foreach ($data as $row => $rowData) {
			if($rowData['type_id'] == 1) {
				continue;
			}
			$sep = substr($rowData['_sep'],1);
			if($sep!=''){
				$sep = str_replace('.','&nbsp;&nbsp;&nbsp;&nbsp;',$sep);
				$dot = '└─';
				if(isset($data[$row+1])){
					if($data[$row+1]['parent_id']==$rowData['parent_id']){
						$dot = '├─';
					}
				}
				//$sep = $sep.$dot;
				//$dot = '';
			}else{
				$dot = '├─';
			}
			$sep = $sep.$dot;
			$dot = '';
			$list[$rowData['id']] = $sep.$rowData['menu_name'];
		}
		$insertForm = array(
				'method' => 'post',
				'renderFormat' => 'html',
				'action' => '',
				'attributes' => array('id' => 'js-form', 'class' => 'form-horizontal'),
				'elements' => array(
						'errors' => array('display', array(
								'div' => false,
								'label-hide' => true,
								'content' => '<div id="js-form-errors" class=""></div><div style="clear:both"></div>',
						)),
						'parent_id' => array('select', array(
								'label' => '父类标识:',
								'attributes' => array('class'=>"m-wrap"),
								'multioptions' => $list,
								'value' => $parentId,
						)),
						'type_id' => array('select', array(
								'label' => '菜单类型:',
								'attributes' => array('class' => "m-wrap"),
								'multioptions' => array(1 => '页面' ,2 => '分类'),
								'value' => $typeId,
						)),

						'menu_name' => array('text', array(
								'label' => '菜单名称：',
								'attributes' => array('class' => "m-wrap"),
								'value' => $name,
								'help' => '必填项',
						)),
						'page_url' => array('text', array(
								'label' => '菜单地址：',
								'attributes' => array('class' => "m-wrap"),
								'value' => $pageUrl,
								'help' => '必填项',
						)),
						'permission' => array('MultiCheckbox', array(
								'label' => '菜单权限:',
								//'attributes' => array('class' => "m-wrap"),
								'multioptions' => PageAuth::getDefined(),//array(1 => '查看', 2 => '初审', 4 => '二审', 8 => '终审', 16 => '增加', 32 => '修改', 64 => '删除'),
								'value' => $permList,
								'help' => '<span class="label-warning label">修改菜单地址时将自动勾选</span>',
						)),
						'sort_id' => array('text', array(
								'label' => '排序标识：',
								'attributes' => array('class' => "m-wrap"),
								'value' => $sortId,
								'help' => '必填项',
						)),
						'is_show' => array('MultiRadio', array(
								'label' => '是否显示：',
								'multioptions' => array(1 => '是',  0 => '否'),
								'value' => array($isShow),
						)),
						'saveAndReutrn' => array('button', array(
								'div' => false,
								'left' => '<div class="form-actions js-submitButton">',
								'label' => '<i class="icon-arrow-left"></i>保存&返回',
								'attributes' => array('class' => "btn blue"),
								'value' => 1
						)),
						'saveAndAdd' => array('button', array(
								'div' => false,
								'left' => ' ',
								'label' => '<i class="icon-plus"></i>保存&新增',
								'attributes' => array('class' => "btn blue"),
								'value' => 1
						)),
						'cancel' => array('display', array(
								'div' => false,
								'left' => ' ',
								'content' => '<a class="btn" href="' . $_SERVER['REQUEST_URI'] . '"><i class="icon-undo"></i>取消</a>',
						)),
						'cancelAndReturn' => array('display', array(
								'div' => false,
								'left' => ' ',
								'right' => '</div>',
								'content' => '<a class="btn" href="' . MenuController::$dataTableUrl . '"><i class="icon-arrow-left"></i>取消&返回</a>',
						)),
				));
	
		if ($isInsert) {
			return $insertForm;
		} else {
			// 将数据写入表单
			foreach ($data as $key => $val) {
				if (isset($insertForm['elements'][$key])) {
					$insertForm['elements'][$key][1]['value'] = $val;
				}
			}
	
			return $insertForm;
		}
	}
	
	//name验证中文字符长度
	public static function vMaxLength($val) {
	
		if(mb_strlen($val,'utf8') > 50) {
			return '名称最大长度为50个字符';
		}
	}
	
	//memo验证中文字符长度
	public static function vMaxMemo($val) {
	
		if(mb_strlen($val,'utf8') > 255) {
			return '描述最大长度为255个字符';
		}
	}
	
	
	/**
	 * 取得表单验证规则
	 * @param  boolean $isInsert 1 是插入表单规则,0 是修改规则
	 * @return array
	 */
	protected function _getFormRule($isInsert = true) {
		$rule = array(
				'menu_name' => array(
						array('required', "请填写菜单名称"),
						//array('maxlength', 20, "名称最大长度为20个字符"),
						array('custom','MenuController::vMaxLength'),
				),
				'page_url' => array(
						array('required', "请填写菜单地址"),
						//array('maxlength', 255, "描述最大长度为255个字符"),
						array('custom','MenuController::vMaxMemo'),
				),
				'sort_id' => array(
						array('required',"请填写菜单标识"),
						array('integer',"菜单标识必须是整数"),
				),
		);
	
	
		if(!$isInsert) {
			unset($rule['play_id']);
			unset($rule['game_id']);
		}
	
		return $rule;
	}
}