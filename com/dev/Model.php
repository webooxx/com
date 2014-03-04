<?php
#    数据模型基类
#       数据模型类依然使用 ox:m 来进行管理，这里只用于构建基本的模型

class Model{

    private $prefix ;
    private $tablename ;
    private $dbname ;
    private $link ;

    #   连接&初始化
    private function linkDb(){

    }

    #   初始化构建,用于 new Model() 的情况
    function __construct( $config ){

    }

    #   选择数据库
    function db(){

    }

    #   设置表名
    function table(){

    }

    #   设置前缀
    function pre(){
        
    }


}

#   处理 mysql 的单例请求,模型文件的请求
function M( $name , $schem = 'mysql'){

}