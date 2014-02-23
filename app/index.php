<?php

define('PATH_ROOT',dirname ( __FILE__ ) );

define('PATH_COM_INSIDE', realpath( PATH_ROOT.'/com') );
define('PATH_COM_OUTSIDE',realpath( PATH_ROOT.'/../com') );

define('PATH_COM' , ( PATH_COM_INSIDE ? PATH_COM_INSIDE : PATH_COM_OUTSIDE ) );

#   入口,仅用于 引入框架文件 和 项目配置文件

$app = require(PATH_COM.'/mvc.php');

// #   如果需要以 single file 模式运行； 1.) 取消配置文件的引用 2.)取消下面处理器的注释

class indexAction extends Action{
    
    function index(){
       A()->dis();
        echo 'hello ox word!';
    }
    function dis(){
        $this->display();
    }
}

class index2Action extends Action{
    
    function index(){
        A('tools')->displaya('a');
        echo 'hello ox word!';
    }
}
$app::init( $argv );