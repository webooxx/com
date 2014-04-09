<?php

define('PATH_APP',dirname ( __FILE__ ) );

define('PATH_COM_INSIDE', realpath( PATH_APP.'/com') );
define('PATH_COM_OUTSIDE',realpath( PATH_APP.'/../com') );

define('PATH_COM' , ( PATH_COM_INSIDE ? PATH_COM_INSIDE : PATH_COM_OUTSIDE ) );

#   入口,仅用于 引入框架文件 和 项目配置文件

$app = require_once(PATH_COM.'/mvc.php');
$cfg = include_once(PATH_APP.'/config.php');

#   如果需要以 single file 模式运行； 1.) 取消配置文件的引用 2.)取消下面处理器的注释

// class indexAction extends Action{
    
//     function index(){
//         echo 'hello ox word!';
//     }
// }


$app::init( $argv , $cfg );