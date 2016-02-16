    <?php
    /*if(trim($data['qr'])!=''){
    ?>
    <div class="ewm_area">
        <p>最新订单的二维码：<br>凭此二维码到门店展示即可取餐</p>
        <div class="ewm">
            <?php echo $data['qr'];?>
        </div>
    </div>
    
    <?php
    }*/

    if(isset($_GET['orderid'])){

    }else{
        echo '<div class="dd_details white_bg">';
        if(isset($_GET['status2'])){
            echo '<p style="color:red">超过当天未付款的订单，系统将自动作废</p>';
        }else{
            echo '<p style="color:red">谢谢配合，支付成功，订单已经生成，凭单取号用餐，请在当天消费，过期无效</p>';
        }
    ?>
        
            <a href="<?php echo appurl('getMeOrder?status=SUCCESS');?>" style=" <?php if($data['orderType'] != 'SUCCESS'){echo 'font-weight: bold;font-size: 16px;';}?> float: left; margin-right: 20px;">已经支付订单</a>
            <a href="<?php echo appurl('getMeOrder?status=SUCCESS&status2=SUCCESS');?>" style="<?php if($data['orderType'] == 'SUCCESS'){echo 'font-weight: bold;font-size: 16px;';}?> float: left; margin-right: 20px;">未支付订单</a>
        </div>
    <?php
    }
    ?>
    <div class="dingdan_list white_bg">
                <?php

            foreach ($data['list'] as $key => $value) {                
                $view = '';
                $print = '';

				//列表
                if(!isset($_GET['orderid'])){
                    $view = '<div class="td_butt"> <a href="'. appurl('getMeOrderById?orderid='.$key) .'">详细</a></div>';
                }
                if( '未支付'== $value[0]['status'] || $value[0]['status'] == 'NOTPAY' ){
                    $print = '<div class="td_butt"> <a href="'. appurl('getMeOrderById?orderid='.$key) .'">继续支付</a></div>';
                    if(isset($_GET['orderid'])){
                        $print = '<div class="td_butt"> <a href="'. appurl('wxpay') .'">继续支付</a></div>';
                    }

                    if(date("Y-m-d",$value[0]['addtime']) != date("Y-m-d") ){
                        $print = '<div class="td_butt">已过期</div>';   
                    }
                }elseif($value[0]['status'] == '已经申请退款' ){
                    $view = '<div class="td_butt"> 退款中 </div>';
                    $print = '';
                }elseif($value[0]['status'] == 'SUCCESS' || $value[0]['status'] == '支付成功' ){
                    if(date("Y-m-d",$value[0]['addtime']) != date("Y-m-d") ){
                        $print = '<div class="td_butt"> 已过期</div>';
                    }elseif($value[0]['printStatus'] == 1 ){
                        $print = '<div class="td_butt"> 订单有效</div>';

                    }else{
                        $print = '<div class="td_butt"> <a href="'. appurl('orderPrint?orderid='.$key) .'">打印小票</a></div>';
                    }
                    
                    //if(isset($_GET['orderid'])){
                    //    $view = '<div class="td_butt"> <a href="'. appurl('orderCancel?orderid='.$key) .'">退款申请</a></div>';
                   // }
                }else{
                    $print = '<div class="td_butt"> '. $value[0]['status'] .'</div>';
                }
				//$print .= '<div class="td_butt"> <a href="'. appurl('orderPrint?orderid='.$key) .'">打印小票</a></div>';
                
                $waimai =  $value[0]['waimai'] ? '外卖单' : '自取单';
                
                echo '<ul style="margin-bottom: 18px;">';
                echo '<li>
                        <div class="" style=" float:left;width: 100%; ">
                            <p class="food_name" style="    font-size: 12px;">订单序号：'. $value[0]['oid'] . '&nbsp;&nbsp;&nbsp;&nbsp; 订单类型：<strong>'. $waimai . '</strong>  <br>                         
                        </p>
                        </div>
                    </li>
                    <li>
                        <div class="" style=" float:left;width: 100%; ">
                            <p style="float: left;margin-top: 10px;">
                            <span class="" style="color:red" ><b>总价钱：￥'.  $value[0]['allPrice'] . '</b></span>
                            <span class="" style="color:red" ><b>总数量：'.  count($value) . '</b></span>
                            '.$view.'
                            '.$print.'
                            <p>
                        </div>
                    </li>';
                foreach ($value as $vkey => $vvalue) {
            ?>
            <li>
                <div class="" style=" float:left;width: 30%;">
                    <p class="food_name"><?php echo $vvalue['title'];?></p>
                    <div class="lajiao" style="width:55%;"></div>
                    <!-- <p class="price" style="float:left;"><a class="yd_price">￥<?php echo $vvalue['wxprice'];?>/份</a></p> -->
                </div>
                <div class="dingdan_num_price"> ￥<?php echo $vvalue['countPrice'];?></div>
                <div class="dingdan_num"><?php echo $vvalue['sum'];?></div>
            </li>
            
            <?php
                }
				echo '<li>
						<div class="" style=" float:right;width: 100%;">
							<p class=""style="float: right;">订单时间：'.date("Y-m-d H:i:s",$vvalue['addtime']).'</p>
						</div>
					</li>';
                echo '</ul>';
            }
            echo $data['total'];
            ?>

    </div>


