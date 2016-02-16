<?php
// ------------------------------ 前台 ---------------------------------------------//
$route['*']['/'] = array('[default]HomeController', 'index');
$route['*']['/home'] = array('[default]HomeController', 'index');
$route['*']['/captcha'] = array('[default]CaptchaController', 'index');

$route['*']['/index'] = array('[default]HomeController', 'showList');
$route['*']['/show'] = array('[default]HomeController', 'show');
$route['*']['/addCart'] = array('[default]HomeController', 'addCart');
$route['*']['/cartList'] = array('[default]HomeController', 'cartList');
$route['*']['/cartDel'] = array('[default]HomeController', 'cartDel');
$route['*']['/submitOrder'] = array('[default]HomeController', 'submitOrder');
$route['*']['/getMeOrder'] = array('[default]HomeController', 'getMeOrder');
$route['*']['/getMeOrderById'] = array('[default]HomeController', 'getMeOrderById');
$route['*']['/orderCancel'] = array('[default]HomeController', 'orderCancel');
$route['*']['/mapList'] = array('[default]HomeController', 'mapList');
$route['*']['/about_us'] = array('[default]HomeController', 'about_us');
$route['*']['/orderPrint'] = array('[default]HomeController', 'orderPrint');

$route['*']['/wxpay'] = array('[default]PayController', 'wxJsPay');
$route['*']['/wxpay/wxpay'] = array('[default]PayController', 'wxJsPay');
$route['*']['/weiXcallBackWeiWeiCcan'] = array('[default]PayController', 'callBack');
$route['*']['/cancelRole'] = array('[default]HomeController', 'cancelRole');
$route['*']['/sendSMSCheckNO'] = array('[default]HomeController', 'sendSMSCheckNO');
$route['*']['/SMSCheckNO'] = array('[default]HomeController', 'SMSCheckNO');
$route['*']['/join'] = array('[default]HomeController', 'join');
$route['*']['/join2'] = array('[default]HomeController', 'join2');
$route['*']['/orderPrint2'] = array('[default]HomeController', 'orderPrint2');
// ------------------------------ 调度 ---------------------------------------------//
$route['*']['/sendBookSUm'] = array('[default]CrontabController', 'sendBookSUm');
// ------------------------------ 后台 ---------------------------------------------//



$preRoute = '/' . $config['adminRoute'];
// ------------------------------ 后台登录 ---------------------------------------------//
$route['*'][$preRoute] = array('[console]LoginController', 'in'); 
$route['*'][$preRoute . '/in'] = array('[console]LoginController', 'in'); 
$route['*'][$preRoute . '/out'] = array('[console]LoginController', 'out'); 
$route['*'][$preRoute . '/login/out'] = array('[console]LoginController', 'out'); 
$route['*'][$preRoute . '/checkMobile'] = array('[console]LoginController', 'checkMobile');
$route['*'][$preRoute . '/home'] = array('[console]HomeController', 'index'); 



// ------------------------------- 系统管理 --------------------------------------------//
$route['*'][$preRoute . '/system'] = array('[console]system/MenuController', 'dataTable');
// ------------------------------- 配置值 --------------------------------------------//
$route['*'][$preRoute . '/system/config'] = array('[console]system/ConfigController', 'dataTable');
$route['*'][$preRoute . '/system/config/dataTable'] = array('[console]system/ConfigController', 'dataTable');
// ------------------------------- 配送处理 --------------------------------------------//
$route['*'][$preRoute . '/system/config/peisong'] = array('[console]system/PeiSongController', 'dataTable');
$route['*'][$preRoute . '/system/config/peisong/dataTable'] = array('[console]system/PeiSongController', 'dataTable');
$route['*'][$preRoute . '/system/config/peisong/add'] = array('[console]system/PeiSongController', 'add');
$route['*'][$preRoute . '/system/config/peisong/mod'] = array('[console]system/PeiSongController', 'mod');
$route['*'][$preRoute . '/system/config/peisong/del'] = array('[console]system/PeiSongController', 'del');

// ------------------------------- 菜单 --------------------------------------------//
$route['*'][$preRoute . '/system/menu'] = array('[console]system/MenuController', 'dataTable');
$route['*'][$preRoute . '/system/menu/dataTable'] = array('[console]system/MenuController', 'dataTable');
$route['*'][$preRoute . '/system/menu/add'] = array('[console]system/MenuController', 'add');
$route['*'][$preRoute . '/system/menu/mod'] = array('[console]system/MenuController', 'mod');
$route['*'][$preRoute . '/system/menu/del'] = array('[console]system/MenuController', 'del');

