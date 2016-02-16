<?php
$payClassPath = Doo::conf()->SITE_PATH."protected/module/default/class/pay/";
require_once $payClassPath."WxpayAPI/lib/WxPay.Config.php";
/** 管理后台的db操作类
*@author xinkq
*@version 2015-08-11 10:49
**/
abstract class ManageBase extends DBproxy {

    protected $_dbNameKey = 'manage'; 

    protected $_created = '2015-08-11 17:46:53'; 

    public static $msg = array();

    public function __CALL($a,$b) {
        return array();
    }

    /**
    * 处理分页转成数据
    *@param str string  1,20
    *@return str limit 0,20   20,20
    */
    private function procLimit($str)
    {
        if(strpos($str,',') === false){
            $limit = '  '; //limit 1
        }else{ 
            $limitArr = explode(',', $str);
            $page = isset($limitArr[0]) ? $limitArr[0] : 0;
            $limit = isset($limitArr[1]) ? $limitArr[1] : 20;
            $offset = $page <= 1 ? 0 : ($page-1) * $limit;
            $limit = ' limit '.intval($offset).','.intval($limit);
        }
        return $limit;
    }

    public function spSysRoleS($id){
        $where = '';
        if($id!=-1){
            $where = "WHERE `id` = '".intval($id)."' limit 1";
        }
        $sql = "SELECT * FROM `role` ".$where;
        $res = self::execute($sql,'s');
        return $res['data']; 
    }
    
    /**
    *@param mixed IN i_flag CHAR(1)           -- i=增加，u=修改
    *@param mixed IN i_id TINYINT UNSIGNED    -- 标识（插入时传入0，修改时传入对应标识）
    *@param mixed IN i_name VARCHAR(10)       -- 名称    
    */
    public function spSysRoleIu($iFlag,$iId,$iName) {
        $sql = '';
        if($iFlag == 'i')
        {
            $param = array(
                'name' => $iName,
            );          
            $key = '`'.implode(array_keys($param),'`,`').'`'; //转key为表字段
            $val = "'".implode($param,"','")."'"; //转值为插入的内容

            $sql = "INSERT INTO `role` (`id`,".$key.") 
            VALUES (NULL, ".$val.")";

        }elseif($iFlag == 'u' && $iId!=0)
        {
            $sql = "UPDATE `role` SET `name` = '" . $iName . "' WHERE `id` = '" . intval($iId) . "'";
        }

        return  self::execute($sql,$iFlag);
    }

    /**
    *@param mixed IN i_role_id TINYINT UNSIGNED        -- 角色标识
    *@param mixed IN i_perm TEXT                       -- 权限字串（x+菜单标识+操作权限）
    *@param mixed 其中x为固定占位符 例如：(x
    *@param mixed menu_id1
    *@param mixed perm_id1)
    *@param mixed (x
    *@param mixed menu_id2
    *@param mixed perm_id2)
    */
    public function spSysRolePermIu($iRoleId,$iPerm) {        
        $sql = "UPDATE `role` SET `perm_id` = '" . $iPerm . "'  WHERE `id` = '" . intval($iRoleId) . "'";
        return  self::execute($sql,'u');
    }

    /**
    *@param mixed IN i_role_id TINYINT UNSIGNED      -- 角色标识    
    */
    public function spSysRolePermS($iRoleId) {
        $data = array();
        $where = "WHERE `id` = '".intval($iRoleId)."' limit 1";
        $sql = "SELECT * FROM `role` ".$where;
        $res = self::execute($sql,'s');
        if($res['status'] == 0){
            $data = $res['data'];
        }
        return $data;
    }

    /**
    * 角色删除
    */
    public function spSysRoleD($iRoleId) { 
        $sql = "DELETE FROM `role` WHERE `role`.`id` = ".intval($iRoleId);
        return $res = self::execute($sql,'d');
    }


    //回滚时候删除用户数据 #TODO未完
    private function backDelData($uid = 0,$fromUid = 0,$fromSum = 0,$transferid=0){
        //删除用户基础表，扩展表，角色表
        $this->delUser($uid,3);
        
        //转出者的筹码回滚
        $inSql  = "update backuser set `CasinoChips` =`CasinoChips`+".floatval($fromSum)." where uid=".intval($fromUid);
        $sqlT = "DELETE FROM `transfer_out_in` WHERE `transfer_out_in`.`id` = ".intval($transferid);        
        $res = self::execute($sqlT,'d');
    }

    /**
    * 添加用户
    *@param str iFlag 插入i,更新u
    *@param array data 数据数组
    *@param int id 主键id
    *@return array i 会返回id,status: true false        
    */
    public function spSysUserIU($iFlag,$data,$id = 0 ){        
        $sql = '';
        if($iFlag == 'i'){
            $params = array(
                'name' => $data['name'],
                'uname' => $data['uname'],
                'passwd' => $data['password'],
                'mobile' => $data['mobile'],
                'CasinoChips' => floatval($data['CasinoChips']),
                'is_check' => $data['is_check'],
                'email' => $data['email'],
                'is_locked' => 0,
                'opName' => User::getUserInfoByAccount(),
                'opTime' => time(),
            );

            $key = '`'.implode(array_keys($params),'`,`').'`'; //转key为表字段
            $val = "'".implode($params,"','")."'"; //转值为插入的内容

            $sql = "INSERT INTO `backuser` (".$key.")  VALUES ( ".$val.")";
        }elseif($iFlag == 'u' && $id != 0 ){
            $sql = "UPDATE `backuser` SET 
                    `name` = '" . trim($data['name']) . "' ,
                    `mobile` = '" . trim($data['mobile']) . "' ,                    
                    `is_locked` = '" . intval($data['is_locked']) . "' ,
                    `is_check` = '" . intval($data['is_check']) . "' ,
                    `email` = '" . trim($data['email']) . "' ,
                    `opName` = '" . trim(User::getUserInfoByAccount()) . "' ,
                    `opTime` = '" . time() . "' 
                    WHERE `uid` = '" . intval($id) . "'";
        }

        return  self::execute($sql,$iFlag);
    }

    /**
    * 处理用户角色 
    *@param str iFlag 插入i,更新u
    *@param array param 数据数组
    *@param int id 主键id
    *@return array i 会返回id,status: true false        
    */
    public function spSysUserRoleIu($iFlag,$data){
        $params = array(
                'uid' => $data['uid'],
                'rolelist' => $data['rolelist'],
            );
        $sql = '';
        if($iFlag == 'i')
        {   
            $key = '`'.implode(array_keys($params),'`,`').'`'; //转key为表字段
            $val = "'".implode($params,"','")."'"; //转值为插入的内容

            $sql = "INSERT INTO `backuser_role` (".$key.") 
                VALUES ( ".$val.")";
                
        }elseif($iFlag == 'u' && intval($data['uid']) > 0)
        {
            $sql = "UPDATE `backuser_role` SET 
                    `rolelist` = '" . trim($data['rolelist']) . "' 
                    WHERE `uid` = '" . intval($data['uid']) . "'";

        }
        return  self::execute($sql,$iFlag);
    }

