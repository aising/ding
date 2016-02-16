<?php
Doo::loadController("ApplicationController");

/**
 * 用户列表
 * @author xinkq
 */
class UserController extends ApplicationController {

    public static $dataTableUrl = NULL;

    public static $addUrl = NULL;

    public static $modUrl = NULL;

    public static $delUrl = NULL;
    
    public static $status = array('0'=>'正常','1'=>'失效');

    public static $city = array();

    public static $shopList = array();

    public function init() {
        UserController::$dataTableUrl = adminAppUrl('operation/user/dataTable');
        UserController::$addUrl = adminAppUrl('operation/user/add');
        UserController::$modUrl = adminAppUrl('operation/user/mod?id=');
        UserController::$delUrl = adminAppUrl('operation/user/del?id=');

        UserController::$city = DBproxy::getProcedure('Manage')->setDimension(2)->getCityList();
    }

    public function dataTable(){
        Doo::loadClassAt('html/DataTable','default');
        $dt = new DataTable();
        function table_button($row,$rowData,$val) {
            $a = '<a class="btn blue-stripe mini" href="'.UserController::$modUrl.$rowData['userid'].'">'.'编辑</a>';            
            //$a .= ' <a href="'.UserController::$delUrl.$rowData['userid'].'" class="red-stripe btn mini js-datatable-del">删除</a>';
            return $a;
        }
        
        function table_status($row,$rowData,$val) {
            $a ='';
            
            if( trim($rowData['shopname']) != '' ){
                $a .= '<span class="label label-warning">店长</span>';
            }

            if( trim($rowData['status'])=='0'){
                $a .= '<span class="label label-success">正常可用</span>';
            }else{
                $a .= '<span class="label">失效';
            }
            
            return $a;
        }        
        function table_time($row,$rowData,$val) {
            $a = date("Y-m-d H:i:s",$rowData['regtime']);
            return $a;
        }

        // 表头
        $header = array(
            'userid' => array('name' => '用户id'),
            'openid' => array('name' => 'openid'),
            'form' => array('name' => '来源'),
            'phone' => array('name' => '手机号码'),
            'regtime' => array('name' => '注册时间','callback'=>'table_time'),
            'status' => array('name' => '可用状态','callback' => 'table_status'),
            'action' => array('name' => '操作','callback' => 'table_button'),
        );
        $param = array('pagesize' => $this->getCurPage().','.Doo::conf()->pagesize);
        $res = DBproxy::getProcedure('Manage')->setDimension(2)->getUserInfo($param);
        
        // 生产表格
        $content = $dt->setTitle('')
                ->setAttr(array('class' => 'table','id' => 'js-queryTable'))
                ->setHeader($header)
                ->setData($res['data'])
                ->setTopContent('')
                ->setBottomContent($this->pager($res['total']))
                ->setDefaultValue('unkown')
                ->render(false);
        $btn = '<a href="'.UserController::$addUrl.'" 
                    class="btn green-stripe"><i class="icon-plus"></i>'.L('添加门店').'</a>';
    $btn = '';
        // 显示模版
        $this->contentlayoutRender($btn.$content);
    }

