<?php

//session_save_path('C:/xampp/tmp');
session_start();
include '../protected/config/common.conf.php';
include '../protected/config/routes.default.conf.php';
include '../protected/config/db.default.conf.php';

include '../protected/config/timezone.conf.php';
include '../protected/config/area.conf.php';

include '../protected/module/default/class/func.php';// 旧方法库

#Just include this for production mode
//include $config['BASE_PATH'].'deployment/deploy.php';

include $config['BASE_PATH'].'Doo.php';
include $config['BASE_PATH'].'app/DooConfig.php';

# Uncomment for auto loading the framework classes.
//spl_autoload_register('Doo::autoload');

Doo::conf()->set($config);

# remove this if you wish to see the normal PHP error view.
include $config['BASE_PATH'].'diagnostic/debug.php';

# database usage
//Doo::useDbReplicate();	#for db replication master-slave usage
//Doo::db()->setMap($dbmap);
Doo::conf()->add('dbconfig',$dbconfig);
Doo::loadClassAt('DBproxy','default');
// Doo::db()->setDb($dbconfig, $config['APP_MODE']);
// Doo::db()->sql_tracking = true;	#for debugging/profiling purpose

Doo::app()->route = $route;

# Uncomment for DB profiling
//Doo::logger()->beginDbProfile('doowebsite');

Doo::app()->run();
//Doo::logger()->endDbProfile('doowebsite');
Doo::logger()->rotateFile(102400);
Doo::logger()->writeLogs('framework/'.date('Ym').'/'.date('d').'.log',false);
?>