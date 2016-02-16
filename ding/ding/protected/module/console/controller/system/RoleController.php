<?php
Doo::loadController('ApplicationController');
/**
 * 用户角色管理
 * @author xinkq
 */
class RoleController extends ApplicationController {

	public static $dataTableUrl = NULL;

	public static $addUrl = NULL;

	public  static $modUrl = NULL;

	public static $delUrl = NULL;

	public function init() {
		RoleController::$dataTableUrl = adminAppUrl('system/role/dataTable');
		RoleController::$addUrl = adminAppUrl('system/role/add');
		RoleController::$modUrl = adminAppUrl('system/role/mod?id=');
		RoleController::$delUrl = adminAppUrl('system/role/del?id=');
	}

	public function dataTable() {
		Doo::loadClassAt('html/DataTable','default');
		$dt = new DataTable();

		function table_button($row,$rowData) {
			$modUrl = RoleController::$modUrl.$rowData['id'];
			$delUrl = RoleController::$delUrl.$rowData['id'];
			$a = '<a href="'.$modUrl.'" class="blue-stripe btn mini">修改</a>';
			$a .= '  <a href="'.$delUrl.'" class="red-stripe btn mini js-datatable-del">删除</a>';
			return $a;
		}

		$header = array(
			    'id'  => 'ID',
				'name' => '角色名',
				'table_button_action' => array('name'=>'操作','callback'=>'table_button')
		);

		Doo::loadClassAt('Role','default');
		$role = new role();
		$data = $role->get_list();
		$content = $dt->setTitle('')
					   ->setAttr(array('class'=>'table'))
					   ->setHeader($header)
					   ->setData($data)
					   ->setDefaultValue('unkown')
					   ->setTopContent('<a href="'.RoleController::$addUrl.'" class="btn green-stripe"><i class="icon-plus"></i>新增角色</a>')
					   ->render(false);
		$this->contentlayoutRender($content);
	}