    /**
    *  获取全部管理员，也可以根据用户id获取单个管理员
    *@param param
    * $param = array(
    *         'uid' => -1
    *         'uname' => $uname,
    *         'pagesize' => $page.','.$psize,
    *         'user_type' => $user_type 用户类型现在没用上
    *      );
    */
    public function spSysUserS($param) {

        $ret['list'] = array();
        $ret['total'] = 0;
        $param['pagesize'] = isset($param['pagesize']) ? $param['pagesize'] : '1';
        
        $order = ' ORDER  BY u.uid DESC';
        $limit = $this->procLimit($param['pagesize']);
        $where = " AND  u.uid=r.uid  AND r.rolelist ='(x,4)' ";
        //获取单个用户
        if(intval($param['uid']) >0 ){
            $where .= " AND  u.`uid` = '".intval($param['uid'])."' ";            
        }
        
        $sql = " SELECT  SQL_CALC_FOUND_ROWS u.`uid`,`name`,`uname`,`passwd`,`mobile`,`is_check`,`email`,`is_locked`,
                        `lastLoginIP`,`lastLoginTime`,`logonTimes`                        
                FROM `backuser` as u ,`backuser_role` as r
                WHERE 1  ".$where.$order.$limit;
        // echo $sql;die;
        $res = self::execute($sql,'s');
        
        $sqlC = "SELECT FOUND_ROWS() as row; ";
        $resC = self::execute($sqlC,'s');
        
        if($res['status']==0 && $resC['status']==0){
            $ret['list'] = $res['data'];
            $ret['total'] = $resC['data'][0]['row'];
        }
        return $ret;
    }


    /**
    * 根据用户名获取用户 --登录时
    *@param param
     * $data = array(
     *               'username' => 'value'
     *   );
    */
    public function spSysUserSSign($param) {
        $data = array();
        $sql = "SELECT `uid`,`name`,`uname`,`passwd`,`mobile`,`is_check`,`email`,`is_locked`,`lastLoginIP`,`lastLoginTime`,`logonTimes`
                FROM `backuser` where `uname`= '".trim($param['username'])."' limit 1";
        $res = self::execute($sql,'s');

        if( $res['status'] == 0  && isset($res['data'][0]) && !empty($res['data'][0])){
            //登录次数加1
            $addSql = "UPDATE `backuser` SET `logonTimes` =logonTimes+1 ,
                        `lastLoginIP`='".get_ip()."',
                        `lastLoginTime`='".time()."'
                        WHERE `uid` = ".$res['data'][0]['uid'];

                        
            self::execute($addSql,'u');            
            $data = $res['data'][0];
        }
        return $data;
    }

    /**
    * 根据用户id获取用户角色
    *@param param
    * $param = array(
    *       'uid' => $uid
    *       );
    */
    public function spSysUserRoleS($param) {
        $role = array();
        $sql = "SELECT `uid`,`rolelist`,`permlist` FROM `backuser_role` 
                where `uid`= '".intval($param['uid'])."' limit 1";
        $res = self::execute($sql,'s');

        if( $res['status'] == 0  && isset($res['data'][0]) && !empty($res['data'][0])){
            $data = $res['data'][0];
        }
        if(isset($data)){
            $roleStrArr = explode('(x,', $data['rolelist']);
            foreach ($roleStrArr as $key => $value) {                
                if(!empty($value)){
                    $roleArr = explode(')', $value);
                    $role[] = array('role_id' => $roleArr[0]);
                }                
            }
        }

        return $role;
    }
        
    
    /**
    * 写入登陆日志
    *@param str iFlag 插入i,更新u
    *@param array param 数据数组
    *@param int id 主键id
    *@return array i 会返回id,status: true false        
    */
    public function spSysUserUSign($data){        
        $params = array(
                'uid' => $data['uid'],
                'ip' => get_ip(),
                'time' => time(),
            );
                
        $key = '`'.implode(array_keys($params),'`,`').'`'; //转key为表字段
        $val = "'".implode($params,"','")."'"; //转值为插入的内容

        $sql = "INSERT INTO `loginlog` (".$key.") 
                VALUES ( ".$val.")";        

        return  self::execute($sql,'i');
    }


    /**
    * 根据用户名检测是否已经存在
    *@param str uname
    *@return bool 存在返回true
    */
    public function checkUnameExist($uname = ''){
        $sql = "SELECT count(`uid`) as c FROM `backuser` WHERE uname='".trim($uname)."'";
        $res = self::execute($sql,'s');

        if( $res['status'] == 0  && $res['data'][0]['c']==1 )
        {
            return true;
        }else{
            return false;
        }
    }

    
    /**
    * 获取分类
    *@return array 分类
    */
    public function getCity( $params = array() ){

        $data = array('status'=>1,'data'=>array());
        $sql = "SELECT gt.`id`,gt.`name` as cname, gt.`sort`, gt.`name` as name,gt.`parentid`,`status`,`child`,
                    (SELECT p.`name` FROM citylist as p WHERE p.`id`=gt.`parentid`) as pname 
                FROM citylist as gt  WHERE  status = 0 ";
        
        $res = self::execute($sql,'s');

        if($res['status'] == 0){
            foreach ($res['data'] as $key => $value) {
                $data['data'][$value['id']] = $value;
            }
            $data['status'] = $res['status'];
        }else{
            $data['status'] = $res['status'];
        }

        return $data;
    }

    /**
    * 分类        
    *@return id
    */
    public function cityIU($iFlag,$param,$id = 0){
       $params = array(
                'parentid' => intval($param['parentid']),                
                'name' => $param['name'],
                'sort' => intval($param['sort']),
                'child' => 0,
                'status' => intval($param['status']),
            );
        $sql = '';

        if($iFlag == 'i')
        {            
            //查找这父类的所有父类
            $pres = $this->getCityArrParentChild($params['parentid']);
            if($pres['status']==0 && !empty($pres['data']))
            {
                $arrparentids = $pres['data']['0']['arrparentid'].','.$params['parentid'];
                $params['arrparentid'] = $arrparentids;
            }elseif($params['parentid']==0){
                $params['arrparentid'] = 0;
            }

            $key = '`'.implode(array_keys($params),'`,`').'`'; //转key为表字段
            $val = "'".implode($params,"','")."'"; //转值为插入的内容
            
            $sql = "INSERT INTO `citylist` (".$key.") 
                VALUES ( ".$val.")";
            $res = self::execute($sql,$iFlag);

        }elseif($iFlag == 'u' && intval($id) > 0)
        {
            //更新分类的情况下，把当前分类的子类合并
            $sql = "UPDATE `citylist` 
                SET `parentid` = '".$params['parentid']."' ,
                `name` = '".$params['name']."' ,
                `sort` = '".$params['sort']."' ,
                `status` = '".$params['status']."'              
                WHERE `id` = '" . intval($id) . "'";
            
            $res = self::execute($sql,$iFlag);
            $res['id'] = $id;
        }
        
        //更新父类信息
        if($res['status']==0 ){            
            $uRes = $this->changeCity();
            if($uRes['status']!=0){
                $res['status'] = $uRes['status'];
            }
        }
        return  $res;
    }

