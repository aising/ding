<?php
header("Content-type: text/html; charset=utf-8");
Doo::loadController('MainController');
$payClassPath = Doo::conf()->SITE_PATH."protected/module/default/class/pay/";
require_once $payClassPath."WxpayAPI/lib/WxPay.Api.php";

/**
* 前台首页
*/
class HomeController extends MainController {
    protected $_checkPageAuth = TRUE;

    //微信公众号配置
    private $_appid = 'wx35be6cdc6a0b2ece';
    private $_secret = '01dc17815f6b934a3e22efeb09579e14';
    //购物车清单失效时间
    private $_expCartTime = 0;
    //曾经光临过的店
    private $_shopKey = '';
    // private $_map_key = '8741310c4659f38a953c4a03603693df';
    private $_map_key = 'lTuku5g2Bqll9G3PCvK6Vn1C';
    private $_map_tableid = '122383';

    //配置key
    private $_configKey = 'settingConfig';

    public function init() {
        
        //$_SESSION['uid']  = '12333337';
        //$_SESSION['openid'] = 'o0nwzs7XPfy7LdTdWORz_zEzAx58';
        // $_SESSION['userInfo']['cityid'] = $_SESSION['cityid'] = 3;
        // $_SESSION['userInfo']['shopname'] =  $_SESSION['shopname'] = '南山A';
        
        if(!isset($_SESSION['uid']) && $this->_action!='orderPrint2'  && $this->_action!='index' )
        {
            echo '异常！';die;
        }

        $this->_expTime();
        $this->_shopKey = $_SESSION['uid'].$_SESSION['openid'].'shop';
    }

    //home
    public function index(){
        if(isset($_GET['code'])){
            //菜单入口文件
            $code = trim($_GET['code']);
            $appid = $this->_appid;
            $secret = $this->_secret;
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret=' . $secret . '&code='.$code.'&grant_type=authorization_code';
            
            //获取 openid
            $openinfo = json_decode(file_get_contents($url));            

            //refresh_token 暂时不用看看
            // $url_re = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid='.$appid.'&grant_type=refresh_token&refresh_token='.$openinfo->refresh_token;
            // $openinfo_re = json_decode(file_get_contents($url_re));

            $openid = $openinfo->openid;
            $info = array('openid'=>$openinfo->openid,
                            'form'=>'weixin',
                            'regtime'=>time(),
                            'regip'=>getIP(),
                            );
            $this->saveUser($info);
        }
        echo '~index';die;
        //通过type值做转发
        //1是早餐，2是中餐
        // $type = $_GET['type'];
        // require_once('./page.php');
    }

    //保存当前用户，以openid为唯一ID,如果DB中有就不保存，没有则新增
    //跳转页面
    private function saveUser($openid){
        $res = DBproxy::getProcedure('Manage')->setDimension(2)->saveUserInfo($openid);
        
        if(isset($res['id']) && intval($res['id']) > 0){
            $_SESSION['uid'] = $res['id'];
            $_SESSION['openid'] = $openid['openid'];

            $_SESSION['userInfo']['cityid'] = $res['cityid'];
            $_SESSION['userInfo']['shopname'] = $res['shopname'];
            switch ($_GET['type']) {
                case '1':
                case '2':
                case '3':
                case '4':
                case '5':
                case '6':
                    header('Location:'.appurl('mapList?type='.$_GET['type']));
                    break;
                case '7': //我的订单
                    header('Location:'.appurl('getMeOrder'));
                    break;
                case '8'://吐槽
                    header('Location:http://www.chaojibiaoge.com/index.php/U/url/UsVhtKxX');
                    break;
                case '9'://合作加盟
                    header('Location:'.appurl('join'));
                    break;
                case '10'://关于我们
                    header('Location:'.appurl('about_us'));
                    break;
                default:
                    echo '<h1>正在开发中,敬请期待...</h1>';die;
                    break;
            }            
        }else{
                echo 'oooh';
        }
    }

    public function about_us(){
        $infoCache = DOO::cache('php')->get($this->_configKey);
        $info = json_decode($infoCache,true);
        $data = array('pageTitle' => '关于本店','aboutHtml'=>$info['editorValue']);
        $this->layoutRender('/about_us',$data);
    }

