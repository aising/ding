<?php 
/*SELECT o.id AS  `oid` , i. * , SUM( i.sum ) 
FROM  `order` AS o,  `orderinfo` AS i
WHERE i.orderKeyId = o.id
AND o.status =  'success'
AND o.id
IN (

SELECT ii.orderKeyId
FROM orderinfo AS ii
)
AND FROM_UNIXTIME( o.`addtime` ,  '%Y%m%d' ) = CURDATE( ) 
GROUP BY i.bookid*/
class ExportExcelController extends DooController{

	/**
	* 导出数据
	*
	*/
	public function exportExcel() {
		$header = $_POST['header'];
		$d = $_POST['data'];
		$header = json_decode(base64_decode($header), true);
		$list = json_decode(base64_decode($d), true);
	
		$t = "序号";
		foreach ( $header as $val) {
			$name = is_array($val) ? $val['name'] : $val;
			$t .= ','.$name;
		}

		$data = [iconv('UTF-8', 'GBK', $t)];
	    foreach ($list as $key => $row) {
	    	$fields = ($key+1).",";
	    	foreach($header as $k => $val) {
	    		if(isset($row[$k])) {
	    			$fields .= $row[$k].",";	    			
	    		} else {
	    			$fields .= '0,';
	    		}
	    	}
	    	$fields =  substr($fields, 0, -1);
	        $cols = iconv('UTF-8', 'GBK', $fields);

	        array_push($data, $cols);
	    }
	    $csvString = implode("\n", $data);

	    header("Content-type:application/vnd.ms-excel");
    	header("content-Disposition:filename=".time().".csv ");

	    echo $csvString;
	}
}
