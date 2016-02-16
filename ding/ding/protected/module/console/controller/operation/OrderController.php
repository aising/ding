<?php
Doo::loadController("ApplicationController");
$payClassPath = Doo::conf()->SITE_PATH."protected/module/default/class/pay/";
require_once $payClassPath."WxpayAPI/lib/WxPay.Api.php";
/**
 * 订单列表
 * @author xinkq
 */
class OrderController extends ApplicationController {

	public static $dataTableUrl = NULL;

    public static $addUrl = NULL;

    public static $modUrl = NULL;

    public static $delUrl = NULL;
    
    public static $status = array();

    public static $city = array();

    public static $waimaiStatus = array('-1'=>'全部','0'=>'自取','1'=>'外卖');

    public static $booktype = array(''=>'全部','50'=>'早餐','57'=>'中餐','65'=>'下午茶','66'=>'晚餐','64'=>'制定餐','67'=>'净菜');

    public function init() {
        OrderController::$status = array(''=>'全部')+ Doo::conf()->wxStatus;

        Doo::loadClassAt('City','default');
        OrderController::$dataTableUrl = adminAppUrl('operation/order/dataTable');
        OrderController::$addUrl = adminAppUrl('operation/order/add');
        OrderController::$modUrl = adminAppUrl('operation/order/mod?id=');
        OrderController::$delUrl = adminAppUrl('operation/order/del?id=');

        OrderController::$city = '<select class="m-wrap" name="city" id="city-element"><option value="0">'.L('顶级城市').'</option>'.
                                                City::cateToOption($this->getUrlVar('city'),false).'</select>';
    }
    
    private function queryOrder($out_trade_no,$inStatus=''){
        
        if(isset($out_trade_no) && $out_trade_no != ""){            
            $input = new WxPayOrderQuery();
            $input->SetOut_trade_no($out_trade_no);
            $info = WxPayApi::orderQuery($input);
            if($inStatus == 'code')
            {
              return $info['trade_state'];
            }
            $status = orderState($info['trade_state']);
        }
        return $status;
    }
    
   //回调订单状态查询
    private function _callbackOrder($callBackOrderid,$jump = false){
        if( trim($callBackOrderid) != '' ){
            $orderStatus = $this->queryOrder($callBackOrderid,'code');            
            $order = DBproxy::getProcedure('Manage')->setDimension(2)->orderUp($orderStatus,0,$callBackOrderid);
            if($jump == true){
                header("Location:" . appurl('getMeOrder') );exit;    
            }
        }
    }

    private function table_status($status,$key=''){
        //$s = $this->queryOrder($key,$status);
        //echo $status .'___'. $s;
        //if($status != $s ){
        // $this->_callbackOrder($key);
        //}
        if($status == '0' ){
          $status = 'NOTPAY';
        }
        $s = isset(Doo::conf()->wxStatus[$status]) ? Doo::conf()->wxStatus[$status] : '状态异常';
        $a = '<span class="label label-warning">'.$s.'</span>';
        return $a;
    }    

    private function table_action($id){
        $export = isset($_GET['export']) ? $_GET['export'] : 0;
        if(trim($export) == 1 ){
            return '';
        }
        $act = '<a class="btn blue-stripe mini" href="'.OrderController::$modUrl.$id.'">'.'编辑</a>';
        $act .= ' <a href="'.OrderController::$delUrl.$id.'" class="red-stripe btn mini js-datatable-del">删除</a>';
        return $act;
    }

