<?php
Doo::loadController('ApplicationController');

class PeiSongController extends ApplicationController {

    public static $dataTableUrl = NULL;

    public static $addUrl = NULL;

    public static $modUrl = NULL;

    public static $delUrl = NULL;

    public static $status = array('0'=>'正常','1'=>'失效');

    public static $city = array();

    public static $cityArr = array();

    public function init() {
        Doo::loadClassAt('Category','default');
        Doo::loadClassAt('City','default');
        PeiSongController::$dataTableUrl = adminAppUrl('system/config/peisong/dataTable');
        PeiSongController::$addUrl = adminAppUrl('system/config/peisong/add');
        PeiSongController::$modUrl = adminAppUrl('system/config/peisong/mod?id=');
        PeiSongController::$delUrl = adminAppUrl('system/config/peisong/del?id=');
        PeiSongController::$city = '<select class="m-wrap" name="city" id="city-element">'.
                                                City::cateToOption($this->getUrlVar('city'),false).'</select>';
         PeiSongController::$cityArr = City::cateToOption(0,false,'array');
    }

    public function dataTable(){        
        $data = DBproxy::getProcedure('Manage')->setDimension(2)->getPeiSongList();
        
        Doo::loadClassAt('html/DataTable','default');
        Doo::loadClassAt('html/DooFormExt','default');
        $dt = new DataTable();

        function table_button($row,$rowData,$val) {            
            $a =  ' <a class="btn blue-stripe mini" href="'.PeiSongController::$modUrl.$rowData['id'].'">'.'编辑</a>';
            $a .= ' <a class="red-stripe btn mini js-datatable-del" href="'.PeiSongController::$delUrl.$rowData['id'].'" >删除</a>';
            return $a;
        }
        function table_city($row,$rowData,$val) {
            $a = PeiSongController::$cityArr[$rowData['cityid']]['name'];
            return $a;
        }

        // 表头
        $header = array(
            'cityid' => array('name' => '城市','callback' => 'table_city'),
            'shopname' => array('name' => '店名'),
            'shopNamePhone' => array('name' => '店长电话'),
            'peisongPhone' => array('name' => '配送间电话'),
            'action' => array('name' => '操作','callback' => 'table_button'),
        );

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
                    'content' => '<a href="'.PeiSongController::$addUrl.'" class="btn green-stripe"><i class="icon-plus"></i>添加</a>',
                    'attributes' => array('class'=>"m-wrap"),
                )),
                // 'city' => array('display', array(
                //     'div' => false,
                //     'left' => ' ',
                //     'hide-label'=> true,
                //     'label' => L('所属城市:'),
                //     'attributes' => array('class' => "m-wrap"),
                //     'content' => PeiSongController::$city,
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
                ->setData($data)
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
            $param['cityid'] = intval($_POST['city']);
            $param['shopname'] = trim($_POST['shopname']);
            $param['shopNamePhone'] = make_semiangle(trim($_POST['shopNamePhone']));
            $param['peisongPhone'] = make_semiangle(trim($_POST['peisongPhone']));


            $list = DBproxy::getProcedure('Manage')->setDimension(2)->getPeiSongIU('i',$param);

            if($list['status'] != 0 ){
                $success = false;
                $errors[] = L('添加失败！DB异常！');
            }
            // 处理返回路径
            if ($success) {
                if (isset($_POST['saveAndReutrn'])) {
                    $errors = PeiSongController::$dataTableUrl;
                }else {
                    $errors = PeiSongController::$addUrl;
                }
            }
            
            // 处理表单位提交
            $this->ajaxFormResult($success, $errors);

        }else{
            // 显示生成表单
            Doo::loadClassAt('html/DooFormExt','default');
            $form = new DooFormExt($this->_getFormConfig(true));
            $btn = '<a class="btn green-stripe" href="'.PeiSongController::$dataTableUrl.'"><i class="icon-backward"> </i>'.L('列表').'</a>';
            // 显示模版
            $this->contentlayoutRender($btn.$form->render());    
        }
        
    }

    public function mod(){
        $id = (int)$this->getUrlVar('id');
        $param['id'] = intval($id);
        $res = DBproxy::getProcedure('Manage')->setDimension(2)->getPeiSongList($param);

        if( empty($res) ){
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
            $param['cityid'] = intval($_POST['city']);
            $param['shopname'] = trim($_POST['shopname']);
            $param['shopNamePhone'] = make_semiangle(trim($_POST['shopNamePhone']));
            $param['peisongPhone'] = make_semiangle(trim($_POST['peisongPhone']));


            $list = DBproxy::getProcedure('Manage')->setDimension(2)->getPeiSongIU('u',$param,$param['id']);

            if($list['status'] != 0 ){
                $success = false;
                $errors[] = L('添加失败！DB异常！');
            }
            // 处理返回路径
            if ($success) {
                if (isset($_POST['saveAndReutrn'])) {
                    $errors = PeiSongController::$dataTableUrl;
                }else {
                    $errors = PeiSongController::$addUrl;
                }
            }
            
            // 处理表单位提交
            $this->ajaxFormResult($success, $errors);

        }else{            
            // 显示生成表单
            Doo::loadClassAt('html/DooFormExt','default');
            $form = new DooFormExt($this->_getFormConfig(false,$res[0]));
            
            $btn = '<a class="btn green-stripe" href="'.PeiSongController::$dataTableUrl.'"><i class="icon-backward"> </i>'.L('列表').'</a>';
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
        $return = DBproxy::getProcedure('Manage')->setDimension(2)->delPeiSong($id);
        
        if ($return['status'] != 0 ) {
            $errors = '操作失败';
        }
        $this->alert($errors, $success ? 'success' : 'error');
    }


    protected function _getFormRule(){
        $rule = array(
                'shopname' => array(
                        array('required', L("请填写分店")),
                        // array('custom','PeiSongController::vMaxLength'),
                ),
                'shopNamePhone' => array(
                        array('required', L("请填写分店长电话")),
                        array('maxlength', 11, L("请填写11位电话号码")),
                        array('minlength', 11, L("请填写11位电话号码")),
                        // array('custom','PeiSongController::vMaxLength'),
                ),
                'peisongPhone' => array(
                        array('required', L("请填写配送间电话")),
                        array('maxlength', 11, L("请填写11位电话号码")),
                        array('minlength', 11, L("请填写11位电话号码")),
                        // array('custom','PeiSongController::vMaxLength'),
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
                        'cityid' => array('display', array(
                                'label' => L('所属城市:'),
                                'attributes' => array('class' => "m-wrap"),
                                'content' => PeiSongController::$city,
                                // 'value' => 0,
                        )),
                        'shopname' => array('text', array(
                                'label' => L('店名:'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
                        )),
                        'shopNamePhone' => array('text', array(
                                'label' => L('店长电话:'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
                        )),
                        'peisongPhone' => array('text', array(
                                'label' => L('配送间电话:'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
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
                                'content' => '<a class="btn" href="' . PeiSongController::$dataTableUrl . '"><i class="icon-arrow-left"></i>'.L('取消&返回').'</a>',
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
            
            $insertForm['elements']['cityid'][1]['content'] = '<select class="m-wrap" name="city" id="city-element"><option value="0">'.L('顶级城市').'</option>'.
                                                City::cateToOption($data['cityid'],false).'</select>';
            return $insertForm;
        }
    }
}

?>