	public function add() {
		if($this->isAjax() && $_POST) {
			$v = Doo::loadHelper('DooValidator',true);
			$success = true;
			$errors = array();

			//D($postData);
			$rules = array(
				'name' => array(
					        array('required',"请填写角色名"),
					        array('minlength',2,"角色名最少长度不允许少于2个字节"),
	                        array('maxlength',12,"角色名最大长度不允许大于12个字节"),
	                        
	                ),
				'perm' =>  array(
	                        array('required',"请勾选角色权限"),
	                )
			);

			// 验证数据
			if($errors = $v->validate($_POST,$rules)) {
				$success = false;
			}

			// 插入角色数据
			if($success) {
				$role = Doo::loadClassAt('Role','default');
				$role = new role();
				list($success,$errors) = array_values($role->insert($_POST));
			}

			// 处理返回路径
			if($success) {
				if(isset($_POST['saveAndReutrn'])) {
					$errors = RoleController::$dataTableUrl;
				} else if(isset($_POST['saveAndSee'])){
					$errors = RoleController::$modUrl.$errors;
				} else {
					$errors = RoleController::$addUrl;
				}
			}

			$this->ajaxFormResult($success,$errors);
		} else {
			Doo::loadClassAt('html/DooFormExt','default');
			Doo::loadClassAt('Menu','default');
			$menu = new Menu(DBproxy::getManage());
			$perms = array();
			$menus = $menu->get_menu_formlists($perms);
			// 引入树状css
			array_push($this->_includeCssFileList,'css/tree.css');

			$form = new DooFormExt(array(
			        'method' => 'post',
			        'renderFormat' => 'html',
			        'action' => '',
			        'attributes'=> array('id'=>'js-form','class'=>'form-horizontal'),
			        'elements' => array(
			        	'errors' => array('display', array(
			        		'div' => false,
			                'label-hide' => false,
			 				'content' => '<div id="js-form-errors" class=""></div><div style="clear:both"></div>',
			            )),
			            'name' => array('text', array(
			                'label' => '角色名',
			 				'attributes' => array('class'=>"m-wrap"),
			 				'value' => ''
			            )),
			            'menus' => array('display', array(
			                'label' => '菜单',
			 				'content' => '<div style="width:800px;height:500px;overflow-y:auto;" class="tree-wrapper">'.$menus.'</div>',
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
			            'saveAndSee' => array('button', array(
			            	'div' => false,
			            	'left' => ' ',
			                'label' => '保存&查看<i class="icon-arrow-right"></i>',
			                'attributes' => array('class'=>"btn blue"),
			                'value' => 1
			            )),
			            'cancel' => array('display', array(
			            	'div' => false,
			            	'left' => ' ',
			                'content' => '<a class="btn" href="'.RoleController::$addUrl.'"><i class="icon-undo"></i>取消</a>',
			            )),
			            'cancelAndReturn' => array('display', array(
			            	'div' => false,
			            	'left' => ' ',
			            	'right' => '</div>',
			                'content' => '<a class="btn" href="'.RoleController::$dataTableUrl.'"><i class="icon-arrow-left"></i>取消&返回</a>',
			            )),
			        ))
			);

			$this->contentlayoutRender($form->render());
		}
	}

	public function del() {
		$id = (int) $this->getUrlVar('id');
		Doo::loadClassAt('Role','default');
		$role = new role();
		$result = $role->del($id);
		$this->alert($result['success'] ? '删除成功' : '删除失败',$result['success'] ? "success" : "errors");
	}

	public function mod() {
		$id = (int) $this->getUrlVar('id');
		Doo::loadClassAt('Role','default');
		$role = new role();
		$data = $role->get_one($id);

		if(empty($data)) {
			$this->alert('没有找到数据');
			return;
		}

		if($this->isAjax() && $_POST) {
			$v = Doo::loadHelper('DooValidator',true);
			$success = true;
			$errors = array();

			//D($postData);
			$rules = array(
				'name' => array(
					        array('required',"请填写角色名"),
					        array('minlength',2,"角色名最少长度不允许少于2个字节"),
	                        array('maxlength',12,"角色名最大长度不允许大于12个字节"),
	                ),
				'perm' =>  array(
	                        array('required',"请勾选角色权限"),
	                )
			);

			// 验证数据
			if($errors = $v->validate($_POST,$rules)) {
				$success = false;
			}

			// 插入角色数据
			if($success) {
				$role = Doo::loadClass('Role',true);
				$_POST['id'] = $id;
				list($success,$errors) = array_values($role->update($_POST));
			}

			// 处理返回路径
			if($success) {
				if(isset($_POST['saveAndReutrn'])) {
					$errors = RoleController::$dataTableUrl;
				} else if(isset($_POST['saveAndSee'])){
					$errors = RoleController::$modUrl.$errors;
				} else {
					$errors = RoleController::$addUrl;
				}
			}

			$this->ajaxFormResult($success,$errors);
		} else {
			Doo::loadClassAt('html/DooFormExt','default');		
			Doo::loadClassAt('Menu','default');
			$menu = new Menu(DBproxy::getManage());
			$perms = $role->get_role_perm($id);			
			$menus = $menu->get_menu_formlists($perms);
			// 引入树状css
			array_push($this->_includeCssFileList,'css/tree.css');

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
			            'name' => array('text', array(
			                'label' => '角色名',
			 				'attributes' => array('class'=>"m-wrap"),
			 				'value' => $data[0]['name']
			            )),
			            'menus' => array('display', array(
			                'label' => '菜单',
			 				'content' => '<div style="width:800px;height:500px;overflow-y:auto;" class="tree-wrapper">'.$menus.'</div>',
			            )),
			            'saveAndReutrn' => array('button', array(
			            	'div' => false,
			            	'left' => '<div class="form-actions js-submitButton">',
			                'label' => '<i class="icon-arrow-left"></i>保存&返回',
			                'attributes' => array('class'=>"btn blue"),
			                'value' => 1
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
			                'content' => '<a class="btn" href="'.RoleController::$dataTableUrl.'"><i class="icon-arrow-left"></i>取消&返回</a>',
			            )),
			        ))
			);

			$this->contentlayoutRender($form->render());
		}
	}

}