    public function dataTable(){
        Doo::loadClassAt('html/DataTable','default');
        Doo::loadClassAt('html/DooFormExt','default');
        
        $nowPage = $this->getCurPage();
        $search = $this->getUrlVar('search');
        if(trim($search) == 1 ){
            // $nowPage = 1;
        }

        $param['starttime'] = $this->getUrlVar('starttime',NULL);
        $param['endtime'] = $this->getUrlVar('endtime',NULL);

        $wvalue = $this->getUrlVar('waimai','-1');
        $param['waimai'] = $wvalue == -1 ? '' : $wvalue;

        $param['status'] = $this->getUrlVar('status');
        // $param['status'] = '4';
        $param['phone'] = $this->getUrlVar('phone');
        $param['id'] = $this->getUrlVar('orderid');
        $param['city'] = $this->getUrlVar('city');
        $param['booktype'] = $this->getUrlVar('booktype');
        $param['pagesize'] = $nowPage .','.Doo::conf()->pagesize;

        $export = $this->getUrlVar('export');
        if($export == 1){
            unset($param['pagesize']);
        }

        $res = DBproxy::getProcedure('Manage')->setDimension(2)->getOrder($param);
        
        $table = '<table class="table table-hover  table-bordered" id="js-queryTable ">
                    <thead>
                    <tr>
                        <th width="10%">店面</th>
                        <th width="10%">订单号</th>
                        <th width="10%">订单时间</th>
                        <th width="10%">菜品</th>
                        <th width="10%">价钱</th>
                        <th width="10%">份量</th>
                        <th width="5%">总价</th>
                        <th width="10%">用户手机</th>
                        <th width="5%" >可用状态</th>
                        <th width="10%" >外卖地址</th>
                        <th width="10%" >操作</th>
                    </tr>
                    </thead><tbody>';
        foreach ($res['data'] as $key => $value) {
            $addr = trim($value[0]['addr'])!='' && $value[0]['waimai']==1 ? ' &nbsp&nbsp&nbsp&nbsp 外卖地址：'.$value[0]['addr'] : '';
            $row = count($value);

            foreach ($value as $vkey => $vvalue) {
                $table .= '<tr>';
                if($vkey == 0 ){
                    $table .= '<td rowspan=" ' . $row . ' " style="text-align: center" > '. $value[0]['shopname'] . '</td>';
                    $table .= '<td rowspan=" ' . $row . ' " style="text-align: center" > '. $value[0]['oid'] .' </td>';
                    $table .= '<td rowspan=" ' . $row . ' " style="text-align: center" > '. date( "Y-m-d H:i:s", $value[0]['addtime'] ) .' </td>';
                }
                $table .= '<td style="text-align: center" > '. $vvalue['title'] .' </td>';
                $table .= '<td style="text-align: center" > '. $vvalue['wxprice'] .' </td>';
                $table .= '<td style="text-align: center" > '. $vvalue['sum'] .' </td>';
                if($vkey == 0 ){
                    $table .= '<td rowspan=" ' . $row . ' " style="text-align: center" > ￥' . $vvalue['allPrice'] . '</td>';
                    $table .= '<td rowspan=" ' . $row . ' " style="text-align: center" > '. $vvalue['phone'] . '</td>';
                    $table .= '<td rowspan=" ' . $row . ' " style="text-align: center" > '. $this->table_status($vvalue['status'],$key) .' </td>';
                    $table .= '<td rowspan=" ' . $row . ' " style="text-align: center" > '. $addr .' </td>';
                    $table .= '<td rowspan=" ' . $row . ' " style="text-align: center" > '. $this->table_action($vvalue['orderKeyId']) .' </td>';
                }
                $table .= '</tr>';
           }
           $table .= '<tr> <td colspan="11" style=""></td></tr>';
        }
        // D($res['total']);
        $table .= '</tbody></table><p>总数：'.$res['orderTotal'].'</p>' . $this->pager($res['total']); 

        //export         
        if(trim($export) == 1 ){
            exportExcel($table);exit();
        }
        
        //查询表单
        $form = new DooFormExt(array(
            'method' => 'get',
            'renderFormat' => 'html',
            'action' => '',
            'attributes'=> array('id'=>'js-get-form','class'=>'form-horizontal'),
            'elements' => array(
                'status' => array('select', array(                   
                    'left' => ' 订单状态：',
                    'hide-label'=> true,
                    'div' => false,
                    'attributes' => array('class' => 'small m-wrap'),
                    'multioptions' => OrderController::$status,
                    'value' => $this->getUrlVar('status')
                )),
                'starttime' => array('text', array(
                    'left' => ' ',
                    'hide-label'=> true,
                    'div' => false,
                    'placeholder' => '开始日期',
                    'attributes' => array('class'=>"m-wrap","onClick"=>"WdatePicker()"),
                    'value' => $this->getUrlVar('starttime')
                )),
                'endtime' => array('text', array(
                    'left' => ' ',
                    'hide-label'=> true,
                    'div' => false,
                    'placeholder' => '结束日期',
                    'attributes' => array('class'=>"m-wrap","onClick"=>"WdatePicker({maxDate:'%y-%M-%d'})"),
                    'value' => $this->getUrlVar('endtime')
                )),

                'waimai' => array('select', array(
                    'left' => ' 取餐方式',
                    'hide-label'=> true,
                    'div' => false,
                    'attributes' => array('class' => 'small m-wrap'),
                    'multioptions' => OrderController::$waimaiStatus,
                    'value' => $this->getUrlVar('waimai')
                )),
                'booktype' => array('select', array(
                    'left' => ' 餐类别：',
                    'hide-label'=> true,
                    'div' => false,
                    'attributes' => array('class' => 'small m-wrap'),
                    'multioptions' => OrderController::$booktype,
                    'value' => $this->getUrlVar('booktype')
                )),
                'city' => array('display', array(
                    'left' => ' 城市：',
                    'hide-label'=> true,
                    'div' => false,
                    'attributes' => array('class' => 'small m-wrap'),
                    'content' => OrderController::$city,
                    'value' => $this->getUrlVar('city')
                )),
                'phone' => array('text', array(
                    'hide-label'=> true,
                    'div' => false,
                    'placeholder' => '11位手机号码',
                    'attributes' => array('class' => 'small m-wrap'),
                    'value' => $this->getUrlVar('phone')
                )),
                'orderid' => array('text', array(
                    'hide-label'=> true,
                    'div' => false,
                    'placeholder' => '订单号',
                    'attributes' => array('class' => 'small m-wrap'),                    
                    'value' => $this->getUrlVar('orderid')
                )),
                'search' => array('button', array(
                    'div' => false,
                    'label' => '<i class="icon-search"></i>查询',
                    'attributes' => array('class'=>"btn blue"),
                    'value' => 1
                )),
                'export' => array('button', array(
                    'div' => false,
                    'label' => ' <i class="icon-export"></i> 导出',
                    'attributes' => array('class'=>"btn black"),
                    'value' => 1
                )),
            ))
        );
        // 显示模版
        $this->contentlayoutRender($form->render().$table);
	}

