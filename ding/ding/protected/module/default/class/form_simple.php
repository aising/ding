<?php
/**
 * 动态表单类
 * @package class
 * @since 1.0.0
 * @version 1.0.0
 + build date 2015-06-03
 */
require_once(SYS_LIBS.'xml.php');
class form_simple {
	private $xml_config;
	private $xml_key;
	private $xml_path;
	
	/**
	 * 构造函数
	 * @param string $xml_key XML配置文件索引
	 * @param string $xml_path XML配置文件路径
	 */
	public function __construct($xml_key,$xml_path){
		$this->set_xmlconfig($xml_key,$xml_path);
	}
	
	public function set_xmlconfig($xml_key,$xml_path=''){
		if($xml_path!=''){
			$this->xml_path = $xml_path;
		}
		$this->xml_key = $xml_key;
		$this->load_xml_config($xml_key);
	}
	
	/**
	 * 生成表单内容
	 * @param  string $xml_text XML（保存到DB的XML）
	 * @return array
	 */
	public function get_form($xml_text){
	
		$forms = array();
	
		$nodes = $this->get_node_list($xml_text);
		$values = $this->get_form_values($nodes);
		
		$multis = array();
		
		foreach($this->xml_config as $k=>$config){
			//生成multi多项的特殊类型
			if($config['form_type']=='multi'){
				$childs = $config['form_ext']['multi'];
				$pnode = $this->get_parent_node($config['id']).'.'.$config['name'];
				if(!empty($config['form_ext']['ptag'])){
					$pnode .= '.'.$config['form_ext']['ptag'];
				}
                
				$pid = $config['id'];
				$m = $n = 1;
				$childs_values = array();
                $childs_count = count($childs);
				foreach($nodes as $x=>$y){
                    if($y['ptag'] != $pnode){continue;}
                    $childs_values[$m][$y['tag']] = $y['value'];
                    $n % $childs_count != 0 || $m++;
                    $n++;
				}
                $m == 1 || $m--;

                $forms[$config['id']]['pid'] = $config['pid'];
                $forms[$config['id']]['multi'] = $config['title'];
                $forms[$config['id']]['form_ext'] = $config['form_ext'];
            
				for($i=1;$i<=$m;$i++){
					foreach($childs as $x=>$y){
						$config = $y;
						if(isset($childs_values[$i][$y['name']])){
							$config['value'] = $childs_values[$i][$y['name']];
						}
						
						if(isset($config['value']) && !empty($config['decode'])){
							if(method_exists($this,$config['decode'])){
								$config['value'] = $this->$config['decode']($config['value']);
							}elseif(function_exists($config['decode'])){
								$config['value'] = $config['decode']($config['value']);
							}
						}
						
						$config['name'] = $this->xml_key.'['.$pid.']'.'['.$i.']'.'['.$y['id'].']';
						$form_item = $this->get_form_item($config);
						if(empty($form_item))continue;
						$forms[$pid]['child'][$i][$config['id']] = array(
								'title'=>$config['title']
								,'desc'=>$config['desc']
								,'item'=>$form_item
								,'name'=>$config['name']
								,'value'=>$config['value']
						);
					}
				}
				continue;
			}
			
			if(isset($values[$config['id']])){
				$config['value'] = $values[$config['id']];
			}
			
			if(!empty($config['decode'])){
				if(method_exists($this,$config['decode'])){
					$config['value'] = $this->$config['decode']($config['value']);
				}elseif(function_exists($config['decode'])){
					$config['value'] = $config['decode']($config['value']);
				}
			}
			
			$config['name'] = $this->xml_key.'['.$config['id'].']';
            if ( ($config['form_type'] === '' && $config['pid'] != '') || $config['form_type'] == 'muti') {
                $forms[$config['id']]['pid'] = $config['pid'];
                $forms[$config['id']]['fieldset'] = $config['title'];
                $forms[$config['id']]['form_ext'] = $config['form_ext'];
            } else {
                $form_item = $this->get_form_item($config);
                if(empty($form_item))continue;
                
                $forms[$config['id']] = array(
                            'title'=>$config['title']
                            ,'pid'=>$config['pid']
                            ,'desc'=>$config['desc']
                            ,'item'=>$form_item
                            ,'name'=>$config['name']
                            ,'value'=>$config['value']
                    );
            }
		}
        $tree = array();
        foreach ($forms as $id=>$item){
            if (isset($forms[$item['pid']])){
                $forms[$item['pid']]['child'][] = &$forms[$id];
            } else {
                $tree[] = &$forms[$id];
            }
        }
		return $tree;
	}
	
