<?php
define('PATH_NOW',dirname($_SERVER['SCRIPT_FILENAME']));
define('PATH_MVC',PATH_NOW.'/mvc');

#   定义系统框架文件和配置文件
define('FILE_MVC',PATH_MVC.'/ooxx.php');


function _configs(){
    #   返回一个参数数组
    $c = array();
    #   项目目录
    $c['DIR_APP']    = 'app-phpmvc'; 
    $c['TPL_ENGINE'] = 'default'; 
	
    if(  $_SERVER['HTTP_HOST'] == '127.0.0.1' ){
        #   本地环境配置指定
        $c['DEV_MOD'] = true;
    }else{
        #   线上环境配置指定
        $c['SYS_SINAAPP_COM'] = true;
    }
    return $c;
}

$app = include(FILE_MVC);$app->init( _configs() ,$argv );