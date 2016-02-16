<div class="food_picture">
    	<img src="<?php echo Doo::conf()->APP_URL . $data['book']['img']?>" />
    </div>
    <div class="food_intro">
    	<div class="name">
            <p class="food_name"><?php echo $data['book']['name']?></p>
            <div class="lajiao">
                <?php
                    echo la($data['book']['la']);
                ?>
            </div>
        </div>
		
		<?php
							//门市价 》微信价 则删除线
							$msclass = '';
							$wxclass = '';
							if($data['book']['price'] >= $data['book']['wxprice']){
								$msclass = 'ms_price';
							}else{
								//$wxclass = 'ms_price';
							}
							?>
	<?php
            //超出预定结束时间 则删除线
            $msclass = '';
            $wxclass = '';
            if($data['book']['timeout'] ){
                    $wxclass = 'ms_price';
            }else{
                    $msclass = 'ms_price';
            }
        ?>						
        <div class="price02" >
            <p class="price">价格：<a class="<?php echo $msclass;?>">￥<?php echo $data['book']['price']?></a></p>
            <p class="price">微信价：<a class="yd_price <?php echo $wxclass;?>">￥<?php echo $data['book']['wxpriceOrg']?></a></p>
        </div>        
    </div>
    <form action="<?php echo appurl('addCart');?>" method="post">
    <div class="intro">
        <p><?php echo $data['book']['descript']?></p>
        <?php echo trim($data['book']['endSaleTime']) == 0 ? '<p>不售卖<p>' : '<p>售卖结束时间：'.$data['book']['endSaleTime'].'</p>'?></p>
        <div class="yuding">	
        	<p>预定</p>
            <div class="choose">
                <img id="cuts" src="<?php echo Doo::conf()->global?>default/image/sub.png" class="sub" />
                <input name="sum" id="sum" type="text" value="1" style="width: 16px;padding: 10px 0px 0px 10px;"> 
                <input name="id" type="hidden" value="<?php echo intval($_GET['id']);?>" >
                <img id="plus" src="<?php echo Doo::conf()->global?>default/image/add.png" class="add"/>
            </div>
        </div>
    </div>
    
    <div class="butt_bottom">
        <input type="submit" class="account" value="放入购物车" style="background-color: #fff;">
        <div class="select_goOn"><a href="javascript:window.history.go(-1)">返回选购</a></div>

        <?php
        //if( trim($data['off']) == '' ){
        /*?>
            <input type="submit" class="account" value="选购" style="background-color: #fff;">
            <div class="select_goOn"><a href="javascript:window.history.go(-1)">继续选购</a></div>
        <?php
        }else{
            echo '<div class="select_goOn" style="font-size:10px;width:80%">' . $data['off'] . '
             预定时间为：'.$data['book']['opentime'] . ' - ' . $data['book']['endtime'] . '</div>';
        }*/
        ?>
        
    </div>
    </form>