	/**
	 * 生成HTML表单
	 * @param array $forms 表单内容
	 * @return string
	 */
	public function get_form_html($forms){
		$html = '';
        foreach($forms as $form){
            if (isset($form['child'])) {
                $ext = $this->get_form_attr('', '', $form['form_ext']);
                if (isset($form['fieldset'])) {
                    $html .= '<div '.$ext.'><fieldset><legend>'.$form['fieldset'].'</legend>';
                } else {
                    if (isset($form['multi'])) {
                        $html .= '<table width="100%" '.$ext.'>';
                        foreach($form['child'] as $items){
                            $html .= '<tr>';
                            foreach($items as $item){
                                $html .= '<td width="136"><label>'.$item['title'].'：</label></td><td>'.$item['item'].'<span class="error">'.$item['desc'].'</span></td>';
                            }
                            $html .= '</tr>';
                        }
                        $html .= '</table>';
                    } else {
                        $html .= '<div '.$ext.'>';
                    }
                }
                $html .= $this->get_form_html($form['child']);
                $html .= '</div>';
            } else {
                $html .= '<table width="100%">';
                if (isset($form['title'])) {
                    $html .= '<tr><td width="136"><label>'.$form['title'].'：</label></td><td>'.$form['item'].'<span class="error">'.$form['desc'].'</span></td></tr>';
                }
                $html .= '</table>';
            }
        }
        return $html;
	}
	
	/**
	 * 表单校验
	 * @param  array $form_data 表单数据
	 * @return array
	 */
	public function checkForm($form_data){
		$errors = array();
		
		//foreach($form_data as $k=>$v){
		foreach($this->xml_config as $config){
			if(empty($config['form_type']))continue;
			$k = $config['id'];
			
			if($config['form_type']=='multi'&&isset($form_data[$k])){
				foreach($form_data[$k] as $vals){
					foreach($config['form_ext']['multi'] as $x=>$y){
						$cfg = $y;
						if(isset($vals[$y['id']])){
							$v = $vals[$y['id']];
							$errors = $this->check_item($v, $cfg);
							if(!empty($errors))return $errors;
						}
					}
				}
				continue;
			}
			
			$v = isset($form_data[$k]) ? $form_data[$k] : '';
			$errors = $this->check_item($v, $config);
			if(!empty($errors))return $errors;
		}
		return $errors;
	}
	
	/**
	 * 表单元素验证
	 * @param  $v 验证值
	 * @param  $config 表单元素配置
	 * @return array
	 */
	private function check_item($v,$config){
		$errors = array();
		
		$check = $config['check'];
		if(!empty($check)){
			if(!is_array($check)){
				$check = array($check);
			}
		
			foreach ($check as $funlist){
				if(strpos($funlist, '|')!==false){
					$funcs = explode('|', $funlist);
				}else{
					$funcs = array($funlist);
				}
					
				$isok = false;
				foreach($funcs as $func){
					$func = trim($func);
					if(($pos=strpos($func,':'))!==false){
						$exts = explode(',',substr($func,$pos+1));
						$func = substr($func,0,$pos);
							
						$isok = $this->$func($v,$exts);
					}else{
						$isok = $this->$func($v);
					}
		
					if($isok)break;
				}
					
				if(!$isok){
					$errors = array('id'=>$config['id'],'title'=>$config['title']);
					return $errors;
				}
			}
		}
			
		if($config['form_type']=='select'){
			if(!empty($config['data']) && !isset($config['data'][$v])){
				$errors = array('id'=>$config['id'],'title'=>$config['title']);
				return $errors;
			}
		}
			
		if($config['form_type']=='checkbox' && !empty($v)){
			if(!empty($config['data'])){
				foreach($v as $val){
					if(!isset($config['data'][$val])){
						$errors = array('id'=>$config['id'],'title'=>$config['title']);
						return $errors;
					}
				}
			}
		
		}
		return $errors;
	}
	
