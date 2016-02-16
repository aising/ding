<!DOCTYPE html>
<html>  
<head>  
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />  
  <title>周边检索</title> 
  <style type="text/css">
    body{
      margin:0;
      height:100%;
      width:100%;
      position:absolute;
      font-size:12px;
    }
    #mapContainer{
      position: absolute;
      top:0;
      left: 0;
      right:0;
      bottom:0;
    }
  </style>
</head>  
<body>  
  <a href="http://yuntuapi.amap.com/datasearch/local?tableid=55d7f89fe4b04026a881e853&city=深圳&keywords=&limit=50&page=1&key=af0f87a55be9c3f482fef6cb8612a5bc">l连接</a.>
    <div id="mapContainer"></div>    
    <script type="text/javascript" src="http://webapi.amap.com/maps?v=1.3&key=af0f87a55be9c3f482fef6cb8612a5bc "></script>
  <script type="text/javascript">
    var marker = [];
    var windowsArr = [];
    //基本地图加载
    var map = new AMap.Map("mapContainer",{ 
      resizeEnable: true,
          view: new AMap.View2D({
            zoom:12 //地图显示的缩放级别
        })});
      cloudSearch();  
    //周边检索函数     
    function cloudSearch() {
      map.clearMap();
        var arr = new Array();   
        var center = new AMap.LngLat(114.001007,22.605137);  
        var search;
        var searchOptions = {
            keywords:'公园',
            orderBy:'_id:ASC'
        };
        //加载CloudDataSearch服务插件
        AMap.service(["AMap.CloudDataSearch"], function() {       
            search = new AMap.CloudDataSearch('55d7f89fe4b04026a881e853 ', searchOptions); //构造云数据检索类
            //周边检索
            search.searchNearBy(center, 10000, function(status, result){
              if(status === 'complete' && result.info === 'OK'){
                cloudSearch_CallBack(result);
              }else{
                cloudSearch_CallBack(result);
              }
            });
        });
    }
    //添加marker和infowindow   
    function addmarker(i, d) {
        var lngX = d._location.getLng();
        var latY = d._location.getLat();
        var markerOption = {
            map:map,
            icon:"http://cache.amap.com/lbs/static/jsdemo003.png", 
            position:new AMap.LngLat(lngX, latY)  
        };            
        var mar = new AMap.Marker(markerOption);  
        marker.push(new AMap.LngLat(lngX, latY));
    
        var infoWindow = new AMap.InfoWindow({
            content:"<font face=\"微软雅黑\"color=\"#3366FF\">"+(i+1) + "."+ d._name +"</font><hr />地址："+ d._address + "<br />" + "创建时间：" + d._createtime+ "<br />" + "更新时间：" + d._updatetime,
            size:new AMap.Size(300, 0),
            autoMove:true,
            offset:new AMap.Pixel(0,-30)
        });  
        windowsArr.push(infoWindow);   
        var aa = function(){infoWindow.open(map, mar.getPosition());};  
        AMap.event.addListener(mar, "mouseover", aa);  
    }
    //回调函数 
    function cloudSearch_CallBack(data) { 
var resultStr="";
        var resultArr = data.datas;
        var resultNum = resultArr.length;  
for (var i = 0; i < resultNum; i++) {  
            resultStr += "<div id='divid" + (i+1) + "' onmouseover='openMarkerTipById1(" + i + ",this)' onmouseout='onmouseout_MarkerStyle(" + (i+1) + ",this)' style=\"font-size: 12px;cursor:pointer;padding:2px 0 4px 2px; border-bottom:1px solid #C1FFC1;\"><table><tr><td><h3><font face=\"微软雅黑\"color=\"#3366FF\">" + (i+1) + "." + resultArr[i]._name + "</font></h3>";
            resultStr += '地址：' + resultArr[i]._address + '<br/>类型：' + resultArr[i].type + '<br/>ID：' + resultArr[i]._id + "</td></tr></table></div>";
            addmarker(i, resultArr[i]);
        }
        map.setFitView();
    } 
    //回调函数
    function errorInfo(data) {
        resultStr = data.info;alert(1);
    }
    //根据id打开搜索结果点tip
    function openMarkerTipById1(pointid,thiss){    
        thiss.style.background='#CAE1FF';  
       windowsArr[pointid].open(map, marker[pointid]);      
    }  
    //鼠标移开后点样式恢复
    function onmouseout_MarkerStyle(pointid,thiss) {   
       thiss.style.background = "";  
    }
  </script>
</body>
</html>