// ------------ 角色 --------------------//
$route['*'][$preRoute . '/system/role/dataTable'] = array('[console]system/RoleController', 'dataTable');
$route['*'][$preRoute . '/system/role/add'] = array('[console]system/RoleController', 'add');
$route['*'][$preRoute . '/system/role/mod'] = array('[console]system/RoleController', 'mod');
$route['*'][$preRoute . '/system/role/del'] = array('[console]system/RoleController', 'del');

$route['*'][$preRoute . '/system/user/dataTable'] = array('[console]system/UserController', 'dataTable');
$route['*'][$preRoute . '/system/user/add'] = array('[console]system/UserController', 'add');
$route['*'][$preRoute . '/system/user/mod'] = array('[console]system/UserController', 'mod');
$route['*'][$preRoute . '/system/user/modPassword'] = array('[console]system/UserController', 'modPassword');
$route['*'][$preRoute . '/system/user/del'] = array('[console]system/UserController', 'del');
$route['*'][$preRoute . '/system/user/login'] = array('[console]system/UserController', 'login');

$route['*'][$preRoute . '/system/userModPassword/modPassword'] = array('[console]system/UserModPasswordController', 'modPassword');
// ------------------------------- 运营管理 --------------------------------------------//

//表单统计
$route['*'][$preRoute . '/operation/analyze'] = array('[console]operation/AnalyzeController', 'dataTable');
// ------------------------ 分类 -----------//
$route['*'][$preRoute . '/operation/book/category/dataTable'] = array('[console]operation/book/CategoryController', 'dataTable');
$route['*'][$preRoute . '/operation/book/category/add'] = array('[console]operation/book/CategoryController', 'add');
$route['*'][$preRoute . '/operation/book/category/mod'] = array('[console]operation/book/CategoryController', 'mod');
$route['*'][$preRoute . '/operation/book/category/del'] = array('[console]operation/book/CategoryController', 'del');

// ------------------------ 列表 -----------//
$route['*'][$preRoute . '/operation/book/dataTable'] = array('[console]operation/book/BookController', 'dataTable');
$route['*'][$preRoute . '/operation/book/add'] = array('[console]operation/book/BookController', 'add');
$route['*'][$preRoute . '/operation/book/mod'] = array('[console]operation/book/BookController', 'mod');
$route['*'][$preRoute . '/operation/book/del'] = array('[console]operation/book/BookController', 'del');
//门店 坐标
$route['*'][$preRoute . '/operation/map/dataTable'] = array('[console]operation/MapController', 'dataTable');
$route['*'][$preRoute . '/operation/map/add'] = array('[console]operation/MapController', 'add');
$route['*'][$preRoute . '/operation/map/mod'] = array('[console]operation/MapController', 'mod');
$route['*'][$preRoute . '/operation/map/del'] = array('[console]operation/MapController', 'del');

//门店 坐标
$route['*'][$preRoute . '/operation/city/dataTable'] = array('[console]operation/CityController', 'dataTable');
$route['*'][$preRoute . '/operation/city/add'] = array('[console]operation/CityController', 'add');
$route['*'][$preRoute . '/operation/city/mod'] = array('[console]operation/CityController', 'mod');
$route['*'][$preRoute . '/operation/city/del'] = array('[console]operation/CityController', 'del');

//用户
$route['*'][$preRoute . '/operation/user/dataTable'] = array('[console]operation/UserController', 'dataTable');
$route['*'][$preRoute . '/operation/user/add'] = array('[console]operation/UserController', 'add');
$route['*'][$preRoute . '/operation/user/mod'] = array('[console]operation/UserController', 'mod');
$route['*'][$preRoute . '/operation/user/del'] = array('[console]operation/UserController', 'del');
//用户
$route['*'][$preRoute . '/operation/order/dataTable'] = array('[console]operation/OrderController', 'dataTable');
$route['*'][$preRoute . '/operation/order/add'] = array('[console]operation/OrderController', 'add');
$route['*'][$preRoute . '/operation/order/mod'] = array('[console]operation/OrderController', 'mod');
$route['*'][$preRoute . '/operation/order/del'] = array('[console]operation/OrderController', 'del');
// ----------- 配置相关 -----------//
//获取语言列表
$route['*']['/api/back/getLanguageList'] = array('[api]back/SettingController', 'getLanguageList');


//菜品点击
$route['*'][$preRoute . '/operation/log'] = array('[console]operation/LogController', 'dataTable');
$route['*'][$preRoute . '/operation/logorder'] = array('[console]operation/LogorderController', 'dataTable');

// ------------------------------ 错误控制器 ---------------------------------------//
$route['*']['/error'] = array('ErrorController', 'index');

?>
