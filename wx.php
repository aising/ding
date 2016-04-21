<?php
/**
  * wechat php test
  */
//接收微信传过来的文件，包括：补始验证、地理位置
error_reporting(0);
//define your token
define("TOKEN", "E265464K17w2Z64X9r9z339BJJ23w92R0j424X15qRm13546XH1");
$wechatObj = new wechatCallbackapiTest();
$wechatObj->valid();

$raw_data1 = file_get_contents('php://input', 'r');
$raw_data = $_REQUEST;
//var_dump($raw_data);
file_put_contents('./input1.txt',$raw_data.$raw_data1,FILE_APPEND);

class wechatCallbackapiTest
{
    public function valid() 
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
            $this->responseMsg();
           // echo $echoStr;
                
        }       
    }

    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        file_put_contents('./input.txt',$postStr,FILE_APPEND);
        //extract post data
        if (!empty($postStr)){

            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time(); 
            $textTpl = "<xml>
                                                    <ToUserName><![CDATA[%s]]></ToUserName>
                                                    <FromUserName><![CDATA[%s]]></FromUserName>
                                                    <CreateTime>%s</CreateTime>
                                                    <MsgType><![CDATA[%s]]></MsgType>
                                                    <Content><![CDATA[%s]]></Content>
                                                    <FuncFlag>0</FuncFlag>
                                                    </xml>";        
            $ev = $postObj->Event;

            if($ev == 'subscribe' || !empty( $keyword ))
            {       
                    $msgType = "text"; 
                    $contentStr = "欢迎关注微微乐！点餐前请选择您附近的门店,<a href='https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx5d43f5f3tefe&redirect_uri=http://www.ws.cn/weixin/can.php?type=2&response_type=code&scope=snsapi_base&state=1&connect_redirect=1#wechat_redirect'>查看附近的门店</a>";
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                    echo $resultStr;
            }else{  
                    echo '';
            }
            
            $ToUserName = (string)$postObj->ToUserName;
            $FromUserName = (string)$postObj->FromUserName;
            $Latitude = (string)$postObj->Latitude;
            $Longitude = (string)$postObj->Longitude;
            $Precision = (string)$postObj->Precision;
            $info = array('toUserName'=>$ToUserName,
                        'fromUserName'=>$FromUserName,
                        'x'=>$Latitude,
                        'y'=>$Longitude,
                        'Precision'=>$Precision);
			if(trim($Latitude)!=''){
        	    file_put_contents('./location/'.$fromUsername.'.php',json_encode($info));
        	}
	}else { 
            echo "";
            exit;
        }
    }

        private function checkSignature()
        {
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

                $token = TOKEN;
                $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
                sort($tmpArr, SORT_STRING);
                $tmpStr = implode( $tmpArr );
                $tmpStr = sha1( $tmpStr );

                if( $tmpStr == $signature ){
                        return true;
                }else{
                        return false;
                }
        }
}

?>

