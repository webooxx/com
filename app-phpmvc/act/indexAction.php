<?php
class indexAction extends Action{

    function __construct(){}

	function test(){
		S('DB_ENGINE',Csv);
		echo C('DB_ENGINE');
		C('DB_ENGINE',Mysql);
		echo C('DB_ENGINE');
		echo '测试函数.';
		
		I('Markdown');
	}

	function index(){
		$this->assign('title','你好 webooxx!');
		$this->assign('description','这是一段程序输出的描述信息');
		$this->assign('data', array( 'First' ,'Second',' ../../ ' ) );
		$this->display();
	}
	
}