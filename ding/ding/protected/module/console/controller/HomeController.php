<?php
Doo::loadController('ApplicationController');
/**
 * 欢迎页
 * 2015.6.02
 * @author xinkq
 */
class HomeController extends ApplicationController {
	
	protected $_checkPageAuth = false;

	public function index() {
		
		// Doo::loadClassAt('Http','default');
		// $res = http::do_post("http://blizzmi.com/api/game/list",
		// 	array('gameid'=>'111','uid'=>'123','format'=>'xml','op'=>'now','sign'=>'sss'));
		// var_dump($res);die;

		$data = array();
		$data['userinfo'] = $userinfo = $this->_user->getUserInfo();		
		$this->layoutRender('/home/welcome',$data);
	}
}