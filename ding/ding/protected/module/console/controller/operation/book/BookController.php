<?php
Doo::loadController('ApplicationController');

class BookController extends ApplicationController {

    public static $dataTableUrl = NULL;

    public static $addUrl = NULL;

    public static $modUrl = NULL;

    public static $delUrl = NULL;
    
    public static $status = array('0'=>'正常','1'=>'失效');

    public static $city = array();

    public function init() {
        Doo::loadClassAt('Category','default');
        Doo::loadClassAt('City','default');

        BookController::$dataTableUrl = adminAppUrl('operation/book/dataTable');
        BookController::$addUrl = adminAppUrl('operation/book/add');
        BookController::$modUrl = adminAppUrl('operation/book/mod?id=');
        BookController::$delUrl = adminAppUrl('operation/book/del?id=');

        BookController::$city = '<select class="m-wrap" name="city" id="city-element"><option value="0">'.L('全部城市').'</option>'.
                                                City::cateToOption($this->getUrlVar('city'),1).'</select>';
    }

    public function dataTable(){
        Doo::loadClassAt('html/DataTable','default');
        Doo::loadClassAt('html/DooFormExt','default');
        $dt = new DataTable();

        function table_button($row,$rowData,$val) {
            $a = '<a class="btn blue-stripe mini" href="'.BookController::$modUrl.$rowData['id'].'">'.'编辑</a>';            
            $a .= ' <a href="'.BookController::$delUrl.$rowData['id'].'" class="red-stripe btn mini js-datatable-del">删除</a>';
            return $a;
        }
        
        function table_status($row,$rowData,$val) {
            if( trim($rowData['status'])=='0'){
                $a = '<span class="label label-success">正常可用</span>';
            }else{
                $a = '<span class="label">失效';
            }
            return $a;
        }        

        // 表头
        $header = array(
            'id' => array('name' => 'id'),
            'name' => array('name' => '名称'),
            'categoryName' => array('name' => '所属父分类'),
            //'categoryid' => array('name' => '所属父ID'), 
            'price' => array('name' => '价钱'),
            'wxprice' => array('name' => '微信价钱'),
            'status' => array('name' => '可用状态','callback' => 'table_status'),            
            'action' => array('name' => '操作','callback' => 'table_button'),
        );
        $param = array();        
        $param['id'] = (int)$this->getUrlVar('bookid',NULL);
        $param['bookname'] = (string)$this->getUrlVar('bookname',NULL);
        $param['booktypeid'] = (int)$this->getUrlVar('booktypeid',0);
        $param['city'] = (int)$this->getUrlVar('city',0);
        
        $res = DBproxy::getProcedure('Manage')->setDimension(2)->getBook($param, $this->getCurPage().','.Doo::conf()->pagesize);
        
        // 生产表格
        $content = $dt->setTitle('')
                ->setAttr(array('class' => 'table','id' => 'js-queryTable'))
                ->setHeader($header)
                ->setData($res['data'])
                ->setTopContent('')
                ->setBottomContent($this->pager($res['total']))
                ->setDefaultValue('unkown')
                ->render(false);
        $btn = '<a href="'.BookController::$addUrl.'" 
                    class="btn green-stripe"><i class="icon-plus"></i>'.L('添加').'</a>';
        //查询表单
        $form = new DooFormExt(array(
            'method' => 'get',
            'renderFormat' => 'html',
            'action' => '',
            'attributes'=> array('id'=>'js-get-form','class'=>'form-horizontal'),
            'elements' => array(                            
                'cityid' => array('display', array(
                    // 'left' => ' 城市',
                    'hide-label'=> true,
                    'div' => false,
                    'attributes' => array('class'=>"m-wrap"),
                    'content' => BookController::$city
                )),
                'booktypeid' => array('display', array(
                    'left' => ' 菜品分类',
                    'hide-label'=> true,
                    'div' => false,
                    'attributes' => array('class'=>"m-wrap"),
                    'content' => '<select id="booktypeid-element" name="booktypeid" class="m-wrap"><option>全部菜品</option>'.Category::cateToOption($param['booktypeid']).'</select>'
                )),
                'bookname' => array('text', array(
                    'left' => ' ',
                    'hide-label'=> true,
                    'div' => false,
                    'placeholder' => '菜品名称',
                    'attributes' => array('class'=>"m-wrap"),
                    'value' => $this->getUrlVar('bookname')
                )),
                'bookid' => array('text', array(
                    'left' => ' ',
                    'hide-label'=> true,
                    'div' => false,
                    'placeholder' => '菜品id',
                    'attributes' => array('class'=>"m-wrap"),
                    'value' => $this->getUrlVar('bookid')
                )),                
                'search' => array('button', array(
                    'div' => false,
                    'label' => '<i class="icon-search"></i>查询',
                    'attributes' => array('class'=>"btn blue"),
                    'value' => 1
                )),                
            ))
        );
        // 显示模版
        $this->contentlayoutRender($btn.$form->render().$content);
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
            //图片处理
            $img = '';
            Doo::loadClassAt('Picupload','default');
            $Picupload = new Picupload();
            $imgRes = $Picupload->upload('img');
            if($imgRes){
                $img = $Picupload::$picinfo['dirname'] . '/' . $Picupload::$picinfo['basename'];
            }          

            //数据处理
            $param['categoryid'] = intval($_POST['categoryid']);
            $param['name'] = trim($_POST['name']);
            $param['sort'] = intval($_POST['sort']);
            $param['status'] = intval($_POST['status']);

            $param['descript'] = trim($_POST['descript']);
            $param['img'] = $img;
            $param['la'] = trim($_POST['la']);
            $param['price'] = trim($_POST['price']);
            $param['wxprice'] = trim($_POST['wxprice']);            
            $param['peiSongSum'] = intval($_POST['peiSongSum']);
            $res = DBproxy::getProcedure('Manage')->setDimension(2)->bookIU('i',$param);
            if($res['status'] != 0 ){
                $success = false;
                $errors[] = '添加DB异常！';
            }
            // 处理返回路径
            if ($success) {
                if (isset($_POST['saveAndReutrn'])) {
                    $errors = BookController::$dataTableUrl;
                }else {
                    $errors = BookController::$addUrl;
                }
            }
            
            // 处理表单位提交
            $this->ajaxFormResult($success, $errors);

        }else{
            // 显示生成表单
            Doo::loadClassAt('html/DooFormExt','default');
            $form = new DooFormExt($this->_getFormConfig(true));
            $btn = '<a class="btn green-stripe" href="'.BookController::$dataTableUrl.'"><i class="icon-backward"> </i>'.L('列表').'</a>';
            // 显示模版
            $this->contentlayoutRender($btn.$form->render());    
        }
        
    }
    
    public function mod(){
        $id = (int)$this->getUrlVar('id');

        $res = DBproxy::getProcedure('Manage')->setDimension(2)->getBook(array('id'=>$id),'1');
        if( empty($res) ){
            $this->alert('data null');
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
            if($success){
                //图片处理
                $img = '';
                Doo::loadClassAt('Picupload','default');
                $Picupload = new Picupload();
                $imgRes = $Picupload->upload('img');
                if($imgRes){
                    $img = $Picupload::$picinfo['dirname'] . '/' . $Picupload::$picinfo['basename'];
                }else{
                    $img = trim($_POST['img']);
                }

                //数据处理
                $param['categoryid'] = intval($_POST['categoryid']);
                $param['name'] = trim($_POST['name']);
                $param['sort'] = intval($_POST['sort']);
                $param['status'] = intval($_POST['status']);
                $param['la'] = trim($_POST['la']);
                $param['descript'] = trim($_POST['descript']);
                $param['img'] = $img;
                $param['price'] = trim($_POST['price']);
                $param['wxprice'] = trim($_POST['wxprice']);
                $param['peiSongSum'] = intval($_POST['peiSongSum']);

                $res = DBproxy::getProcedure('Manage')->setDimension(2)->bookIU('u',$param,$id);
                
                if($res['status'] != 0 ){
                    $success = false;
                    $errors[] = 'DB异常！';
                }
            }
            // 处理返回路径
            if ($success) {
                if (isset($_POST['saveAndReutrn'])) {
                    $errors = BookController::$dataTableUrl;
                }else {
                    $errors = BookController::$addUrl;
                }
            }
            
            // 处理表单位提交
            $this->ajaxFormResult($success, $errors);

        }else{
            
            // 显示生成表单
            Doo::loadClassAt('html/DooFormExt','default');
            $form = new DooFormExt($this->_getFormConfig(false,$res['data'][0]));
            $btn = '<a class="btn green-stripe" href="'.BookController::$dataTableUrl.'"><i class="icon-backward"> </i>'.L('列表').'</a>';
            // 显示模版
            $this->contentlayoutRender($btn.$form->render());    
        }
    }
    
    //删除
    public function del() {
        $success = true;
        $errors = '删除成功';
        $id = (int) $this->getUrlVar('id',0);
        // 删除数据
        $return = DBproxy::getProcedure('Manage')->setDimension(2)->bookDel($id);
        if ($return['status'] != 0 ) {
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
    protected function _getFormConfig($isInsert = true, $data = array()) {
        Doo::loadClassAt('DataExt','default');
        $dataExt = new DataExt();
        
        $id = isset($data['categoryid']) ? $data['categoryid'] : '';

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
                        'categoryid' => array('display', array(
                                'label' => L('父分类:'),
                                'attributes' => array('class'=>"m-wrap"),
                                'content' => '<select id="categoryid-element" name="categoryid" class="m-wrap">'.Category::cateToOption($id).'</select>'
                        )),

                        'name' => array('text', array(
                                'label' => L('名称:'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
                        )),
                        'price' => array('text', array(
                                'label' => L('价格'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => 0,
                        )),
                        'wxprice' => array('text', array(
                                'label' => L('微信预定价格'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => 0,
                        )),
                        'peiSongSum' => array('text', array(
                                'label' => L('配送调度数'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => 10,
                        )),
                        'la' => array('text', array(
                                'label' => L('辣程序（越高越辣）'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => 0,
                        )),
                        'sort' => array('text', array(
                                'label' => L('排序:'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
                                'help' => '数字越大排越前',
                        )),
                        'status' => array('select', array(
                                'label' => L('可用状态:'),
                                'attributes' => array('class' => "m-wrap"),
                                'multioptions' => BookController::$status,
                                'value' => 0,
                        )),
                        'descript' => array('text', array(
                                'label' => L('描述'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
                        )),
                        'img' => array('file', array(
                                'label' => L('菜品图片:'),
                                'attributes' => array('class' => "m-wrap"),                                
                                'help' => '图片规格：（宽*高）640*480px，大小200-300k左右'
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
                                'content' => '<a class="btn" href="' . BookController::$dataTableUrl . '"><i class="icon-arrow-left"></i>取消&返回</a>',
                        )),
                ));
    
        if ($isInsert) {
            return $insertForm;
        } else {
            #$insertForm['elements']['img'][1]['help'].= '<label class="m-wrap text"><img style="height:100px" src="' . Doo::conf()->APP_URL.$data['img'] . '"> <input type="hidden" name="img" id="img-element" value="' . $data['img'] . '"> </label>';
            #$insertForm['elements']['img'][0] = 'display';
            #$insertForm['elements']['img'][1]['content'] = '<label class="m-wrap text"><img style="height:100px" src="' . Doo::conf()->APP_URL.$data['img'] . '"> <input type="hidden" name="img" id="img-element" value="' . $data['img'] . '"> </label>';
            // 将数据写入表单
            foreach ($data as $key => $val) {
                if (isset($insertForm['elements'][$key])) {
                    $insertForm['elements'][$key][1]['value'] = $val;
                }
            }
            $insertForm['elements']['img'][1]['help'].= '<label class="m-wrap text"><img style="height:100px" src="' . Doo::conf()->APP_URL.$data['img'] . '"> <input type="hidden" name="img" id="img-element" value="' . $data['img'] . '"> </label>';
            $insertForm['elements']['img'][1]['value'] = ''; 
            return $insertForm;
        }
    }

     protected function _getFormRule(){
        $rule = array(
                'name' => array(
                        array('required', L("请填写名称")),
                        array('maxlength', 20, L("名称最大长度为20个字符")),
                        array('minlength', 2, L("名称最小长度为2个字符")),
                ), 
                'categoryid' => array(
                        array('required', L("请选择分类")),
                ),                 
        );        
        return $rule;
    }


}

?>
