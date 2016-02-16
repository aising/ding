<?php

/**
 * 提取HTML中标签中的字符串数据
 * @param string $Label 标签名
 * @param string $Content 内容
 * @return string 返回标签内字符串
 */
function label_Content($Label,$Content) {
	preg_match("/(\<".$Label.".*?\>)(.*?)(\<\/".$Label."\>)/i",$Content,$matches);
	return $matches;
}



/**
 * 判断数组下的二级数组是否有值存在
 * @param array $arr  要验证的数组
 * @return bool 返回bool值。。有值存在返回真，无值存在返回假
 */
function is_sub_array_empty($arr){
	foreach($arr as $key => $var){
		if(!empty($var))return true;
	}
	return false;
}



/**
 * dateAdd方法作用：对一个特定的日期天数的加或减形成新的日期
* 参数：$current_time[说明：当前的日期，格式为（年-月-日）或（年-月-日 时-分-秒）]
*       $k_time[说明：要加或减的（年或月或天或小时）]
*       $time_state[说明：y:代表年，m:代表月,d:代表天,h：代表小时,i:代表分,s:代表秒]
*       $dis_style[说明：$dis_style=='d'输出的结果是[Y-m-d] $dis_style=='h'输出的结果[Y-m-d G:i:s]]
* 返回：计算后的日期
*/
function dateAdd($current_time, $k_time, $time_state, $dis_style) {
	$re_time = ''; // 返回值用
	$aos_str = ''; // 存加或减
	switch ($time_state) {
		case 'y' :
			$aos_str = $k_time . ' Year';
			break;
		case 'm' :
			$aos_str = $k_time . ' Month';
			break;
		case 'd' :
			$aos_str = $k_time . ' Days';
			break;
		case 'h' :
			$aos_str = $k_time . ' Hour';
			break;
		case 'i' :
			$aos_str = $k_time . ' Minute';
			break;
		case 's' :
			$aos_str = $k_time . ' Second';
			break;
	}
	if ($dis_style == 'd')
		$re_time = date('Y-m-d', strtotime($current_time . $aos_str));
	if ($dis_style == 'h')
		$re_time = date('Y-m-d G:i:s', strtotime($current_time . $aos_str));
	return $re_time;
}

/**
 * K_Twodays_Sub两个日期相减
 * 参数： $Big_time[说明：大日期]
 *        $Small_time[说明：小日期]
 * 返回：天数$n_days
 */
function dateDiff($format='d',$Big_time,$Small_time){
	switch ($format) {
		case 's' :
			$dividend = 1; // 秒
			break;
		case 'i' : // 分
			$dividend = 60;
			break;
		case 'h' : // 时
			$dividend = 3600;
			break;
		case 'd' : // 天
			$dividend = 86400;
			break;
	}
	$time1 = strtotime($Big_time);
	$time2 = strtotime($Small_time);
	if ($time1 && $time2)
		return (int)(($time1 - $time2) / $dividend);
	else
		return 0;
}

/**
 * 该过滤器用于对 "<>& 以及 ASCII 值在 32 值以下的字符进行转义。
 */
function repPostVar($val) {
	return filter_var($val, FILTER_SANITIZE_SPECIAL_CHARS);
}

/**
 * 字符串加密、解密函数
 * @param	string	$txt		字符串
 * @param	string	$operation	ENCODE为加密，DECODE为解密，可选参数，默认为ENCODE，
 * @param	string	$key		密钥：数字、字母、下划线
 * @return	string
 */
function sys_auth($txt, $operation = 'ENCODE', $key = '') {
	$key = $key ? $key : MD5_KEY;
	
	$txt = $operation == 'ENCODE' ? (string)$txt : base64_decode($txt);
	
	$len = strlen($key);
	$code = '';
	for($i = 0; $i < strlen($txt); $i++) {
		$k = $i % $len;
		$code .= $txt[$i] ^ $key[$k];
	}
	$code = $operation == 'DECODE' ? $code : base64_encode($code);
	
	return $code;
}


/**
 * 获得当前的脚本网址
 */
function getCurUrl() {
	if (!empty($_SERVER['REQUEST_URI'])) {
		$nowurl = $_SERVER['REQUEST_URI'];
	} else {
		$nowurl = empty($_SERVER["QUERY_STRING"]) ? $_SERVER["PHP_SELF"] : $_SERVER["PHP_SELF"] . '?' . $_SERVER["QUERY_STRING"];
	}
	return $nowurl;
}

/**
 * 设置Url地址串中的参数和值
 * @param string $url
 * @param string $key
 * @param string $value
 */
