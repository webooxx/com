<?php
#    数据模型基类
#       数据模型类依然使用 ox:m 来进行管理，这里只用于构建基本的模型
#       
#       支持 M( Table , schem )
#       
#       支持 mod/userModel.php    数据模型自定义（侦测数据源存在[、数据字段完整、数据字段格式校验、]数据表自动构建）
#       
#       区分读写（只读模式、读写分离）

class Model{

    public $host   ;        //  主机
    public $dbname ;        //  库名
    public $prefix ;        //  表前缀
    public $tablename ;     //  表名

    public $handle ;

    #   连接&初始化
    public function linkDb(){

    }

    // #   初始化构建,用于 new Model() 的情况
    // function __construct( $config ){

    // }
    public  static function getInstace( $table , $engine = Null ){
        $engine  = ( $engine ? $engine : C('DB_ENGINE') ).'Model';
        #    缓存模型对象，免得每次都新建一个
        $instace =  Model::$instaces[$engine] ?  Model::$instaces[$engine] : Model::$instaces[$engine] = new $engine;
        return $instace->table($table);
    }

    #   选择数据库/目录
    function db(){}
    #   设置前缀
    function pre(){}
    #   设置表名
    function table(){}




}

#   处理 mysql 的单例请求,模型文件的请求
function M( $name , $schem = 'Mysql'){
    $model = $schem.'Model';
    if(!ox::$m[ $model ] ){
        ox::$m[ $model ] = new $model;
    }
    ox::$m[ $model ]->table( $name );
    return ox::$m[ $model ] ;
}