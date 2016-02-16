<?php

/** 
 * 图片处理类
 * @package application
 * @author xinkq
 * @version 1.0.0
 + build date 2014-7-22
 * 
 */
class Picupload {
	
	private static $picupload;
	
	/**
	 * 允许的类型
	 * @var array
	 */
	private $_extension = array(
			'jpge'=>IMAGETYPE_JPEG,
			'jpg'=>IMAGETYPE_JPEG,
			'png'=>IMAGETYPE_PNG,
			'gif'=>IMAGETYPE_GIF,
			'bmp'=>IMAGETYPE_BMP
	);
	
	/**
	 * 文件上传路径
	 * @var string
	 */
	private $_dir = 'img/';
	
	/**
	 * 最小文件大小(bytes)
	 * @var uint
	 */
	private $_minsize = 0;
	
	/**
	 * 最大文件大小(bytes)
	 * @var uint
	 */
	private $_maxsize = 1024000;
	
	/**
	 * 宽度
	 * @var uint(px)
	 */
	private $_width;
	
	/**
	 * 高度
	 * @var uint(px)
	 */
	private $_height;
	
	/**
	 * 保存文件名(不要带后缀)
	 * 默认为：md5(uniqid(microtime(), true)) 32位长文件名
	 * @var string
	 */
	private $_savename;
	
	/**
	 * 是否覆盖现有文件
	 * @var boolean
	 */
	private $_overwrite = FALSE;
	
	/**
	 * FTP登录信息
	 * @var array
	 */
	private $_ftpconf = array();
	
	/**
	 * 上传图片后的文件信息
	 * 包含pathinfo里的数据及width、height、size
	 * @var array
	 */
	public static $picinfo;
	
	/**
	 * 返回消息
	 * @var string
	 */
	public static $message;
	

//	public function __construct() {
//            $this->init();
//        }
	private function __clone(){}
	
	/**
	 * 初始化类
	 */
	public  function __construct() {
	}
	
        /**
	 * 初始化类
	 */
	public static function init() {
		if (!(self::$picupload instanceof self)) {
			self::$picupload = new self();
		}
		self::$message = '';
		return self::$picupload;
	}
        
        /**
         * 设置保存的文件的目录
         * @param str $name
         * @return str
         */
        public function setDir($name){
            return $this->_dir = $name;
        }
        
        /**
         * 设置保存的文件名
         * @param str $name
         * @return str
         */
        public function setSavename($name){
            return $this->_savename = $name;
        }
        
        /**
         * 设置是否覆盖
         * @param bool $bool
         * @return bool
         */
        public function setOverwrite($bool){
            return $this->_overwrite = $bool;
        }

        /**
	 * 上传文件
	 * @param string $uploadformname 需要上传的表单名,$_FILES中         
	 * @return boolean
	 */
	public function upload($uploadformname) {
		//文件检查
		if(!$this->check($uploadformname)) {
			return false;
		}
		//要上传的图片缓存信息
		$upfile = $_FILES[$uploadformname];
		//图像类型
		$imgtype = exif_imagetype($upfile['tmp_name']);
		//图片尺寸信息
		$size = getimagesize($upfile['tmp_name']);
		
		if(!is_dir($this->_dir) && !$this->mkdir($this->_dir)) {
			self::$message = '创建存储目录失败';
			return false;
		}
		
		if(!$this->_overwrite && is_file($this->_dir.$this->_savename)) {
			self::$message = '同名文件已存在';
			return false;
		}
		
		//保存图片
		if(!move_uploaded_file($upfile['tmp_name'], $this->_dir.$this->_savename)) {
			self::$message = '文件保存失败';
			return false;
		}
		
		//返回图片属性
		self::$picinfo = pathinfo($this->_dir.$this->_savename);
		self::$picinfo['width'] = $size[0];
		self::$picinfo['height'] = $size[1];
		self::$picinfo['size'] = $upfile['size'];
		
		self::$message = '上传成功';
		return true;
	}
	
	/**
	 * 通过FTP上传图片
	 * FTP上传不检查文件是否已存在（存在会返回FALSE）
	 * @param string $uploadformname 需要上传的表单名,$_FILES中
	 * @return boolean
	 */
	public function ftpUpload($uploadformname) {
		if(empty($this->_ftpconf)) {
			self::$message = '未设置FTP登录信息';
			return false;
		}
		
		//文件检查
		if(!$this->check($uploadformname)) {
			return false;
		}
		
		//要上传的图片缓存信息
		$upfile = $_FILES[$uploadformname];
		//图像类型
		$imgtype = exif_imagetype($upfile['tmp_name']);
		//图片尺寸信息
		$size = getimagesize($upfile['tmp_name']);
		
		$ftp = new Ftp();
		$constatus = $ftp->connect($this->_ftpconf);
		if(!$constatus) {
			self::$message = '上传服务器连接失败';
			return false;
		}
		
		//尝试创建目录，不返回状态，有可能目录已存在
		$ftp->mkdir($this->_dir);
		$upsatus = $ftp->upload($upfile['tmp_name'], $this->_dir.$this->_savename,'');
		if(!$upsatus) {
			self::$message = '上传失败，服务器繁忙';
			return false;
		}		
		//返回图片属性
		self::$picinfo = pathinfo($this->_dir.$this->_savename);
		self::$picinfo['width'] = $size[0];
		self::$picinfo['height'] = $size[1];
		self::$picinfo['size'] = $upfile['size'];
		self::$message = '成功';
		return true;
	}
	