function setUrlPara($full_url, $key, $value) {
	$url_arr = explode('?', $full_url);
	$url = $url_arr[0];
	$para = isset($url_arr[1]) ? $url_arr[1] : '';
	parse_str($para, $arr);
	$arr[$key] = $value;
	return $url . '?' . http_build_query($arr);
}


/**
 * 写日志
 * @param string $method    接口方法名
 * @param string $para		接收到的参数
 * @param string $returnStr 返回的字符串
 */
function writeLog($method, $para, $returnStr) {
	$path = LOG . $method;
	if (!file_exists($path)) {
		mkdir($path, 0777);
	}
	$fileName = $path . '/' . date('Y-m-d') . '.log';
	
	$fp = fopen($fileName, 'a+');
	// 判断创建或打开文件是否 创建或打开文件失败，请检查权限或者服务器忙;
	if ($fp === false) {
		return false;
	} else {
		if (fwrite($fp, '[TIME:' . date("Y-m-d H:i:s") . '] ---- [PARA:' . $para . '] ---- [RETURN: {' . $returnStr . "}]\r\n")) {
			fclose($fp);
			return true;
		} else {
			return false;
		}
	}
}

/**
 * 写入接口日志
 * @param array $arr 接收的参数数组
 * @param string $returnStr 接口返回值
 * @return void 无返回
 *
 */
function writeInterFaceLog($arr = array(), $returnStr) {
	$method = $arr['method'];
	$para = arrToStr($arr, '&');
	writeLog($method, $para, $returnStr);
}

/**
 * 获取传递的参数
 * @param string $method 接口方法名
 */
function getParaArr($method,$isCheck=false)
{
	$paraArr = Array();
	$paraConf = __loadConfig('config_interface_parame');
	$paraKeyArr = $paraConf[$method];
	sort($paraKeyArr);
	foreach ($paraKeyArr as &$value){
		$paraArr[$value] = requestContext($value);
	}

	if ($isCheck) {
		//获取公用参数
		$sig = isset($_GET['sig']) && RepPostVar($_GET['sig']) ? RepPostVar($_GET['sig']) : '';
		 
		//校验，先对数组转为字符串，然后加上密钥，再与传递过来的Sig比对
		$verifyStr = arrToStr($paraArr,'').'secret='.SECRET;
			
		if ($sig != md5($verifyStr))
			return 0;
		else
			return $paraArr;
	}else {
		return $paraArr;
	}
}

/**
 * 数组转字符串
 * @param Array $arr 数组
 * @param string $separator 分隔符
 */
function arrToStr($arr=Array(),$separator){
	$return = '';
	if(is_array($arr))
	{
		foreach ($arr as $key=>$val){
			$return .= $return ? $separator : '';
			$return .= $key.'='.$val;
		}
	}
	return $return;
}

/**
 * 将数组格式进行格式化输出
 * @param Array $arr 数组
 * @param string $format 格式化类型，默认或出错时为XML，值为XML,JSON,TXT
 **/
function formatOutPut($arr=Array(),$format="XML",$encode=true)
{
	$rtnHeadStr = $rtnValue = $rtnEndStr = '';

	//默认为XML格式
	$format = strtoupper($format);
	$format = ('' == $format && 'JSON' != $format && 'TXT' != $format) ? 'XML' : $format;
	if($format == 'XML')
	{
		$rtnHeadStr = '<?xml version="1.0" encoding="UTF-8"?><request>';
		$rtnEndStr ='</request>';
	}

	if(is_array($arr))
	{
		switch($format)
		{
			case 'JSON':
				if ($encode) {
					$rtnValue = json_encode($arr);
				}else{
					arrayRecursive($arr, 'urlencode', true);
					$rtnValue = json_encode($arr);
					$rtnValue = urldecode($rtnValue);
				}
				break;
			case "TXT":
				foreach($arr as $key=>$val){
					//$rtnValue .= unicode_encode($val).'#';
					$rtnValue .= $val.'#';
					//$rtnValue = substr($rtnValue,0,-1);
				}
				break;
			default:
				foreach($arr as $key=>$val)
					$rtnValue .= '<'.$key.'>'.$val.'</'.$key.'>';
				break;
		}
	}
	return $rtnHeadStr.$rtnValue.$rtnEndStr;
}

function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
{
	static $recursive_counter = 0;
	if (++$recursive_counter > 1000) {
		die('possible deep recursion attack');
	}
	foreach ($array as $key => $value) {
		if (is_array($value)) {
			arrayRecursive($array[$key], $function, $apply_to_keys_also);
		} else {
			$array[$key] = $function($value);
		}

		if ($apply_to_keys_also && is_string($key)) {
			$new_key = $function($key);
			if ($new_key != $key) {
				$array[$new_key] = $array[$key];
				unset($array[$key]);
			}
		}
	}
	$recursive_counter--;
}


