<?php
function _getCfgs(){

    $cfgs = array();

	$cfgs['DIR_APP']       = '.'; 	#	项目名称（目录）
	$cfgs['SET_TPL_THEME'] = '.';		#	模板目录
	$cfgs['DIR_COM']       = 'tmp';		#	模板编译目录
	
	$cfgs['DIR_DOC']       = 'docroot';		#	Markdown文档主目录
	$cfgs['EXT_DOC']       = 'txt';		#	Markdown自定义文档后缀

    switch ( getenv('SERVER_ADDR') ){
		
		#	本地调试环境
        case '127.0.0.1' :
            $cfgs['DEV_MOD'] = true;
        break;
		
		#	线上环境
        default:
		    
        break;
    }
    return $cfgs;
}
return _getCfgs();