	/**
	 * 检查上传头像信息
	 * @param string $uploadformname 上传文件的表单名
	 * @return boolean 不合格时返回false，错误信息见$message
	 */
	public function check($uploadformname) {
		//获取上传的文件
		$upfile = isset($_FILES[$uploadformname]) ? $_FILES[$uploadformname] : null;
		if(empty($upfile)) {
			self::$message = '没有提交图像';
			return false;
		}
		
		if (!is_uploaded_file($upfile['tmp_name'])) {
			self::$message = '图片不存在';
			return false;
		}
		
		if($upfile['error'] != 0) {
			self::$message = '上传文件出错('.$upfile['error'].')';
			return false;
		}
		
		//图像类型
		$imgtype = (filesize($upfile['tmp_name']) > 11) ? exif_imagetype($upfile['tmp_name']) : false;
		
		if(!$imgtype) {
			self::$message = '上传文件格式非图片';
			return false;
		}
		
		if(!in_array($imgtype, $this->_extension)) {
			self::$message = '上传的图片格式不允许';
			return false;
		}
		
		$size = getimagesize($upfile['tmp_name']);
		if(!empty($this->_width) && $size[0] > $this->_width) {
			self::$message = '图片宽度超出限制';
			return false;
		}
		
		if(!empty($this->_height) && $size[1] > $this->_height) {
			self::$message = '图片高度超出限制';
			return false;
		}
		
		if(!empty($this->_minsize) && $upfile['size'] < $this->_minsize) {
			self::$message = '图片文件太小';
			return false;
		}
		
		if($upfile['size'] > $this->_maxsize) {
			self::$message = '图片文件太大';
			return false;
		}

		//处理存储文件名
		if(empty($this->_savename)) {
			$this->_savename = md5(uniqid(microtime(), true));
		}
		$typeArr = array_flip($this->_extension);//加上文件名后缀
		$this->_savename .= '.'.$typeArr[$imgtype];
		
		return true;
	}
	
	/**
	 * 允许的图片类型，只可是$this->_extension的交集
	 * 如无任何交集时则保持原匹配
	 * @param string|array $arr 允许的类型如array('jpg','jpge','png')
	 */
	public function setType($extension=array('jpg','jpge','png','gif','bmp')) {
		is_string($extension) ? ($extension = array($extension)) : null;
		//与现有设置求交集
		$types = array_intersect(array_keys($this->_extension), $extension);
		if(!empty($types)) {
			//允许的格式与现有格式求差集，清除掉差集内容
			foreach (array_diff_assoc(array_keys($this->_extension), $types) as $val) {
				unset($this->_extension[$val]);
			}
		}
		return $this;
	}
	
	/**
	 * 设置允许上传的文件大小(bytes)
	 * @param int $max 最大值(bytes)
	 * @param int $min 最小值(bytes)
	 */
	public function setSize($max, $min=0) {
		if(is_numeric($max) && $max > 0) {
			$this->_maxsize = $max;
		}
		if(is_numeric($min) && $min > 0) {
			$this->_minsize = $min;
		}
		return $this;
	}
	
	/**
	 * 设置宽度
	 * @param int $width 宽度(px)
	 */
	public function setWidth($width) {
		if(is_numeric($width) && $width > 0) {
			$this->_width = $width;
		}
		return $this;
	}
	
	/**
	 * 设置高度
	 * @param int $height 高度(px)
	 * @return picupload
	 */
	public function setHeight($height) {
		if(is_numeric($height) && $height > 0) {
			$this->_height = $height;
		}
		return $this;
	}
	
	/**
	 * 设置文件保存路径
	 * @param string $path 路径
	 * @return picupload
	 */
	public function setSaveDir($path) {
		/*if(!is_dir($path)) {
			mkdir($path);
		}*/
		if(is_string($path) && strlen($path) > 0) {
			$this->_dir = in_array(substr($path,-1), array('/','\\')) ? $path : ($path.'/');
			$this->_dir = str_replace('\\', '/', $this->_dir);
		}
		return $this;
	}
	
	/**
	 * 设置存储文件名
	 * @param string $name
	 * @return picupload
	 */
	public function setName($name) {
		$this->_savename = $name;
		return $this;
	}
        
        /**
	 * 获取存储文件名
	 * @param string $name
	 * @return picupload
	 */
	public function getName() {
		return $this->_savename;		
	}
	
	/**
	 * 设置为覆盖已存在的文件
	 */
	public function overwrite() {
		$this->_overwrite = true;
		return $this;
	}
	
	/**
	 * 设置FTP登录信息
	 * @param string $ip			服务器IP
	 * @param string $username		账号
	 * @param string $password		密码
	 * @param number $prot			端口
	 */
	public function setFtpLogin($ip, $username='', $password='', $prot=21) {
		$this->_ftpconf = array(
				'hostname' => $ip,
				'username' => $username,
				'password' => $password,
				'port' => $prot
		);
		return $this;
	}
	
	/**
	 * 创建目录，支持递归创建
	 * @param string $path 目录
	 * @param bool $recursion 是否递归，默认true
	 * @return bool
	 */
	private function mkdir($path ,$recursion=true) {
		if(!$recursion) {
			return $this -> mkdir2($path);
		}
	
		$result = false;
		$dir = explode('/', $path);
                
		$p = '';
		for ($i=0; $i<count($dir); $i++) {
			if(!empty($dir[$i])) {
				$p .= $dir[$i];                                
				if (!file_exists($p)) {
					$result = mkdir($p);
					if (!$result) break;
				}
			}
		}
		return $result;
	}
	
	/**
	 * 创建目录，不进行递归
	 * @param string $path 目录
	 * @return bool
	 */
	private function mkdir2($path) {
		$result = false;
		if (!file_exists($path)) {
			$result = mkdir($path);
			if (!$result) {
                            return false;
                        }
		}
		return $result;
	}
	
	function __destruct() {}
}

?>
