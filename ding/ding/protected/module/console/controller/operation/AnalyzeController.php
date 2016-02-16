<?php
Doo::loadController("ApplicationController");

/**
 * 数据统计
 * @author xinkq
 */
class AnalyzeController extends ApplicationController {

	public static $dataTableUrl = NULL;

    public static $addUrl = NULL;

    public static $modUrl = NULL;

    public static $delUrl = NULL;
    
    public static $status = array('0'=>'正常','1'=>'失效');

    public static $city = array();

    public function init() {
        
    }

    public function dataTable(){
        $url = $_GET['url'];
        header('Location:'.$url);
    }


}

?>