	/**
	 * 获取保存表单后的XML
	 * @param  array $form_data 表单内容
	 * @return XML
	 */
	public function get_xml($form_data){
		$errors = $this->checkForm($form_data);
		$result = array('status'=>0,'msg'=>'成功');
		if(!empty($errors)){
			$result = array('status'=>1,'msg'=>'[ '.$errors['title'].' ]输入不正确');
			return $result;
		}
		
		$xml_data = $this->xml_config;
		foreach($xml_data as $k=>$v){
			if(isset($form_data[$v['id']])){
				$xml_data[$k]['value'] = $form_data[$v['id']];
			}
		}
		$xml = $this->create_xml($xml_data);
		$result['xml'] = $xml;
		return $result;
	}
	
	/**
	 * 生成XML
	 * @param  array $xml_data XML内容
	 * @return string
	 */
	private function create_xml($xml_data){
		$xml =  '';
		$xml_config_old = $this->xml_config;
		
		$this->xml_config = $xml_data;
		$xml .= $this->create_xml_do(0);
		
		$this->xml_config = $xml_config_old;
		
		return $xml;
	}
	
	/**
	 * 递归生成XML节点
	 * @param  int $pid 根节点ID
	 * @return xml
	 */
	private function create_xml_do($pid=0){
		$xml =  '';
		$nodes = $this->get_xml_by_pid($pid,1);
		
		foreach($nodes as $k=>$v){
			$childs = $this->get_xml_by_pid($v['id'],1);
			
			
			//取属性
			$attrs = $this->get_xml_by_pid($v['id'],0);
			$attr = '';
			if(!empty($attrs)){
				foreach($attrs as $x=>$y){
					if(!empty($y['encode'])){
						if(method_exists($this,$y['encode'])){
							$y['value'] = $this->$y['encode']($y['value']);
						}elseif(function_exists($y['encode'])){
							$y['value'] = $y['encode']($y['value']);
						}
					}
					$attr .= ' '.$y['name'].'="'.$y['value'].'"';
				}
			}
			
			if($v['form_type']=='checkbox'){
				$v['value'] = is_array($v['value']) ? implode('|', $v['value']) : $v['value'];
				
				$val = $v['value'];
				if(is_array($val)){
					$val = implode('|', $val);
					if(!empty($y['encode'])){
						if(method_exists($this,$y['encode'])){
							$val = $this->$y['encode']($val);
						}elseif(function_exists($y['encode'])){
							$val = $y['encode']($val);
						}
					}
				}
				
			}elseif($v['form_type']=='multi'&&is_array($v['value'])){
				$value = '';
				$value .= "\n";
				
				$tag_type = isset($v['form_ext']['ptag_type']) ? $v['form_ext']['ptag_type'] : $v['type'];
				foreach($v['value'] as $vals){
					if(!empty($v['form_ext']['ptag'])){
						if($tag_type==1){
							//简单闭合
							$value .= '<'.$v['form_ext']['ptag'].'';
						}else{
							//对称闭合
							$value .= '<'.$v['form_ext']['ptag'].'>';
							$value .= "\n";
						}
					}
					foreach($v['form_ext']['multi'] as $x=>$y){
						$val = isset($vals[$y['id']]) ? $vals[$y['id']] : $y['value'];
						if(!empty($y['encode'])){
							if(method_exists($this,$y['encode'])){
								$val = $this->$y['encode']($val);
							}elseif(function_exists($y['encode'])){
								$val = $y['encode']($val);
							}
						}
						
						if($tag_type==1){
							$value .= ' '.$y['name'].'="'.$val.'"';
						}else{
							$value .= '<'.$y['name'].'>';
							$value .= $val;
							$value .= '</'.$y['name'].'>';
							$value .= "\n";
						}
					}
					if(!empty($v['form_ext']['ptag'])){
                        $value .= ($tag_type==1 ? '/>' : '</'.$v['form_ext']['ptag'].'>')."\n"; //简单闭合|对称闭合
					}
				}
				$v['value'] = $value;
			}
			
			if(!empty($childs)){
				$xml .= '<'.$v['name'].$attr.'>';
				$xml .= "\n";
				$xml .= $this->create_xml_do($v['id']);
				$xml .= '</'.$v['name'].'>';
				$xml .= "\n";
			}else{
				if(!empty($v['encode'])){
					if(method_exists($this,$v['encode'])){
						$v['value'] = $this->$v['encode']($v['value']);
					}elseif(function_exists($v['encode'])){
						$v['value'] = $v['encode']($v['value']);
					}
				}
				
				if($v['type']==1){
					//简单闭合
					$xml .= '<'.$v['name'].$attr.'/>';
					$xml .= "\n";
				}else{
					//对称闭合
					$xml .= '<'.$v['name'].$attr.'>'.$v['value'].'</'.$v['name'].'>';
					$xml .= "\n";
				}
			}
		}
		
		return $xml;
	}
	
