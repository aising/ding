
    <style type="text/css">
        body, html {width: 100%;height: 100%;margin:0;font-family:"微软雅黑";}
        #allmap{width:100%;height:80%;}
        p{margin-left:5px; font-size:14px;}
        #mapListTxt{max-height: 200px;overflow: auto;}
        .mapListTxtP{margin-top:5px;margin-bottom:5px; padding: 5px; font-size:14px;background-color: #666;}
        .mapListTxtP a{color: #FFF}
        
        .mapa{color: #FFF}
        
    </style>
    <script type="text/javascript" src="http://api.map.baidu.com/api?type=quick&v=2.0&ak=8741310c4659f38a953c4a03603693df"></script>
    <script type="text/javascript" src="<?php echo Doo::conf()->global;?>js/jquery1.9.0.js"></script>

    <div id="allmap"></div>
    
    <p>当前区域店门列表:</p>
    <?php
    $locationArr = json_decode($data['jsonLocation']);
    if(empty($locationArr)){
        echo '<p style="color:red">你当前位置没有我们的门店，我们会尽快提供！</p>';
    }
    ?>
    <div id="mapListTxt"></div>
    

<script type="text/javascript">
    
    // 百度地图API功能    
    map = new BMap.Map("allmap");
    map.centerAndZoom(new BMap.Point(<?php echo $data['location'];?>), 15); //15是放大比率，越小距离越大
    
    // var data_info = [[116.417854,39.921988,"地址：北京市东城区王府井大街88号乐天银泰百货八层",'<a href="">aaa</a>'],
    //                  [116.406605,39.921585,"地址：北京市东城区东华门大街"],
    //                  [116.412222,39.912345,"地址：北京市东城区正义路甲5号"]
    //                 ];
    
    var myIcon = new BMap.Icon("<?php echo Doo::conf()->global;?>default/image/me.png", new BMap.Size(23, 25), {  
                        offset: new BMap.Size(10, 25), // 指定定位位置  
                        imageOffset: new BMap.Size(0, 0) // 设置图片偏移  
                    });  
    var markerMe = new BMap.Marker(new BMap.Point(<?php echo $data['location'];?>),{icon:myIcon});    
    map.addOverlay(markerMe);               // 将标注添加到地图中
    
    function locationHereAndMark(point){
        var point = new BMap.Point(parseFloat($(point).attr("x")),parseFloat($(point).attr("y")));
        map.panTo(point);
        map.setZoom(16);        
        // landmarker.setPosition(point);
        // map.addOverlay(landmarker);
    }

    var data_info = <?php echo $data['jsonLocation'];?>; //坐标列表
   
    var opts = {
                width : 250,     // 信息窗口宽度
                height: 80,     // 信息窗口高度
                title : "<b>详细详情</b>" , // 信息窗口标题
                enableMessage:true//设置允许信息窗发送短息
               };
    
    for(var i=0;i<data_info.length;i++){
		//验证店id和当前城市id是否一致
		//if(<?php echo $_SESSION['cityid']?> != data_info[i][4]){
		//	continue;
		//}
        var marker = new BMap.Marker(new BMap.Point(data_info[i][0],data_info[i][1]));  // 创建标注
        var label = new BMap.Marker(data_info[i][2],{offset:new BMap.Size(20,-10)}); //红点的标注文字
        marker.setIcon(label);
        // map.addOverlay(label);    

        var content = '进入门店：'+data_info[i][2]; //内容
        map.addOverlay(marker);               // 将标注添加到地图中
        addClickHandler(content,marker);

        //文字提示
        var mapListTxt = '<p class="mapListTxtP"><a  href="javascript:void(0)" onclick="locationHereAndMark(this)" x="'+data_info[i][0]+'" y="'+data_info[i][1]+'">'+data_info[i][2]+' '+data_info[i][3]+'</a></p>';
        addClickHandler(mapListTxt,marker);
        $('#mapListTxt').append(mapListTxt);
    }    
    
    function addClickHandler(content,marker){
        marker.addEventListener("click",function(e){
            openInfo(content,e)}
        );
        label.addEventListener("click",function(e){
            openInfo(content,e)}
        );
    }
    function openInfo(content,e){
        var p = e.target;
        var point = new BMap.Point(p.getPosition().lng, p.getPosition().lat);
        var infoWindow = new BMap.InfoWindow(content,opts);  // 创建信息窗口对象 
        map.openInfoWindow(infoWindow,point); //开启信息窗口
    }
</script>
