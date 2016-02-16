<?php
Doo::loadController('MainController');
/**
 * 验证码
 * 2015.6.01
 * @author xinkq
 */
class CaptchaController extends MainController {

	/**
	 * 验证码
	 * @return [type] [description]
	 */
	public function index() {
		//定义图片的长宽
		$img_width = 80;
		$img_height = 25;
		$safe_code = '';

		//生产验证码字符
		//$char = '0,1,2,3,4,5,6,7,8,9,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z';
		$char = '1,2,3,4,5,6,7,8,9,A,B,C,D,E,F,G,H,J,K,M,N,P,R,S,T,U,V,W,X,Y';
		$len = count(explode(',',$char)) - 1;
		$list = explode(',', $char);
		for($i=0; $i<4; $i++) {
			$rand_num = rand(0, $len);
			$safe_code .= $list[$rand_num];
		}

		//把验证码字符保存到session
		$_SESSION['safe_code'] = $safe_code;

		//生成图片
		$img = imagecreate($img_width, $img_height);

		//图片底色，ImageColorAllocate第1次定义颜色PHP就认为是底色了
		imagecolorallocate($img, 255, 255, 255);

		//定义需要的黑色
		$black = imagecolorallocate($img, 0, 0, 0);
		for($i=1; $i<=100; $i++) {
			imagestring($img, 1, mt_rand(1, $img_width), mt_rand(1, $img_height), "@", imagecolorallocate($img, mt_rand(200,255), mt_rand(200,255), mt_rand(200,255)));
		}

		//为了区别于背景，这里的颜色不超过200，上面的不小于200
		for($i=0; $i<strlen($safe_code); $i++) {
			imagestring($img, mt_rand(3,5), $i*$img_width/4+mt_rand(2,7), mt_rand(1,$img_height/2-2), $safe_code[$i], imagecolorallocate($img, mt_rand(0,100), mt_rand(0,150), mt_rand(0,200)));
		}

		//画一个矩形
		imagerectangle($img, 0, 0, $img_width-1, $img_height-1, $black);

		//生成jpeg格式
		header("Content-type: image/jpeg");
		imagejpeg($img);

		imagedestroy($img);
	}
} 
