<?php
header("Content-type: text/html; charset=utf-8");
Doo::loadController('MainController');
$payClassPath = Doo::conf()->SITE_PATH."protected/module/default/class/pay/";
require_once $payClassPath."WxpayAPI/lib/WxPay.Api.php";
require_once $payClassPath."WxpayAPI/lib/WxPay.Data.php";
require_once $payClassPath."WxpayAPI/example/WxPay.JsApiPay.php";
require_once $payClassPath."WxpayAPI/example/log.php";
/**
* 支付
*/
class PayController extends MainController {
    protected $_checkPageAuth = TRUE;

    public function init() {        
        if(!isset($_SESSION['uid']) && $this->_action!='callBack' )
        {
            echo '用户获取异常！';die;
        }
    }

    public function wxJsPay(){
        
        $list = isset($_SESSION['orderinfo']) ? $_SESSION['orderinfo'] : array();
        if(!isset($_SESSION['orderinfo'])){
           header("Location:".appurl('getMeOrder'));die;
        }
        //验证是否能预定
        if(isset($_SESSION['orderList'])){
            foreach ($_SESSION['orderList'] as $key => $value) {

                $this->checkCanSum($value['bookid'],$value);
                
                if( !$this->checkBook($value['bookid']) )
                {
                    $this->alert($value['title'].'已经售卖结束');exit;
                }
            }
        }
        

        //初始化日志
        $logHandler= new CLogFileHandler("../logs/".date('Y-m-d').'.log');
        $log = Log::Init($logHandler, 15);

        //①、获取用户openid
        $tools = new JsApiPay();
        $openId = $tools->GetOpenid();        

        //②、统一下单        
        //价钱转成分
        $totalFee = $list['allPrice']*100;        
        $input = new WxPayUnifiedOrder();
        $input->SetBody("订单");
        $input->SetAttach("订单");
        $input->SetOut_trade_no($list['orderid']);
        $input->SetTotal_fee($totalFee);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("订单");
        $input->SetNotify_url(appurl('callBack')); // "http://paysdk.weixin.qq.com/example/notify.php"
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = WxPayApi::unifiedOrder($input);
        echo '<font color="#f00"><b>支付中,客官请稍后....</b></font><br/>';
        // $this->printf_info($order);
        $jsApiParameters = $tools->GetJsApiParameters($order);

        //获取共享收货地址js函数参数
        $editAddress = $tools->GetEditAddressParameters();

        //③、在支持成功回调通知中处理成功之后的事宜，见 notify.php
        /**
         * 注意：
         * 1、当你的回调地址不可访问的时候，回调通知会失败，可以通过查询订单来确认支付是否成功
         * 2、jsapi支付时需要填入用户openid，WxPay.JsApiPay.php中有获取openid流程 （文档可以参考微信公众平台“网页授权接口”，
         * 参考http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html）
         */

        $data = array( 'jsApiParameters' => $jsApiParameters,'editAddress' => $editAddress, 'orderid'=>$list['orderid'],'pageTitle' => '支付');
        unset($_SESSION['orderinfo']);
        $this->layoutRender('/wxpay',$data);
    }

    //打印输出数组信息
    function printf_info($data)
    {
        foreach($data as $key=>$value){
            echo "<font color='#00ff55;'>$key</font> : $value <br/>";
        }
    }

    public function callBack(){
        $file = '/ding/protected/module/default/cache/callback-'.date("Y-m--d").'.php';
        
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $result = WxPayResults::Init($xml);

        file_put_contents($file,var_export($result,true),FILE_APPEND );

        if($result['result_code'] == 'SUCCESS'){
            $callBackOrderid = $result['out_trade_no'];
            $orderStatus = $result['result_code'];
            $order = DBproxy::getProcedure('Manage')->setDimension(2)->orderUp($orderStatus,0,$callBackOrderid);
            if($order['status'] == 0){
                echo 'SUCCESS';
            }            
        }
    }

}
