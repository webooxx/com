<?php
function _getCfgs(){
    $cfgs = array();
	$cfgs['DIR_APP']       = '.';		#	项目目录,无需修改
	$cfgs['DIR_DOC']       = 'doc';		#	文档目录
	$cfgs['DIR_COM']       = 'tmp';		#	模板编译目录，必须
	$cfgs['EXT_DOC']       = 'txt';		#	Markdown 文件后缀
	$cfgs['SET_TPL_THEME'] = '.';		#	主题目录，无需修改

	
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