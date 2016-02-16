<?php
/**
 * 游戏分类
 * @author xinkq
 */
class Category{
    //游戏分类处理转成按父类排序的option或者数组
    static $cateVal = array();

    static function cateToArr($data = array(),$pid = 0,$level = 0 ,$type='html')
    {
        foreach ($data as $ckey => $cvalue) 
        {
            if($cvalue['parentid']==$pid){

                if($type == 'html'){
                    $l = str_repeat("&nbsp&nbsp&nbsp&nbsp",$level);    
                }else{
                    $l = str_repeat("&nbsp&nbsp&nbsp&nbsp",$level);
                }
                
                Category::$cateVal[$cvalue['id']]= array('name' => $l.$cvalue['name'],
                                                                    'parentid'=>$cvalue['parentid'],
                                                                    'child'=>$cvalue['child']);
                self::cateToArr($data,$cvalue['id'],$level+1,$type);
            }
        }
    }

    static public function cateToOption($id = 0,$child = true,$type = 'html',$data = array()){
        //所有分类转成按父类排序的数组
        if( empty($data)){
            $category = DBproxy::getProcedure('Manage')->setDimension(2)->getCategory();
        }else{
            $category = $data;
        }        
        self::cateToArr($category['data'],0,0,$type);

        //输出的数据类型
        $option = $type == 'html' ? '' : array();        

        foreach (self::$cateVal as $key => $value) {
            $selected = '';
            $attr = '';
            //有子类的时候不可以选上
            if($value['child']=='1' && $child){
                $attr = 'disabled="disabled"';
            }else{
                $attr = ' style="color:#000"';
            }            
            if($key == $id ){
                $selected = 'selected="selected"';
            }
            //输出的数据类型
            if($type == 'array'){
                $option[$key] = array('id'=>$key,'name'=>$value['name'],'parentid'=>$value['parentid']);
                //找子类
                self::findArrChild($category['data'],$key);
                Category::$arrchildids[] = $key;
                $option[$key]['arrchildid'] = implode(',',Category::$arrchildids);
                Category::$arrchildids = array();
                //找父类
                self::findArrParent($category['data'],$value['parentid']);
                $option[$key]['arrparentid'] = implode(',',Category::$arrparentids);
                Category::$arrparentids = array();
                //child
                $option[$key]['child'] = count($option[$key]['arrchildid'])==1 && $option[$key]['arrchildid']==$key ? 0 : 1;
            }else{
                $option.= '<option value="'.$key.'" '.$attr.$selected.' >'.$value['name'].'</option>';    
            }            
        }        
        return $option;
    }

    static $arrchildids = array();
    //查出所有父类,把当前id，去匹配全部分类,如果有分类的父类id为当前id则加入
    static public function findArrChild($data,$id){        
        foreach ($data as $key => $value) {
            if($value['parentid']==$id){
                Category::$arrchildids[] = $value['id'];
                self::findArrChild($data,$value['id']);
            }    
        }
    }

    static $arrparentids = array();
    //查出所有父类,把当前id，去匹配全部分类,如果有分类的父类id为当前id则加入
    static public function findArrParent($data,$pid){        
        foreach ($data as $key => $value) {
            if($value['id']==$pid){
                Category::$arrparentids[] = $value['id'];
                self::findArrParent($data,$value['parentid']);
            }    
        }
    }
	
}
