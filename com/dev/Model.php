<?php
#    数据模型基类
#       数据模型类依然使用 ox:m 来进行管理，这里只用于构建基本的模型
#       
#       支持 M( Table , schem )
#       
#       支持 mod/userModel.php    数据模型自定义（侦测数据源存在[、数据字段完整、数据字段格式校验、]数据表自动构建）
#       
#       区分读写（只读模式、读写分离）

#    数据模型管理器
class Model{
    private static $instaces;

    public  static function getInstace( $table = false , $engine = Null ){
       
        $engine  = ( $engine ? $engine : ox::c('DB_ENGINE') ).'Model';  #   MysqlModel || CsvModel  => userMysqlModel || userCsvModel => User.php


        #   是否已缓存
        $cache_name = $table;
        if( Model::$instaces[$cache_name] ){
            return Model::$instaces[$cache_name];
        }

        #   检测通用模型和自定义模型
        $mod_app = realpath( ox::c('PATH_APP').'/'.ox::c('DIR_MOD').'/'.$table.'.php'  );
        $mod_com = realpath( ox::c('PATH_COM').'/'.ox::c('DIR_MOD').'/'.$table.'.php'  );
        $mod = $mod_app ? $mod_app : $mod_com ;

        if( $mod ){
            include_once( $mod );
            Model::$instaces[$cache_name] = new $cache_name;
        }else{
            Model::$instaces[$cache_name] = new $engine;
        }
        return $instace->table($table);
    }


//    public $host;    #  主机
//    public $db;      #  库
//    public $prefix;  #  表前缀
//    public $table;   #  表
//
//    public $username;  #  用户名
//    public $password;  #  密码
//
//
//    public $handle;   #  链接对象
//    public $operate;  #  操作栈

    #  数据集/表 是否存在,由子类实现
    public function isExist(){}

    #  设置
    public function _host(   $n ){ $this->host = $n; return $this;   }
    public function _db(     $n ){ $this->db = $n; return $this;     }
    public function _prefix( $n ){ $this->prefix = $n; return $this; }
    public function _table(  $n ){ $this->table = $n; return $this;  }

    function __call( $do,$args = array() ){
        switch ( $do ) {

            case 'query':   #   最终执行 => 在此步完成  Debug、性能策略

            break;

            #   构建最终查询串
            case 'add':

            break;

            case 'del':
            case 'delete':

            break;

            case 'save':

            break;
            case 'find':

            break;
            case 'findAll':

            break;
            #   构建选项
            case 'table':

            break;

            case 'where':

            break;
            case 'data':

            break;

            #   展现
            case 'limit':

            break;
            case 'group':

            break;
            case '':

            break;
        }
        ddump($do);
    }

}

#   MYSQL的模型基类
class MysqlModel extends Model{


    public function isExist(){

    }


    #   连接操作
    public function _handle(){}

    #   构建最终SQL
    public function add(){}         #   增
    public function del(){}         #   删
    public function delete(){}
    public function save(){}        #   改
    public function update(){}
    public function find(){}        #   查
    public function findAll(){}

    public function count(){}       #   统计

    #   执行
    public function query(){}
    
    #   选项
    public function table(){}   #   表
    public function field(){}   #   选择的字段

    public function where(){}   #   条件查询
    public function data(){}    #   字段值暂存
    
    #   展现
    public function limit(){}
    public function group(){}
    public function order(){}
    public function having(){}

}

function M( $t = false , $e = null ){
    return Model::getInstace( $t , $e );
}
/*
class Model{

    public $host   ;        //  主机
    public $dbname ;        //  库名
    public $prefix ;        //  表前缀
    public $tablename ;     //  表名

    public $engine = 'Mysql';

    public $handle ;

    #   连接&初始化
    public function linkDb(){

    }

    // #   初始化构建,用于 new Model() 的情况
    // function __construct( $config ){

    // }
    public  static function getInstace( $table , $engine = Null ){
        
        $baseModel  = $engine ? $engine : $this->$engine;
        
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
*/