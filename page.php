<?php
header("Content-type: text/html; charset=utf-8"); 
#�߼������ļ�
echo $type = intval($_GET['type']);
switch($type){
        case 1:
                $info = caocan();
        break;
            
        default :
                $info = de();
}

function de(){
        return 'Ĭ��';
}

function caocan(){
        return '���'; 
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