    //获取所有父类和子类
    private function getCityArrParentChild($id = 0){
        //查找这父类的所有父类
        $psql = "SELECT arrparentid,arrchildid from citylist WHERE status = 0 AND  id in(".$id.")";
        return $pres = self::execute($psql,'s');
    }

    /**
    * 更新所有分类的，所有子类，父类，是否有子类
    *@return bool
    */
    public function changeCity(){
        Doo::loadClassAt('City','default');
        $name = City::cateToOption(0,false,'array');;
        
        foreach ($name as $key => $value) {
            $arrparent = trim($value['arrparentid']) == '' ? 0 : trim($value['arrparentid']);
            $upSql = "UPDATE `citylist` SET `child` = '".intval($value['child'])."' ,
                        `arrparentid`='".$arrparent."' ,
                        `arrchildid`='".trim($value['arrchildid'])."' 
                    WHERE `id` = '" . intval($value['id']) . "'";
            self::execute($upSql,'u');
        }
        return array('status'=>0);
    }


    /**
    * 根据分类id，把其及其下子类删除
    * @param int id 分类id
    *@return bool
    */
    public function delCity($id = 0){ 
        $res['status'] = 1;
        $sql = "SELECT arrchildid FROM citylist as t WHERE t.id = ".intval($id);
        $resArr = self::execute($sql,'s');

        if($resArr['status'] == 0 ){
            $rSql = "UPDATE citylist  SET `status` = 1 WHERE id in (".$resArr['data'][0]['arrchildid'].")";            
            $res = self::execute($rSql,'u');
        }        
        return $res;
    }


    /**
    * 获取全部的顶级分类
    *@return array 分类的父类
    */
    public function getTopCategory($params = array()){
        
        $data = array();
        $sql = "SELECT `id`,`name`,`parentid` FROM  type WHERE parentid=0 AND status=0";

        if( !empty( $params ) ){
            $where = '';
            foreach ($params as $key => $value) {
                $where .=  ' AND ' . $key . '=' . $value;
            }
        }        
        $sql .= $where;
        $res = self::execute($sql,'s');
        
        if( $res['status'] == 0  && !empty($res['data']) )
        {
            //把id合并入数据
            foreach ($res['data'] as $key => $value) {
                $data[$value['id']] = $value['name'];
            }
        }
        return $data;
    }


    /**
    * 获取分类
    *@return array 分类
    */
    public function getCategory( $params = array() ){

        $data = array('status'=>1,'data'=>array());
        $sql = "SELECT gt.`endSaleTime`,gt.`id`,gt.`name` as cname, gt.`sort`,CONCAT(gt.`name`,'-',( SELECT name FROM `citylist` WHERE id = gt.`city`)) as name,gt.`parentid`,`status`,`child`,
                    (SELECT p.`name` FROM type as p WHERE p.`id`=gt.`parentid`) as pname ,
                    city,opentimeDay,opentime,endtime,waimaiEndtime
                FROM type as gt  ";

        $where = ' WHERE gt.status!=2  ';
        if( isset($params['id'])  && intval($params['id']) > 0){
            $where .= " AND  gt.id=".intval($params['id']);
        }
        if( isset($params['city'])  && intval($params['city']) > 0){
            $where .= " AND  gt.city=".intval($params['city']);
        }

        $sql .= $where;
        
        $res = self::execute($sql,'s');

        if($res['status'] == 0){
            foreach ($res['data'] as $key => $value) {
                $data['data'][$value['id']] = $value;
            }
            $data['status'] = $res['status'];
        }else{
            $data['status'] = $res['status'];
        }

        return $data;
    }

    /**
    * 分类        
    *@return id
    */
    public function categoryIU($iFlag,$param,$id = 0){
       $params = array(
                'parentid' => intval($param['parentid']),                
                'name' => $param['name'],
                'sort' => intval($param['sort']),
                'child' => 0,
                'status' => intval($param['status']),
                'city' => intval($param['city']),
                'opentimeDay' => trim($param['opentimeDay']),
                'opentime' => trim($param['opentime']),
                'endtime' => trim($param['endtime']),
                'waimaiEndtime' => trim($param['waimaiEndtime']),
                'openSaleTime' => '0:00',
                'endSaleTime' => trim($param['endSaleTime']),

            );
        $sql = '';

        if($iFlag == 'i')
        {            
            //查找这父类的所有父类
            $pres = $this->getArrParentChild($params['parentid']);
            if($pres['status']==0 && !empty($pres['data']))
            {
                $arrparentids = $pres['data']['0']['arrparentid'].','.$params['parentid'];
                $params['arrparentid'] = $arrparentids;
            }elseif($params['parentid']==0){
                $params['arrparentid'] = 0;
            }

            $key = '`'.implode(array_keys($params),'`,`').'`'; //转key为表字段
            $val = "'".implode($params,"','")."'"; //转值为插入的内容
            
            $sql = "INSERT INTO `type` (".$key.") 
                VALUES ( ".$val.")";
            $res = self::execute($sql,$iFlag);

        }elseif($iFlag == 'u' && intval($id) > 0)
        {
            //更新分类的情况下，把当前分类的子类合并
            $sql = "UPDATE `type` 
                SET `parentid` = '".$params['parentid']."' ,
                `name` = '".$params['name']."' ,
                `sort` = '".$params['sort']."' ,
                `status` = '".$params['status']."' ,
                `opentimeDay` = '".$params['opentimeDay']."' ,
                `opentime` = '".$params['opentime']."' ,
                `endtime` = '".$params['endtime']."' ,
                `waimaiEndtime` = '".$params['waimaiEndtime']."' ,
                `endSaleTime` = '".$params['endSaleTime']."' ,
                `openSaleTime` = '".$params['openSaleTime']."' ,
                `city` = '".$params['city']."' 
                WHERE `id` = '" . intval($id) . "'";
            
            $res = self::execute($sql,$iFlag);
            $res['id'] = $id;
        }
        
        //更新父类信息
        if($res['status']==0 ){            
            $uRes = $this->changeCategory();
            if($uRes['status']!=0){
                $res['status'] = $uRes['status'];
            }
        }
        return  $res;
    }

    //获取所有父类和子类
    private function getArrParentChild($id = 0){
        //查找这父类的所有父类
        $psql = "SELECT arrparentid,arrchildid from type WHERE status = 0 AND  id in(".$id.")";
        return $pres = self::execute($psql,'s');
    }


    /**
    * 更新所有分类的，所有子类，父类，是否有子类
    *@return bool
    */
    public function changeCategory(){
        Doo::loadClassAt('Category','default');
        $name = Category::cateToOption(0,false,'array');;
        
        foreach ($name as $key => $value) {
            $arrparent = trim($value['arrparentid']) == '' ? 0 : trim($value['arrparentid']);
            $upSql = "UPDATE `type` SET `child` = '".intval($value['child'])."' ,
                        `arrparentid`='".$arrparent."' ,
                        `arrchildid`='".trim($value['arrchildid'])."' 
                    WHERE `id` = '" . intval($value['id']) . "'";
            self::execute($upSql,'u');
        }
        return array('status'=>0);
    }

