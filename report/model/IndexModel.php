<?php
/**
 * 首页控制器
 */
namespace report;
use \Framework\MysqlModel as Model;

class IndexModel extends Model
{
    function index()
    {
        echo 'report model!';
    }
}