    private function getMap($data = array(),$location){
        // radius 后面的是米数
        $url = "http://api.map.baidu.com/geosearch/v3/nearby?ak=".$this->_map_key."&geotable_id=".$this->_map_tableid."&location=".$location."&page_size=50&radius=150000000"; 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
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
    //获取用户的当前坐标
    private function getUserLocation($openid){
        $locationsz = '113.999548,22.54641';// 深圳
        //$location = '116.4077,39.887348'; //北京
        $info = @file_get_contents('/data/wstreet.cn/web/weixin/location/'.$openid.'.php');
        $l = json_decode($info,true);
        $location = $l['y'].','.$l['x'];
        if(trim($l['x']) == '' || trim($l['y']) == ''){
            return $locationsz;
        }else{
            return $location;
        }
    }

    //地图店列表
    //店获取,如果有则直接进入
    public function mapList(){
        $typeid = isset($_GET['type']) ? $_GET['type'] : '0';

        //店面处理
        $removeShopCache = isset($_GET['removeShopCache']) ? $_GET['removeShopCache'] : 0;
        if($removeShopCache == 1 ){
            DOO::cache('php')->flush($this->_shopKey);
        }
        $shopCache = DOO::cache('php')->get($this->_shopKey);
        if(isset($shopCache['shopname']) && trim($shopCache['shopname']) != '' && isset($shopCache['cityid']) && intval($shopCache['cityid']) > 0 )
        {
            header("Location:".appurl('index?typeid='.$typeid.'&city='.$shopCache['cityid'].'&shopname='.$shopCache['shopname']));
            exit();
        }
        //地图显示
        $location = $this->getUserLocation($_SESSION['openid']);
        $cacheData = '';
        $cartKey = 'maplist'.$location.$typeid.'.txt';
        DOO::cache('php')->flush($cartKey);
        $cacheData = DOO::cache('php')->get($cartKey);
        
        $len = strlen($cacheData);

        if( $cacheData !='' && $cacheData !== false && $len > 10){
            $jsonLocation = $cacheData;            
        }else{            
            $res = $this->getMap(array(),$location);
            $data = json_decode($res,true);
            $mapData = array();
            if(isset($data['contents'])){
                foreach ($data['contents'] as $key => $value) {
                    $mapData[] = array(
                                        $value['location'][0],
                                        $value['location'][1],
                                        '<a class="mapa" href="'.appurl('index?typeid='.$typeid.'&city='.$value['cityid'].'&shopname='.$value['title']).'">'.$value['title'].'</a>',
                                        $value['address'],
                                        $value['cityid'],
                                        $value['title'])
                                        ;

                }
            }

            $jsonLocation = json_encode($mapData);
            $cacheData = DOO::cache('php')->set($cartKey,$jsonLocation,3600*24*7);
        }
        $data = array( 'jsonLocation' => ($jsonLocation),'location' => $location,'pageTitle' => '门店列表');
        $this->layoutRender('/maplist',$data);
    }

    //餐的类别,默认是早餐
    private function canType($typeid = 1){
        $params['type'] = '0';
        if( $typeid == 1){
            $params['type'] = '50'; //早餐
        }elseif( $typeid == 2){
            $params['type'] = '57'; //中餐
        }elseif( $typeid == 3){
            $params['type'] = '65'; //下午茶
        }elseif( $typeid == 4){
            $params['type'] = '66'; //晚餐
        }elseif( $typeid == 5){
            $params['type'] = '64'; //制定
        }elseif( $typeid == 6){
            $params['type'] = '67'; //净菜
        }
        return $params['type'];
    }

    //根据城市获取分类
    //index
    public function showList(){        
        //餐的分类
        $typeid = isset($_GET['typeid']) ? intval($_GET['typeid']) : (isset($_SESSION['typeid']) ? $_SESSION['typeid'] : '1');
        $params['type'] = $this->canType($typeid);
        //城市
        $params['city'] = isset($_GET['city']) ? intval($_GET['city']) : (isset($_SESSION['cityid']) ? $_SESSION['cityid'] : '2');
        
        $subType = DBproxy::getProcedure('Manage')->setDimension(2)->getbookType($params);

        $preid = NULL;
        //预选上的餐品分类
        foreach ($subType as $key => $value) {
            $subTypeName[] = $value['name'];
            if($value['name'] == '今日推荐' ){
                $preid = $value['id'];break;
            }
        }
        //if( isset($_SESSION['scategory']) )
        //{
        //    header('Location:'.appurl( 'index?categoryid='.$_SESSION['scategory'] ) );unset($_SESSION['scategory']);exit;
        //}
        $_SESSION['scategory'] = $_GET['categoryid'] = $params['categoryid'] = isset($_GET['categoryid']) ? intval($_GET['categoryid']) : $preid;
        
        //店名
        $params['shop'] = isset($_GET['shopname']) ? trim($_GET['shopname']) : (isset($_SESSION['shopname']) ? $_SESSION['shopname'] : '');
        //用户选择的店做缓存,下次直接进入
        $shopCache['shopname'] = $_SESSION['shopname'] = $params['shop'];
        $shopCache['cityid'] = $_SESSION['cityid'] = $params['city'];
        $_SESSION['typeid'] = $typeid;
        
        DOO::cache('php')->set($this->_shopKey,$shopCache);
        $book = DBproxy::getProcedure('Manage')->setDimension(2)->getsubType($params);
        //显示菜品被预定的情况
        $list = $this->countPrice();
        foreach ($book as $key => $value) {
            $book[$key]['wxpriceOrg'] = $value['wxprice'];
            $book[$key]['timeout'] = time() > strtotime($value['endtime']) ? true : false;
        $book[$key]['wxprice'] = $this->priceProce($value['opentime'],$value['endtime'],$value['wxprice'],$value['price'],$value['opentimeDay']);
            //$book[$key]['wxprice'] = $this->priceProce($value['opentime'],$value['endtime'],$value['wxprice'],$value['price']);
        }
        $data = array('subType' => $subType,'book' => $book,'list'=>$list,'params'=>$params);
        $this->layoutRender('/index',$data);
    }

    public function show(){
        $params['id']  = intval($_GET['id']);
        $book = DBproxy::getProcedure('Manage')->setDimension(2)->getBookList($params);
        
        if( isset($book[0]) && !empty($book[0])){
            //点击日志
            $loginfo['id'] = $params['id'];
            $loginfo['categoryid'] = $book['0']['categoryid'];
            $this->saveLog($loginfo);

            //预定时间中
            $off = '';
            $opentime = $book[0]['opentime'];
            $endtime = $book[0]['endtime'];
            $optimeDay = $book[0]['opentimeDay'];
            //$optimeDay = '1';
            //$opentime = '9:00';
            //$endtime = '12:00';
            $nowtime = time();
            //前一天,大于开始时间和小于结束时间则可以预定
            if($optimeDay == 1)
            {
                if( $nowtime < strtotime($opentime) && $nowtime >= strtotime($endtime) )
                {
                    $off = '不在预定时间内';
                }
            }
            //当天
            if($optimeDay == 0)
            {
                if( $nowtime < strtotime($opentime)  || $nowtime > strtotime($endtime) ){
                    $off = '不在预定时间内';
                }
            }
            $book[0]['timeout'] = time() > strtotime($book[0]['endtime']) ? true : false;
            $book[0]['wxpriceOrg'] = $book[0]['wxprice'];
            $book[0]['wxprice'] = $this->priceProce($opentime,$endtime,
                                $book[0]['wxprice'],$book[0]['price'],$book[0]['opentimeDay']);
            $data = array( 'book' => $book[0]);
            $data['off'] = $off;
            $this->layoutRender('/food_details',$data);
        }else{
            $this->alert('data is null');
        }
    }

    //价钱处理
    //不在设定时间内的weixin预定价 为正常价
    private function priceProce($opentime,$endtime,$wxprice = 0,$price = 0,$opentimeDay = 0){       
         //开餐时间处理
        $outOpentime = strtotime($opentime);
        $outEndtime = strtotime($endtime);
        $now = time();
        //前一天,大于开始时间和小于结束时间则可以预定
        if($opentimeDay == 1)
        {
            if( $now > ($outOpentime) || $now <= ($outEndtime) )
            {
                $outPrice = $wxprice;
            }else{
                //$off = '不在预定时间内';
                $outPrice = $price;
            }
        }
        //当天
        if($opentimeDay == 0)
        {
            if( $now > ($outOpentime)  && $now < ($outEndtime) ){                
                $outPrice = $wxprice;
            }else{
                //$off = '不在预定时间内';
                $outPrice = $price;
            }
        }
        // echo $wxprice,'-',$price,'-'; echo "$now < $outOpentime && $now > $outEndtime";die;
        //小于开业时间和大于结束时间
        // if($now < $outOpentime || $now > $outEndtime){
        //     $outPrice = $price;
        // }else{
        //     $outPrice = $wxprice;
        // }
        //echo "$outOpentime,$outEndtime,$now,$wxprice,$price,$outPrice";die;
        return $outPrice;
        ////////////////////////////////////////////////////////////////////////////////////////////
        //开餐时间处理
        $outOpentime = strtotime($opentime);
        $outEndtime = strtotime($endtime);
        $now = strtotime(date("H:i:s"),time());
        // echo $wxprice,'-',$price,'-'; echo "$now < $outOpentime && $now > $outEndtime";die;
        //小于开业时间和大于结束时间
        if($now < $outOpentime || $now > $outEndtime){
            $outPrice = $price;
        }else{
            $outPrice = $wxprice;
        }
        //echo "$outOpentime,$outEndtime,$now,$wxprice,$price,$outPrice";die;
        return $outPrice;
    }
    //写点击日志
    private function saveLog($loginfo){
        $loginfo1['booktype'] = $loginfo['categoryid'];
        $loginfo1['bookid'] = $loginfo['id'];

        $loginfo1['uid'] = $_SESSION['uid'];
        $loginfo1['time'] = time();
        $loginfo1['agent'] = $_SERVER['HTTP_USER_AGENT'];
        $loginfo1['ip'] = getIP();            

        $loginfo1['city'] = $_SESSION['cityid'];
        $loginfo1['shopname'] = $_SESSION['shopname'];
        
        //log
        DBproxy::getProcedure('Manage')->setDimension(2)->saveHistLog($loginfo1);
    }

    //同id的则数量+1
    public function addCart(){
        if(!isset($_POST)){
            header('Location:'.appurl('index'));
        }

        $type = $this->getUrlVar('type',NULL);
        $cartKey = $_SESSION['uid'].'_cart';

        //提交时候的购物车处理
        if($type == 'submit'){            
            if(!isset($_POST['cart'])){
                $this->alert('菜单不能为空');exit;
            }

            //验证手机号码 
            $uparam['userid'] = $_SESSION['uid'];
            $userInfo = DBproxy::getProcedure('Manage')->setDimension(2)->getUserInfo($uparam);
            
            //$postPhone = $userInfo['data'][0]['phone'];
            $postPhone = $this->post('phone');
            $postAddr = $this->post('addr');
            if(!is_numeric($postPhone) || strlen($postPhone)!=11){
                Doo::logger()->info('time:'.date("Y-m-d H:i:s",time())."\tip:".getIP()."\t 手机号码保存异常:uparam:".var_export($uparam,true),'addcart');
                $this->alert('请验证手机号码，或者联系客服。');exit;

            }

            //外卖要地址
            if(isset($_POST['waimai']) && ($_POST['waimai']) == '选择外卖' ){                
                if(strlen(trim($postAddr)) < 3 ){
                    $this->alert('请填写正确的地址');exit;
                }
                $_SESSION['waimai'] = '1';
            }
            
            $info['addr'] = trim($postAddr);
            $info['phone'] = $postPhone;
            $info['userid'] = $_SESSION['uid'];
            $saveInfo = DBproxy::getProcedure('Manage')->setDimension(2)->saveUserInfo($info);            
            if($saveInfo['status'] != 0){
                $this->alert('保存异常');exit;
            }
            DOO::cache('php')->flush($cartKey);
        }

        //单个
        if( isset($_POST['id']) && isset($_POST['sum']) ){
            $cart[] = array('id' => $_POST['id'], 'sum' => $_POST['sum']);
        }else{
            //多个
            $cart = $_POST['cart'];
        }
        
        $cartCache = DOO::cache('php')->get($cartKey);

        //加入购物车
        if(is_array($cart) && !empty($cart)){
            foreach ($cart as $key => $value) {

                $id = intval($value['id']);
                $sum = intval($value['sum']);
                
                //检测可订份数
                $this->checkCanSum($id,$value);
                //检测菜品时间
                if(!$this->checkBook($id)){
                    $this->alert('菜品已经超出售卖时间，不可预定了！');exit();
                }

                if( $id > 0  && $sum > 0 ){
                    //如果已经存在的处理，如果sum是负数则减少，如果此id减后少于0则删除这个产品
                    if( isset($cartCache[$id]) ){
                        $cartCache[$id]['sum'] +=$sum;
                        if( $cartCache[$id]['sum'] < 1 ){
                            unset($cartCache[$id]);
                        }
                    }else{
                        //添加时候只接受正数
                        if($sum >= 1){
                            $cartCache[$id] = array( 'id' => $id, 'sum' => (int)$sum );
                        }
                    }
                }
            }
        }
        DOO::cache('php')->set($cartKey,$cartCache,$this->_expCartTime);

        if( $type == 'submit' ){
            // $this->checkWXVer();
            header('Location:'.appurl('submitOrder'));
        }else{
            
            header('Location:'.appurl('cartList'));
        }
        
    }

    //微信版本,小于5不能支付 
    protected function checkWXVer(){            
            $arr = explode('/', $_SERVER['HTTP_USER_AGENT']);
            sort($arr);
            if( intval($arr[0]) <7){
                $this->alert('你的微信版不支持支付，微信版本号高于或者等于5.0才支持支付。');exit();
            }
    }
    //显示购物车列表
    public function cartList(){
        $show  =  strpos ( $_SERVER['HTTP_REFERER'] ,  'show' ); 
        $index  =  strpos ( $_SERVER['HTTP_REFERER'] ,  'index' ); 
        if ( $show  ===  false && $index  ===  false ) {
            header("Location:" . appurl('index') );exit;
        }

        $userInfo = $list = array();
        $cartKey = $_SESSION['uid'].'_cart';
        $cart = DOO::cache('php')->get($cartKey);

        if( $cart ){
            $list = $this->countPrice();
            $off = $this->checkBookEndTime($list['data']); //外卖及预定时间判断
            $info['userid'] = $_SESSION['uid'];
            $userInfo = DBproxy::getProcedure('Manage')->setDimension(2)->getUserInfo($info);
        }else{
            header("Location:" . appurl('getMeOrder') );exit;
        }

        $data = array('list' => $list,'off'=>$off,'userInfo'=>$userInfo,'pageTitle' => '购物车列表');
        // D($data);
        $this->layoutRender('/cartlist',$data);
    }
    //判断购物车中列表是是否超出外卖时间和预定时间
    private function checkBookEndTime($list,$submit=false){
        $off = array();
        foreach ($list as $key => $value) 
        {
            $opentime = $value['opentime'];
            $endtime = $value['endtime'];
            $optimeDay = $value['opentimeDay'];
            $waimaiEndtime = $value['waimaiEndtime'];
            $nowtime = time();

            //外卖时间判断
            if($nowtime > strtotime($waimaiEndtime)){
                $off['waimai']['id'] = '1';
                $off['waimai']['name'] = $value['name'];
                $off['waimai']['endtime'] = $waimaiEndtime;
            }
            //预定时间
            if($optimeDay == '1')
            {
                if( $nowtime < strtotime($opentime) && $nowtime >= strtotime($endtime) )
                {
                    $off['yuding'] = '1';
                    $off['name'] = $value['name'];
                }
            }else //当天
            {
                if( $nowtime < strtotime($opentime)  || $nowtime > strtotime($endtime) ){
                    $off['yuding'] = '1';
                    $off['name'] = $value['name'];
                }
            }
            
            if( !empty($off['waimai']) && !empty($off['yuding']) ){
                break;
            }
        }
        return $off;
    }

    //统计购物车数据
    public function countPrice(){
        $ids = '';        
        $cartKey = $_SESSION['uid'].'_cart';
        $cart = DOO::cache('php')->get($cartKey);
        $item = array();

        if( $cart ){
            $item['allPrice'] = 0;
            $item['allSum'] = 0;
            $ids = array_keys($cart);
            $idsStr = implode(',', $ids);
            $params['id']  = $idsStr;
            $data = DBproxy::getProcedure('Manage')->setDimension(2)->getBookList($params);

            foreach ($data as $key => $value) {                
                //$price = $this->priceProce($value['opentime'],$value['endtime'],$value['wxprice'],$value['price']);
                $price = $this->priceProce($value['opentime'],$value['endtime'],$value['wxprice'],$value['price'],$value['opentimeDay']);
                $itemPrice = $price * $cart[$value['id']]['sum'];
                $item['data'][$value['id']] = array(    
                                                        'name'  => $value['name'],
                                                        'img' => $value['img'], 
                                                        'wxprice' => $price,
                                                        'price' => $value['price'],
                                                        'la' => $value['la'],
                                                        'countPrice' => $itemPrice,
                                                        'opentimeDay' => $value['opentimeDay'],
                                                        'opentime' => $value['opentime'],
                                                        'endtime' => $value['endtime'],
                                                        'waimaiEndtime' => $value['waimaiEndtime'],
                                                        'endSaleTime' => $value['endSaleTime'],
                                                        'sum' => $cart[$value['id']]['sum']);
                $item['allPrice'] += $itemPrice;
                $item['allSum'] += $cart[$value['id']]['sum'];
            }
            $item['uid'] = $_SESSION['uid'];
        }
        return $item;
    }


    //删除购物车
    public function cartDel(){
        $cartKey = $_SESSION['uid'].'_cart';
        $id = $this->getUrlVar('id');        
        if($id){ //删除单个            
            $cart = DOO::cache('php')->get($cartKey);
            unset($cart[$id]);
            DOO::cache('php')->set($cartKey,$cart,$this->_expCartTime);
        }else{            
            $cart = DOO::cache('php')->flush($cartKey);
        }
        $cart = DOO::cache('php')->get($cartKey);

        //0则购物车没物品，回到首页，1则刷新本页
        if(empty($cart)){
            echo '0';            
        }else{
            echo '1';
        }
    }

    

    //提交订单
    public function submitOrder(){        
        $addRes = false;
        $list = $this->countPrice();

        if(empty($list)){
            header("Location:" . appurl('index') );exit;
        }
        // 验证数据
        $v = Doo::loadHelper('DooValidator',true);
        $success = true;
        $errors = array('Exception！');
        $rules = $this->_getFormRule(true);
        
        if($errors = $v->validate($list,$rules)) {
            $success = false;
        }
        //验证外卖起送份数
        $infoCache = DOO::cache('php')->get($this->_configKey);
        $info = json_decode($infoCache,true);
        $waimaiSum =  isset($info['waimaiSum']) ? intval($info['waimaiSum']) : 1;
        if( $list['allSum'] < $waimaiSum ){
            $this->alert('外卖起送份数为:'.$info['waimaiSum'].'份');exit;
        }
        //检查验证
        foreach ($list['data'] as $key => $value) {
            $this->checkCanSum($key,$value);
            // //验证剩余数量是否足够
            // $todayBookOrder = DBproxy::getProcedure('Manage')->setDimension(2)->getTodayOKBookSum($key,0);
            // $yestodayBookOrder = DBproxy::getProcedure('Manage')->setDimension(2)->getTodayOKBookSum($key,1);
            // // var_dump($yestodayBookOrder,$todayBookOrder);die;
            // $todayCanSum = $todayBookOrder[0]['booksum'];
            // $yestodayCanSum = $yestodayBookOrder[0]['booksum']+$yestodayBookOrder[0]['peiSongSum'];
            // $hasBookSum = $yestodayCanSum - $todayCanSum;
            // if($value['sum'] > $hasBookSum){
            //     $this->alert($value['name'].'已经供应完毕，明天请尽早下单~');exit;
            // }
            
            //验证是否能预定
            if( !$this->checkBook($key) )
            {
                $this->alert($value['name'].'的售卖结束时间是：'.$value['endSaleTime']);exit;
            }
        }
        
        //#验证数据
        
        if( $success ){
            //gen_orderid
            $orderid = DBproxy::getProcedure('Manage')->setDimension(2)->gen_orderid($_SESSION['uid']);
            $list['orderid'] = $orderid;
            if(isset($_SESSION['waimai'])){
                $iswaimai = $_SESSION['waimai'];
                unset($_SESSION['waimai']);
            }else{
                $iswaimai = 0;
            }
            $list['waimai'] = $iswaimai;
            $addRes = DBproxy::getProcedure('Manage')->setDimension(2)->addOrder($list);
            if( $addRes == true ){
                $cartKey = $_SESSION['uid'].'_cart';
                $cart = DOO::cache('php')->flush($cartKey); 
            }else{
                $this->alert('生成订单失败');
            }
        }
        $dbres = DBproxy::getProcedure('Manage')->setDimension(2)->getMsg();
        $res = array_keys($dbres);
        
        // $res[0] = 4;//4是成功
        if($res[0] == 4){            
            // Doo::loadControllerAt('PayController','default');            
            // //调用支付接口
            // $pay = new PayController();
            // $pay->wxJsPay($list);

            $_SESSION['orderinfo'] = $list;
            $cartKey = $_SESSION['uid'].'_cart';
            $cart = DOO::cache('php')->flush($cartKey); 
            header("Location:".appurl('wxpay'));
        }else
        {
            $this->alert($dbres,'success',true,appurl('getMeOrder'),2);
        }
    }

    //订单状态查询
    //如果是在申请退款则不走微信状态
    private function queryOrder($out_trade_no='',$inStatus=0){        
        if($inStatus == 4){
            return $status = '已经申请退款';
        }
        
        if(isset($out_trade_no) && $out_trade_no != ""){            
            $input = new WxPayOrderQuery();
            $input->SetOut_trade_no($out_trade_no);
            $info = WxPayApi::orderQuery($input);            
            if( isset($info['err_code']) && $info['err_code'] == 'ORDERNOTEXIST'){
                $trade_state = 'NOTPAY';
            }else{
                $trade_state = $info['trade_state'];
            }
            // $status = orderState($trade_state);
        }
        return $trade_state;
    }

    //回调订单状态查询
    private function _callbackOrder($callBackOrderid,$jump = false){
        if( trim($callBackOrderid) != '' ){
            $orderStatus = $this->queryOrder($callBackOrderid);            
            $order = DBproxy::getProcedure('Manage')->setDimension(2)->orderUp($orderStatus,0,$callBackOrderid);
            //支付完毕打印订单
            if($orderStatus == 'SUCCESS'){
                $this->orderPrintPaySUCCESS($callBackOrderid);    
            }
            
            if($jump == true){
                header("Location:" . appurl('getMeOrder') );exit;    
            }
        }
    }

    //如果是店主则显示外卖订单
    private function shopMaster(){

        if(trim($_SESSION['userInfo']['shopname']) != '' && trim($_SESSION['userInfo']['cityid']) != '' && trim($_SESSION['userInfo']['cityid']) != '0'){
            return true;
        }else{
            return false;
        }
    }

    //获取自己的订单信息
    //带订单id,跳转时候做支付状态查询,并更新db
    public function getMeOrder(){
        $callBackOrderid = $this->getUrlVar('orderid',NULL);
        $this->_callbackOrder($callBackOrderid,true);
        
        //列表显示
        $data = array('list' => array(),'pageTitle' => '订单列表','total'=>'','qr'=>'');
        $params['pagesize'] = $this->getCurPage().',10';
        
        if($this->shopMaster() == false){
            $params['uid'] = $_SESSION['uid'];
        }else{
            $params['waimai'] = '1';
            $data['pageTitle'] = $_SESSION['userInfo']['shopname'].'外卖店长查看列表';
            $params['city'] = $_SESSION['userInfo']['cityid'];
            $params['shopname'] = $_SESSION['userInfo']['shopname'];
        }
        $state = $this->getUrlVar('status','SUCCESS');
        $state2 = $this->getUrlVar('status2',NUll);
        $data['orderType'] = $state2;
        $params['status'] = $state;
        $params['status2'] = $state2;
        
        $order = DBproxy::getProcedure('Manage')->setDimension(2)->getOrder($params);
        if(!empty($order['data'])){
            $data['list'] = $order['data'];
            $data['total'] = $this->pager($order['total']);            
            foreach ($data['list'] as $key => $value) {
                $orderStatus = $this->queryOrder($key,$value[0]['status']);
                $data['list'][$key][0]['status'] = Doo::conf()->wxStatus[$orderStatus];
                if($value[0]['status'] == '0' ){//未取到回调的
                   $value[0]['status'] = 'NOTPAY';
                }
                $data['list'][$key][0]['status'] = $value[0]['status'];
                //更新订单，避免回调失效
                if($value[0]['status'] != $data['list'][$key][0]['status']){
                    $this->_callbackOrder($key);
                }
            }            
            $newOrder = current($data['list']);
            $orderid = $newOrder[0]['orderid'];
            if( $newOrder[0]['status'] == 'SUCCESS'){
                $data['qr'] = $this->qrImg($orderid);
            }else{
                $data['qr'] = '';
            }
        }        
        
        $this->layoutRender('/get',$data);
    }

    //获取自己的订单信息
    public function getMeOrderById(){
        $params['orderid'] = $this->getUrlVar('orderid',NULL);
        if(!isset($params['orderid']) || trim($params['orderid']) =='' ){
            $this->alert('data null0');exit();
        }
        $params['pagesize'] = $this->getCurPage().','.Doo::conf()->pagesize;
        
        if($this->shopMaster() == false){
            $params['uid'] = $_SESSION['uid'];
        }else{
            $data['pageTitle'] = '店长查看外卖列表';
            $params['waimai'] = '1';
            $params['city'] = $_SESSION['cityid'];
            $params['shopname'] = $_SESSION['shopname'];
        }
        $order = DBproxy::getProcedure('Manage')->setDimension(2)->getOrder($params);
        
        //此订单是本人的验证
        if($order['total'] < '1'){
            $this->alert('data null2');exit();
        }
        $data = array('list' => $order['data'],'pageTitle' => '订单');
        $newOrder = current($order['data']);
        $orderid = $newOrder[0]['orderid'];
        
        $data['total'] = '';
        
        $orderStatus = $this->queryOrder($orderid,$order['data'][$orderid][0]['status']);
        // $orderStatus = $order['data'][$orderid][0]['status'];
        $data['list'][$orderid][0]['status'] = Doo::conf()->wxStatus[$orderStatus];
        //支付成功，并且没打印过的才可以显示二维码
        if( 'SUCCESS' == $data['list'][$orderid][0]['status'] ){
            $data['qr'] = $this->qrImg($orderid);
        }else{      
            $data['qr'] = '';
        }
        $_SESSION['orderinfo'] = $data['list'][$orderid][0];
        $_SESSION['orderList'] = $data['list'][$orderid];
        
        $this->layoutRender('/get',$data);
    }


    public function join(){
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width; "><!--去除放大页面-->
<meta http-equiv="Pragma" content="no-cache"> <!--禁止浏览器缓存-->
<meta name=”viewport” content=”target-densitydpi=device-dpi” />
<meta name=”viewport” content=”target-densitydpi=high-dpi” />
</head>

<body>
<div class="content" style=" background-color:#ffffff;style="width:90%"">
        <p style="font-size:16px; margin-bottom:5px; font-weight:bold;width:250px">
            <a href="'.appurl('join').'" style="width: 100px;float: left;">加盟申请</a>
            <a href="'.appurl('join2').'" style="width: 100px;float: left;">合作申请</a>
        </p>

 <iframe width="300" height="600" src="http://www.chaojibiaoge.com/index.php/U/url/TCvBmOYq" style="float: left;"></iframe></div>';
die;

$data = array('pageTitle' => '加盟申请');
        $this->layoutRender('/join',$data);
    }
    public function join2(){
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width; "><!--去除放大页面-->
<meta http-equiv="Pragma" content="no-cache"> <!--禁止浏览器缓存-->

</head>

<body>
<div class="content" style=" background-color:#ffffff;">
        <p style="font-size:16px; margin-bottom:5px; font-weight:bold;width:250px">
            <a href="'.appurl('join').'" style="width: 100px;float: left;">加盟申请</a>
            <a href="'.appurl('join2').'" style="width: 100px;float: left;">合作申请</a>
        </p>

 <iframe width="300" height="650" src="http://www.chaojibiaoge.com/index.php/U/url/oiPZtWgh" style="float: left;"></iframe>';
die;
        $data = array('pageTitle' => '合作申请');
        $this->layoutRender('/join2',$data);
    }

    public function cancelRole(){
        $data['pageTitle'] = '退订规则';
        $this->layoutRender('/cancelRole',$data);
    }

    //取消订单
    public function orderCancel(){        
        
        $params['orderid'] = $this->getUrlVar('orderid',NULL);
        if(!isset($params['orderid']) || intval($params['orderid']) < 1){
            $this->alert('data null');exit();
        }
        $params['pagesize'] = $this->getCurPage().','.Doo::conf()->pagesize;
        $params['uid'] = $_SESSION['uid'];
        $order = DBproxy::getProcedure('Manage')->setDimension(2)->getOrder($params);
        //此订单是本人的验证
        if($order['total'] != '1'){
            $this->alert('data null');exit();
        }

        $status = 4; //申请 退款
        $id = $order['data'][$params['orderid']]['0']['oid'];
        $upRes = DBproxy::getProcedure('Manage')->setDimension(2)->orderUp($status,$id);
        
        if($upRes['status'] != 0){
            $this->alert('申请失败，请稍后重试！');exit();   
        }else{
            $this->alert('申请退款成功！');exit();
        }        
    } 
    
    //短信验证
    private function logsms($arr,$result){
        $file = '/data/wstreet.cn/web/weixin/ding/ding/protected/module/default/cache/sms-check'.date("Y-m-d").'.php';
        file_put_contents($file,var_export($arr,true)."\tresult:".var_export($result,true)."\r\n",FILE_APPEND );
    }

    //验证发送的验证码
    public function SMSCheckNO(){        
        $code_key =  $_SESSION['uid'].':sms_code' ;
        $info['phone'] = $this->post('phone');
        $info['userid'] = $_SESSION['uid'];

        //如果验证码失效
        if(!$this->cacheKV()->exists($code_key) ){
            $err = '验证码已经失效';
            $this->logsms($info,$err);
            echo $err;
            exit;
        }
        $checkCode = $this->cacheKV()->get($code_key);
        if(trim($this->post('phoneCheckNo'))!=''
         && strtoupper($this->post('phoneCheckNo')) == $checkCode){            
            
            $saveInfo = DBproxy::getProcedure('Manage')->setDimension(2)->saveUserInfo($info);
            if($saveInfo['status'] != 0){
                $err = '保存异常!';                
                echo $err;
            }else{
                $err = 'ok';                
                echo $err;                
            }
            $this->cacheKV()->del($code_key);            
        }else{            
            $err = '验证码不对';            
            echo $err;
        }
        $this->logsms($info,$err);
        exit;
    }

    //发短信验证码
    public function sendSMSCheckNO(){
        $phone = $this->post('phone');
        $code = randCode();
        $redis_key = $_SESSION['uid'].':sms' ;
        $code_key =  $_SESSION['uid'].':sms_code' ;
        if(strlen($code) != 4)
        {
            echo '短信发送异常';exit();
        }

        if(mobileCheck($phone) )
        {
            echo '手机号码不对';exit();
        }
        
        //限制发送时间
        if($this->cacheKV()->exists($redis_key))
        {
            echo '请稍后再发送';exit();
        }
        $this->cacheKV()->setex($redis_key ,$this->kvExTime() ,1);//发送成功标识，用来重发
        Doo::loadClassAt('SmsApi','default');
        $clapi  = new SmsApi();
        $result = $clapi->sendSMS($phone, '您好，您的验证码是 '.$code.' <微微乐>','true');
        $result = $clapi->execResult($result);
        if($result[1] == 0 ){
            echo '发送成功';
            $this->cacheKV()->setex($code_key ,$this->kvExTime()*10 ,$code); //10分钟内有效
        }else{

            echo "发送失败{$result[1]}";
        }
        exit;
    }

    //验证发送的验证码
    public function ___SMSCheckNO(){        
        $code_key =  $_SESSION['uid'].':sms_code' ;        
        //如果验证码失效
        if(!$this->cacheKV()->exists($code_key) ){
            echo '验证码已经失效';exit;
        }
        $checkCode = $this->cacheKV()->get($code_key);
        if(trim($this->post('phoneCheckNo'))!=''
         && strtoupper($this->post('phoneCheckNo')) == $checkCode){            
            $info['phone'] = $this->post('phone');
            $info['userid'] = $_SESSION['uid'];
            $saveInfo = DBproxy::getProcedure('Manage')->setDimension(2)->saveUserInfo($info);
            if($saveInfo['status'] != 0){
                echo '保存异常!';
            }else{
                echo 'ok';
            }
            $this->cacheKV()->del($code_key);            
        }else{
            echo '验证码不对';
        }
        exit;
    }
    /**
     * 取得表单验证规则
     * @param  boolean $isInsert 1 是插入表单规则,0 是修改规则
     * @return array
     */
    protected function _getFormRule($isInsert = true) {
        $rule = array(
                'uid' => array(
                            array('required', '没有用户信息'),
                    ),
                'allPrice' =>  array(
                            array('required',"价格异常"),                            
                    ),                
            );        
        return $rule;
    }

    public function orderPrint2(){
        // $params['orderid'] = $this->getUrlVar('orderid',NULL);
        $params['orderid'] = '12413237022015092911210327222';
        $params['pagesize'] = $this->getCurPage().','.Doo::conf()->pagesize;
        //验证此订单是本人的，店长打印则跳开
        if($this->shopMaster() == false){
            // $params['uid'] = $_SESSION['uid'];
        }

        if(!isset($params['orderid']) || trim($params['orderid']) == ''){
            $this->alert('data null2');exit();
        }

        $order = DBproxy::getProcedure('Manage')->setDimension(2)->getOrder($params);
        
        if($order['total'] < '1'){
            $this->alert('data null2');exit();
        }
        //打印处理
        if($order['data'][$params['orderid']]['0']['printStatus'] > 0  && $this->shopMaster() == false ){
            // $this->alert('已经打印过了');exit();
        }
        
        //订单的状态是否能打印,在微信接口验证
        // $orderStatus = $this->queryOrder($params['orderid'],$order['data'][$params['orderid']][0]['status']);        
        // if($orderStatus != 'SUCCESS'){
        //     $this->alert('未支付成功,order status err ');exit();
        // }
        //D($order['data'][$params['orderid']]);
        foreach ($order['data'][$params['orderid']] as $key => $value) {
            $title[] = $value['title'].'   '.$value['countPrice'].'   ('.$value['sum'].')';
        }

        $msgNo = time()+rand(1,9999);
        /*
         自由格式的打印内容
        */
if($order['data'][$params['orderid']]['0']['waimai'] == '1' ){
    $waimai = '外卖单 ';
    $sb = '  ******';
    $addr = "\r\n".'送货地址：'.$order['data'][$params['orderid']]['0']['addr']."\r\n";
}else{
    $waimai = '堂食单';
    $addr = '';
    $sb = '';
}
        $freeMessage = array(
            'memberCode'=>MEMBER_CODE, 
            'msgDetail'=>
'
微微乐餐饮欢迎您订购
'.$waimai.': 序号：'.$order['data'][$params['orderid']]['0']['oid'].$sb.'

条目   单价(元)   数量
-----------------
'.implode("\r\n", $title).'

-----------------
合计：'.$order['data'][$params['orderid']]['0']['allPrice'].'元 

店名：'.$order['data'][$params['orderid']]['0']['shopname'].$addr.'
联系电话：'.$order['data'][$params['orderid']]['0']['phone'].'
订购时间：'.date('Y-m-d H:i:s',$order['data'][$params['orderid']]['0']['addtime']).'
',
                                'deviceNo'=>DEVICE_NO, 
                                'msgNo'=>$msgNo,
                            );
        $order = DBproxy::getProcedure('Manage')->setDimension(2)->upOrderPrint($order['data'][$params['orderid']]['0']['oid']);
        
        $printStatus =  $this->sendFreeMessage($freeMessage);

        if( $printStatus == 0 )
        {
            echo '打印请求/任务中队列中，等待打印';
            $order = DBproxy::getProcedure('Manage')->setDimension(2)->upOrderPrint($order['data'][$params['orderid']]['0']['oid']);
        }elseif( $printStatus == 1 )
        {
            echo '打印任务已完成/请求数据已打印';
            $order = DBproxy::getProcedure('Manage')->setDimension(2)->upOrderPrint($order['data'][$params['orderid']]['0']['oid']);
        }elseif( $printStatus == 2 )
        {
            echo '打印任务/请求失败';
        }
        else
        {
            echo $printStatus;
        }

        return $msgNo;

    }

    public function orderPrint(){
        // $params['orderid'] = '12413237022015092911210327222';
        $params['orderid'] = $this->getUrlVar('orderid',NULL);
        $params['pagesize'] = $this->getCurPage().','.Doo::conf()->pagesize;
        //验证此订单是本人的，店长打印则跳开
        if($this->shopMaster() == false){
            $params['uid'] = $_SESSION['uid'];
        }

        if(!isset($params['orderid']) || trim($params['orderid']) == ''){
            $this->alert('data null2');exit();
        }

        $order = DBproxy::getProcedure('Manage')->setDimension(2)->getOrder($params);
        
        if($order['total'] < '1'){
            $this->alert('data null2');exit();
        }
        //打印处理
        if($order['data'][$params['orderid']]['0']['printStatus'] > 0  && $this->shopMaster() == false ){
            $this->alert('已经打印过了');exit();
        }
        
        //订单的状态是否能打印,在微信接口验证
        $orderStatus = $this->queryOrder($params['orderid'],$order['data'][$params['orderid']][0]['status']);        
        if($orderStatus != 'SUCCESS'){
            $this->alert('未支付成功,order status err ');exit();
        }
        //D($order['data'][$params['orderid']]);
        foreach ($order['data'][$params['orderid']] as $key => $value) {
            $title[] = $value['title'].'   '.$value['countPrice'].'   ('.$value['sum'].')';
        }

        $msgNo = time()+rand(1,9999);
        /*
         自由格式的打印内容
        */
if($order['data'][$params['orderid']]['0']['waimai'] == '1' ){
    $waimai = '外卖单 ';
    $sb = '  ******';
    $addr = "\r\n".'送货地址：'.$order['data'][$params['orderid']]['0']['addr']."\r\n";
}else{
    $waimai = '堂食单';
    $addr = '';
    $sb = '';
}
        $freeMessage = array(
            'memberCode'=>MEMBER_CODE, 
            'msgDetail'=>
'
微微乐餐饮欢迎您订购
'.$waimai.': 序号：'.$order['data'][$params['orderid']]['0']['oid'].$sb.'

条目   单价(元)   数量
-----------------
'.implode("\r\n", $title).'

-----------------
合计：'.$order['data'][$params['orderid']]['0']['allPrice'].'元 

店名：'.$order['data'][$params['orderid']]['0']['shopname'].$addr.'
联系电话：'.$order['data'][$params['orderid']]['0']['phone'].'
订购时间：'.date('Y-m-d H:i:s',$order['data'][$params['orderid']]['0']['addtime']).'
',
                                'deviceNo'=>DEVICE_NO, 
                                'msgNo'=>$msgNo,
                            );
        $order = DBproxy::getProcedure('Manage')->setDimension(2)->upOrderPrint($order['data'][$params['orderid']]['0']['oid']);
        
        $printStatus =  $this->sendFreeMessage($freeMessage);

        if( $printStatus == 0 )
        {
            echo '打印请求/任务中队列中，等待打印';
            $order = DBproxy::getProcedure('Manage')->setDimension(2)->upOrderPrint($order['data'][$params['orderid']]['0']['oid']);
        }elseif( $printStatus == 1 )
        {
            echo '打印任务已完成/请求数据已打印';
            $order = DBproxy::getProcedure('Manage')->setDimension(2)->upOrderPrint($order['data'][$params['orderid']]['0']['oid']);
        }elseif( $printStatus == 2 )
        {
            echo '打印任务/请求失败';
        }
        else
        {
            echo $printStatus;
        }

        return $msgNo;
    }

    private function orderPrintPaySUCCESS($orderid = 0){
        // $params['orderid'] = '12413237022015092911210327222';
        $params['orderid'] = $orderid;
        $params['pagesize'] = $this->getCurPage().','.Doo::conf()->pagesize;
        //验证此订单是本人的，店长打印则跳开
        if($this->shopMaster() == false){
            $params['uid'] = $_SESSION['uid'];
        }

        if(!isset($params['orderid']) || trim($params['orderid']) == ''){
            $this->alert('data null2');exit();
        }

        $order = DBproxy::getProcedure('Manage')->setDimension(2)->getOrder($params);
        
        if($order['total'] < '1'){
            $this->alert('data null2');exit();
        }
        //打印处理
        if($order['data'][$params['orderid']]['0']['printStatus'] > 0  && $this->shopMaster() == false ){
            $this->alert('已经打印过了');exit();
        }
        
        //订单的状态是否能打印,在微信接口验证
        $orderStatus = $this->queryOrder($params['orderid'],$order['data'][$params['orderid']][0]['status']);        
        if($orderStatus != 'SUCCESS'){
            $this->alert('未支付成功,order status err ');exit();
        }
        //D($order['data'][$params['orderid']]);
        foreach ($order['data'][$params['orderid']] as $key => $value) {
            $title[] = $value['title'].'   '.$value['wxprice'].'   ('.$value['sum'].')';
        }

        $msgNo = time()+rand(1,9999);
        /*
         自由格式的打印内容
        */
if($order['data'][$params['orderid']]['0']['waimai'] == '1' ){
    $waimai = '外卖单 ';
    $sb = '  ******';
    $addr = "\r\n".'送货地址：'.$order['data'][$params['orderid']]['0']['addr']."\r\n";
}else{
    $waimai = '堂食单';
    $addr = '';
    $sb = '';
}
        $freeMessage = array(
            'memberCode'=>MEMBER_CODE, 
            'msgDetail'=>
'
微微乐餐饮欢迎您订购
'.$waimai.': 序号：'.$order['data'][$params['orderid']]['0']['oid'].$sb.'

条目   单价(元)   数量
-----------------
'.implode("\r\n", $title).'

-----------------
合计：'.$order['data'][$params['orderid']]['0']['allPrice'].'元 

店名：'.$order['data'][$params['orderid']]['0']['shopname'].$addr.'
联系电话：'.$order['data'][$params['orderid']]['0']['phone'].'
订购时间：'.date('Y-m-d H:i:s',$order['data'][$params['orderid']]['0']['addtime']).'
',
                                'deviceNo'=>DEVICE_NO, 
                                'msgNo'=>$msgNo,
                            );
        $order = DBproxy::getProcedure('Manage')->setDimension(2)->upOrderPrint($order['data'][$params['orderid']]['0']['oid']);
        
        $printStatus =  $this->sendFreeMessage($freeMessage);
        Doo::logger()->info('time:'.date("Y-m-d H:i:s",time())."\tip:".getIP()."\tprintStatus:".$printStatus,'print');
        if( $printStatus == 0 )
        {
            echo '打印请求/任务中队列中，等待打印';
            $order = DBproxy::getProcedure('Manage')->setDimension(2)->upOrderPrint($order['data'][$params['orderid']]['0']['oid']);
        }elseif( $printStatus == 1 )
        {
            echo '打印任务已完成/请求数据已打印';
            $order = DBproxy::getProcedure('Manage')->setDimension(2)->upOrderPrint($order['data'][$params['orderid']]['0']['oid']);
        }elseif( $printStatus == 2 )
        {
            echo '打印任务/请求失败';
        }
        else
        {
            echo $printStatus;
        }        
        return $msgNo;
    }
    private function sendFreeMessage($msg) {
        $msg['reqTime'] = number_format(1000*time(), 0, '', '');
        $content = $msg['memberCode'].$msg['msgDetail'].$msg['deviceNo'].$msg['msgNo'].$msg['reqTime'].FEYIN_KEY;
        $msg['securityCode'] = md5($content);
        $msg['mode']=2;

        return $this->sendMessage($msg);
    }
    private function sendFormatedMessage($msgInfo) {
        // $str = implode('', $msgInfo);
        $msgInfo['reqTime'] = number_format(1000*time(), 0, '', '');
        $content = $msgInfo['memberCode'].$msgInfo['customerName'].$msgInfo['customerPhone'].$msgInfo['customerAddress'].$msgInfo['customerMemo'].$msgInfo['msgDetail'].$msgInfo['deviceNo'].$msgInfo['msgNo'].$msgInfo['reqTime'].FEYIN_KEY;

        $msgInfo['securityCode'] = md5($content);
        $msgInfo['mode']=1;
        
        return $this->sendMessage($msgInfo);
    }
    private function sendMessage($msgInfo) {
        Doo::loadClassAt('HttpClient','default');
        $client = new HttpClient(FEYIN_HOST,FEYIN_PORT);        
        if(!$client->post('/api/sendMsg',$msgInfo)){ //提交失败
            return 'faild';
        }
        else{
            return $client->getContent();
        }
    }

    private function qrImg($orderid){
        include(Doo::conf()->SITE_PATH. 'protected/module/default/class/phpqrcode/qrlib.php'); 
        $codeContents = $orderid; 
        $tempDir = Doo::conf()->QR_FILE_PATH;
        $fileName = $orderid.'.jpg'; 
        $outerFrame = 0; 
        $pixelPerPoint = 5; 
        $jpegQuality = 95; 
         
        // generating frame 
        $frame = QRcode::text($codeContents, false, QR_ECLEVEL_M); 
        // rendering frame with GD2 (that should be function by real impl.!!!) 
        $h = count($frame); 
        $w = strlen($frame[0]); 
         
        $imgW = $w + 3*$outerFrame; 
        $imgH = $h + 3*$outerFrame; 
         
        $base_image = imagecreate($imgW, $imgH); 
         
        $col[0] = imagecolorallocate($base_image,255,255,255); // BG, white  
        $col[1] = imagecolorallocate($base_image,0,0,0);     // FG, black blue 

        imagefill($base_image, 0, 0, $col[0]); 

        for($y=0; $y<$h; $y++) { 
            for($x=0; $x<$w; $x++) { 
                if ($frame[$y][$x] == '1') { 
                    imagesetpixel($base_image,$x+$outerFrame,$y+$outerFrame,$col[1]);  
                } 
            } 
        } 
         
        // saving to file 
        $target_image = imagecreate($imgW * $pixelPerPoint, $imgH * $pixelPerPoint); 
        imagecopyresized( 
            $target_image,  
            $base_image,  
            0, 0, 0, 0,  
            $imgW * $pixelPerPoint, $imgH * $pixelPerPoint, $imgW, $imgH 
        ); 
        imagedestroy($base_image); 
        imagejpeg($target_image, $tempDir.$fileName, $jpegQuality); 
        imagedestroy($target_image); 

        // displaying 
        $qrImg = '<img src="'.Doo::conf()->QR_URL_PATH.$fileName.'" />';
        return $qrImg;
    }
    //过期时间为第二天3点
    private function _expTime(){
        $this->_expCartTime = strtotime('+1 day',strtotime('3:00')) - time();
    }
}