    /**
    * 根据分类id，把其及其下子类删除
    * @param int id 分类id
    *@return bool
    */
    public function delCategory($id = 0){ 
        $res['status'] = 1;
        $sql = "SELECT arrchildid FROM type as t WHERE t.id = ".intval($id);
        $resArr = self::execute($sql,'s');

        if($resArr['status'] == 0 ){
            $rSql = "UPDATE type  SET `status` = 2 WHERE id in (".$resArr['data'][0]['arrchildid'].")";            
            $res = self::execute($rSql,'u');
        }        
        return $res;
    }


    //根据参数 可以传1,2,3这样的 id, 获取菜品列表
     public function getBookList( $params ){
        $sql = 'SELECT g.*,t.`waimaiEndtime`,t.`opentimeDay`,t.`opentime`,t.`endtime`,t.`name` as tname,t.`endSaleTime` FROM `booklist` as g,`type` as t WHERE g.id in( ' . trim( $params['id'] ) .' ) AND g.categoryid=t.id AND g.status = 0';   

        $res = self::execute($sql,'s');
        if( $res['status'] == 0  && !empty($res['data']) ){
            return $res['data'];
        } else{
            return array();
        }

     }

    /**
    * 获取
    *@param str $page '1,20'
    *@param array param['id']
    *@return array
    */
    public function getBook($param = array(),$page='1,20'){
        $data = array('data'=>array(),'total'=>0);
        $where = ' AND t.status!=2  AND g.status!=2 ';
        if(isset($param['id']) && trim($param['id'])!='0'){
            $where .= " AND g.id in (" . $param['id'] . ') ';
        }
        if(isset($param['bookname']) && trim($param['bookname'])!=''){
            $where .= ' AND g.name like "%'. trim($param['bookname']) . '%" ';
        }
        if(isset($param['booktypeid']) && intval($param['booktypeid'])!='0'){
            $where .= " AND g.categoryid in (" . intval($param['booktypeid']) . ') ';
        }
        if(isset($param['city']) && intval($param['city'])!='0'){
            $where .= " AND t.city in (" . intval($param['city']) . ') ';
        }

        /*$sql = " SELECT SQL_CALC_FOUND_ROWS g.`id`,g.`la`,g.`descript`,g.`sort`,g.`name`,g.`status`,t.`name` as categoryName,g.`categoryid` ,`price`,`wxprice`,`img`
                FROM `booklist` as g,`type` as t where t.id=g.`categoryid` ".$where." order by g.id desc ,g.sort DESC";
        $limit = $this->procLimit($page);
        */
       $sql = " SELECT SQL_CALC_FOUND_ROWS g.`id`,g.`peiSongSum`,g.`la`,g.`descript`,g.`sort`,g.`name`,g.`status`, CONCAT(t.`name`,'-',( SELECT name FROM `type` WHERE id = t.`parentid`),'-',( SELECT name FROM `citylist` WHERE id = t.`city`)) as categoryName, t.`name` as categoryNameOr,g.`categoryid` ,`price`,`wxprice`,`img` ,t.`endSaleTime`
       FROM `booklist` as g,`type` as t where t.id=g.`categoryid`".$where." order by g.id desc ,g.sort DESC";
        $limit = $this->procLimit($page);

        $sql .= $limit;

        $res = self::execute($sql,'s');

        $sqlC = "SELECT FOUND_ROWS() as c; ";
        $resC = self::execute($sqlC,'s');
        
        if( $res['status'] == 0  && !empty($res['data'][0]) && $resC['status'] == 0  && !empty($resC['data'][0]['c'])){
            $data['data'] = $res['data'];
            $data['total'] = $resC['data'][0]['c'];
        }
        return $data;
    }

    /**
    * 添加或者更新
    *@param str iFlag i添加u修改
    *@param array param
    *        $params['name'] = $param['name'];
    *        $params['categoryid'] = $param['categoryid'];
    *        $params['sort'] = $param['sort'];
    *        $params['status'] = $param['status'];
    *@param int iFlag u 时候用的uid
    *@return array status=0 is ok
    */
    public function bookIU($iFlag,$param,$id = 0 ){
        $res = array();
        
        $params['name'] = trim($param['name']);
        $params['categoryid'] = intval($param['categoryid']);
        $params['sort'] = intval($param['sort']);
        $params['status'] = intval($param['status']);

        $params['la'] = trim($param['la']);
        $params['descript'] = trim($param['descript']);
        $params['img'] = trim($param['img']);
        $params['price'] = trim($param['price']);
        $params['wxprice'] = trim($param['wxprice']);
        $params['peiSongSum'] = intval($param['peiSongSum']);

        if($iFlag == 'i' ){
            $key = '`'.implode(array_keys($params),'`,`').'`'; //转key为表字段
            $val = "'".implode($params,"','")."'"; //转值为插入的内容

            $sql = "INSERT INTO `booklist` (".$key.") 
            VALUES ( ".$val.")";
        }elseif($id > 0 && $iFlag == 'u' ){
            $sql = "UPDATE `booklist` 
                    SET `name` = '" . $params['name'] . "' ,
                        `categoryid` = '" . $params['categoryid'] . "' ,
                        `sort` = '" . $params['sort'] . "' ,
                        `status` = '" . $params['status'] . "' ,
                        `descript` = '" . $params['descript'] . "' ,
                        `img` = '" . $params['img'] . "' ,
                        `la` = '" . $params['la'] . "' ,
                        `price` = '" . $params['price'] . "' ,
                        `wxprice` = '" . $params['wxprice'] . "', 
                        `peiSongSum` = '" . $params['peiSongSum'] . "' 
                    WHERE `id` = '" . intval($id) . "'";
        }

        $res = self::execute($sql,$iFlag);
        
        return $res;
    }

    /**
    *@param int gameid
    *@return array status=0 ok
    */
    public function bookDel($id = 0 ){
        $sql = "UPDATE `booklist` SET `status` = '2'  WHERE `booklist`.`id` = ".intval($id);
        $res = self::execute($sql,'d');
        return $res;
    }

    /**
     * 修改用户密码
     * @param array param 
     *                  int $uid
     *                  string $pwd
     * @param int $type  1 修改自己的密码，2修改下属用户的密码
     * @return int 0成功 非0失败
     */
    public function sysUserUPass($type,$param){
        $res = array('status'=>1);
        $params = array();

        if(!empty($param)){
            $params['uid'] = intval($param['uid']);
            $params['passwd'] = trim($param['pwd']);
        }
        // D($param);
        $currentUid = user::getUserInfoByUId($_SESSION['userinfo']['uid']);
        $currentUid = $params['uid'];
        switch ($type) {
            case '1':
                $sql = "UPDATE `backuser` 
                SET `passwd` = '".$params['passwd']."' WHERE `backuser`.`uid` = ".$currentUid;
            break;
            case '2':                
                $sql = "UPDATE `backuser` ,`back_user_ext_info`
                SET `passwd` = '".$params['passwd']."' WHERE `backuser`.`uid` = ".$params['uid'] . " AND parentid = ".intval($currentUid);                
            break;
            default:
                # code...
                break;
        }
        // echo $sql;die;
        $res = self::execute($sql,'u');
        return $res;
    }


