<?php
Doo::loadController("ApplicationController");

/**
 * 日志
 * @author xinkq
 */
class LogController extends ApplicationController {

	public static $dataTableUrl = NULL;

    public static $addUrl = NULL;

    public static $modUrl = NULL;

    public static $delUrl = NULL;
    
    // public static $status = array(''=>'全部','0'=>'未付款','1'=>'未消费', '2'=>'已经消费','3'=>'退款中','4'=>'退款完毕');

    public static $city = array();

    public function init() {
        Doo::loadClassAt('Category','default');
        Doo::loadClassAt('City','default');
        LogController::$dataTableUrl = adminAppUrl('operation/user/dataTable');
        LogController::$addUrl = adminAppUrl('operation/user/add');
        LogController::$modUrl = adminAppUrl('operation/user/mod?id=');
        LogController::$delUrl = adminAppUrl('operation/user/del?id=');

        LogController::$city = '<select class="m-wrap" name="city" id="city-element"><option value="0">'.L('全部城市').'</option>'.
                                                City::cateToOption($this->getUrlVar('city'),1).'</select>';
    }
    
    private function table_action($id){
        $export = isset($_GET['export']) ? $_GET['export'] : 0;
        if(trim($export) == 1 ){
            return '';
        }
        $act = '<a class="btn blue-stripe mini" href="'.LogController::$modUrl.$id.'">'.'编辑</a>';
        $act .= ' <a href="'.LogController::$delUrl.$id.'" class="red-stripe btn mini js-datatable-del">删除</a>';
        return $act;
    }

    public function dataTable(){
        Doo::loadClassAt('html/DataTable','default');
        Doo::loadClassAt('html/DooFormExt','default');
        $dt = new DataTable();
        
        $param['starttime'] = $this->getUrlVar('starttime',NULL);
        $param['endtime'] = $this->getUrlVar('endtime',NULL);
        $param['bookid'] = $this->getUrlVar('bookid',NULL);
        $param['order'] = $this->getUrlVar('order',NULL);
        $param['bookname'] = $this->getUrlVar('bookname',NULL);
        
        $param['booktypeid'] = $this->getUrlVar('booktypeid',0);
        $param['city'] = $this->getUrlVar('city',0);
        $param['shopname'] = $this->getUrlVar('shopname','');        
        
        $export = $this->getUrlVar('export');

        // 表头
        $header = array(
            //'days' => array('name' => '日期'),
            'bookname' => array('name' => '菜品名称'),
            'cc' => array('name' => '数量'),            
        );
        //所有店列表
        $shopNameList = DBproxy::getProcedure('Manage')->setDimension(2)->getShopName();        
        $shopNameOpt = '<select class="m-wrap" name="shopname" id="city-element"><option>全部门店</option>';
        foreach ($shopNameList as $key => $value) {
            $selected = $this->getUrlVar('shopname') == $value['cityid'].','.$value['shopname'] ? 'selected=selected' : '';
            $shopNameOpt .= '<option '.$selected.' value="'.$value['cityid'].','.$value['shopname'].'">'.$value['cityNshopname'].'</option>';
        }
        $shopNameOpt .= '</select>';


        $res = DBproxy::getProcedure('Manage')->setDimension(2)->queryHit($param);
        $sum = 0;
        foreach ($res['data'] as $key => $value) {
            $sum+=$value['cc'];
        }        
        // 生产表格
        $content = $dt->setTitle('')
                ->setAttr(array('class' => 'table table-hover  ','id' => 'js-queryTable '))
                ->setHeader($header)
                ->setData($res['data'])
                ->setTopContent('')
                ->setBottomContent('')
                ->setDefaultValue('unkown')
                ->render(false);        

         //查询表单
        $form = new DooFormExt(array(
            'method' => 'get',
            'renderFormat' => 'html',
            'action' => '',
            'attributes'=> array('id'=>'js-get-form','class'=>'form-horizontal'),
            'elements' => array(
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
                'city' => array('display', array(
                    // 'left' => ' 城市',
                    'hide-label'=> true,
                    'div' => false,
                    'attributes' => array('class'=>"m-wrap"),
                    'content' => LogController::$city
                )),                
                'shopname' => array('display', array(
                    'left' => '',
                    'hide-label'=> true,
                    'div' => false,
                    'attributes' => array('class' => 'small m-wrap'),
                    'content' => $shopNameOpt,
                    
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
                'order' => array('select', array(                   
                    'left' => ' 点击量排序',
                    'hide-label'=> true,
                    'div' => false,
                    'attributes' => array('class' => 'small m-wrap'),
                    'multioptions' => array('desc'=>'多','asc'=>'少'),
                    'value' => $this->getUrlVar('order')
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
        $sumDiv = '点击总数量：'.$sum;
        $content .=$sumDiv;
        //export         
        if(trim($export) == 1 ){
            exportExcel($content);exit();
        }

        // 显示模版
        $this->contentlayoutRender($form->render().$content);
    }

   
}

?>
