<?php
header("Content-type: text/html; charset=utf-8"); 
#逻辑处理文件
echo $type = intval($_GET['type']);
switch($type){
        case 1:
                $info = caocan();
        break;
            
        default :
                $info = de();
}

function de(){
        return '默认';
}

function caocan(){
        return '早餐'; 
}

?>


<html> 

<head>
  <title><?php echo $info; ?></title>
</head>

<body>
  <?php  
    var_dump($info);

  ?>
</body>

</html>

