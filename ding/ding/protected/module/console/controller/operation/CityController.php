<?php
Doo::loadController('ApplicationController');

class CityController extends ApplicationController {

    public static $dataTableUrl = NULL;

    public static $addUrl = NULL;

    public static $modUrl = NULL;

    public static $delUrl = NULL;

    public static $status = array('0'=>'正常','1'=>'失效');

    public static $data = array();

    public static $city = array();

    public function init() {
        Doo::loadClassAt('City','default');
        CityController::$dataTableUrl = adminAppUrl('operation/city/dataTable');
        CityController::$addUrl = adminAppUrl('operation/city/add');
        CityController::$modUrl = adminAppUrl('operation/city/mod?id=');
        CityController::$delUrl = adminAppUrl('operation/city/del?id=');
        // CityController::$city = DBproxy::getProcedure('Manage')->setDimension(2)->getCityList();
    }

    public function dataTable(){
        $param['city'] = $this->getUrlVar('city',NULL);        
        $data = DBproxy::getProcedure('Manage')->setDimension(2)->getCity($param);
        
        CityController::$data = $data['data'];

        Doo::loadClassAt('html/DataTable','default');
        Doo::loadClassAt('html/DooFormExt','default');
        $dt = new DataTable();

        function table_button($row,$rowData,$val) {            
            $a =  ' <a class="btn blue-stripe mini" href="'.CityController::$modUrl.$rowData['id'].'">'.'编辑</a>';
            $a .= ' <a class="red-stripe btn mini js-datatable-del" href="'.CityController::$delUrl.$rowData['id'].'" >删除</a>';
            return $a;
        }

        function table_status($row,$rowData,$val) {
            $status = CityController::$data[$rowData['id']]['status'];
            if( trim($status)=='0'){
                $a = '<span class="label label-success">正常可用</span>';
            }else{
                $a = '<span class="label">失效';
            }
            return $a;
        }
        
        function table_prentid($row,$rowData,$val) {
            $pname = CityController::$data[$rowData['id']]['pname'];
            if( trim($pname) ==  ''  ){
                $a = '顶级父类';
            }else{
                $a = CityController::$data[$rowData['id']]['pname'];
            }
            return $a;
        }
        
        // 表头
        $header = array(            
            'name' => array('name' => '城市名称'),
            'pname' => array('name' => '所属城市','callback' => 'table_prentid'),
            'id' => array('name' => '城市id(和百度的城市id关联)'),
            'status' => array('name' => '状态','callback' => 'table_status'),
            'action' => array('name' => '操作','callback' => 'table_button'),
        );

        $name = City::cateToOption(0,false,'array',$data);

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
                    'content' => '<a href="'.CityController::$addUrl.'" class="btn green-stripe"><i class="icon-plus"></i>添加城市</a>',
                    'attributes' => array('class'=>"m-wrap"),
                )),
                )
            ));
        // 生产表格
        $content = $dt->setTitle('')
                ->setAttr(array('class' => 'table table-hover','id' => 'js-queryTable'))
                ->setHeader($header)
                ->setData($name)
                ->setTopContent($form->render())
                ->setDefaultValue('unkown')
                ->render(false);
        
        // 显示模版
        $this->contentlayoutRender($content);
    }

    public function add(){
        if ($this->isAjax() && $_POST) {        
            $v = Doo::loadHelper('DooValidator', true);
            $success = true;
            $errors = array();
            $rules = $this->_getFormRule();
            // 验证数据
            if ($errors = $v->validate($_POST, $rules)) {
                $success = false;
            }

            //数据处理
            $param['parentid'] = intval($_POST['parentid']);
            $param['name'] = trim($_POST['name']);
            $param['sort'] = intval($_POST['sort']);
            $param['status'] = trim($_POST['status']);            

            $list = DBproxy::getProcedure('Manage')->setDimension(2)->cityIU('i',$param);

            if($list['status'] != 0 ){
                $success = false;
                $errors[] = L('添加分类失败！DB异常！');
            }
            // 处理返回路径
            if ($success) {
                if (isset($_POST['saveAndReutrn'])) {
                    $errors = CityController::$dataTableUrl;
                }else {
                    $errors = CityController::$addUrl;
                }
            }
            
            // 处理表单位提交
            $this->ajaxFormResult($success, $errors);

        }else{
            // 显示生成表单
            Doo::loadClassAt('html/DooFormExt','default');
            $form = new DooFormExt($this->_getFormConfig(true));
            $btn = '<a class="btn green-stripe" href="'.CityController::$dataTableUrl.'"><i class="icon-backward"> </i>'.L('城市列表').'</a>';
            // 显示模版
            $this->contentlayoutRender($btn.$form->render());    
        }
        
    }

    public function mod(){
        $id = (int)$this->getUrlVar('id');
        $param['id'] = $id;
        $res = DBproxy::getProcedure('Manage')->setDimension(2)->getCity($param);

        if($res['status'] !=0 ){
            $this->alert('data error');die;
        }
        if ($this->isAjax() && $_POST) {        
            $v = Doo::loadHelper('DooValidator', true);
            $success = true;
            $errors = array();
            $rules = $this->_getFormRule();
            // 验证数据
            if ($errors = $v->validate($_POST, $rules)) {
                $success = false;
            }

            //数据处理
            $param['parentid'] = intval($_POST['parentid']);
            $param['name'] = trim($_POST['name']);
            $param['sort'] = intval($_POST['sort']);
            $param['status'] = trim($_POST['status']);
            
            $list = DBproxy::getProcedure('Manage')->setDimension(2)->cityIU('u',$param,$id);

            if($list['status'] != 0 ){
                $success = false;
                $errors[] = L('修改失败！DB异常！');
            }
            // 处理返回路径
            if ($success) {
                if (isset($_POST['saveAndReutrn'])) {
                    $errors = CityController::$dataTableUrl;
                }else {
                    $errors = CityController::$addUrl;
                }
            }
            
            // 处理表单位提交
            $this->ajaxFormResult($success, $errors);

        }else{            
            // 显示生成表单
            Doo::loadClassAt('html/DooFormExt','default');
            $form = new DooFormExt($this->_getFormConfig(false,$res['data'][$id]));
            $btn = '<a class="btn green-stripe" href="'.CityController::$dataTableUrl.'"><i class="icon-backward"> </i>'.L('城市列表').'</a>';
            // 显示模版
            $this->contentlayoutRender($btn.$form->render());    
        }
    }

    //删除分类
    public function del() {
        $success = true;
        $errors = '删除成功';
        $id = (int) $this->getUrlVar('id',0);
        // 删除数据
        $return = DBproxy::getProcedure('Manage')->setDimension(2)->delCity($id);
        
        if ($return['status'] != 0 ) {
            $errors = '操作失败';
        }
        $this->alert($errors, $success ? 'success' : 'error');
    }


    protected function _getFormRule(){
        $rule = array(
                'name' => array(
                        array('required', L("请填写名称")),
                        array('maxlength', 20, L("名称最大长度为20个字符")),
                        array('minlength', 2, L("名称最小长度为2个字符")),
                        // array('custom','CityController::vMaxLength'),
                ),                
        );    
        return $rule;
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
        
        $id = isset($data['parentid']) ? $data['parentid'] : '';

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
                        'parentid' => array('display', array(
                                'label' => L('父分类:'),
                                'attributes' => array('class'=>"m-wrap"),
                                'content' => '<select id="parentid-element" name="parentid" class="m-wrap"><option value="0">'.L('顶级城市').'</option>'.
                                                City::cateToOption($id,false).'</select>'
                        )),
                        'name' => array('text', array(
                                'label' => L('城市名称:'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
                        )),
                        'status' => array('select', array(
                                'label' => L('可用状态:'),
                                'attributes' => array('class' => "m-wrap"),
                                'multioptions' => CityController::$status,
                                'value' => 0,
                        )),
                        'sort' => array('text', array(
                                'label' => L('排序:'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '0',
                        )),
                       

                        'saveAndReutrn' => array('button', array(
                                'div' => false,
                                'left' => '<div class="form-actions js-submitButton">',
                                'label' => '<i class="icon-arrow-left"></i>'.L('保存&返回'),
                                'attributes' => array('class' => "btn blue"),
                                'value' => 1
                        )),
                        'saveAndAdd' => array('button', array(
                                'div' => false,
                                'left' => ' ',
                                'label' => '<i class="icon-plus"></i>'.L('保存&新增'),
                                'attributes' => array('class' => "btn blue"),
                                'value' => 1
                        )),
                        'cancel' => array('display', array(
                                'div' => false,
                                'left' => ' ',
                                'content' => '<a class="btn" href="' . $_SERVER['REQUEST_URI'] . '"><i class="icon-undo"></i>'.L('取消').'</a>',
                        )),
                        'cancelAndReturn' => array('display', array(
                                'div' => false,
                                'left' => ' ',
                                'right' => '</div>',
                                'content' => '<a class="btn" href="' . CityController::$dataTableUrl . '"><i class="icon-arrow-left"></i>'.L('取消&返回').'</a>',
                        )),
                ));
    
        if ($isInsert) {
            return $insertForm;
        } else {
            // 将数据写入表单
            foreach ($data as $key => $val) {
                if (isset($insertForm['elements'][$key])) {
                    if($key == 'name'){
                        $val = $data['cname'];
                    }
                    $insertForm['elements'][$key][1]['value'] = $val;
                }
            }
    
            return $insertForm;
        }
    }
}

?>