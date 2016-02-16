<?php
/**
 * 数据库MySQL5驱动
 * @package class
 * @author xinkq
 * @since 1.0.0
 * @version 1.0.0
 + build date 2015-6-3
 */
Doo::loadClassAt('User','default');
class Mysql{

	private $sql;
	private $conn;
    
    /**
     * @param array $config 数据库配置，数组格式：array('host'=>ip, 'port'=>port, 'uid'=>dbuser, 'pwd'=>dbpass, 'db'=>dbname)
	 * @return void
	 * @since 1.0.0
     */         
    public function __construct($config) {
        $this->connect($config);
    }

    /**
    * db 处理统一接口
    */
	public function execute($sql = '',$type = 's'){

		$res['status'] = 1;
		$dbRes = false;

		switch ($type) {
			//select
			case 's':
				$dbRes = mysqli_query($this->conn,$sql);
				$data =  $this->fetch_all_result($dbRes,1);				
				$res['data']  = $data[0];
				break;
			//insert
			case 'i':
				$dbRes = mysqli_query($this->conn,$sql);
				$res['id'] = mysqli_insert_id($this->conn);
				break;
			//update
			case 'u':
				$dbRes = mysqli_query($this->conn,$sql);				
				break;
			//delete
			case 'd':
				$dbRes = mysqli_query($this->conn,$sql);
				break;
			default:
				# code...
				break;
		}		
		if($dbRes == true){
			$res['status'] = 0;
		}
		Doo::logger()->info('time:'.date("Y-m-d H:i:s",time())."\tip:".getIP()."\tsql:".$sql."\t".var_export($res['status'],true),'DB');
		return $res;
	}

	/**
	 * 连接数据库
	 * @param array $config 数据库配置，数组格式：array('host'=>ip, 'port'=>port, 'uid'=>dbuser, 'pwd'=>dbpass, 'db'=>dbname)
	 * @return void
	 * @since 1.0.0
	 */
	public function connect($config) {
		
		if(strpos($config[0],':') === false) {
			$port = 3306;
			$host = $config[0];	
		} else {
			list($host,$port) = explode(':',$config[0]);
		}
		
		$conn = mysqli_connect($host, $config[2], $config[3], $config[1], $port);

		if ($conn) {
			mysqli_set_charset($conn, $config['charset']);
			$this->conn = $conn;
		}
		return $this->conn;
	}

	
	/**
	 * 获取多个结果集组成三维数组
	 * @param resource $stmt 资源对象(mysqli_result)
	 * @param int $type 结果集类型
	 * @return array
	 * @since 1.0.0
	 */
	private function fetch_all_result($stmt, $type) {
		$result = array();
		$result[] = mysqli_fetch_all($stmt, $type);
		while (mysqli_more_results($this->conn)) {
			mysqli_next_result($this->conn);
			if ($stmt2 = mysqli_store_result($this->conn)) {
				$row = mysqli_fetch_all($stmt2, $type);
				array_push($result, $row);
				mysqli_free_result($stmt2);
			}
		}
		return $result;
	}


	/**
	 * 清空多余结果集
	 * @return void
	 * @since 1.0.0
	 */
	private function clear_more_result() {
		while (mysqli_more_results($this->conn)) {
			mysqli_next_result($this->conn);
			if($rs = mysqli_store_result($this->conn)) {
				mysqli_free_result($rs);
			}
		}
	}


	/**
	 * 获取错误信息
	 * @return string
	 * @since 1.0.0
	 */
	private function get_error() {
		$errnum = mysqli_errno($this->conn);
		$errmsg = mysqli_error($this->conn);
		$result = $errnum.$errmsg;
		return $result;
	}


	/**
	 * 返回执行的SQL语句
	 * 此方法无法获取存储过程中执行的SQL
	 * @return string
	 * @since 1.0.0
	 */
	public function get_sql() {
		return $this->sql;
	}


	/**
	 * 返回数据库的版本信息
	 * @return string
	 * @since 1.0.0
	 */
	public function get_version() {
		return mysqli_get_server_info($this->conn);
	}


	public function __destruct() {
		if ($this->conn)
			mysqli_close($this->conn);
	}
}
?>