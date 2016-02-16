<?php
/**
 * 调整缓存生成类
 * @author xinkq
 */
class KVCache{

	private $cache;
	
	public function __construct(){		
		return Doo::cache('php');
	}
	
	public function cfile(){
		return Doo::cache('php');
	}

	public function redis(){
		$this->cache = new Redis();
		$this->cache->connect(DOO::conf()->redis[0],DOO::conf()->redis[1]);
		if($this->cache){
			return $this->cache;
		}else{
			return false;
		}		
	}
	
	public function __destruct(){
		unset($this->cache);
	}
	
}
