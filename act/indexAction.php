<?php
/**
 * @file 默认应用构建控制器
 * @description 这个控制器在没有应用的情况下提供，创建应用、编译框架的功能
 */
class indexAction extends Action{

    function __construct(){
        $this->assign('title','ox install');
    }

    function index(){
        $this->display();
    }
    function install(){
        $this->display('install.html',array('step'=>(int)$_GET['step'] ));
    }

}