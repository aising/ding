<?php

$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx35be6cdc6a0b2ece&secret=01dc17815f6b934a3e22efeb09579e14';
$s = file_get_contents($url);
$d = json_decode($s);
var_dump($d);
//die;
define('ACCESS_TOKEN', $d->access_token);

//创建菜单
$url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.ACCESS_TOKEN;
$s = file_get_contents($url);
var_dump($s);

$s = ' 
 {
     "button":[
     {    
          "name":"订餐",
           "sub_button":[
           {    
               "type":"view",
               "name":"早餐",
               "url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx35be6cdc6a0b2ece&redirect_uri=http://www.wstreet.cn/weixin/can.php?type=1&response_type=code&scope=snsapi_base&state=1&connect_redirect=1#wechat_redirect"
            },
            {
               "type":"view",
               "name":"中餐",
               "url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx35be6cdc6a0b2ece&redirect_uri=http://www.wstreet.cn/weixin/can.php?type=2&response_type=code&scope=snsapi_base&state=1&connect_redirect=1#wechat_redirect"
            },
            {
               "type":"view",
               "name":"下午茶",
               "url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx35be6cdc6a0b2ece&redirect_uri=http://www.wstreet.cn/weixin/can.php?type=3&response_type=code&scope=snsapi_base&state=1&connect_redirect=1#wechat_redirect"
            },
            {
               "type":"view",
               "name":"晚餐",
               "url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx35be6cdc6a0b2ece&redirect_uri=http://www.wstreet.cn/weixin/can.php?type=4&response_type=code&scope=snsapi_base&state=1&connect_redirect=1#wechat_redirect"
            },
            {
               "type":"view",
               "name":"定制餐",
               "url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx35be6cdc6a0b2ece&redirect_uri=http://www.wstreet.cn/weixin/can.php?type=5&response_type=code&scope=snsapi_base&state=1&connect_redirect=1#wechat_redirect"
            }
            ]
      },
      {
             "type":"view",
              "name":"净菜配送",
              "url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx35be6cdc6a0b2ece&redirect_uri=http://www.wstreet.cn/weixin/can.php?type=6&response_type=code&scope=snsapi_base&state=1&connect_redirect=1#wechat_redirect"
      },
      {
           "name":"服务中心",
           "sub_button":[
           {    
               "type":"view",
               "name":"营销活动",
               "url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx35be6cdc6a0b2ece&redirect_uri=http://www.wstreet.cn/weixin/can.php?type=11&response_type=code&scope=snsapi_base&state=1&connect_redirect=1#wechat_redirect"
            },
            {    
               "type":"view",
               "name":"我的订单",
               "url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx35be6cdc6a0b2ece&redirect_uri=http://www.wstreet.cn/weixin/can.php?type=7&response_type=code&scope=snsapi_base&state=1&connect_redirect=1#wechat_redirect"
            },
            {
               "type":"view",
               "name":"吐槽",
               "url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx35be6cdc6a0b2ece&redirect_uri=http://www.wstreet.cn/weixin/can.php?type=8&response_type=code&scope=snsapi_base&state=1&connect_redirect=1#wechat_redirect"
            },
            {
               "type":"view",
               "name":"合作加盟",
               "url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx35be6cdc6a0b2ece&redirect_uri=http://www.wstreet.cn/weixin/can.php?type=9&response_type=code&scope=snsapi_base&state=1&connect_redirect=1#wechat_redirect"
            },
            {
               "type":"view",
               "name":"关于我们",
               "url":"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx35be6cdc6a0b2ece&redirect_uri=http://www.wstreet.cn/weixin/can.php?type=10&response_type=code&scope=snsapi_base&state=1&connect_redirect=1#wechat_redirect"
            }]
       }]
 }

';

var_dump(createMenu($s));

 function createMenu($data,$url=''){
   $ch = curl_init();
   if($url == ''){
      curl_setopt($ch, CURLOPT_URL, "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".ACCESS_TOKEN); 
   }else{
    curl_setopt($ch, CURLOPT_URL, $url); 
   }
   
   curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
   curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
   curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
   curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   $tmpInfo = curl_exec($ch);
   if (curl_errno($ch)) {
    return curl_error($ch);
   }
   curl_close($ch);
   return $tmpInfo;
}
?>
