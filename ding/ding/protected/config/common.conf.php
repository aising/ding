<?php

/*
 * Common configuration that can be used throughout the application
 * Please refer to DooConfig class in the API doc for a complete list of configurations
 * Access via Singleton, eg. Doo::conf()->BASE_PATH;
 */
error_reporting(0);

date_default_timezone_set('asia/shanghai');

/**
 * for benchmark purpose, call Doo::benchmark() for time used.
 */
$config['START_TIME'] = microtime(true);

//For framework use. Must be defined. Use full absolute paths and end them with '/'      eg. /var/www/project/
$config['SITE_PATH'] = realpath('../') . '/';
//$config['PROTECTED_FOLDER'] = 'protected/';
$config['BASE_PATH'] = realpath('../../') . '/dooframework/';
//cache
$config['CACHE_PATH'] = realpath('../') . '/cache/';

//----------------- optional, if not defined, default settings are optimized for production mode ----------------
//if your root directory is /var/www/ and you place this in a subfolder eg. 'app', define SUBFOLDER = '/app/'

$config['SUBFOLDER'] = str_replace( str_replace('\\', '/',$config['SITE_PATH']), '', $_SERVER['DOCUMENT_ROOT']);

if (strpos($config['SUBFOLDER'], '/') !== 0) {
    $config['SUBFOLDER'] = '/' . $config['SUBFOLDER'];
}
$config['SUBFOLDER'] = '/weixin/ding/ding/public/';
$config['APP_URL'] = 'http://' . $_SERVER['HTTP_HOST'] . $config['SUBFOLDER'];
$config['AUTOROUTE'] = TRUE;
$config['DEBUG_ENABLED'] = false;

//$config['TEMPLATE_COMPILE_ALWAYS'] = TRUE;
//register functions to be used with your template files
//$config['TEMPLATE_GLOBAL_TAGS'] = array('url', 'url2', 'time', 'isset', 'empty');

/**
 * Path to store logs/profiles when using with the logger tool. This is needed for writing log files and using the log viewer tool
 */
$config['LOG_PATH'] = $config['SITE_PATH'] . 'logs/';
$config['LOG_SYSTEM_PATH'] = $config['LOG_PATH'] . 'system/';
$config['QR_FILE_PATH'] = $config['SITE_PATH'] . 'public/img/QR/';
$config['QR_URL_PATH'] = $config['APP_URL'] . 'img/QR/';

//创蓝发送短信接口URL, 如无必要，该参数可不用修改
$config['sms_api_send_url'] = '';
//创蓝短信余额查询接口URL, 如无必要，该参数可不用修改
$config['sms_api_balance_query_url'] = '';
//创蓝账号 
$config['sms_api_account']    = '';
//创蓝密码 
$config['sms_api_password']   = '';

//模板类型
define('TEMPLATESTYLE', 'default/');

//无线打印 使用本测试代码，您需要设置以下3项变量
//@ MEMBER_CODE：商户代码，登录飞印后在“API集成”->“获取API集成信息”获取
//@ FEYIN_KEY：密钥，获取方法同上
//@ DEVICE_NO：打印机设备编码，通过打印机后面的激活按键获取，为16位数字，例如"4600365507768327";
define('MEMBER_CODE', '');
define('FEYIN_KEY', '');           // '');
define('DEVICE_NO','');  // '');


//以下2项是平台相关的设置，您不需要更改
define('FEYIN_HOST','');
define('FEYIN_PORT', 80);

/**
 * defined either Document or Route to be loaded/executed when requested page is not found
 * A 404 route must be one of the routes defined in routes.conf.php (if autoroute on, make sure the controller and method exist)
 * Error document must be more than 512 bytes as IE sees it as a normal 404 sent if < 512b
 */
$config['ERROR_404_ROUTE'] = '/error';

/**
 * you can include self defined config, retrieved via Doo::conf()->variable
 * Use lower case for you own settings for future Compability with DooPHP
 */
$config['pagesize'] = 20;

//超管role id
$config['adminRoleId'] = 4;
//超管菜单屏蔽id
$config['hidden'] = array('40','55','60');

// 资源路径
$config['global'] = $config['APP_URL'] . 'global/';

// 语言定义
$config['lang'] = 'zh';

// 首选语言
$config['langList'] = array('zh' => 'Chinese','en'=>'English');

// 币种
$config['currency'] = array('USD','CNY');


// 站点密钥
$config['KEY_PASSWORD'] = '';

// 当路由重写隐藏index.php时请留空
$config['routeIndexFile'] = ''; //$config['routeIndexFile'] = 'index.php';

// 网站名称
$config['siteName'] = '微微乐';


// 定义redis服务器IP&PORT
$config['redis'] = array('127.0.0.1', '6379');
$config['redisPrefix'] = 'manage';

//后台前缀入口
$config['adminRoute'] = '~admin';

define('APILOG', $config['SITE_PATH'] . 'logs/api/');
/**
 * 调试代码
 * @param [type]  $data [description]
 * @param boolean $exit [description]
 */
function D($data, $exit = TRUE) {
    echo '<pre>';
    print_r($data);
    echo '<pre>';
    if ($exit)
        exit;
}

