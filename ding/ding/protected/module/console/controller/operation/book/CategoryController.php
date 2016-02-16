<?php
Doo::loadController('ApplicationController');

class CategoryController extends ApplicationController {

    public static $dataTableUrl = NULL;

    public static $addUrl = NULL;

    public static $modUrl = NULL;

    public static $delUrl = NULL;

    public static $status = array('0'=>'正常','1'=>'失效');

    public static $data = array();

    public static $city = array();

    public function init() {
        Doo::loadClassAt('Category','default');
        Doo::loadClassAt('City','default');
        CategoryController::$dataTableUrl = adminAppUrl('operation/book/category/dataTable');
        CategoryController::$addUrl = adminAppUrl('operation/book/category/add');
        CategoryController::$modUrl = adminAppUrl('operation/book/category/mod?id=');
        CategoryController::$delUrl = adminAppUrl('operation/book/category/del?id=');
        CategoryController::$city = '<select class="m-wrap" name="city" id="city-element"><option value="0">'.L('顶级城市').'</option>'.
                                                City::cateToOption($this->getUrlVar('city'),false).'</select>';
    }

    public function dataTable(){
        $param['city'] = $this->getUrlVar('city',NULL);        
        $data = DBproxy::getProcedure('Manage')->setDimension(2)->getCategory($param);
        
        CategoryController::$data = $data['data'];

        Doo::loadClassAt('html/DataTable','default');
        Doo::loadClassAt('html/DooFormExt','default');
        $dt = new DataTable();

        function table_button($row,$rowData,$val) {            
            $a =  ' <a class="btn blue-stripe mini" href="'.CategoryController::$modUrl.$rowData['id'].'">'.'编辑</a>';
            $a .= ' <a class="red-stripe btn mini js-datatable-del" href="'.CategoryController::$delUrl.$rowData['id'].'" >删除</a>';
            return $a;
        }

        function table_status($row,$rowData,$val) {
            $status = CategoryController::$data[$rowData['id']]['status'];
            if( trim($status)=='0'){
                $a = '<span class="label label-success">正常可用</span>';
            }else{
                $a = '<span class="label">失效';
            }
            return $a;
        }
        
        function table_prentid($row,$rowData,$val) {
            $pname = CategoryController::$data[$rowData['id']]['pname'];
            if( trim($pname) ==  ''  ){
                $a = '顶级父类';
            }else{
                $a = CategoryController::$data[$rowData['id']]['pname'];
            }
            return $a;
        }
        
        // 表头
        $header = array(
            'name' => array('name' => '分类名称'),
            'pname' => array('name' => '所属分类','callback' => 'table_prentid'),
            'status' => array('name' => '状态','callback' => 'table_status'),
            'action' => array('name' => '操作','callback' => 'table_button'),
        );

        $name = Category::cateToOption(0,false,'array',$data);

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
                    'content' => '<a href="'.CategoryController::$addUrl.'" class="btn green-stripe"><i class="icon-plus"></i>添加分类</a>',
                    'attributes' => array('class'=>"m-wrap"),
                )),
                // 'city' => array('display', array(
                //     'div' => false,
                //     'left' => ' ',
                //     'hide-label'=> true,
                //     'label' => L('所属城市:'),
                //     'attributes' => array('class' => "m-wrap"),
                //     'content' => CategoryController::$city,
                //     'value' => 7,
                // )),
                // 'search' => array('button', array(
                //     'div' => false,
                //     'label' => '<i class="icon-search"></i>查询',
                //     'attributes' => array('class'=>"btn blue"),
                //     'value' => 1
                //     )),
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
            $param['city'] = trim($_POST['city']);
            $param['opentime'] = make_semiangle(trim($_POST['opentime']));
            $param['endtime'] = make_semiangle(trim($_POST['endtime']));
            $param['endSaleTime'] = make_semiangle(trim($_POST['endSaleTime']));


            $list = DBproxy::getProcedure('Manage')->setDimension(2)->categoryIU('i',$param);

            if($list['status'] != 0 ){
                $success = false;
                $errors[] = L('添加分类失败！DB异常！');
            }
            // 处理返回路径
            if ($success) {
                if (isset($_POST['saveAndReutrn'])) {
                    $errors = CategoryController::$dataTableUrl;
                }else {
                    $errors = CategoryController::$addUrl;
                }
            }
            
            // 处理表单位提交
            $this->ajaxFormResult($success, $errors);

        }else{
            // 显示生成表单
            Doo::loadClassAt('html/DooFormExt','default');
            $form = new DooFormExt($this->_getFormConfig(true));
            $btn = '<a class="btn green-stripe" href="'.CategoryController::$dataTableUrl.'"><i class="icon-backward"> </i>'.L('分类列表').'</a>';
            // 显示模版
            $this->contentlayoutRender($btn.$form->render());    
        }
        
    }

    public function mod(){
        $id = (int)$this->getUrlVar('id');
        $param['id'] = $id;
        $res = DBproxy::getProcedure('Manage')->setDimension(2)->getCategory($param);

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
            $param['city'] = trim($_POST['city']);
            $param['opentimeDay'] = make_semiangle(trim($_POST['opentimeDay']));
			$param['opentime'] = make_semiangle(trim($_POST['opentime']));
            $param['endtime'] = make_semiangle(trim($_POST['endtime']));
            $param['waimaiEndtime'] = make_semiangle(trim($_POST['waimaiEndtime']));
            $param['endSaleTime'] = make_semiangle(trim($_POST['endSaleTime']));
            $list = DBproxy::getProcedure('Manage')->setDimension(2)->categoryIU('u',$param,$id);

            if($list['status'] != 0 ){
                $success = false;
                $errors[] = L('修改分类失败！DB异常！');
            }
            // 处理返回路径
            if ($success) {
                if (isset($_POST['saveAndReutrn'])) {
                    $errors = CategoryController::$dataTableUrl;
                }else {
                    $errors = CategoryController::$addUrl;
                }
            }
            
            // 处理表单位提交
            $this->ajaxFormResult($success, $errors);

        }else{            
            // 显示生成表单
            Doo::loadClassAt('html/DooFormExt','default');
            $form = new DooFormExt($this->_getFormConfig(false,$res['data'][$id]));
            $btn = '<a class="btn green-stripe" href="'.CategoryController::$dataTableUrl.'"><i class="icon-backward"> </i>'.L('分类列表').'</a>';
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
        $return = DBproxy::getProcedure('Manage')->setDimension(2)->delCategory($id);
        
        if ($return['status'] != 0 ) {
            $errors = '操作失败';
        }
        $this->alert($errors, $success ? 'success' : 'error');
    }


    protected function _getFormRule(){
        $rule = array(
                'name' => array(
                        array('required', L("请填写分类名称")),
                        array('maxlength', 20, L("名称最大长度为20个字符")),
                        array('minlength', 1, L("名称最小长度为1个字符")),
                        // array('custom','CategoryController::vMaxLength'),
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
                                'content' => '<select id="parentid-element" name="parentid" class="m-wrap"><option value="0">'.L('顶级父类').'</option>'.
                                                Category::cateToOption($id,false).'</select>'
                        )),
                        'name' => array('text', array(
                                'label' => L('分类名称:'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
                        )),
                        'status' => array('select', array(
                                'label' => L('可用状态:'),
                                'attributes' => array('class' => "m-wrap"),
                                'multioptions' => CategoryController::$status,
                                'value' => 0,
                        )),
                        'city' => array('display', array(
                                'label' => L('所属城市:'),
                                'attributes' => array('class' => "m-wrap"),
                                'content' => CategoryController::$city,
                                // 'value' => 0,
                        )),
                        'sort' => array('text', array(
                                'label' => L('排序:'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '0',
                        )),
                        'opentimeDay' => array('select', array(
                                'label' => L('开始预定日期:'),
                                'attributes' => array('class' => "m-wrap"),
                                'multioptions' => array(0=>'当天'),//,1=>'前一天'),
                                'value' => 0,
                        )),
                        'opentime' => array('text', array(
                                'label' => L('开始预定时间:'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
                                'help'=>'24小时格式，例子 9:00'
                        )),
                        'endtime' => array('text', array(
                                'label' => L('结束预定时间:'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
                                'help'=>'24小时格式，例子 12:00'
                        )),                        
                        // 'waimaiOpentime' => array('text', array(
                        //         'label' => L('开始外卖预定时间:'),
                        //         'attributes' => array('class' => "m-wrap"),
                        //         'value' => '',
                        //         'help'=>'24小时格式，例子 16:00'
                        // )),
                        'waimaiEndtime' => array('text', array(
                                'label' => L('结束外卖预定时间:'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
                                'help'=>'24小时格式，例子 9:00'
                        )),

                        // 'opentime' => array('text', array(
                        //         'label' => L('开卖时间:'),
                        //         'attributes' => array('class' => "m-wrap"),
                        //         'value' => '',
                        //         'help'=>'24小时格式，例子 9:00'
                        // )),
                        'endSaleTime' => array('text', array(
                                'label' => L('结束售卖时间:'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
                                'help'=>'24小时格式，例子 12:00'
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
                                'content' => '<a class="btn" href="' . CategoryController::$dataTableUrl . '"><i class="icon-arrow-left"></i>'.L('取消&返回').'</a>',
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
            
            $insertForm['elements']['city'][1]['content'] = '<select class="m-wrap" name="city" id="city-element"><option value="0">'.L('顶级城市').'</option>'.
                                                City::cateToOption($data['city'],false).'</select>';
            return $insertForm;
        }
    }
}

?>