    public function add(){
        
    }
    
    private function edit_status($btn,$id,$value){
        Doo::loadClassAt('html/DataTable','default');
        Doo::loadClassAt('html/DooFormExt','default');
        $statusArr = OrderController::$status;
        unset($statusArr['']);
        //修改表单
        $form = new DooFormExt(array(
            'method' => 'post',
            'renderFormat' => 'html',
            'action' => '',
            'attributes'=> array('id'=>'js-form','class'=>'form-horizontal'),
            'elements' => array(
                'href' => array('display', array(
                    'hide-label'=> true,
                    'div' => false,
                    'attributes' => array('class' => 'small m-wrap'),
                    'content' => $btn
                )),
                'status' => array('select', array(
                    'left' => ' 订单状态：',
                    'hide-label'=> true,
                    'div' => false,
                    'attributes' => array('class' => 'small m-wrap'),
                    'multioptions' => $statusArr,
                    'value' => $value
                )),
               'submit' => array('button', array(
                    'div' => false,
                    'label' => ' <i class="icon-export"></i> 修改',
                    'attributes' => array('class'=>"btn black"),
                    'value' => 1
                )),
            ))
        );
        return $form->render();
    }

    public function mod(){
        $res = array();
        $params['id'] = (int)$this->getUrlVar('id');
        $params['pagesize'] = $this->getCurPage().','.Doo::conf()->pagesize;
        if($params['id'] > 0){
            $res = DBproxy::getProcedure('Manage')->setDimension(2)->getOrder($params);
        }
        
        if( !isset($res['data']) || empty($res['data']) ){
        	$this->alert('data null');die;
        }

        if ($this->isAjax() && $_POST) {        
            
            $success = true;
            $errors = array();
            
            //数据处理            
            $status = intval($_POST['status']);
            $res = DBproxy::getProcedure('Manage')->setDimension(2)->orderUp($status,$params['id']);
            
            if($res['status'] != 0 ){
                $success = false;
                $errors[] = 'DB异常！';
            }
            // 处理返回路径
            if ($success) {
                $errors = OrderController::$modUrl.$params['id'];
            }
            
            // 处理表单位提交
            $this->ajaxFormResult($success, $errors);
        }else{
            
            $table = '<table class="table table-hover  table-bordered" id="js-queryTable ">
                    <thead>
                    <tr>
                        <th width="45%">订单详情</th>
                        <th width="5%">价钱</th>
                        <th width="20%">用户手机</th>
                        <th width="10%" >可用状态</th>                        
                    </tr>
                    </thead>';
            foreach ($res['data'] as $key => $value) {
                $addr = trim($value[0]['addr'])!='' && $value[0]['waimai']==1 ? ' &nbsp&nbsp&nbsp&nbsp 外卖地址：'.$value[0]['addr'] : '';
                $table .= '<tbody>
                        <tr style="background-color:#efefef"><td colspan="6"> 订单号：' . $key . ' 
                        <span style="margin: 0 0 0 30px;"> 订单时间：' .date( "Y-m-d H:i:s", $value[0]['addtime'] ). '</span>
                        '.$addr.'
                        </td></tr>';
                        // $table .= '<tbody>
                        //     <tr style="background-color:#efefef"><td colspan="6"> 订单号：' . $key . ' 
                        //     <span style="margin: 0 0 0 30px;"> 订单时间：' .date( "Y-m-d H:i:s", $value[0]['addtime'] ). '</span>
                        //     </td></tr>';
                $row = count($value);

                foreach ($value as $vkey => $vvalue) {                
                    $table .= '<tr>';
                    $table .= '<td><div style="float:left; width:35%;"> ' . $vvalue['title'] .'</div>'.
                                // ' <div style="float:left;width:10%;color:#666"> x' .$vvalue['sum'] .'</div>'.
                                ' <div style="float:left;width:50%;" class="gary"> 成交价：￥' .$vvalue['wxprice']  .' x  ' .$vvalue['sum'] . ' = ' .$vvalue['countPrice']. ' </div>'.
                                '</td>';

                    if($vkey == 0 ){
                        $table .= '<td  rowspan=" ' . $row . ' " style="text-align: center" > ￥' . $vvalue['allPrice'] . '</td>';
                        $table .= '<td  rowspan=" ' . $row . ' " style="text-align: center" > ' . $vvalue['phone'] . '</td>';
                    
                        $table .= '<td rowspan=" ' . $row . ' " style="text-align: center" > '. $this->table_status($vvalue['status']) .' </td>';                        
                    }
                    
                    $table .= '</tr>';
               }
               $table .= '<tr> <td colspan="5" style=""></td></tr></tbody>';
            }
            $table .= '</table>';
            $btn = '<a href="'.OrderController::$dataTableUrl.'" 
                    class="btn green-stripe"><i class="icon"></i>返回列表</a> ';
            $fromHtml = $this->edit_status($btn,$params['id'],$vvalue['status']);
            // 显示模版
            $this->contentlayoutRender($fromHtml.$table);
        }
    }
    
    //删除
    public function del() {
        $success = true;
        $errors = '删除成功';
        $id = (int) $this->getUrlVar('id',0);
        // 删除数据
        $return = DBproxy::getProcedure('Manage')->setDimension(2)->orderDel($id);
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
                                'value' => 0,
                        )),
                        'city' => array('select', array(
                                'label' => L('城市:'),
                                'attributes' => array('class' => "m-wrap"),
                                'multioptions' => OrderController::$city,
                                'value' => 0,
                        )),
                        'status' => array('select', array(
                                'label' => L('可用状态:'),
                                'attributes' => array('class' => "m-wrap"),
                                'multioptions' => OrderController::$status,
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
                                'content' => '<a class="btn" href="' . OrderController::$dataTableUrl . '"><i class="icon-arrow-left"></i>取消&返回</a>',
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
                        array('required', L("请填写城市名称")),
                ),
        );        
        return $rule;
    }


}

?>