/**
 * 输出格式化后的结果，结束页面执行
 * @param array $arr 需要转换的数据
 * @param string $format 格式化类型，默认或出错时为XML，值为XML,JSON,TXT
 * @param bool $log 是否记录访问日志
 */
function print_format_result($arr=Array(), $format="XML", $log=true, $encode=true){
	$content = formatOutPut($arr, $format, $encode);
	if($log){
		writeInterFaceLog($_GET,$content);
	}
	//输出xml时定义页头
	if(strtoupper($format) == "XML" || $format == ""){
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control:post-check=0,pre-check=0",false);
		header("Pragma: no-cache");
		header("Content-type: text/xml; charset=UTF-8");
	}
	echo $content;
	exit();		//输出结果后结束页面
}

/**
 * 将内容进行UNICODE编码
 * @param $name 需要进行Unicode编码的值
 */
function unicode_encode($name)
{
	$name = iconv('UTF-8', 'UCS-2', $name);
	$len = strlen($name);
	$str = '';
	for ($i = 0; $i < $len - 1; $i = $i + 2)
	{
		$c = $name[$i];
		$c2 = $name[$i + 1];
		if (ord($c) > 0)
		{    // 两个字节的文字
			$str .= '\u'.base_convert(ord($c), 10, 16).base_convert(ord($c2), 10, 16);
		}
		else
		{
			$str .= $c2;
		}
	}
	return $str;
}

/**
 * 将UNICODE编码后的内容进行解码
 * @param $name 需要进行Unicode解码的值
 **/
function unicode_decode($name)
{
	// 转换编码，将Unicode编码转换成可以浏览的utf-8编码
	$pattern = '/([\w]+)|(\\\u([\w]{4}))/i';
	preg_match_all($pattern, $name, $matches);
	if (!empty($matches))
	{
		$name = '';
		for ($j = 0; $j < count($matches[0]); $j++)
		{
			$str = $matches[0][$j];
			if (strpos($str, '\\u') === 0)
			{
				$code = base_convert(substr($str, 2, 2), 16, 10);
				$code2 = base_convert(substr($str, 4), 16, 10);
				$c = chr($code).chr($code2);
				$c = iconv('UCS-2', 'UTF-8', $c);
				$name .= $c;
			}
			else
			{
				$name .= $str;
			}
		}
	}
	return $name;
}

/**
 * 计算密码强度(与客户端计算方式一样)
 *
 * @param string $pass 明文密码
 * @return int 密码强度值，值0表示弱，值1表示中，值2表示强
 *
 */
function passwordStrong($pass){
	$strong = 0;
	foreach (str_split($pass) as $str){
		if(is_numeric($str))
			$strong += 1;
		elseif (preg_match("/^[a-zA-Z]$/", $str))
		$strong += 2;
		else
			$strong += 5;
	}

	if($strong>5 && $strong<15){
		return 0;
	}else if($strong>=15 && $strong<30){
		return 1;
	}else if($strong>=30){
		return 2;
	}
}

/**
 *
 * 内容去BOM
 * @param $content 内容
 */
function del_bom($content){
	$s = substr($content, 0, 3);
	if(array_pop(unpack('H*',$s)) == 'efbbbf') {
		$content = substr($content, 3);
	}
	return $content;
}




/**
 * 读取配置状态
 * @param int $codeid 错误编码
 * @param string 错误信息(使用APP缓存接口后，错误信息接口直接返回)
 * @return $arr = array('status' => 状态码,'statusnote' => 状态信息);
 */
function read_status($codeid , $note=''){
	global $errCodeArr;
	if(isset($errCodeArr[$codeid]) && empty($note)){
		$arr = array('status' => $codeid,'statusnote' => $errCodeArr[$codeid]);
	}else{
		$arr = array('status' => $codeid,'statusnote' => $note);
	}
	return $arr;
}

/**
 * 随机数生成
 * @param 二进制  $word 值0001默认＝数字，值0010=小写字母，值0100=大写字母，值1000=特殊字符,可0001 - 1111 组合
 */
function random($mode = '0001',$len = 6){
	$num = '1234567890';
	$lower = 'abcdefghijklmnopqrstuvwxyz';
	$capital = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$special = '!@#$%&*?';
	$soure = array(
			'1' => $num
			,'2' => $lower
			,'3' => $num.$lower
			,'4' => $capital
			,'5' => $num.$capital
			,'6' => $lower.$capital
			,'7' => $num.$lower.$capital
			,'8' => $special
			,'9' => $special.$lower
			,'10' =>$special.$lower
			,'11' =>$special.$num.$lower
			,'12' =>$special.$capital
			,'13' =>$special.$capital.$num
			,'14' =>$special.$capital.$lower
			,'15' =>$special.$capital.$lower.$num
	);
	$accord = isset($soure[bindec($mode)]) ? $soure[bindec($mode)] : $soure['1'] ;
	$rNum = '';
	for($i = 0;$i < $len;$i ++){
		$rNum .= $accord[mt_rand(0, strlen($accord)-1)];
	}
	return $rNum;
}