/**
* 多语言
* 根据session 拿到的语言进行匹配，没有则默认传过来的参数
* @param string  $str 对应内容的key
*/
function L($str){
    return $str;
    //语言包
    $_SESSION['language'] = 'chinese';
    include '../protected/config/language/'.$_SESSION['language'].'.php';    
    return $L[$str];
}
/**
 * 生成后台appurl
 * @param  [type] $url [description]
 * @return [type]      [description]
 */
function adminAppUrl($url = '') {
    if(strpos($url,'/')===0){
        $first = '';
    }else{
        $first = '/';
    }
    return Doo::conf()->APP_URL . 'index.php/' . Doo::conf()->adminRoute . $first . $url;
}

/**
 * 生成前台appurl
 * @param  [type] $url [description]
 * @return [type]      [description]
 */
function appUrl($url = '') {
  return Doo::conf()->APP_URL . 'index.php/' . str_replace('//','/', (Doo::conf()->routeIndexFile ? Doo::conf()->routeIndexFile . '/' : '') . $url);
}

/**
 * 取值
 * @param  mixed array or object $data
 * @param  string $key     
 * @param  mixed $default 
 * @return mixed
 */
function getVar($data, $key, $default = NULL) {
    if (is_array($data)) {
        return isset($data[$key]) ? $data[$key] : $default;
    } else if (is_object($data)) {
        return isset($data->$key) ? $data[$key] : $default;
    }
    return $default;
}
//辣的标识
function la($la){
    $a = '';
    for ($i=0; $i < $la; $i++) { 
        $a .= '<img src="' . Doo::conf()->global . 'default/image/lajiao.png" width="10" />';
    }
    return $a;
}
//db订单状态
function orderStatus($value='0')
{
    switch ($value) {
        case '0':
            $str = '未付款';
            break;
        case '1':
            $str = '未消费';
            break;
        case '2':
            $str = '已经消费';
            break;
        case '3':
            $str = '退款中';
            break;
        case '4':
            $str = '退款完毕';
            break;
        default:
            $str = '未付款';
            break;
    }
    return $str;
}
//weixin订单状态
$config['wxStatus'] = array('SUCCESS'=>'支付成功',
                            'REFUND'=>'转入退款',
                            'NOTPAY'=>'未支付',
                            'CLOSED'=>'已关闭',
                            'REVOKED'=>'已撤销（刷卡支付）',
                            'USERPAYING'=>'用户支付中',
                            'PAYERROR'=>'支付失败(其他原因，如银行返回失败)',
    );

//weixin订单状态
function orderState($value)
{
    $status = '未支付';
    switch ($value) {
            case 'SUCCESS':
                $status = '支付成功';
                break;
            case 'REFUND':
                $status = '转入退款';
                break;
            case 'NOTPAY':
                $status = '未支付';
                break;
            case 'CLOSED':
                $status = '已关闭';
                break;
            case 'REVOKED':
                $status = '已撤销（刷卡支付）';
                break;
            case 'USERPAYING':
                $status = '用户支付中';
                break;
            case 'PAYERROR':
                $status = '支付失败(其他原因，如银行返回失败)';
                break;
            default:
                $status = '未支付';
                break;
        }
        return $status;
}
/**
* 导出数据 html格式
*/
function exportExcel($data) {
    ob_clean();
    $header = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <meta name="viewport" content="width=device-width; initial-scale=1.0,user-scalable=no">
                <meta http-equiv="Pragma" content="no-cache">
                </head>
            <body>';    
    header("Content-type:application/vnd.ms-excel");
    header("content-Disposition:filename=".date("Y-m-d H",time()).".xls ");    
    echo $header.$data;die;
}
//全角转半角
function make_semiangle($str)
{
	$arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
	'５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
	'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
	'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
	'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
	'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
	'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
	'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
	'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
	'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
	'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
	'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
	'ｙ' => 'y', 'ｚ' => 'z',
	'（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
	'】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
	'‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<',
	'》' => '>',
	'％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
	'：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
	'；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
	'”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"',
	'　' => ' ','＄'=>'$','＠'=>'@','＃'=>'#','＾'=>'^','＆'=>'&','＊'=>'*',
	'＂'=>'"');
	return strtr($str, $arr);
}
/**
* 手机号码正则验证
* @param str $mobile
* @return 不正常返回true
*/
function mobileCheck($mobile=0)
{
    return !preg_match("/^1(3([0-9])||(45)||(47)||(5[0-3])||(5[5-9])||(8[0-9]))\d{8}$/", $mobile);
}
function randCode(){
    $code = '';
    // $char = '1,2,3,4,5,6,7,8,9,A,B,C,D,E,F,G,H,J,K,M,N,P,R,S,T,U,V,W,X,Y';
    $char = '1,2,3,4,5,6,7,8,9';
    $len = count(explode(',',$char)) - 1;
    $list = explode(',', $char);
    for($i=0; $i<4; $i++) {
        $rand_num = rand(0, $len);
        $code .= $list[$rand_num];
    }
    return $code;
}

