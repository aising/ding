<?php
//菜单入口文件
$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$siteUrl = 'http://www.wstreet.cn/weixin/ding/ding/public/index.php/home?code='.$code.'&type='.$type;

header('Location:'.$siteUrl);exit;

?>
