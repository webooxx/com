<?php
 class indexAction extends Action{

    function __construct(){
        
    }

    /**
     * 首页文章列表控制器
     */
    function index(){

        djson( M('user')->findAll() );
        $this->display();
    }


    /**
     * 文章处理控制器
     */
    function p(){

    }
    /**
     * 页面处理控制器
     */
    function page(){

    }
    /**
     * 分类文章列表处理控制器
     */
    function c(){

    }

    /**
     * 标签分类文章列表处理控制器
     */
    function tags(){

    }

}