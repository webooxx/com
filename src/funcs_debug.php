<?php #   开发调试函数
function dump($arg)
{
    @header("Content-type:text/html;charset=utf-8");
    echo '<pre>';
    var_dump($arg);
    echo '</pre>';
}

function ddump($arg)
{
    @header("Content-type:text/html;charset=utf-8");
    echo '<pre>';
    var_dump($arg);
    die('</pre>');
}

function json($arg)
{
    @header("Content-type:text/json;charset=utf-8");
    echo json_encode($arg);
}

function djson($arg)
{
    @header("Content-type:text/json;charset=utf-8");
    die(json_encode($arg));
}

function p($arg)
{
    @header("Content-type:text/html");
    echo '<pre>';
    print_r($arg);
    echo('</pre>');
}