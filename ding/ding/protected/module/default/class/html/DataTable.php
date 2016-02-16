<?php
/**
 * 生成数据表格
 * 2014.3.23
 * @author hofa(firebellqq@163.com)
 Example
 
function age($row,$rowData,$val) {
	return $val + 10;
}

function table_button($row,$rowData) {
	$a = '<a href="/upd?id='.$rowData['id'].'">修改</a>';
	$a .= '  <a href="/del?id='.$rowData['id'].'">删除</a>';
	return $a;
}

$header = array(
	    'id'  => 'ID',
		'name' => '姓名',
		'sex'  => array('name'=>'性别'),
		'age'  => array('name'=>'年龄','callback'=>'age'),
		'age2'  => array('name'=>'默认年龄','defaultValue'=>30),
		'table_button_action' => array('name'=>'操作','callback'=>'table_button')
);

$data = array(
        0 => array('id'=>1,'name'=>'刘先生','sex'=>'男生','age'=>10),
        1 => array('id'=>2,'name'=>'杨先生','sex'=>'男生','age'=>12),
        2 => array('id'=>3,'name'=>'好生先','sex'=>'男生','age'=>16),
        3 => array('id'=>4,'name'=>'K要先','sex'=>'男生','age'=>60),
        4 => array('id'=>5,'name'=>'lala','sex'=>'男生','age2'=>90),
);

//adv first
$dt = new DataTable();
$dt->setTitle('Test DataTable')
   ->setAttr(array('border'=>'1px'))
   ->setHeader($header)
   ->setData($data)
   ->setDefaultValue('unkown')
   ->render();

$beforeHeader = array(0 => array('name'=>''),
	                  1 => array('name'=>'名姓','attr'=>'colspan="2"'),
	                  2 => array('name'=>'年龄','attr'=>'colspan="2"'),
	                  3 => ''
	                  );

//adv second
$dt = new DataTable();
$dt->setTitle('Test DataTable')
   ->setAttr(array('border'=>'1px'))
   ->setBeforeHeader($beforeHeader)
   ->setHeader($header)
   ->setData($data)
   ->setDefaultValue('unkown')
   ->setTopContent('<a href="xx">top</a>')
   ->setBottomContent('<a href="xx">bottom</a>')
   ->setFooter('page:1,2,3,4')
   ->render();


//simple
$dt = new DataTable();
$dt->setHeader($header)->setData($data)->render();


 */
class DataTable{

	protected $_attr = '';

	protected $_topContent = NULL;

	protected $_bottomContent = NULL;

	protected $_header = array();

	protected $_beforeHeader = array();

	protected $_data = array();

	protected $_footer = '';

	protected $_defaultValue = '';

	protected $_title = '';

	protected $_colspanCount = 0;

	protected $_isOutputData = false;

	protected $_dataDefinition = '';

	protected $_outputDataUrl = '/app/index.php/excel/exportExcel';

	public function setTitle($title) {
		$this->_title = $title;
		return $this;
	}

	public function setCaption($caption) {
		return $this->setTitle($caption);
	}

	public function setTopContent($content) {
		$this->_topContent = $content;
		return $this;
	}

	public function setBottomContent($content) {
		$this->_bottomContent = $content;
		return $this;
	}	

	public function setAttr($attr) {
		$this->_attr = is_array($attr) ? $this->attr($attr) : $attr;
		return $this;
	}

	public function setBeforeHeader($data) {
		$this->_beforeHeader = $data;
		return $this;
	}

	public function setHeader($data) {
		$this->_header = $data;
		$this->_colspanCount = count($data);
		return $this;
	}

	public function setData($data = array()) {
		$this->_data = $data;
		return $this;
	}

	public function attr($data) {
		$str = '';
		foreach ($data as $key => $value) {
			$str .= $key.'="'.$value.'" ';
		}
		return $str;
	}

	public function setFooter($footer) {
		$this->_footer = $footer;
		return $this;
	}

