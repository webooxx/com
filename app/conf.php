<?php
function _getCfgs(){

    $cfgs = array();
	$cfgs['DIR_APP']       = 'app';		#	项目目录
	$cfgs['SET_DB_PREFIX'] = 'xzh_';	#	表名前缀
	
    switch ( getenv('SERVER_ADDR') ){
		
		#	本地调试环境
        case '127.0.0.1' :
		
            $cfgs['SET_DB_NAME'] = 'test';
            $cfgs['SET_DB_HOST'] = '127.0.0.1';
            $cfgs['SET_DB_USERNAME'] = 'root';
            $cfgs['SET_DB_PASSWORD'] = '';
			
            $cfgs['DEV_MOD'] = true;
        break;
		
		#	线上环境
        default:
		    
			$cfgs['SET_DB_NAME'] = 'nyaRWctetNokwguvNUkn';
            $cfgs['SET_DB_HOST'] = getenv('HTTP_BAE_ENV_ADDR_SQL_IP');
            $cfgs['SET_DB_PORT'] = getenv('HTTP_BAE_ENV_ADDR_SQL_PORT');
            $cfgs['SET_DB_USERNAME'] = getenv('HTTP_BAE_ENV_AK');
            $cfgs['SET_DB_PASSWORD'] = getenv('HTTP_BAE_ENV_SK');
        break;
    }
    return $cfgs;
}
return _getCfgs();
/*
return array(
    
    'REQ_MOD_KEY'=> 'm',                  #    浏览器GET请求，模块文件名
    'REQ_ACT_KEY'=> 'a',                  #    浏览器GET请求，模块文件中的方法名
    
    'SYS_DEF_MOD'=> 'index',              #    默认请求的模块文件名
    'SYS_DEF_ACT'=> 'index',              #    默认执行的模块方法
    
    'SYS_CLASS_EXT'=> 'Action',           #    模块类名后缀
    'SYS_FILE_EXT' => 'Action.php',       #    模块文件名后缀

    'DIR_APP'=>'.',                  #    项目程序目录
    'DIR_ACT'=>'act',                #    项目程序模块文件目录
    'DIR_TPL'=>'tpl',                #    项目程序模板文件目录
    
    'SET_TPL_THEME' =>'default'          #    模板主题目录,可设置
    'SET_TPL_ENGINE'=>'default'          #    模板引擎类型,可设置
    'SET_SQL_TYPE'  =>'mysql'            #    模板引擎类型,可设置
);
*/  