     /**
     * 获取城市列表
     */
     public function getCityList(){
        $sql = "SELECT * FROM `citylist` WHERE child=1 ";
        $res = self::execute($sql,'s');
        $city[0] = '请选择';
        if( $res['status'] == 0  && !empty($res['data']) ){
            foreach ($res['data'] as $key => $value) {
                $city[$value['id']] = $value['name'];
            }
        }
        return $city;
     }


     /**
     * 根据类型获取子类
     * $params['city'] = '2';
     * $params['type'] = '35';
     */
     public function getbookType($params){
        // $sql = ' SELECT g.* FROM `type` as g WHERE 
        // find_in_set (`id` , (select arrchildid from type where id = ' . $params['type'] . ' and city=' . $params['city'] . ' )) AND id != ' . $params['type'] ;
        $sql = 'SELECT g.* FROM `type` as g WHERE find_in_set (`id` , (select arrchildid from type where id = ' . $params['type'] . '  )) AND  g.`status` =0 AND id != ' . $params['type'] . ' AND city=' . $params['city'] ;
        $sql .= ' ORDER BY g.sort DESC';
        
        $res = self::execute($sql,'s');
        if( $res['status'] == 0  && !empty($res['data']) ){
            return $res['data'];
        } else{
            return array();
        }
     }

// -- 根据城市，早餐或者晚餐等找出下面的 分类
// SELECT g.* FROM `type` as g WHERE 
// find_in_set (`id` , (select arrchildid from type where id = 35 and city=2 )) AND id != 35
// -- 分类下的菜品
// SELECT g.* FROM `booklist` as g WHERE 
// find_in_set (`categoryid` , (select arrchildid from type where id = 35 and city=2 )) AND categoryid=38

     /**
     * 根据类型获取子类
     * $params['city'] = '2';
     * $params['type'] = '35';
     * categoryid 分类
     */
     public function getsubType($params){
        // $sql = 'SELECT g.*,t.`name` as tname FROM `booklist` as g,type as t WHERE
        // find_in_set (`categoryid` , 
        //     (SELECT `arrchildid` FROM `type` WHERE `city` = ' . $params['city'] . ' AND id=' . $params['type'] . ') ) 
        // AND g.`categoryid` = t.id  ';
        $WHERE = '';
        if(isset($params['categoryid'])){
            $WHERE .= ' AND  g.`categoryid` = ' .intval($params['categoryid']);
        }
       /* $sql = 'SELECT g.*,t.`name` as tname FROM `booklist` as g,type as t WHERE
        find_in_set (`categoryid` , 
            (SELECT `arrchildid` FROM `type` WHERE  id=' . $params['type'] . ') ) 
        AND g.`categoryid` = t.id  AND `city` = ' . $params['city'] . $WHERE .' ORDER BY g.sort DESC ';
        */
        $sql = 'SELECT g.*,t.`waimaiEndtime`,t.`opentimeDay`,t.`opentime`,t.`endtime`,t.`name` as tname FROM `booklist` as g,type as t WHERE
        find_in_set (`categoryid` , 
            (SELECT `arrchildid` FROM `type` WHERE  id=' . $params['type'] . ') ) 
        AND g.`categoryid` = t.id  
        AND g.status=0
        AND t.status=0
        AND `city` = ' . $params['city'] . $WHERE .' 
        ORDER BY g.sort DESC ';
        
 
        $res = self::execute($sql,'s');
        if( $res['status'] == 0  && !empty($res['data']) ){
            return $res['data'];
        } else{
            return array();
        }
     }


     //产生全站唯一订单号
     public function gen_orderid($uid){
        
        //return $id = WxPayConfig::MCHID.microtime(true) . $uid . rand(0,9999);
        return  $id = WxPayConfig::MCHID.date("YmdHis") . rand(10000,99999);
        return str_replace('.', '', $id);
     }

     //订单入库
     public function addOrder($param){
        if(!isset($param) || empty($param)){
            self::errorMsg(0);
            return false;
        }

        // $orderid = $this->gen_orderid($param['uid']);
        $orderid = $param['orderid'];
        $order = array(
                'uid' => $param['uid'],
                'orderid' => $orderid,
                'allPrice' => $param['allPrice'],
                'status' => '0',
                'addtime' => time(),
                'waimai' => $param['waimai'],
                'shopname' =>$_SESSION['shopname'],
            );        
        $key = '`'.implode(array_keys($order),'`,`').'`'; //转key为表字段
        $val = "'".implode($order,"','")."'"; //转值为插入的内容
        $sql = "INSERT INTO `order` (".$key.") 
            VALUES ( ".$val.")";

        $order = self::execute($sql,'i');

        if($order['status'] != 0){
            self::errorMsg(2);
            return false;
        }

        foreach ($param['data'] as $key => $value) {            
            $orderinfo = array(
                'orderKeyId' => $order['id'],
                'bookid' => $key,
                'sum' => $value['sum'],
                'countPrice' => $value['countPrice'],
                'price' => $value['price'],
                'wxprice' => $value['wxprice'],
                'title' => $value['name'],                
            );        
            
            $key = '`'.implode(array_keys($orderinfo),'`,`').'`'; //转key为表字段
            $val = "'".implode($orderinfo,"','")."'"; //转值为插入的内容
            $infoSql = "INSERT INTO `orderinfo` (".$key.") 
                VALUES ( ".$val.")";                
            
            $infoRes = self::execute($infoSql,'i');

            if($infoRes['status'] != 0){
                self::errorMsg(3);
                return false;
            }
        }
        self::errorMsg(4);
        return true;
     }