	/**
	 * 载入表单XML配置
	 * @param  string $xml_key 表单配置索引
	 */
	private function load_xml_config($xml_key){
		$config_file = $this->xml_path.$xml_key.'.php';
		if(is_file($config_file)){
			$this->xml_config = include $config_file;
		}else{
			exit('xml config file not exists');
		}
	}
	
	/**
	 * 解析XML
	 * @param  string $xml XML
	 * @return array
	 */
	private function parse_xml($xml){
		$array = xml::gamexml2array($xml);
		return $array;
	}
	
	/**
	 * 按节点ID取配置
	 * @param int $id 节点ID
	 * @return array
	 */
	private function get_xml_node($id){
		$node_config = array();
		foreach($this->xml_config as $k=>$v){
			if($v['id'] == $id){
				$node_config = $v;
				break;
			}
		}
		return $node_config;
	}
	
	/**
	 * 按父节点ID取字节点
	 * @param  int $pid 父节点ID
	 * @param  boolean $istag 是否节点1是,0否(属性)
	 * @return array
	 */
	private function get_xml_by_pid($pid,$istag=1){
		$node_config = array();
		foreach($this->xml_config as $k=>$v){
			if($v['pid'] != $pid)continue;
			if(($istag && in_array($v['type'],array(0,1))) || (!$istag && $v['type']==2)){
				$node_config[] = $v;
			}
		}
		return $node_config;
	}
	
	/**
	 * 按XML（保存到DB的XML）取节点
	 * @param string  $xml_text XML
	 * @return array
	 */
	public function get_node_list($xml_text){
		$xml_arr = $this->parse_xml($xml_text);
		$ptag = $xml_arr['tag'];
		$childs = isset($xml_arr['child']) ? $xml_arr['child'] : null;
		$attributes = isset($xml_arr['attributes']) ? $xml_arr['attributes'] : null;
		$notes = array();
		
		if(is_array($attributes)){
			foreach($attributes as $kk=>$vv){
				$this->get_nodes(array('tag'=>$kk,'value'=>$vv), $ptag, $notes);
			}
		}

		if(is_array($childs)){
			foreach($childs as $k=>$v){
				$this->get_nodes($v,$ptag,$notes);
                /**
                 * update 2013-11-05 14:00 修复属性值无法还原的BUG
                 * @author yeguanghao
                 */
                /*if (isset($v['attributes'])) {
                    foreach($v['attributes'] as $kk=>$vv){
                        $this->get_nodes(array('tag'=>$kk,'value'=>$vv), $ptag.'.'.$v['tag'], $notes);
                    }
                }*/
			}
		}
        
		return $notes;
	}
	
