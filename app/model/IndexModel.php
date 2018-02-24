<?php
/**
 * 首页控制器
 */
namespace app;
use \Framework\MysqlModel as Model;

class IndexModel extends Model
{
    function index()
    {
        echo 'model!';
    }
}
