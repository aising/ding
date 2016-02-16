<?php
Doo::loadController("ApplicationController");

/**
 * 门店列表
 * @author xinkq
 */
class MapController extends ApplicationController {

	public static $dataTableUrl = NULL;

    public static $addUrl = NULL;

    public static $modUrl = NULL;

    public static $delUrl = NULL;
    
    public static $status = array('0'=>'正常','1'=>'失效');

    public static $city = array();

    public function init() {
        MapController::$dataTableUrl = adminAppUrl('operation/map/dataTable');
        MapController::$addUrl = adminAppUrl('operation/map/add');
        MapController::$modUrl = adminAppUrl('operation/map/mod?id=');
        MapController::$delUrl = adminAppUrl('operation/map/del?id=');

        MapController::$city = DBproxy::getProcedure('Manage')->setDimension(2)->getCityList();
    }

    public function dataTable(){
        Doo::loadClassAt('html/DataTable','default');
        $dt = new DataTable();
        function table_button($row,$rowData,$val) {
            $a = '<a class="btn blue-stripe mini" href="'.MapController::$modUrl.$rowData['id'].'">'.'编辑</a>';            
            $a .= ' <a href="'.MapController::$delUrl.$rowData['id'].'" class="red-stripe btn mini js-datatable-del">删除</a>';
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
            'name' => array('name' => '名称'),
            'longitude' => array('name' => '经度'),
            'latitude' => array('name' => '纬度'),             
            'status' => array('name' => '可用状态','callback' => 'table_status'),
            'action' => array('name' => '操作','callback' => 'table_button'),
        );
        $param = array('pagesize' => $this->getCurPage().','.Doo::conf()->pagesize);
        $res = DBproxy::getProcedure('Manage')->setDimension(2)->getMap($param);
        
        // 生产表格
        $content = $dt->setTitle('')
                ->setAttr(array('class' => 'table','id' => 'js-queryTable'))
                ->setHeader($header)
                ->setData($res['data'])
                ->setTopContent('')
                ->setBottomContent($this->pager($res['total']))
                ->setDefaultValue('unkown')
                ->render(false);
        $btn = '<a href="'.MapController::$addUrl.'" 
                    class="btn green-stripe"><i class="icon-plus"></i>'.L('添加门店').'</a>';
        // 显示模版
        $this->contentlayoutRender($btn.$content);
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

            if($success){
                //数据处理
                $param['name'] = trim($_POST['name']);
                $param['city'] = trim($_POST['city']);
                $param['longitude'] = intval($_POST['longitude']);
                $param['latitude'] = intval($_POST['latitude']);
                $param['status'] = intval($_POST['status']);

                $res = DBproxy::getProcedure('Manage')->setDimension(2)->mapIU('i',$param);
                if($res['status'] != 0 ){
                    $success = false;
                    $errors[] = '添加DB异常！';
                }
            }
            // 处理返回路径
            if ($success) {
                if (isset($_POST['saveAndReutrn'])) {
                    $errors = MapController::$dataTableUrl;
                }else {
                    $errors = MapController::$addUrl;
                }
            }
            
            // 处理表单位提交
            $this->ajaxFormResult($success, $errors);

        }else{
            // 显示生成表单
            Doo::loadClassAt('html/DooFormExt','default');
            $form = new DooFormExt($this->_getFormConfig(true));
            $btn = '<a class="btn green-stripe" href="'.MapController::$dataTableUrl.'"><i class="icon-backward"> </i>门店列表</a>';
            // 显示模版
            $this->contentlayoutRender($btn.$form->render());    
        }
        
    }
    
    public function mod(){
        $id = (int)$this->getUrlVar('id');
        $res = DBproxy::getProcedure('Manage')->setDimension(2)->getMap(array('id'=>$id));

        if( !isset($res['data']) || empty($res['data']) ){
        	$this->alert('data null');die;
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
            if($success){
                //数据处理
                $param['name'] = trim($_POST['name']);
                $param['city'] = trim($_POST['city']);
                $param['longitude'] = intval($_POST['longitude']);
                $param['latitude'] = intval($_POST['latitude']);
                $param['status'] = intval($_POST['status']);

                $res = DBproxy::getProcedure('Manage')->setDimension(2)->mapIU('u',$param,$id);
                
                if($res['status'] != 0 ){
                    $success = false;
                    $errors[] = 'DB异常！';
                }
            }
            // 处理返回路径
            if ($success) {
                if (isset($_POST['saveAndReutrn'])) {
                    $errors = MapController::$dataTableUrl;
                }else {
                    $errors = MapController::$addUrl;
                }
            }
            
            // 处理表单位提交
            $this->ajaxFormResult($success, $errors);

        }else{
            // 显示生成表单
            Doo::loadClassAt('html/DooFormExt','default');
            $form = new DooFormExt($this->_getFormConfig(false,$res['data'][0]));
            $btn = '<a class="btn green-stripe" href="'.MapController::$dataTableUrl.'"><i class="icon-backward"> </i>门店列表</a>';
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
        $return = DBproxy::getProcedure('Manage')->setDimension(2)->mapDel($id);
        if ($return['status'] != 0 ) {
            $errors = '操作失败';
            $success = false;
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

                        'name' => array('text', array(
                                'label' => '门店名称:',
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
                        )),
                        'longitude' => array('text', array(
                                'label' => '经度:',
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
                        )),                        
                        'latitude' => array('text', array(
                                'label' => '纬度',
                                'attributes' => array('class' => "m-wrap"),
                                'value' => '',
                        )),
                        'city' => array('select', array(
                                'label' => L('城市:'),
                                'attributes' => array('class' => "m-wrap"),
                                'multioptions' => MapController::$city,
                                'value' => '',
                        )),
                        'status' => array('select', array(
                                'label' => L('可用状态:'),
                                'attributes' => array('class' => "m-wrap"),
                                'multioptions' => MapController::$status,
                                'value' => 0,
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
                                'content' => '<a class="btn" href="' . MapController::$dataTableUrl . '"><i class="icon-arrow-left"></i>取消&返回</a>',
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

     protected function _getFormRule(){
        $rule = array(
                'name' => array(
                        array('required', L("请填写门店名称")),
                ), 
                'longitude' => array(
                        array('required', L("请填写经度")),
                ),
                'latitude' => array(
                        array('required', L("请填写纬度")),
                ),
                'city' => array(
                        array('required', L("请选择城市")),
                        array('between',1,999, L("请选择城市")),
                ),
        );        
        return $rule;
    }


}

?>