	public function setDefaultValue($value) {
		$this->_defaultValue = $value;
		return $this;
	}

	public function setUrl($url) {
		$this->_url = $url;
		return $this;
	}

	public function IsOutputData($val) {
		$this->_isOutputData = $val;
		return $this;
	}

	public function setDataDefinition($val) {
		$this->_dataDefinition = $val;
		return $this;
	}

	public function render($output = TRUE) {
		$result = $this->_topContent;
		if($this->_isOutputData) {
			$data = base64_encode(json_encode($this->_data));
			$header = base64_encode(json_encode($this->_header));
			$result .= '<form action="'.$this->_outputDataUrl.'" method="post" target="_blank">
				<input type="hidden" name="header" value='.$header.' />
				<input type="hidden" name="data" value='.$data.' />
				<button class="btn blue" type="submit" id="submit">导出数据</button>';
				if(!empty($this->_dataDefinition)) {
					$result .='<a style="padding-left:10px;" target="_blank" href="'.appUrl('analysis/dataDefinition'.$this->_dataDefinition).'"><input class="btn blue" type="button" value="数据定义"/></a>';
				}
			$result .= '</form>';			
		}
		$result .= '<table '.$this->_attr.'>'."\n";
			
		if(!empty($this->_title)) {
			$result .= '<caption>'.$this->_title.'</caption>'."\n";
		}

		// 生成表头
		$result .= '<thead>'."\n";

		// 前置表头
		if(!empty($this->_beforeHeader)) {
			$result .= '<tr>';
			foreach ($this->_beforeHeader as $key => $val) {
				$name = is_array($val) ? $val['name'] : $val;
				$attr = is_array($val) && isset($val['attr'])? $val['attr'] : ''; 
				$result .= '<th '.$attr.'>'.$name.'</th>'."\n";
			}
			$result .= '</tr>'."\n";
		}

		// 表头
		if(!empty($this->_header)) {
			$result .= '<tr>'."\n";
			foreach ($this->_header as $key => $val) {
				$name = is_array($val) ? $val['name'] : $val;
				$attr = is_array($val) && isset($val['headerAttr'])? $val['headerAttr'] : ''; 
				$result .= '<th '.$attr.'>'.$name.'</th>'."\n";
			}
			$result .= '</tr>'."\n";	
		}

		$result .= '</thead>'."\n";
		
		// 表数据
		$result .= '<tbody>'."\n";
		$i = 0;
		if(!empty($this->_header) && !empty($this->_data)) {
			foreach($this->_data as $key => $val) {
				$i++;
				$result .= '<tr>'."\n";
				foreach ($this->_header as $k => $v) {
					$callback = is_array($v) && isset($v['callback'])? $v['callback'] : '';
					$attr = is_array($v) && isset($v['bodyAttr'])? $v['bodyAttr'] : ''; 
					if(!empty($callback)) {
						$result .= '<td '.$attr.'>'.$callback($i,$val,isset($val[$k]) ? $val[$k] : '').'</td>'."\n";
					} else {
						$value = isset($val[$k]) ? $val[$k] : (isset($v['defaultValue']) ? $v['defaultValue'] : $this->_defaultValue);
						if($value == 'unkown') {
							$value = 0;
						}
						$result .= '<td '.$attr.'>'.$value.'</td>'."\n";	
					}
					
				}
				$result .= '</tr>'."\n";
			}
		} else {
			$result .= '<tr><td class="alert" colspan="'.count($this->_header).'">暂无数据</td></tr>';
		}

		$result .= '</tbody>'."\n";

		if(!empty($this->_footer)) {
			$result .= '<tfoot>'."\n";
			$result .= '<tr><td colspan="'.$this->_colspanCount.'">'.$this->_footer.'</td></tr>';
			$result .= '</tfoot>'."\n";
		}

		$result .= '</table>'."\n";
		$result .= $this->_bottomContent;
		if($output) {
			echo $result;
		}

		return $result;
	}

}