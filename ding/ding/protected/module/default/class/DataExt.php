<?php
/**
 * 二维数组数据处理类
 * @author xinkq
 * 2015-06-03
 * @example
 
// 数据合并特殊样例
$dt = new DataExt;
$data1 = array(
	array('post_date'=>'2014-06-21','var1'=>22,'var2'=>33),
	array('post_date'=>'2014-06-22','var1'=>111,'var2'=>122,'var3'=>133)
);

$data2  = array(
	array('post_date'=>'2014-06-21','var1'=>111,'v1'=>22,'v2'=>33),
	);

$actionStr = "\$val[\"var2\"] = isset(\$val[\"var2\"]) ? \$val[\"var2\"] : 0;
              \$val[\"var1\"] = isset(\$val[\"var1\"]) ? \$val[\"var1\"] : 0;
              \$val[\"game_total\"] = \$val[\"var1\"] + \$val[\"var2\"]";
$vd = $dt->tableDataMerge('post_date',array('sort'=>'post_date desc','sortType'=>'date','calData'=> array(
	            	array('field'=>false,
	                      'valType'=>'custom',
	                      'action'=>$actionStr,
	                )
	  		   )),$data1,$data2);

var_dump($vd);
exit;
 */
class DataExt {

	/**
	* @param array 要处理的数组
	* @param array 字段改名 array('原来的字段名'=>'新的字段名')
	* @param array 字段值运算 array(array('field(字段名)'=>'字段','newField(新的字段名,该项可以不输入)'=>'','valType(值类型)'=>'date 或者 number','action(运算符)'=>'+/*-%','changeVal'=>'值变化')
	* @return array
	*/
	public function calData($data,$changes = array('post_date'=>'post_date2',
		                                         'amount'=>'amount2'),
	                              $cals = array(
				                                 array('field'=>'post_date2',
				                                 	'newField'=>'post_date',
				                                    'valType'=>'date',
				                                    'action'=>'+',
				                                    'changeVal'=>86400),

				                                 array('field'=>'amount2',
				                                    'valType'=>'number',
				                                    'action'=>'/',
				                                    'changeVal'=>100),
				                                 )
	                       ) {
		$output = array();
		if(!empty($data) && is_array($data)) {
			foreach ($data as $key => $val) {
				if(!empty($changes)) {
					foreach ($changes as $changeOldField => $changeNewField) {
						if(isset($val[$changeOldField])) { 
							$temp = $val[$changeOldField];
							if($changeNewField !== false) {
								$val[$changeNewField] = $temp;
							}
							unset($val[$changeOldField]);
						}
					}
				}

				if(!empty($cals)) {
					foreach ($cals as $c) {
						$this->_setExecVal($c);
						$this->_execVal($val,$c['field'],isset($c['newField']) ? $c['newField'] : $c['field'],$c['valType'],$c['action'],$c['changeVal']);
					}
				}
				$output[$key] = $val;
			}
		}
		return $output;
	}

	/**
	 * 处理值
	 * @param  [type] $val       [description]
	 * @param  [type] $field     [description]
	 * @param  [type] $newField  [description]
	 * @param  [type] $valType   [description]
	 * @param  [type] $action    [description]
	 * @param  [type] $changeVal [description]
	 * @return void
	 */
	protected function _execVal(&$val,$field,$newField,$valType,$action,$changeVal) {
		if($action == '/' && $changeVal == 0) {
			return $val;
		}

		$fields = explode(',',$field);
		$newFields = explode(',',$newField);
		foreach ($val as $k => $v) {
			if(!in_array($k,$fields) && $valType != 'custom') {
				continue;
			}

			$temp = $v;
			switch ($valType) {
				case 'date':
					$temp = strtotime($temp);
					eval("\$temp = {$temp} {$action} {$changeVal};");
					$temp = date('Y-m-d',$temp);
					break;
				case 'number':
					eval("\$temp = {$temp} {$action} {$changeVal};");
					break;
				case 'custom':
					eval($action.";");
					break;
			}

			if($valType == 'custom') continue;
			$val[$newFields[array_search($k,$fields)]] = $temp;
		}
	}

