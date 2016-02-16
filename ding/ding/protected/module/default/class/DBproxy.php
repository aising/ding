<?php
/**
 * DB代理
 * @author xinkq
 * +2015-06-03
 */
class DBproxy {

	/**
	 * 写缓存模式,目前生成key的规则是 "存储过程_md5(入参)" true时和入参不为空时直接查询数据库,true时和入参为空时则可以查询缓存数据
	 * @var boolean
	 */
	public $cacheStrict = true;

	/**
	 * mysql列表
	 * @var array
	 */
	protected static $_dbList = array();

	/**
	 * procedure列表
	 * @var array
	 */
	protected static $_procedureList = array();
	
	/**
	 * dbkey名称
	 * @var string
	 */
	protected $_dbNameKey = '';

	// /**
	//  * 返回结果
	//  * @var boolean
	//  */
	// protected $_success = true;

	// /**
	//  * 返回结果内容
	//  * @var array
	//  */
	// protected $_errors = array();

	/**
	 * 缓存存储过程数据
	 * @var array
	 */
	protected $_cacheProcedureList = array();

	protected $_dimension = 2;

	protected $_type = 1;

	public static function get($dbName) {
		if(!isset(self::$_dbList[$dbName])) {
			Doo::loadClassAt('Mysql','default');
			self::$_dbList[$dbName] = new Mysql(Doo::conf()->dbconfig[$dbName]);
		}
		return self::$_dbList[$dbName];
	}

	public static function getProcedure($dbName) {
		$dbName = ucfirst($dbName);
		if(!isset(self::$_procedureList[$dbName])) {
			Doo::loadClassAt('procedures/'.$dbName,'default');
			self::$_procedureList[$dbName] = new $dbName;
		}
		return self::$_procedureList[$dbName];
	}	

	/**
	 * 取manage库mysql对象
	 * @return object 
	 */
	public static function getManage() {
		return DBproxy::get('manage');
	}

	public function setDimension($value=2) {
		$this->_dimension = $value;
		return $this;
	}

	public function setType($value = 1) {
		$this->_type = $value;
		return $this;
	}


	/**
    * db 处理统一接口
    * @param str sql语句
    * @param str 执行类型curd 
    */
	public function execute($sql = '',$type = 's'){
		
		return self::get($this->_dbNameKey)->execute($sql,$type);
	}


	/**
	 * 供子类使用执行mysql
	 * @var array
	 */
	protected function ProcessExecute($procedure, &$param = array()) {
		$output = array();

		// 插入、变更数据必须进入下面的流程
		if(!empty($param) && $this->cacheStrict) {
			$res = self::get($this->_dbNameKey)->execute($procedure,$param,$this->_dimension,$this->_type);
			foreach ($this->_cacheProcedureList as $key => $val) {
				$vals = explode(',',$val);
				if(in_array($procedure,$vals)) {
					Doo::loadCore('cache/DooPhpCache');
					$cacheObj = new DooPhpCache($this->_dbNameKey);
					// 删除缓存数据
					$cacheObj->del($cacheKey,$res);
				}
			}
		} else { 
			if(isset($this->_cacheProcedureList[$procedure])) {
				Doo::loadCore('cache/DooPhpCache');
				$cacheObj = new DooPhpCache($this->_dbNameKey);
				$cacheKey = empty($param) ? $procedure : $procedure.'_'.md5(var_export($param,true));
				$res = $cacheObj->get($cacheKey);
				if(empty($res)) {
					$res = self::get($this->_dbNameKey)->execute($procedure,$param,$this->_dimension,$this->_type);
					if(!empty($res)) {
						// 写入缓存数据
						$cacheObj->set($cacheKey,$res);
					}
				}
			} else {
				$res = self::get($this->_dbNameKey)->execute($procedure,$param,$this->_dimension,$this->_type);
			}
		}

		// // 固定返回结果格式
		// if($this->_success) {
		// 	return array('success'=>$this->_success,'errors'=>$res);
		// } else {
		// 	return array('success'=>$this->_success,'errors'=>$this->_errors);
		// }
		$this->_dimension = 2;
		$this->_type = 1;
		return $res;
	}

}