<?php
 class indexAction extends Action{
#    这是从系统自动创建的示例模块文件

    function __construct(){
        #    父类构造器中没有逻辑，可以尽情编写
    }
    
    function index(){

        $this->display();
        // I('Markdown');
        // $this->display('index.html',array(
        //     'title' => 'ooxx-phpmvc help',
        //     'content' => Markdown( F('README.md') )
        // ));
    }

}