	/**
	 * 设定默认值
	 * @param [type] $val [description]
	 */
	protected function _setExecVal(&$val) {
		$defaultValues = array(
			'changeVal' => ''
		);
		foreach ($defaultValues as $key => $default) {
			if(!isset($val[$key])) {
				$val[$key] = $default;
			}
		}
	}
	/**
	 * 数据合并
	 * @param  string $keys 数据合并key,多个以逗号分隔 post_date,amount
	 * @param  array  $opt  [description]
	 * @return [type]       [description]
	 * @example DataExt::tableDataMerge('post_date',array('sort'=>'post_date desc','sortType'=>'date'),$data1,$data2);
	 */
	public function tableDataMerge($keys='post_date',$opt = array('sort'=>'post_date desc','sortType'=>'date')) {
		$data = array();
		$numargs = func_num_args();
		$arg_list = func_get_args();
	    for ($i = 2; $i < $numargs; $i++) {
        	$data[] = $arg_list[$i];
    	}

    	return $this->_tableDataMerge($keys,$opt,$data);
	}

	/**
	 * 数据合并
	 * @param  string $keys [description]
	 * @param  array  $opt  [description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function tableDataMerge2($keys='post_date',$opt = array('sort'=>'post_date desc','sortType'=>'date'),$data) {
		if(empty($data)) {
			return $data;
		}

		// if(count($data) <= 1) {
		// 	return current($data);
		// }
		return $this->_tableDataMerge($keys,$opt,$data);
	}

	/**
	 * 数据合并2
	 * @param  string $keys [description]
	 * @param  array  $opt  [description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	protected function _tableDataMerge($keys='post_date',$opt = array('sort'=>'post_date desc','sortType'=>'date','keyType'=>'merge'),$data) {
    	$tempData = array();
    	$keysList = explode(',',$keys);
    	$keyType = isset($opt['keyType']) ? $opt['keyType'] : 'merge';

    	if(count($data) >= 1) {
	    	foreach ($data as $onlyOneData) {
	    		if(empty($onlyOneData)) {
	    			continue;
	    		}
	    		foreach ($onlyOneData as $val) {
	    			$keysValStr = array();
	    			foreach ($keysList as $key) {
	    				$keysValStr[] = strval($val[$key]);
	    			}
	    			$keysValStr = implode('_',$keysValStr);

	    			if(isset($tempData[$keysValStr])) {
	    				if($keyType == 'merge') {
	    					$tempData[$keysValStr] = array_merge($tempData[$keysValStr],$val);
	    				} else {
	    					$tempData[$keysValStr][] = $val;
	    				}
	    			} else {
	    				if($keyType == 'merge') {
	    					$tempData[$keysValStr] = $val;
	    				} else {
	    					$tempData[$keysValStr] = array();
	    					$tempData[$keysValStr][] = $val;
	    				}
	    			}
	    		}
	    	}
    	}

    	// 数据运算处理
		if(isset($opt['calData'])) {
			foreach ($tempData as $keysValStr => $v) {
				foreach ($opt['calData'] as $c) {
					$this->_setExecVal($c);
					$c['field'] = isset($c['field']) ? $c['field'] : false;
					$this->_execVal($tempData[$keysValStr],$c['field'],isset($c['newField']) ? $c['newField'] : $c['field'],$c['valType'],$c['action'],$c['changeVal']);
				}
			}
		}

    	$tempData = array_values($tempData);

    	// 数据排序
    	if(isset($opt['sort'])) {
    		$opt['sortType'] = isset($opt['sortType']) ? $opt['sortType'] : 'date';
    		//$tempData = $this->quickSort($tempData,$opt['sort'],$opt['sortType']);
    		$tempData = $this->insertSort($tempData,$opt['sort'],$opt['sortType']);
    	}
    	return $tempData;
	}

	/**
	 * 二维数组插入排序
	 * @param  [type] $data     [description]
	 * @param  string $sort     [description]
	 * @param  string $sortType [description]
	 * @return [type]           [description]
	 */
	public function insertSort($data,$sort='post desc',$sortType='date') {
	    //区分 哪部分是已经排序好的
	    //哪部分是没有排序的
	    //找到其中一个需要排序的元素
	    //这个元素 就是从第二个元素开始，到最后一个元素都是这个需要排序的元素
	    //利用循环就可以标志出来
	    //i循环控制 每次需要插入的元素，一旦需要插入的元素控制好了，
	    //间接已经将数组分成了2部分，下标小于当前的（左边的），是排序好的序列
	    for($i=1, $len=count($data); $i<$len; $i++) {
	        //获得当前需要比较的元素值。
	        $tmp = $data[$i];
	        //内层循环控制 比较 并 插入
	        for($j=$i-1;$j>=0;$j--) {
	   		//$data[$i];//需要插入的元素; $data[$j];//需要比较的元素
	   			$res = $this->_compare($tmp,$data[$j],$sort,$sortType);
	            if(!$res) {
	                //发现插入的元素要小，交换位置
	                //将后边的元素与前面的元素互换
	                $data[$j+1] = $data[$j];
	                //将前面的数设置为 当前需要交换的数
	                $data[$j] = $tmp;
	            } else {
	                //如果碰到不需要移动的元素
	           //由于是已经排序好是数组，则前面的就不需要再次比较了。
	                break;
	            }
	        }
	    }
	    //将这个元素 插入到已经排序好的序列内。
	    //返回
	    return $data;
	}
	/**
	 * 快速排序
	 * @param  [type] $data     [description]
	 * @param  string $sort     [description]
	 * @param  string $sortType [description]
	 * @return [type]           [description]
	 */
	public function quickSort($data,$sort='post desc',$sortType='date') {
		$size = count($data);
		if($size > 1) {
			$k = $data[0];
			$x = array();
			$y = array();
			for($i=1;$i<$size;$i++) {
				$res = $this->_compare($data[$i],$k,$sort,$sortType);
				if($res) {
					$x[] = $data[$i];
				} else {
					$y[] = $data[$i];
				}
			}
			$x = $this->quickSort($data,$sort,$sortType);
			$y = $this->quickSort($data,$sort,$sortType);
			return array_merge($x,array($k),$y);
		} else {
			return $data;
		}
	}

