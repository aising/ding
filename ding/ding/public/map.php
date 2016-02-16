<?php
$url = 'http://yuntuapi.amap.com/datasearch/around?key=af0f87a55be9c3f482fef6cb8612a5bc&extensions=base&language=en&enc=utf-8&output=jsonp&keywords=&sortrule=_id:1&tableid=55d7ff08e4b04026a881e9f9&center=114.067408,22.572998&radius=10000'; //&callback=jsonp_868011_
$mapInfo = file_get_contents($url);
$infoArr =(json_decode($mapInfo,true));
if($infoArr['count'] < 1 || empty($infoArr['datas'])){
		exit('附近没数据');
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no, width=device-width">
    <title>周边检索</title>
    <link rel="stylesheet" href="http://cache.amap.com/lbs/static/main.css"/>
    <script type="text/javascript"
            src="http://webapi.amap.com/maps?v=1.3&key=af0f87a55be9c3f482fef6cb8612a5bc"></script>
</head>
<body style='font-size:13px'>
<div id="mapContainer"></div>
<script type="text/javascript">
    var marker = [];
    var windowsArr = [];
    //基本地图加载
    var map = new AMap.Map("mapContainer", {
        resizeEnable: true,
        zoom: 12 //地图显示的缩放级别
    });
    cloudSearch();
    //周边检索函数
    function cloudSearch() {
		cloudSearch_CallBack(<?php echo json_encode($infoArr['datas']);?>);
		return true;
		
        map.clearMap();
        var arr = new Array();
        var center = [114.067408,22.572998]; //用户当前坐标
        var search;
        var searchOptions = {
            //keywords: '公园',
            keywords: '',
            orderBy: '_id:ASC'
        };
        //加载CloudDataSearch服务插件
        AMap.service(["AMap.CloudDataSearch"], function() {
            search = new AMap.CloudDataSearch('55d7ff08e4b04026a881e9f9', searchOptions); //构造云数据检索类
            //周边检索
            search.searchNearBy(center, 10000, function(status, result) {
                console.info(result);
                if (status === 'complete' && result.info === 'OK') {
                    cloudSearch_CallBack(result);
                } else {
                    cloudSearch_CallBack(result);
                }
            });
        });
    }
    //添加marker和infowindow
    function addmarker(i, d) {
		var xy = d._location.split(",");		
        var lngX = xy[0];//d._location.getLng();
        var latY = xy[1];//d._location.getLat();
        var markerOption = {
            map: map,
            icon: "http://api.amap.com/Public/images/js/yun_marker.png",
            position: [lngX, latY]
        };
        var mar = new AMap.Marker(markerOption);
        marker.push([lngX, latY]);

        var photo=[];
        if(d._image[0]){//如果有上传的图片
            photo=['<img width=240 height=100 src="'+d._image[0]._preurl+'"><br>'];
        }
        var infoWindow = new AMap.InfoWindow({
            //content: "<font face=\"微软雅黑\"color=\"#3366FF\">" + (i + 1) + "." + d._name + "</font><hr />"+photo.join("")+"地址：" + d._address + "<br />" + "创建时间：" + d._createtime + "<br />" + "更新时间：" + d._updatetime,
			content: "<font face=\"微软雅黑\"color=\"#3366FF\">" + '城市id:' + d.cityid + d._name + "</font><hr />"+photo.join("")+"地址：" + d._address + "<br />" ,
            size: new AMap.Size(0, 0),
            autoMove: true,
            offset: new AMap.Pixel(0, -30)
        });
        windowsArr.push(infoWindow);
        var aa = function() {
            infoWindow.open(map, mar.getPosition());
        };
        mar.on( "click", aa);
		//mar.on( "mouseover", aa);
    }
    //回调函数
    function cloudSearch_CallBack(data) {	
        var resultStr = "";
        var resultArr = data;//data.datas;
        var resultNum = resultArr.length;
        for (var i = 0; i < resultNum; i++) {
            resultStr += "<div id='divid" + (i + 1) + "' onmouseover='openMarkerTipById1(" + i + ",this)' onmouseout='onmouseout_MarkerStyle(" + (i + 1) + ",this)' style=\"font-size: 12px;cursor:pointer;padding:2px 0 4px 2px; border-bottom:1px solid #C1FFC1;\"><table><tr><td><h3><font face=\"微软雅黑\"color=\"#3366FF\">" + (i + 1) + "." + resultArr[i]._name + "</font></h3>";
            resultStr += '地址：' + resultArr[i]._address + '<br/>类型：' + resultArr[i].type + '<br/>ID：' + resultArr[i]._id + "</td></tr></table></div>";
			
            addmarker(i, resultArr[i]);
        }
        map.setFitView();
    }
    //回调函数
    function errorInfo(data) {
        resultStr = data.info;
    }
    //根据id打开搜索结果点tip
    function openMarkerTipById1(pointid, thiss) {
        thiss.style.background = '#CAE1FF';
        windowsArr[pointid].open(map, marker[pointid]);
    }
    //鼠标移开后点样式恢复
    function onmouseout_MarkerStyle(pointid, thiss) {
        thiss.style.background = "";
    }
</script>
</body>
</html>