     /**
     * $id 用户uid
     * 返回某用户的订单
     * SELECT o.*,i.*,u.phone,u.userid FROM `order` as o ,`orderinfo` as i ,`user` as u WHERE i.orderKeyId = o.id AND o.uid=u.userid
     * @return array('status' =>0 'data' = array(0=>array(xxx)))
     */
     public function getOrder($param){
        $data = array('total' => 0, 'data' => array());
        $where = '';
        $sql = 'SELECT o.*,o.id as `oid`,i.*,u.phone,u.userid,u.addr FROM `order` as o ,`orderinfo` as i ,`user` as u WHERE i.orderKeyId = o.id AND o.uid=u.userid  AND o.status!= -1 AND o.id in (select ii.orderKeyId from orderinfo as ii )';

        if( isset($param['uid']) && intval($param['uid']) > 0){
            $where .= ' AND o.uid='.intval($param['uid']);
        }

        if( isset($param['status']) && trim($param['status'])!='' ){
            //所有没支付成功的
            if( isset($param['status2']) && trim($param['status2'])!='' ){
                $where .= " AND o.status != '".trim($param['status2'])."'";
                //查个人时候，未支付的，只查当天记录
                if( isset($param['uid']) && intval($param['uid']) > 0){
                    $where .= " AND FROM_UNIXTIME( o.`addtime` ,  '%Y%m%d' ) = CURDATE( ) ";
                }
            }else{
            //只对应状态的
                $where .= " AND o.status = '".trim($param['status'])."'";    
            }
        }
        if( isset($param['phone']) && trim($param['phone'])!='' ){
            $where .= " AND u.phone = '".trim($param['phone'])."'";
        }
        if( isset($param['id']) && intval($param['id']) > 0){
            $where .= " AND o.id = '".intval($param['id'])."'";
        }
        if( isset($param['waimai']) && trim($param['waimai']) !='' ){
            $where .= " AND o.waimai = '".intval($param['waimai'])."'";
        }
        if( isset($param['shopname']) && trim($param['shopname']) !='' ){
            $where .= " AND o.shopname = '".trim($param['shopname'])."'";
        }
        if( isset($param['orderid']) && trim($param['orderid'])!='' ){
            $where .= " AND o.orderid = '".trim($param['orderid'])."'";
        }
        if( isset($param['starttime']) && trim($param['starttime'])!='' ){
            $where .= " AND o.addtime >= '".strtotime(trim($param['starttime']))."'";
        }
        if( isset($param['endtime']) && trim($param['endtime'])!='' ){
            $endtime = strtotime(trim($param['endtime']))+60*60*24;
            $where .= " AND o.addtime < '".$endtime."'";
        }
        //餐类别，早餐|中餐。。
        if( isset($param['booktype']) && intval($param['booktype']) > 0 && intval($param['booktype']) != 6 ){
            $where .= " AND i.bookid in ( SELECT g.id FROM `booklist` as g,type as t WHERE find_in_set (`categoryid` , (SELECT `arrchildid` FROM `type` WHERE id= ".intval($param['booktype'])." ) ) AND g.`categoryid` = t.id )";
        }
        //城市id 精确到具体城市,如深圳，广州，北京  最终级
        if( isset($param['city']) && intval($param['city']) > 0  && intval($param['city']) !=6 ){
            $where .= "  AND i.bookid in ( SELECT g.id FROM `booklist` as g,type as t WHERE t.city = " . intval($param['city']) . " AND g.`categoryid` = t.id )";
        }

        $param['pagesize'] = isset($param['pagesize']) ? $param['pagesize'] : '1';
        $limit = $this->procLimit($param['pagesize']);
        $order = ' ORDER  BY o.id DESC';

        $sql .= $where . $order .  $limit;
      // echo $sql;die; 
        $res = self::execute($sql,'s');
        
        $sqlC = "
        SELECT count(o.id) as row FROM `order` as o , `user` as u ,`orderinfo` AS i
        WHERE 1           
            AND i.orderKeyId = o.id
            AND o.uid=u.userid
            AND o.id in (SELECT i.orderKeyId FROM `orderinfo` as i)
                ";
        $sqlC .= $where . ' ';
        // echo $sqlC;die;
        $resC = self::execute($sqlC,'s');

        $sqlOC = "
        SELECT  count(distinct  o.id) as row FROM `order` as o , `user` as u ,`orderinfo` AS i
        WHERE 1           
            AND i.orderKeyId = o.id
            AND o.uid=u.userid
            AND o.id in (SELECT i.orderKeyId FROM `orderinfo` as i)
                ";
        $sqlOC .= $where . ' ';
        // echo $sqlOC;die;
        $sqlOC = self::execute($sqlOC,'s');
        
        //把同一个订单号的订单合并
        if($res['status']==0 && $resC['status']==0){
            foreach ( $res['data'] as $key => $value) {
                $data['data'][$value['orderid']][] = $value;
            }
            $data['total'] = $resC['data'][0]['row'];
            $data['orderTotal'] = $sqlOC['data'][0]['row'];
        }
        // D($data);
        return $data;
     }

     //根据订单id 订单删除,状态删除
     public function orderUp($status = 0,$id = 0,$orderid = 0){
        $where = "  `id` = 0 ";
        if($orderid != 0 ){
            $where = "  `orderid` = '".trim($orderid)."' ";
        }
        if($id != 0 ){
            $where = "  `id` = '".intval($id)."' ";
        }
        $sql = "UPDATE `order` SET `status` = '" . $status . "' 
        WHERE ".$where;
        
        $res = self::execute($sql,'u');        
        
        return $res;
     }

     //根据订单id 订单删除,状态删除
     public function orderDel($id){
        $sql = "UPDATE `order` SET `status` = '-1' WHERE `id` = '".intval($id)."'";
        $res = self::execute($sql,'u');
        
        return $res;
     }

     //start 
     /**
     * 返回门店坐标
     * @return array('status' =>0 'data' = array(0=>array(xxx)))
     */
     public function getMap($param){
        $data = array();
        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM `map` WHERE 1 = 1 ';
        $where = '';
        if( isset($param['id']) ){
            $where .= ' AND id = ' .intval($param['id']);
        }

        $order = ' ORDER  BY id DESC';

        $param['pagesize'] = isset($param['pagesize']) ? $param['pagesize'] : '1';
        $limit = $this->procLimit($param['pagesize']);
        $sql .= $where . $order .  $limit;

        $res = self::execute($sql,'s');
        
        $sqlC = "SELECT FOUND_ROWS() as row; ";
        $resC = self::execute($sqlC,'s');
        
        if($res['status']==0 && $resC['status']==0){
            $data['data'] = $res['data'];
            $data['total'] = $resC['data'][0]['row'];
        }
        return $data;
    }

    //门店增改
    public function mapIU($iFlag = 'i',$param = array(),$iId = 0){
        $sql = '';
        $map = array(
                    'name' => $param['name'],
                    'city' => $param['city'],
                    'longitude' => $param['longitude'],
                    'latitude' => $param['latitude'],
                    'status' => '0'
                );
        if( $iFlag == 'i' ){            
            $key = '`'.implode(array_keys($map),'`,`').'`'; //转key为表字段
            $val = "'".implode($map,"','")."'"; //转值为插入的内容
            $sql = "INSERT INTO `map` (".$key.") VALUES ( ".$val.")";

        }elseif($iFlag == 'u' && $iId != 0 )
        {
            $sql = "UPDATE `map` SET `name` = '" . $map['name'] . "' ,
            `city` = '".$map['city']."' ,
            `longitude` = '".$map['longitude']."' ,
            `latitude` = '".$map['latitude']."' ,
            `status` = '".$map['status']."' 
            WHERE `id` = '" . intval($iId) . "'";
        }
        return  self::execute($sql,$iFlag);
     }

    /**
    * 删除门店
    *@param int gameid
    *@return array status=0 ok
    */
    public function mapDel($id = 0 ){
        $del = array('status' => 1);
        //删前检查
        $res = $this->getMap(array('id' => $id ));
        if( isset($res['data'][0]) && !empty($res['data'][0]) ){
            $sql = "DELETE FROM `booklist` WHERE `booklist`.`id` = ".intval($id);
            $del = self::execute($sql,'d');
        }
        return $del;
    }
    //end

