<?php
#   返回一个参数数组
$c = array();
$c['DIR_APP']    = 'app'; 
$c['TPL_ENGINE'] = 'ooxx'; 

if(  $_SERVER['HTTP_HOST'] == '127.0.0.1' ){

    $c['ENV_LOCALHOST'] = true;
    
}else{
    #   线上环境配置指定
    
}

return $c;