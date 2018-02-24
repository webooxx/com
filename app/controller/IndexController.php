<?php
/**
 * 首页控制器
 */
namespace app;
use \Framework\Controller as Controller;
// use \Framework\Utils as Utils;

class IndexController extends Controller
{
    function index()
    {
        ddump( A('index','report')->index() );
    }
    function help(){
        echo 'help';
    }
}
