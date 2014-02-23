<?php

#    博客项目配置

$c = array();

#   项目目录
$c['DIR_APP']   = 'app-blog';
$c['DB_PREFIX'] = 'wb_';
$c['TPL_ENGINE'] = 'default';

#	百度云存储模块使用参数
$c['BCS_AK'] = '-';
$c['BCS_SK'] = '-';
$c['BCS_DEF_BUCKET'] = '-';

if(  $_SERVER['HTTP_HOST'] == '127.0.0.1' ){
    #   本地环境配置指定
	
	$c['DB_NAME'] = 'wb_blog';
	$c['DB_HOST'] = '127.0.0.1:3306';
	$c['DB_USERNAME'] = 'root';
	$c['DB_PASSWORD'] = 'root';
	
}else{
    #   线上环境配置指定

	$c['DB_NAME'] = '-';
	$c['DB_HOST'] = getenv('HTTP_BAE_ENV_ADDR_SQL_IP').':'.getenv('HTTP_BAE_ENV_ADDR_SQL_PORT');
	$c['DB_USERNAME'] = getenv('HTTP_BAE_ENV_AK');
	$c['DB_PASSWORD'] = getenv('HTTP_BAE_ENV_SK');

}
return $c;