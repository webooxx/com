<?php
 class indexAction extends Action{

    function __construct(){ 
    }

    /**
     * 首页文章列表控制器
     */
    function index(){
        $data = array(
            'username' => 'test',
            'password' => '',
        );
        //M('user')->debug(1)->data( $data )->add();

            //M('user')->where('id=3')->del();

         ddump( M('user') );
        
        ddump( M('user')->debug(1)->limit(99)->data()->where('1')->findAll() );
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