/**
 * 提取HTML中标签中的字符串数据
 * @param string $Label 标签名
 * @param string $Content 内容
 * @return string 返回标签内字符串
 */
function label_Content_($Label,$Content) {
	preg_match("/(\<".$Label.".*?\>)(.*?)(\<\/".$Label."\>)/i",$Content,$matches);
	return $matches;
}


/**
 * 生成调用接口的地址串
 * @param unknown_type $arr
 */
function createInterfaceUrl($arr=array()){
	$para=arrToStr($arr,'&');
	ksort($arr);
	$sig = md5(arrToStr($arr,'').'secret='.SECRET);
	$para .= '&sig='.$sig;
	return $para;
}


function color_int2hex($number){
	if(empty($number))return '';
	return '#'.str_pad(dechex($number), 6, "0", STR_PAD_LEFT);
}

function color_hex2int($str){
	if(empty($str))return 0;
	return hexdec($str);
}


/**
 * 检验时间
 * @param $date date 日期
 * @param $format string 日期格式
 *
 * return bool
 */
function validateDate($date, $format = 'Y-m-d') {
	$d = DateTime::createFromFormat($format, $date);
	
	return $d && $d->format($format) == $date;
}


/**
 * 数据的日期参数格式化为一个字符串
 * 把['2014-01-01','2014-01-02'] 转为 "'2014-01-01','2014-01-02'"
 * param $data array
 *
 * return str
 */
function formatFilterDate($data) {
	$date_1 = isset($data['date_1']) ? $data['date_1'] : '';
	$date_2 = isset($data['date_2']) ? $data['date_2'] : '';
	$date_3 = isset($data['date_3']) ? $data['date_3'] : '';
	$date_4 = isset($data['date_4']) ? $data['date_4'] : '';
	
	$date = '';
	if($date_1) $date[] = $date_1;
	if($date_2) $date[] = $date_2;
	if($date_3) $date[] = $date_3;
	if($date_4) $date[] = $date_4;
	if(empty($date)) $date[] = date('Y-m-d');
	if(count($date) > 1) {
		$date_str = "'";
		$date_str .= implode("','",$date);
		$date_str .= "'";
	} else {
		$date_str = $date[0];
	}
	
	return $date_str;
}


/**
 * 数据的日期参数格式化为一个字符串(重构新添加，旧的因为还在用，所以加一个新的方法)
 * 把['2014-01-01','2014-01-02'] 转为 "'2014-01-01','2014-01-02'"
 * param $data array
 * @date 2014-05-29
 * @author  
 * return str
 */
function formatFilterDateNew($data) {
	$date_1 = isset($data['0']) ? $data['0'] : '';
	$date_2 = isset($data['1']) ? $data['1'] : '';
	$date_3 = isset($data['2']) ? $data['2'] : '';
	$date_4 = isset($data['3']) ? $data['3'] : '';
	
	$date = '';
	if($date_1) $date[] = $date_1;
	if($date_2) $date[] = $date_2;
	if($date_3) $date[] = $date_3;
	if($date_4) $date[] = $date_4;
	if(empty($date)) $date[] = date('Y-m-d');
	if(count($date) > 1) {
		$date_str = "'";
		$date_str .= implode("','",$date);
		$date_str .= "'";
	} else {
		$date_str = $date[0];
	}
	
	return $date_str;
}

function getIP() {
	return get_ip();
}

//取用户IP
function get_ip() {
    $ip = '';
    //nginx做前端代理时获取客户端真实IP
    if (function_exists('getallheaders')) {
            $_headers = getallheaders();
            if(!empty($_headers['Client_IP'])){
                    $ip = $_headers['Client_IP'];
            }
    }

    if($ip==''){
            if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
                    $ip = getenv("HTTP_CLIENT_IP");
            else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
                    $ip = getenv("HTTP_X_FORWARDED_FOR");
            else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
                    $ip = getenv("REMOTE_ADDR");
            else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
                    $ip = $_SERVER['REMOTE_ADDR'];
            else
                    $ip = "unknown";
    }
    return $ip;
}

/**
 * 数值转IP格式数据
 * @param  [type] $ipNumber [description]
 * @return [type]           [description]
 */
function numberToIp($ipNumber) {
	$ips = array();
	for($i = 0;$i < 4;$i++) {
		$ips[$i] = $ipNumber % 256;
		$ipNumber /= 256;
	}

	return implode('.',$ips);
}