	/**
	 * 递归取XML所有节点
	 * @param array $xml_arr
	 * @param string $ptag
	 * @param array &$notes
	 */
	private function get_nodes($xml_arr,$ptag,&$notes){
		
		if(isset($xml_arr['attributes'])){
			$ptagattr = $ptag.'.'.$xml_arr['tag'];
			foreach($xml_arr['attributes'] as $k=>$v){
				$notes[] = array('ptag'=>$ptagattr,'tag'=>$k,'value'=>$v);
			}
		}
		
		if(isset($xml_arr['child'])){
			$ptag .= '.'.$xml_arr['tag'];
			foreach($xml_arr['child'] as $k=>$v){
				$this->get_nodes($v,$ptag,$notes);
			}
		}else{
			$notes[] = array('ptag'=>$ptag,'tag'=>$xml_arr['tag'],'value'=>$xml_arr['value']);
		}
	}
	
	/**
	 * 取父节点完整名称
	 * @param int $id 节点ID
	 * @return string
	 */
	private function get_parent_node($id){
		$configs = $this->get_xml_node($id);
		$pid = $configs['pid'];
		$parents = '';
		while (true){
			$configs = $this->get_xml_node($pid);
			if(empty($configs))break;
			$pid = $configs['pid'];
			$parents = $configs['name'].'.'.$parents;
		}
		$parents = substr($parents,0,-1);
		
		return $parents;
	}
	
	/**
	 * 取各节点的值
	 * @param array $notes 各节点数据
	 * @return array
	 */
	private function get_form_values($notes){
		$values = array();
		$used = array();
		foreach($notes as $k=>$v){
			foreach($this->xml_config as $x=>$config){
				$id = $config['id'];
				if(isset($values[$id]))continue;
				
				$ptag = $this->get_parent_node($id);
				if($config['name'] == $v['tag'] && $ptag == $v['ptag']){
					$values[$id] = $v['value'];
					break;
				}
			}
		}
		return $values;
	}
	
	/**
	 * 生成表单元素
	 * @param  array $config 表单元素配置
	 * @return string
	 */
	private function get_form_item($config){
		$form_type = strtolower($config['form_type']);
		$item = '';
		switch($form_type){
			case 'text':
				$item = '<input name="'.$config['name'].'" type="'.$config['form_type'].'" ';
                $item .= $this->get_form_attr($config['name'], 'text-input', $config['form_ext']);
				if(isset($config['value'])){
					$item .= ' value="'.$config['value'].'"';
				}
				
				$item .= ' />';
				break;
			case 'textarea':
				$item = '<textarea name="'.$config['name'].'" ';
                $item .= $this->get_form_attr($config['name'], 'text-input', $config['form_ext']);
				
				$item .= '>';
				
				if(isset($config['value'])){
					$item .= $config['value'];
				}
				
				$item .= '</textarea>';
				break;
			case 'select':
				$item = '<select name="'.$config['name'].'" ';
                $item .= $this->get_form_attr($config['name'], 'select', $config['form_ext']);
                $item .= '>';
				if(!empty($config['data'])){
					$key_selected = '';
					if(isset($config['value'])){
						$key_selected = $config['value'];
					}
					foreach($config['data'] as $k=>$v){
						$item .= '<option value="'.$k.'"'.($key_selected==$k ? ' selected':'').'>'.$v.'</option>';
					}
				}
				$item .= '</select>';
				break;
			case 'checkbox':
				$item = '';
				if(!empty($config['data'])){
					$values = explode('|', $config['value']);
					foreach($config['data'] as $k=>$v){
						$key_selected = '';
						if(in_array($k, $values)){
							$key_selected = ' checked="checked"';
						}
                        $item .= '<input type="checkbox" ';
                        $item .= $this->get_form_attr($config['name'].'['.$k.']', 'checkbox', $config['form_ext']);
						$item .= ' value="'.$k.'"'.$key_selected.' /><lable>'.$v.'</lable>';
					}
				}
				break;
			case 'radio':
				$item = '';
				if(!empty($config['data'])){
					$values =  $config['value'];
                    $_tmp = $this->get_form_attr($config['name'], 'radio', $config['form_ext']);
					foreach($config['data'] as $k=>$v){
						$key_selected = '';
						if($k == $values){
							$key_selected = ' checked="checked"';
						}
                        $item .= '<input type="radio" ';
                        $item .= $_tmp;
						$item .= ' value="'.$k.'"'.$key_selected.' /><lable>'.$v.'</lable>';
					}
				}
				break;
		}
		return $item;
	}
    