    public function __add(){
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
            // 处理返回路径
            if ($success) {
                if (isset($_POST['saveAndReutrn'])) {
                    $errors = UserController::$dataTableUrl;
                }else {
                    $errors = UserController::$addUrl;
                }
            }
            
            // 处理表单位提交
            $this->ajaxFormResult($success, $errors);

        }else{
            // 显示生成表单
            Doo::loadClassAt('html/DooFormExt','default');
            $form = new DooFormExt($this->_getFormConfig(true));
            $btn = '<a class="btn green-stripe" href="'.UserController::$dataTableUrl.'"><i class="icon-backward"> </i>门店列表</a>';
            // 显示模版
            $this->contentlayoutRender($btn.$form->render());    
        }
        
    }
    
    public function mod(){
        $id = (int)$this->getUrlVar('id');
        $res = DBproxy::getProcedure('Manage')->setDimension(2)->getUserInfo(array('userid'=>$id));

        if( !isset($res['data'][0]) || empty($res['data'][0]) ){
            $this->alert('data null');die;
        }

        //所有店列表
        $shopNameList = DBproxy::getProcedure('Manage')->setDimension(2)->getShopName();        
        $shopNameOpt = '<select class="m-wrap" name="shopname" id="city-element"><option>无</option>';
        foreach ($shopNameList as $key => $value) {
            $selected = $res['data'][0]['cityid'].','.$res['data'][0]['shopname'] == $value['cityid'].','.$value['shopname'] ? 'selected=selected' : '';
            $shopNameOpt .= '<option '.$selected.' value="'.$value['cityid'].','.$value['shopname'].'">'.$value['cityNshopname'].'</option>';
        }
        $shopNameOpt .= '</select>';
        UserController::$shopList = $shopNameOpt;

        if ($this->isAjax() && $_POST) {        
            // $v = Doo::loadHelper('DooValidator', true);
            $success = true;
            // $errors = array();
            // $rules = $this->_getFormRule();
            // // 验证数据
            // if ($errors = $v->validate($_POST, $rules)) {
            //     $success = false;
            // }

            //数据处理
            $cityNshopnameArr = explode(',', trim($_POST['shopname']));
            $param['userid'] = intval($id);
            $param['cityid'] = $cityNshopnameArr[0];
            $param['shopname'] = $cityNshopnameArr[1];
            $param['phone'] = trim($_POST['phone']);
            $param['addr'] = trim($_POST['addr']);

            $res = DBproxy::getProcedure('Manage')->setDimension(2)->saveUserInfo($param);
            
            if($res['status'] != 0 ){
                $success = false;
                $errors[] = 'DB异常！';
            }
            // 处理返回路径
            if ($success) {
                $errors = UserController::$modUrl.$id;
            }
            
            // 处理表单位提交
            $this->ajaxFormResult($success, $errors);

        }else{
            // 显示生成表单
            Doo::loadClassAt('html/DooFormExt','default');
            $form = new DooFormExt($this->_getFormConfig(false,$res['data'][0]));
            $btn = '<a class="btn green-stripe" href="'.UserController::$dataTableUrl.'"><i class="icon-backward"> </i>用户信息</a>';
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
        // D($data);
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

                        'userid' => array('display', array(
                                'label' => '用户id:',
                                'attributes' => array('class' => "m-wrap"),
                        )),
                        'openid' => array('display', array(
                                'label' => 'openid:',
                                'attributes' => array('class' => " large m-wrap"),
                        )),
                        'phone' => array('text', array(
                                'label' => L('电话:'),
                                'attributes' => array('class' => "m-wrap"),
                                'value' => 0,
                        )),
                        'shopname' => array('display', array(
                            'label' => '门长所属门店:',
                            'attributes' => array('class' => 'small m-wrap'),
                            'content' => UserController::$shopList,
                        )),
                        
                        'addr' => array('text', array(
                                'label' => L('地址:'),
                                'attributes' => array('class' => "large m-wrap"),
                                'value' => '',
                        )),
                        'regtime' => array('display', array(
                                'label' => L('注册时间:'),
                                'attributes' => array('class' => "m-wrap"),
                        )),
                        'saveAndReutrn' => array('button', array(
                                'div' => false,
                                'left' => '<div class="form-actions js-submitButton">',
                                'label' => '<i class="icon-arrow-left"></i>保存&返回',
                                'attributes' => array('class' => "btn blue"),
                                'value' => 1
                        )),
                        // 'saveAndAdd' => array('button', array(
                        //         'div' => false,
                        //         'left' => ' ',
                        //         'label' => '<i class="icon-plus"></i>保存&新增',
                        //         'attributes' => array('class' => "btn blue"),
                        //         'value' => 1
                        // )),
                        // 'cancel' => array('display', array(
                        //         'div' => false,
                        //         'left' => ' ',
                        //         'content' => '<a class="btn" href="' . $_SERVER['REQUEST_URI'] . '"><i class="icon-undo"></i>取消</a>',
                        // )),
                        'cancelAndReturn' => array('display', array(
                                'div' => false,
                                'left' => ' ',
                                'right' => '</div>',
                                'content' => '<a class="btn" href="' . UserController::$dataTableUrl . '"><i class="icon-arrow-left"></i>返回</a>',
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
            $insertForm['elements']['regtime'][1]['content'] = date("Y-m-d H:i:s",$data['regtime']);
            $insertForm['elements']['userid'][1]['content'] = '<label class=" m-wrap">'.$data['userid'].'</label>';
            $insertForm['elements']['openid'][1]['content'] = $data['openid'];
            return $insertForm;
        }
    }

     protected function _getFormRule(){
        
    }


}

?>
