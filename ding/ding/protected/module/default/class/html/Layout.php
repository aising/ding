<?php
/**
 * 布局
 * 2014.3.23
 * @author hofa(firebellqq@163.com)
 */
class Layout {

	protected $_layoutData;

	protected $_displayData;

	public function __construct($layoutData,$displayData) {
		$this->_layoutData = $layoutData;
		$this->_displayData = $displayData;
	}

	public function render() {
		$result = '';
		foreach ($this->_layoutData as $k => $row) {
			$result .= '<div class="row-fluid">';
			foreach($row as $v) {
				
				if(is_array($v[0])) {
					$ar = '';
					foreach($v[0] as $v1) {
						$ar .= $this->_displayData[$v1];
					}
					$result .= '<div class="span'.$v[1].'">'.$ar.'</div>';
				} else {
					$result .= '<div class="span'.$v[1].'">'.$this->_displayData[$v[0]].'</div>';
				}
				
			}
			$result .= '</div>';
		}
		return $result;
	}
}