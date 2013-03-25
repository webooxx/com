<?php
function _configs(){
    #   返回一个参数数组
    $c = array();

    #   项目目录
    $c['DIR_APP']    = 'app-txtdoc'; 
    $c['DIR_DOC']    = 'app-txtdoc/doc';
    $c['DIR_THEME']  = '.'; 
    $c['TPL_ENGINE'] = 'default'; 
    
    if(  $_SERVER['HTTP_HOST'] == '127.0.0.1' ){
        #   本地环境配置指定
        $c['DEV_MOD'] = true;
    }else{
        #   线上环境配置指定
        
    }
    return $c;
}

