<?php

#    数据模型基类
class Model{
    private static $instaces;
    public  static function getInstace( $table = Null , $engine = Null ){
        $engine  = ( $engine ? $engine : C('DB_ENGINE') ).'Model';
        $table   = $table  ? $table  : C('SYS_CURRENT_MOD');
        #    缓存模型对象，免得每次都新建一个
        $instace =  Model::$instaces[$engine] ?  Model::$instaces[$engine] : Model::$instaces[$engine] = new $engine;
        return $instace->table($table);
    }
}