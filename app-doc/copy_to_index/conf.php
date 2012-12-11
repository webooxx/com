<?php
function _getCfgs(){

    $cfgs = array();

	$cfgs['DIR_APP']       = 'app-doc'; #   目录 - 项目名称
	$cfgs['DIR_COM']       = 'tmp';		#   目录 - 模板编译
	$cfgs['DIR_DOC']       = 'doc';		#	目录 - 文档主目录
	
	$cfgs['SET_TPL_THEME'] = 'simple';  #	模板主题
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