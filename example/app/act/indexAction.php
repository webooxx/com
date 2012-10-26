<?php

class indexAction extends Action{

    function __construct(){}

	function index(){	
		$this->assign('title','你好 WEBOOXX!');
		$this->assign('description','这是一段程序输出的描述信息');
		$this->display();
	}
}
