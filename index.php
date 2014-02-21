<?php

define('PATH_ROOT',dirname ( __FILE__ ) );

define('PATH_COM',realpath( PATH_ROOT.'/com' ) );
define('PATH_COM_OUTSIDE',realpath( PATH_ROOT.'/../com' ) );

define('PATH_COM' , PATH_COM ? PATH_COM : PATH_COM_OUTSIDE );



#   入口,仅用于 引入框架文件 和 项目配置文件
die(PATH_COM);
$app = require(PATH_COM'/mvc/ooxx.php');
$app->init( $argv  );


#   如果需要以 single file 模式运行； 1.) 取消参数的引用 2.)取消下面处理器的注释

class indexAction extends Action{
    
    function index(){
        die( 'hello' );
    }
}