    //保存用户的信息，openid 和电话号码
    //如果存在则更新，默认是新增
    public function saveUserInfo($info){
        $res['status'] = 1;
        //提交订单电话号码处处理
        if(isset($info['userid'])){
            $sql = "SELECT count(`userid`) as row FROM  `user` WHERE `userid`='".intval($info['userid'])."'";
            $res = self::execute($sql,'s');

            if($res['status'] == 0 && isset($res['data'][0]['row']) && $res['data'][0]['row'] >0 ){
                if( isset($info['phone']) ){
                    $info_addr = isset($info['addr']) ? trim($info['addr']) : '';
                    $info_shopname = isset($info['shopname']) ? trim($info['shopname']) : '';
                    $info_cityid = isset($info['cityid']) ? trim($info['cityid']) : '';

                    $iFlag = 'u';
                    $psql = " UPDATE `user` set `addr` = '".$info_addr ."' 
                    , `phone` = '".$info['phone'] ."' 
                    , `shopname` = '".$info_shopname ."' 
                    , `cityid` = '".$info_cityid ."' 
                    WHERE `userid` = '".intval($info['userid'])."'";
                }else{
                    $psql = 'select a';
                }
            }else{
                $iFlag = 'i';
                $key = '`'.implode(array_keys($info),'`,`').'`'; //转key为表字段
                $val = "'".implode($info,"','")."'"; //转值为插入的内容
                $psql = "INSERT INTO `user` (".$key.") VALUES ( ".$val.")";
            }
            // echo $psql;die;     
            $pres = self::execute($psql,$iFlag);
            return $pres;
        }
        //传openid，存在则不用处理，否则新增
        if(isset($info['openid'])){
            $sql = "SELECT * FROM  `user` WHERE `openid`='".trim($info['openid']) ."'";
            $res = self::execute($sql,'s');
        
            if($res['status'] == 0 && isset($res['data'][0]) && !empty($res['data'][0]) ){
                $res = array('id' => $res['data'][0]['userid'],
                             'cityid' => $res['data'][0]['cityid'],
                             'shopname' => $res['data'][0]['shopname'],
                             'addr' => $res['data'][0]['addr']
                    );
            }else{
                $iFlag = 'i';
                $key = '`'.implode(array_keys($info),'`,`').'`'; //转key为表字段
                $val = "'".implode($info,"','")."'"; //转值为插入的内容
                $psql = "INSERT INTO `user` (".$key.") VALUES ( ".$val.")";
                $res = self::execute($psql,$iFlag);
            }
            
            return $res;
        }
    }

    //根据用户ID,获取用户信息
    public function getUserInfo($param){
        $data = array();
        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM `user` WHERE 1 = 1 ';
        $where = '';

        if( isset($param['userid']) ){
            $where .= ' AND userid = ' .intval($param['userid']);
        }

        $order = ' ORDER  BY userid DESC';

        $param['pagesize'] = isset($param['pagesize']) ? $param['pagesize'] : '1';
        $limit = $this->procLimit($param['pagesize']);
        $sql .= $where . $order .  $limit;

        $res = self::execute($sql,'s');
        
        $sqlC = "SELECT FOUND_ROWS() as row; ";
        $resC = self::execute($sqlC,'s');
        
        if($res['status']==0 && $resC['status']==0){
            $data['data'] = $res['data'];
            $data['total'] = $resC['data'][0]['row'];
        }
        return $data;
    }

    //根据用户ID,获取用户信息
    public function ___getUserInfo($info){
        $sql = 'SELECT * FROM  `user` WHERE `userid`='.intval($info['userid']);
        return $res = self::execute($sql,'s');
    }

    //订单退订
    public function orderCancel(){
        self::errorMsg(2);
    }

    //菜品点击日志
    public function saveHistLog($info){        
        $key = '`'.implode(array_keys($info),'`,`').'`'; //转key为表字段
        $val = "'".implode($info,"','")."'"; //转值为插入的内容
        $sql = "INSERT INTO `hitlog` (".$key.") VALUES ( ".$val.")";
        return $res = self::execute($sql,'i');        
    }

    //菜品点击查询
    public function queryHit($param){        
        $where = '';
        if( isset($param['bookid']) && trim($param['bookid'])!='' ){
            $where .= " AND bookid = '".intval($param['bookid'])."'";
        }
        if( isset($param['starttime']) && trim($param['starttime'])!='' ){
            $where .= " AND time >= '".strtotime(trim($param['starttime']))."'";
        }
        if( isset($param['endtime']) && trim($param['endtime'])!='' ){
            $endtime = strtotime(trim($param['endtime']))+60*60*24;
            $where .= " AND time <= '".$endtime."'";
        }
        if( isset($param['bookname']) && trim($param['bookname'])!='' ){
            $where .= " AND b.name  like '%".trim($param['bookname'])."%'";
        }
        if( isset($param['booktypeid']) && intval($param['booktypeid']) > 0 ){
            $where .= " AND b.categoryid = '".intval($param['booktypeid'])."' ";
        }
        if( isset($param['city']) && intval($param['city']) > 0 ){
            $where .= " AND city = '".intval($param['city'])."' ";
        }

        if( isset($param['shopname']) && intval($param['shopname']) > '0' ){
            $shopNcity = explode(',', trim($param['shopname']));
            $where .= " AND shopname = '".trim($shopNcity[1])."' ";
            $where .= " AND  city= '".intval($shopNcity[0])."' ";
        }

        if( isset($param['order']) && strtoupper(trim($param['order']))=='ASC' ){
            $orderby = ' ASC ';
        }else{
            $orderby = ' DESC ';
        }
        
        //$sql = "SELECT FROM_UNIXTIME(l.time,'%Y%m%d') days,b.name as bookname,COUNT(l.id) as cc FROM hitlog as l inner join booklist as b on b.id=l.bookid where 1 ";
        //$group = ' GROUP BY bookname,days ORDER BY days desc , cc '.$orderby;
        $sql = "SELECT b.name as bookname,COUNT(l.id) as cc FROM hitlog as l inner join booklist as b on b.id=l.bookid where 1 ";
        $group = ' GROUP BY bookname ORDER BY cc '.$orderby;
        $limit = ' limit 200';
        $sql .=$where . $group . $limit;
        // $sql = "SELECT FROM_UNIXTIME(l.time,'%Y%m%d') days,b.name as bookname,COUNT(l.id) as cc FROM hitlog as l inner join booklist as b on b.id=l.bookid where 1 ";
        // $group = ' GROUP BY bookname,days ORDER BY cc '.$orderby;        
        // $sql .=$where . $group;
        
        return $res = self::execute($sql,'s');
    }

    //菜品销量
    public function queryOrderHit($param){
        $where = '';
        if( isset($param['starttime']) && trim($param['starttime'])!='' ){
            $where .= " AND `order`.addtime >= '".strtotime(trim($param['starttime']))."'";
        }
        if( isset($param['endtime']) && trim($param['endtime'])!='' ){
            $endtime = strtotime(trim($param['endtime']))+60*60*24;
            $where .= " AND `order`.addtime <= '".$endtime."'";
        }
        if( isset($param['bookname']) && trim($param['bookname'])!='' ){
            $where .= " AND title like '%".trim($param['bookname'])."%'";
        }
        if( trim($param['waimai']) == 0 || trim($param['waimai']) == 1 ){
            $where .= " AND `order`.waimai = '".trim($param['waimai'])."' ";
        }
        if( isset($param['shopname']) && intval($param['shopname']) > '0' ){
            $shopNcity = explode(',', trim($param['shopname']));
            $where .= " AND `order`.shopname = '".trim($shopNcity[1])."' ";
        }
        if( isset($param['order']) && strtoupper(trim($param['order']))=='ASC' ){
            $orderby = ' ASC ';
        }else{
            $orderby = ' DESC ';
        }
        
        $sql   = "SELECT o.bookid, o.title as title, SUM(o.sum) as sum, l.wxprice as price FROM `orderinfo` as o left join `order` on o.orderKeyId = `order`.id left join booklist as l on o.bookid = l.id WHERE `order`.status='SUCCESS' ";
        $group = ' GROUP BY o.bookid ORDER BY sum '.$orderby;
        
        //$sql = "SELECT b.name as bookname, COUNT(l.id) as cc FROM hitlog as l inner join booklist as b on b.id=l.bookid where 1 ";
        //$group = ' GROUP BY bookname ORDER BY cc '.$orderby;
        $limit = ' limit 200';
        $sql .=$where . $group . $limit;
        
        return $res = self::execute($sql,'s');
    }

