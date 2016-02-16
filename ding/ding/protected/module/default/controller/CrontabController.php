<?php
header("Content-type: text/html; charset=utf-8");
Doo::loadController('MainController');

/**
* 调度
*/
// */30 * * * * curl http://www.wstreet.cn/weixin/ding/ding/public/index.php/sendBookSUm
class CrontabController extends MainController {
    
    //配置key
    private $_configKey = 'settingConfig';
    //send key
    private $_sendKey = 'bookSMSSendKey';

    public function init() {
        Doo::loadClassAt('SmsApi','default');
    }

    public function sendBookSUm(){

        $fileRun = '/data/wstreet.cn/web/weixin/ding/ding/protected/module/default/cache/crontab-run'.date("Y-m-d").'.php';
        file_put_contents($fileRun,'1',FILE_APPEND );

        $configKey = 'settingConfig';
        $infoCache = DOO::cache('php')->get($configKey);
        $info = json_decode($infoCache,true);

        if(!isset($info['peiSwitch']) || $info['peiSwitch']!='1'){
             die('没开');
        }

        $list = DBproxy::getProcedure('Manage')->setDimension(2)->getPeiSongList();

        foreach ($list as $key => $lvalue) {
            echo $lvalue['cityid'].$lvalue['shopname'].'<br>';
            
            $info = DBproxy::getProcedure('Manage')->setDimension(2)->getBookSum($lvalue['cityid']);

            //所有菜品
            foreach ($info as $key => $value) 
            {
                $sum = $value['peiSongSum']+$value['booksum'];
                $strArr[$value['endSaleTime']][] = $value['title'].':'.$sum.'份';
            }
            //匹配结束时间进行推送
            foreach ($strArr as $key => $svalue) 
            {
                if($key==0){continue;}
                $typeTime = $key==0 ? '00:00' : $key;
                $sendTime = strtotime(date($typeTime))+3600*24;
                $sendKey = date("Y-m-d H:i:s",$sendTime).'-'.$lvalue['cityid'].$lvalue['shopname'];
                //当前时间大于 菜品分类的结束时间则进行发送
                if( $sendTime < time())
                {
                    $str = date("Ymd").$lvalue['shopname'].'配送信息:';
                    $str .=implode(',', $svalue);
                    echo $typeTime.'>>'.date("Y-m-d H:i:s",$sendTime).'------------';
                    //发送标识限制
                    //$sendCache = DOO::cache('php')->flush($sendKey);
                    $sendCache = DOO::cache('php')->get($sendKey);

                    if($sendCache){
                        echo '今天的已经发送<br>';continue;
                    }
                    
                    if(empty($info) ){
                        echo '昨天没预定<br>';continue;
                    }
                    //号码循环发送
                    if( !empty($info) )
                    {
                        // unset($phone);
                        $phone[] = $lvalue['shopNamePhone'];
                        $phone[] = $lvalue['peisongPhone'];                        
                        foreach ($phone as $key => $pvalue) {
                            if(trim($pvalue) == ''){
                                echo '发送的号码为空';die;    
                            }
                            echo $pvalue;
                            $this->sendSMS($pvalue,$str);                            
                            echo '<hr>';
                        }
                    }
                    DOO::cache('php')->set($sendKey,1,$this->exptime());
                    $file = '/data/wstreet.cn/web/weixin/ding/ding/protected/module/default/cache/crontab-'.date("Y-m-d").'.php';
                    file_put_contents($file,date('Y-m-d H:i:s',time()).$sendKey.implode(',', $phone).$str."\r\n",FILE_APPEND );
                }
            }
        }
    }
    //第二天2点失效
    private function exptime(){
        return strtotime('02:00')+24*60*60 - time();
    }

    private function sendSMS($phone,$str){
        $len = 1600;
        $clapi  = new SmsApi();
        $result[1] = 0;
        //短信字数限制 每次发送不超500字,区别单条还是多条
        $allLen = mb_strlen ($str,'utf-8');
        if($allLen > $len)
        {
            $c = ceil($allLen/$len);
            for ($i=0; $i < $c; $i++) 
            { 
                $smsCount = $i;
                $smsStr ='<'.++$smsCount.'>';
                $smsStr .= mb_substr($str, $i*$len,$len,'utf-8');
                // echo $smsStr;
                $result = $clapi->sendSMS($phone, $smsStr,'true');
                $result = $clapi->execResult($result);                
                if($result[1] == 0 )
                {
                    echo '<br>发送成功';
                }else{
                    echo "<br>发送失败{$result[1]} ";
                }
                Doo::logger()->info('time:'.date("Y-m-d H:i:s",time())."\tip:".getIP()."\tresult:".$result[1]."\tphone:".$phone.'\tsmsStr:'.$smsStr."\tstr:".$str,'SMS');
            }
        }else
        {
            $result = $clapi->sendSMS($phone, $str,'true');
            $result = $clapi->execResult($result);            
            if($result[1] == 0 )
            {
                echo '<br>发送成功';
            }else{
                echo "<br>发送失败{$result[1]}";
            }
            Doo::logger()->info('time:'.date("Y-m-d H:i:s",time())."\tip:".getIP()."\tresult:".$result[1]."\tphone:".$phone."\tstr:".$str,'SMS');
        }        
    }

}


