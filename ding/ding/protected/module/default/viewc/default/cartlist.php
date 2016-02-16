<?php

if( !isset($data['list']['data']) || empty($data['list']['data'])){
        header("Location:" . appurl('getMeOrder') );exit;
        echo '跳转中...<script>window.location.href="'.appurl('getMeOrder').'"; </script>';die;
    }
?>
 <div class="dd_details white_bg">
        <span>订单详情</span>
        <a style="font-size:16px; font-weight:600;">合计：<b id="cartPrice"><?php echo isset($data['list']['allPrice']) ? $data['list']['allPrice'] : '';?></b>元</a>
    </div>
    <form action="<?php echo appurl('addCart?type=submit');?>" method="post">
    <div class="dingdan_list white_bg">
    	<ul>
            <?php
            if( isset($data['list']['data']) && !empty($data['list']['data'])){

                foreach ($data['list']['data'] as $key => $value) {

            ?>
            	<li>
                	<div class="name" style=" float:left; ">
                        <p class="food_name"><?php echo $value['name'];?></p>
                        <div class="lajiao" style="width:55%;">
                            <?php
                                echo la($value['la']);
                            ?>
                        </div>
                        <p class="price" style="float:left;"><a class="yd_price">￥<?php echo $value['wxprice'];?>/份</a></p>
                    </div>
                    <div class="yuding" style="float:right;width:auto; margin-top:6px;">	
                        <div class="choose" >
                            <img id="cuts" src="<?php echo Doo::conf()->global?>default/image/sub.png" class="sub" price="<?php echo $value['wxprice'];?>" />
                            <input name="cart[<?php echo $key;?>][sum]" type="text" value="<?php echo $value['sum'];?>" style="width: 25px;padding: 10px 0px 0px 0px;"> 
                            <input name="cart[<?php echo $key;?>][id]" type="hidden" value="<?php echo $key;?>" >
                            <img id="plus" src="<?php echo Doo::conf()->global?>default/image/add.png" class="add" price="<?php echo $value['wxprice'];?>"/>

                        </div>
                        <span class="delCart" bookId="<?php echo $key?>" style=" padding-left:8px; width: 50px; background-color: #fff; height: 30px;margin: 0 auto; line-height: 30px;">删除</span>
            		</div>
                </li>
            <?php
                }
            }
            
            ?>
        </ul>
    </div>
    <div class="mar_t_44">
    <?php
            //外卖需要的地址
            $readonly = '';
            $note = '';
            if( isset($data['off']['waimai']) )
            {
                $readonly = ' readonly="readonly" ';
                if( trim($data['off']['waimai']['endtime'])!='' ){
                    $note = $data['off']['waimai']['name'].'外卖结束时间为：'.$data['off']['waimai']['endtime'];
                }else{
                     $note = $data['off']['waimai']['name'].'不支持外卖';
                }
            }
            ?>
            <div class="business baddr" style="height: 80px;">
                <div class="input-group">
                    <span class="title_name"><a style="color:#e63600; width:12%;">* </a>外卖地址:</span>
                    <input type="text" name="addr"  class="waimaiNote"  note="<?php echo $note;?>" <?php echo $readonly;?> id="addr" placeholder="仅为本大楼提供外卖服务"
                     value="<?php echo isset($data['userInfo']['data'][0]['addr']) && trim($data['userInfo']['data'][0]['addr'])!='' ? $data['userInfo']['data'][0]['addr'] : ''; ?>">
                    
                </div>
            </div>

            <?php
                // }
            ?>

            <div class="business btel" style="height: 165px; clear:both">
                <div class="input-group">
                    <span class="title_name"><a style="color:#e63600; width:12%;">* </a>电话</span>
                    <input type="tel" name="phone" id="phone" placeholder="请输入11位的电话号码"
                     value="<?php echo trim($data['userInfo']['data'][0]['phone'])!='' ? $data['userInfo']['data'][0]['phone'] : ''; ?>">
                     <?php
                     /*if( trim($data['userInfo']['data'][0]['phone'])=='' ){
                        echo '<a id="getCheckNo" class="select_goOn" style="background-color: #fff;">获取手机验证码</a>';
                     }*/
                     ?>
                    
                </div>
            </div>

            <?php
             /*if( trim($data['userInfo']['data'][0]['phone'])=='' ){
                echo '<div class="business check6No" style="display:none">
                        <div class="input-group">
                            <span class="title_name"><a style="color:#e63600; width:12%;">* </a>接收到的验证码</span>
                            <input type="tel" name="phoneCheckNo" id="phoneCheckNo" placeholder="请输入4位验证码"
                             value="" style="width:30%;float:left">
                             <a id="checkNo" class="select_goOn" style="    background-color: #fff; width: 25%; float: left;  padding-top: 0px;  margin-top: 0px;">验证此号码</a>
                        </div>
                    </div>';    
             }*/
            ?>

            
        
    </div>
    <div class="butt_bottom">
        <?php        
            $width = '';
            if( !isset($data['off']['waimai']) ){
                $width = 'width:30%';
                //$waimaiStr = '<input type="hidden" name="waimai" value="1">';
                echo $waimaiStr ='<input type="submit" name="waimai"  value="选择外卖" style="width:20%; font-size:12px; background-color: #fff;height: 30px; line-height: 30px;  color: #ea5515; text-align: center;float: left;    margin-top: 14px;">';
            }else{
				if( trim($data['off']['waimai']['endtime'])!='' ){
					$note = $data['off']['waimai']['name'].'外卖结束时间为：'.$data['off']['waimai']['endtime'];
				}else{
					$note = $data['off']['waimai']['name'].'不支持外卖';
				}
				echo $waimaiStr ='<span id="waimaiSpan" class="waimaiNote" note="'.$note.'" style="width:20%; font-size:12px; background-color: #fff;height: 30px; line-height: 30px;  color: #ea5515; text-align: center;float: left;    margin-top: 14px;">选择外卖'.'</span>';
			}
            if(isset( $data['off']['yuding']) && $data['off']['yuding'] == '1' ){
                //echo '<div class="select_goOn" style="font-size:12px;width:80%">'. $data['off']['name'] .' 超出预定时间了~</div>';
            }else{
        ?>
        <?php        
            }        
        ?>
        <input type="submit" class="account" value="去支付" style="background-color: #fff; width:30%">
        <div class="select_goOn" style="<?php echo $width;?>"><a href="javascript:window.history.go(-2)">继续选购</a></div>
        

    </div>
    </form>