    /**
     * 对XML定义的form_ext进行分析
     * @param type $name
     * @param type $class
     * @param type $ext
     */
    private function get_form_attr($name='', $class='', $ext=array()){
        if (is_array($ext)) {
            if (isset($ext['id'])) {
                $ext['id'] = ($ext['id']!=$name) ? ($name==''?'':$name).$ext['id'] : $ext['id'];
            } else {
                $ext['id'] = $name;
            }
            if (isset($ext['class'])) {
                $ext['class'] = $ext['class']!=$class ? ($class==''?'':$class).$ext['class'] : $class;
            } else {
                $ext['class'] = $class;
            }
            if (isset($ext['type'])) {
                unset($ext['type']);
            }
            if (isset($ext['name'])) {
                unset($ext['name']);
            }
            if (isset($ext['value'])) {
                unset($ext['value']);
            }
            if (isset($ext['ptag'])) {
                unset($ext['ptag']);
            }
            if (isset($ext['multi'])) {
                unset($ext['multi']);
            }
            $return = '';
            foreach($ext as $key=>$value){
                $return .= ' '.$key.'="'.$value.'"';
            }
            return $return;
        }
        return '';
    }

    /////---以下为校验函数---/////
	/**
	 * 验证是否范围内
	 * @param  $str 数值
	 * @param  $ext ex:array(1,100)
	 * @return boolean
	 */
	private function range($str,$ext){
		return ($str>=$ext[0] && $str<=$ext[1]);
	}
	
	/**
	 * 验证字符长度
	 * @param  $str
	 * @param  $ext ex:array(1,100)
	 * @return boolean
	 */
	private function length($str,$ext){
		if (function_exists('mb_strlen'))
			$len = mb_strlen($str, CHARSET);
		else
			$len = strlen($str);
	
		return ($len>=$ext[0] && $len<=$ext[1]);
	}
	
	/**
	 * 验证非空
	 * @param  $str 字符串
	 * @return boolean
	 */
	private function is_notempty($str){
		return is_array($str) ? !empty($str) : trim($str)!='';
	}
	
	
	/**
	 * 验证是否为整数
	 * @param  $str 字符串
	 * @return bool
	 */
	private function is_int($str){
		return preg_match('/^\d+$/', $str);
	}
	
	/**
	 * 验证邮箱地址是否合法
	 * @param string $email 邮箱地址
	 * @return bool
	 * @since 1.0.0
	 */
	private function is_email($email) {
		return preg_match('/^[a-zA-Z0-9_\.]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$/i', $email);
	}
	
