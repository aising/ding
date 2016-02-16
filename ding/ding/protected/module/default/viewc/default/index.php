 <!--左边tab_start-->
        <div class="menu_tab">
        <ul>
                <!-- <li class="menu_li selected">今日特价<img src="<?php echo Doo::conf()->global?>default/image/tejia.png" class="teJia" /></li> -->
            <!-- <li class="menu_li">本店招牌<div class="num">2</div></li>
            <li class="menu_li ">主食<div class="num">2</div></li>
            <li class="menu_li ">冷饮<div class="num">2</div></li> -->
            <?php
            
            $urlParams = '';
            $urlParams .= isset($_SESSION['typeid']) ? '&typeid='.$_SESSION['typeid'] : '';
            $urlParams .= isset($_SESSION['cityid']) ? '&city='.$_SESSION['cityid'] : '';
            foreach ($data['subType'] as $tkey => $tvalue) {
                // D($tvalue);
                $class= isset($_GET['categoryid'])&&trim($_GET['categoryid']) == $tvalue['id'] ? 'selected' : '';
                echo '<li class="menu_li  '. $class .'"><a href="'.appurl('index?categoryid='.$tvalue['id']).$urlParams.'">' . $tvalue['name'] . '</a><!--<div class="num"></div>--></li>';
            }
            ?>
        </ul>
    </div>
    <!--左边tab_end-->
    <!--右边菜单start-->
        <div class="food_list">
        <p style="padding:10px;">
            <?php
                echo $_SESSION['shopname'].'&nbsp&nbsp&nbsp&nbsp<a style="display:inline;padding:10px;    color: #FF0000;" href="'.appurl('mapList?type='.$_SESSION['typeid'].'&city='.$_SESSION['cityid'].'&removeShopCache=1').'">更换店门</a>';
            ?>
        </p>

        <div class="total_title">
            <?php
            $sum = 0;
            if( isset($data['list']['data']) && !empty($data['list']['data']) ){
                foreach ($data['list']['data'] as $key => $value) {
                    $sum+=$value['sum'];
                }
            }
            ?>

                <span>购物车：<a><?php echo $sum; ?>份</a></span>&nbsp;&nbsp;&nbsp;&nbsp;合计：<span><a>￥<?php echo isset($data['list']['allPrice']) ? $data['list']['allPrice'] : '0'; ?></a></span>
        </div>
        <ul>
            <?php
            foreach ($data['book'] as $bkey => $bvalue) {
            ?>
                <li>
                    <a href="<?php echo appurl('show?id=' . intval($bvalue['id']));?>">
                        <img src="<?php echo Doo::conf()->global?>default/image/href.png" style=" display:block; position:absolute; top:42px;right:5px; width:10px;"/>
                        <div class="pic">
                            <?php
                            /*if(isset($data['list']['data'][$bvalue['id']])){
                                echo '<div class="food_num">'.$data['list']['data'][$bvalue['id']]['sum'].'</div>';
                            }
                              */
                            ?>

                            <div class="food_pic">
                                <img src="<?php echo Doo::conf()->APP_URL?><?php echo $bvalue['img'];?>"/>
                            </div>
                        </div>
                       <div class="food_msg">
                            <p class="food_name"><?php echo $bvalue['name'];?></p>
                            <div class="lajiao">
                                <?php
                                    echo la($bvalue['la']);
                                ?>
                            </div>
                            <?php
                                //超出预定结束时间 则删除线
                                $msclass = '';
                                $wxclass = '';
                                if($bvalue['timeout'] ){
                                        $wxclass = 'ms_price';
                                }else{
                                        $msclass = 'ms_price';
                                }
                            ?>
                            <div style="clear:both"><p class="price">价格：<a class="<?php echo $msclass;?>">￥<?php echo $bvalue['price'];?></a></p>
                            <p class="price">微信价：<a class="yd_price <?php echo $wxclass;?>">￥<?php echo $bvalue['wxpriceOrg'];?></a></p>
                            </div>
                        </div>
                        <div class="food_msg">
                            <div style="clear:both">
                                <?php
                                if(trim($bvalue['waimaiEndtime'])!=''  )
                                {
                                    echo '<p class="price">外卖结束时间：当天'.$bvalue['waimaiEndtime'].'</p>';
                                }
                                ?>
                                <?php
                                if(trim($bvalue['opentime'])!='' && trim($bvalue['endtime'])!='' )
                                {
                                    
                                    if(trim($bvalue['opentimeDay']) == 0){
                                        $waimai = '当天:';
                                    }else{
                                        $waimai = '前一天:';
                                    }
                                    echo '<p class="price">微信价时间：'.$waimai .$bvalue['opentime'].' - 当天:'.$bvalue['endtime'].'</p>';
                                
                                }
                                ?>
                            </div>
                         </div>
                    </a>
                </li>

            <?php
            }
            ?>
        </ul>
    </div>
    <!--右边菜单end-->
    <?php 
        if( $sum > 0 ){

    ?>
<div class="" style="height:38px;clear:both"></div>
    <div class="butt_bottom" style="height:38px">
        <div class="select_goOn" style="width: 100%;margin-top: 5px;">
            <a href="<?php echo appurl('cartList')?>">已选列表</a>
        </div>
    </div>
    <?php 
        }
    ?>