	/**
	 * 比较值
	 * @param  [type] $k1       [description]
	 * @param  [type] $k2       [description]
	 * @param  [type] $sort     [description]
	 * @param  [type] $sortType [description]
	 * @return boolen
	 */
	protected function _compare($k1,$k2,$sort,$sortType) {
		list($field,$sort) = explode(' ',$sort);
		switch ($sortType) {
			case 'number':
			case 'int':
			case 'float':
				$res = $k1[$field] > $k2[$field];
				break;
			case 'date':
			case 'datetime':
			case 'time':
			default:
				$res = strtotime($k1[$field]) > strtotime($k2[$field]);
				break;
		}
		return strtolower($sort) == 'desc' ? !$res : $res;
	}

	/**
	 * 转换成下拉菜单需要的数据类型
	 * @param  [type] $data  [description]
	 * @param  [type] $field [description]
	 * @param  [type] $name  *:代表全部,其余是key字符
	 * @return [type]        [description]
	 */
	public function toSelectData($data,$field = 'id',$name = 'name') {
		$output = array();
		foreach ($data as $key => $val) {
			$f = $field == null ? $key : $val[$field];
			if($name == '*') {
				$output[$f] = $val;
			} else {
				$output[$f] = $val[$name];
			}
		}
		return $output;
	}

	/**
	* 当查询2个或以上游戏时，同一天如果一款游戏有数据，令一款游戏没有数据，则当天数据为0
	* @param  array $data  要处理的数据
	* @param  array $dataKey 要处理的字段
	* @return array处理过的数据
	*/
	function dataKey($data, $dataKey) {
		foreach($data as $key => $val) {
			foreach ($dataKey as $v) {
				if(!isset($val[$v])) {
					$data[$key][$v] = 0;
				}
				
			}
		}
		return $data;
	}
}