	/**
	 * 验证字符串是否为英文字母
	 * @param string $str 输入字符串
	 * @return bool
	 * @since 1.0.0
	 */
	private function is_alpha($str) {
		return preg_match('/^[a-zA-Z]+$/i', $str);
	}
	
	
	/**
	 * 验证字符串是否为英文字母或数字
	 * @param string $str 输入字符串
	 * @return bool
	 * @since 1.0.0
	 */
	private function is_alpha_num($str) {
		return preg_match('/^[a-zA-Z0-9]+$/i', $str);
	}
	
	
	/**
	 * 验证字符串是否为英文字母、数字、下划线
	 * @param string $str 输入字符串
	 * @return bool
	 * @since 1.0.0
	 */
	private function is_alpha_num_line($str) {
		return preg_match('/^[a-zA-Z0-9_]+$/i', $str);
	}
	
	
	/**
	 * 验证字符串是否为中文字符
	 * @param string $str 输入字符串
	 * @return bool
	 * @since 1.0.0
	 */
	private function is_chinese($str) {
		return preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$str);
	}
	
	
	/**
	 * 验证日期是否有效
	 * 输入日期的为YYYYMMDD格式，且有合法的分隔符(-/.)，例如：1999-2-2，99/02/02，2001.12.3
	 * @param string $date 日期
	 * @param string $separate 数组或字符，作为分隔符
	 * @return bool
	 * @since 1.0.0
	 */
	private function is_date($date,$separate = array('/','.','-')) {
		$srcTime = strtotime($date);
		$strTime = date('Ymd',$srcTime);
		$srcStr = str_replace($separate, '', $date);
		return $srcStr == $strTime;
	}
	
	
	/**
	 * 验证身份证号码是否正确
	 * 如果身份证号码错误，返回0，否则返回身份证对应的性别数值(1=女，2=男)
	 * @param string $idcard 身份证号码
	 * @return int
	 * @since 1.0.0
	 */
	private function is_idcard($idcard) {
		$idlen = strlen($idcard);
		if ($idlen !== 15 && $idlen !== 18 )
			return 0;
	
		if ($idlen == 15) {
			$birthday = get_birthday($idcard);
			if (!$birthday)
				return 0;
			else
				return substr($idcard,15,1)%2+1;
		} else {
			$idcard17 = substr($idcard, 0, 17);
			$birthday = get_birthday($idcard);
			if (!$birthday) {
				return 0;
			} else {
				//加权因子
				$factor = array(7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2);
	
				//对应检验码
				$ckcode = array('0'=>1, '1'=>0, '2'=>'X', '3'=>9, '4'=>8, '5'=>7, '6'=>6, '7'=>5, '8'=>4, '9'=>3, '10'=>2);
	
				//计算总和
				$idsum = 0;
				for ($i=0; $i<$idlen-1; $i++) {
					$idsum += substr($idcard17, $i, 1) * $factor[$i];
				}
	
				//对比检验码
				$lastid = $ckcode[$idsum % 11];
				if (strtoupper($lastid) == strtoupper(substr($idcard, 17, 1))) {
					//对于18位的身份证，倒数第二位为性别，奇数为男性，偶数为女性
					return substr($idcard,16,1)%2+1;
				} else {
					return 0;
				}
			}
		}
	}
	
	/**
	 * 验证是否URL地址
	 * @param  $str URL地址串
	 * @return boolean
	 */
	private function is_url($str) {
		return preg_match('/^([a-z]+)?(:\/\/)?([\w\d-\.]+)+(:\d+)?\/?(\?[\d\w-.\/%&=]*)?([\w\?\=\.\-&%]*)*$/i', $str,$matches);
	}
	
	/**
	 * 验证是否为手机号
	 * @param  $str 手机号
	 * @return boolean
	 */
	private function is_mobile($str){
		return preg_match('/^1\d{10}$/', $str);
	}
	
	/**
	 * 自定正则验证
	 * @param  $str 字符串
	 * @param  $ext 正则表达式
	 * @return boolean
	 */
	private function is_preg($str,$ext){
		return preg_match($ext[0],$str);
	}
	
	
	////数据转换
	
	/**
	 * 日期转整型(YYmmdd)
	 * @param str $date
	 * @return int
	 */
	private function date2int($date){
		if(empty($date))return '';
		return date('Ymd',strtotime($date));
	}
	
	/**
	 * 整型日期转日期(YY-mm-dd)
	 * @param int $intdate
	 * @return string
	 */
	private function int2date($intdate){
		if($intdate<=0)return '';
		return date('Y-m-d',strtotime($intdate));
	}
}