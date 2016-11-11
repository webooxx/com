<?php

class indexAction extends Action
{


    function __construct($value = '')
    {
    }

    function index()
    {
        $arr = array_diff(get_class_methods(__CLASS__), get_class_methods(get_parent_class(__CLASS__)));
        foreach ($arr as $item) {
            echo '<a href="?a=' . $item . '" target="_blank">' . $item . '</a><br>';
        }

    }

    function testThis()
    {
        echo '<pre>';
        dump($this);
    }

    function testAssign()
    {

        $this->assign('name', 'tom');
        $this->assign('age', '12');
        $this->assign('arr', array('n' => 'val'));
        echo '<pre>';
        dump($this->displayVal);
    }

    function testRedirect()
    {
        if (isset($_GET['redirectDone'])) {
            echo '?a=testRedirect -> ?a=testRedirect&redirectDone=true';
        } else {
            $this->redirect('?a=testRedirect&redirectDone=true');
        }
    }

    function testInsideCallNoneMethod()
    {
        dump($this->noneMethod());
    }

    function testInsideCallMethod()
    {
        dump($this->_inside_call_test());
    }

    function testInsideCallCanDisplay()
    {
        $this->testOutDisplay();
    }


    function testAutoLoadHelper()
    {
        Helper::sayHello();
    }

    function testTplUrl()
    {
        dump('root : ' . C('TPL_URL_ROOT'));
        dump('public : ' . C('TPL_URL_PUBLIC'));
    }

    function testLayout()
    {
        $this->layout('bare.php');
        dump($this->layoutName);
        $this->layout('layout/base.php');
        dump($this->layoutName);
    }


    function testFetch()
    {

        $this->layout('layout/base.php');
        echo $this->fetch('testOutDisplay.php', array('name' => "预先定义的 name 内容", 'content' => "content aaa"));
    }

    function testModel()
    {
        // $m = M('test')->debug()->findAll();

        // dump($m);

        // dump(M('test')->desc());

        dump(M('test')->copyStructureTo('test2') );
        // dump(M('test2')->drop('test2') );
    }

}