    //获取城市列表
    public function getShopName(){
        $shopnameList = array();
        $sql = "SELECT c.`name` as cityname,l.`city`,l.`shopname` FROM `hitlog` as l inner join `citylist` as c ON c.id= l.city WHERE `shopname`!='' group by `shopname`";
        $res = self::execute($sql,'s');
        if($res['status'] == 0){
            foreach ($res['data'] as $key => $value) {
                $shopnameList[] = array('cityNshopname'=>$value['cityname'].'-'.$value['shopname'],
                    'shopname'=>$value['shopname'],
                    'cityid'=>$value['city'],
                    'city'=>$value['cityname']);
            }
        }
        return $shopnameList;
    }

    //更新订单打印状态
    public function upOrderPrint($id){
        $sql = "UPDATE  `order` SET  `printStatus` =  '1' WHERE  `id` =".intval($id);
        $res = self::execute($sql,'u');
        if($res['status'] == 0){
            return true;
        }else{
            return false;
        }
    }

    //有菜品的城市列表
    public function citylist(){
        $cityInfo = array();
        $citylistSql = "SELECT c.name,city as cityid FROM `type` as t,citylist as c where c.id=t.city and t.city!=6 group by `city`";
        $cityRes = self::execute($citylistSql,'s');
        if($cityRes['status'] == 0){
            $cityInfo = $cityRes['data'];
        }
        return $cityInfo;
    }

    //获取昨天预定的菜品
    // int  cityid 城市的id 默认是3，深圳
    public function getBookSum($cityid = 3){
        $info = array();
        $sql = "SELECT b.name as title,sum(i.sum) as booksum,peiSongSum,t.`endSaleTime`  FROM `type` as t,booklist as b 
        left join `orderinfo` AS i on i.bookid=b.id  AND i.orderKeyId in (  SELECT o.id FROM `order` as o where o.status = 'SUCCESS' AND  FROM_UNIXTIME( o.`addtime` , '%Y%m%d' ) = date_sub(curdate(),interval 0 day)  ) 
        WHERE b.`status`=0 
        AND t.`status`=0 
        AND t.id=b.categoryid
        AND t.city=".intval($cityid)."
        GROUP BY b.id order by sum desc";
        
        $res = self::execute($sql,'s');
        if($res['status'] == 0){
            $info = $res['data'];
        }
        return $info;
    }

    //获取配送设置列表
    public function getPeiSongList($param = array()){
        $info = array();
        $where = '';
        if(isset($param['id']) && intval($param['id']) > '0' )
        {
            $where .= " WHERE  id  = " . intval($param['id']) ;
        }
        $listSql = "SELECT * FROM `peisong` ";
        $listSql .= $where;
        
        $res = self::execute($listSql,'s');

        if($res['status'] == 0){
            $info = $res['data'];
        }
        return $info;
    }

    //配送增改
    public function getPeiSongIU($iFlag = 'i',$param = array(),$iId = 0){
        $sql = '';
        $params = array(
                    'cityid' => $param['cityid'],
                    'shopname' => $param['shopname'],
                    'shopNamePhone' => $param['shopNamePhone'],
                    'peisongPhone' => $param['peisongPhone'],                    
                );
        if( $iFlag == 'i' ){            
            $key = '`'.implode(array_keys($params),'`,`').'`'; //转key为表字段
            $val = "'".implode($params,"','")."'"; //转值为插入的内容
            $sql = "INSERT INTO `peisong` (".$key.") VALUES ( ".$val.")";

        }elseif($iFlag == 'u' && $iId != 0 )
        {
            $sql = "
            UPDATE `peisong` SET 
            `cityid` = '". $params['cityid'] ."', 
            `shopname` = '". $params['shopname'] ."', 
            `shopNamePhone` = '". $params['shopNamePhone'] ."', 
            `peisongPhone` = '". $params['peisongPhone'] ."'
            WHERE `id` = '" . intval($iId) . "'";
        }
        
        return  self::execute($sql,$iFlag);
    }

    //获取今天天成功预定数
    public function getTodayOKBookSum($bookid,$day=0){
        $info = array();
        $sql = "
                SELECT b.id as bookid,sum(i.sum) as booksum,peiSongSum
                FROM booklist as b 
                left join `orderinfo` AS i on i.bookid=b.id AND i.orderKeyId in ( SELECT o.id FROM `order` as o where o.status = 'SUCCESS' AND FROM_UNIXTIME( o.`addtime` , '%Y%m%d' ) = date_sub(curdate(),interval ".$day." day) ) 

                WHERE b.`status`=0 AND  i.bookid = " .intval($bookid);
        $res = self::execute($sql,'s');
        if($res['status'] == 0){
            $info = $res['data'];
        }
        return $info;
    }
    
//获取今天天成功预定数
    public function getTodayEndTimeOKBookSum($bookid,$day=0,$endtime = ''){
        $info = array();
        $sql = "
                SELECT b.id as bookid,sum(i.sum) as booksum,peiSongSum
                FROM booklist as b
                left join `orderinfo` AS i on i.bookid=b.id AND i.orderKeyId in ( SELECT o.id FROM `order` as o where o.status = 'SUCCESS' AND o.addtime > ".$endtime." AND  FROM_UNIXTIME( o.`addtime` , '%Y%m%d' ) = date_sub(curdate(),interval ".$day." day) )

                WHERE b.`status`=0 AND  i.bookid = " .intval($bookid);
        $res = self::execute($sql,'s');
        if($res['status'] == 0){
            $info = $res['data'];
        }
        return $info;
    }

    /**
    * 删除
    *@param int gameid
    *@return array status=0 ok
    */
    public function delPeiSong($id = 0 ){
        $del = array('status' => 1);
        //删前检查
        $res = $this->getMap(array('id' => $id ));
        if( isset($res['data'][0]) && !empty($res['data'][0]) ){
            $sql = "DELETE FROM `peisong` WHERE `peisong`.`id` = ".intval($id);
            $del = self::execute($sql,'d');
        }
        return $del;
    }

    public static function errorMsg($id){
        $note = array(  '0'=>'参数不存在',
                        '1'=>'参数不全',
                        '2'=>'生成主订单失败',
                        '3'=>'生成详细订单失败',
                        '4'=>'订单提交成功',
            );
        self::$msg[$id] = $note[$id];
    }

    public function getMsg(){
        return self::$msg;
    }
}

