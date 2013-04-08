<?php
function _configs(){
    #   返回一个参数数组
    $c = array();
    
    #   项目目录
    $c['DIR_APP']    = 'app-YOUAPPNAME'; 
    #    模板主题
    $c['DIR_THEME']  = 'default';
    #    模板引擎,为 none 直接输出速度很快, default 使用php语法, smarty
    $c['TPL_ENGINE'] = 'default'; 
    
    if(  $_SERVER['HTTP_HOST'] == '127.0.0.1' ){
        #   本地环境配置指定
        $c['MODE_DEV'] = true;
		
		$c['DB_NAME'] = 'databasename';
		$c['DB_HOST'] = '127.0.0.1:3306';
		$c['DB_USERNAME'] = 'root';
		$c['DB_PASSWORD'] = 'root';
        
    }else{
        #   线上环境配置指定
        
    }
    return $c;
}

