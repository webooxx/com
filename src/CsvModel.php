<?php
require_once('Model.php');
/**
 * @file Csv通用数据模型支持
 * @description  该模块提供一个Csv通用数据模型类
 */
/**
 * @name CsvModel
 * @class Csv通用数据模型类
 * @extends Model
 * @description  CsvModel 支持在本地以文本形式读写通用的Csv文件数据，可以免除数据库的需求
 * 约定第一行作为字段信息
 */
class CsvModel extends Model{

    #   确认 主机连接/目录
    function connect( $db ){

        $path = ox::c('PATH_APP') .'/'. $db['DB_NAME'] ;
        $this->operate['PATH_DB'] = $path;
        if( !realpath($path) && ! mkdir( $path , 0755) ){
            ox::l( $this->operate['PATH_DB'].'不存在，且 项目目录不具备写权限!'  ,3, 3);
        }
        

        return 'connected';
    }

    #   确认文件并且设置handle
    function table($table){

        $path = $this->operate['PATH_DB'] .'/'.$table.'.csv' ;
        $this->operate['PATH_TABLE'] = $path;

        if( !file_exists($path) && !$this->initTable() ){
            ox::l( $this->operate['PATH_TABLE'].' 不存在，且数据目录不具备写权限!'  ,3, 3);
        }

        $this->setHandle();


        return $this;
    }

    /**
     * 初始化数据表
     * @return Boolean 如果自定义模型有structure方法，那么会从这个方法的
     */
    function initTable(){
        $path = $this->operate['PATH_TABLE'];
        if( method_exists($this, 'structure') ){
            $keys = array_keys( $this->structure() );
           if( touch($path) ){
                $this->setHandle();
                return file_put_contents($path,implode(',', $keys));
           }
        }
        return false;
    }

    /**
     * 设置数据模型的handle，在打开一个文件前，如果已经有了handle则会先关闭
     * @param string $mode 可选，打开模式，默认为rw
     */
    function setHandle( $mode = "rw"){
        $file = $this->operate['PATH_TABLE'];
        if( $this->handle ){
            fclose($file);
        }
        $this->handle = fopen( $file ,$mode);
    }

    function query(){}
}
/*
#    CSV数据模型类
class CsvModel extends Model{
    public  $opt = array();

    #    处理数据路径
    function __construct(){
        $this->tablePre = J( ox::c('PATH_APP'),ox::c('DB_PREFIX') );
        $this->tableExt = '.csv';
        if( !realpath( $this->tablePre ) ){
            mkdir( $this->tablePre , 0700);
        }
        $this->focusLimit = -1;
    }

    #    设置表名，创建空数据文件，$table 参数必有
    function table( $table ){

        $this->tableName = $table;
        $this->tablePath = J( $this->tablePre ,$this->tableName.$this->tableExt );
        if( !file_exists( $this->tablePath ) ){
            $this->link = false;
        }else{
            $this->link = @fopen($this->tablePath,'r+');
            $this->tableField = fgetcsv( $this->link );
        }
        return $this;
    }

    #    查询，第一个参数为 字段列表，返回一个带有键的数组
    function find( $arg = Null ){
        if( $arg ){ $this->field($arg); }
        $limit = $this->focusLimit;
        $field = $this->focusField ? $this->focusField  : $this->tableField;

        while ( $data = fgetcsv(  $this->link ) ) {
            foreach( $data as $k => $v ){ $_row[ $this->tableField[$k] ] = $v; }
            #    条件限制
            if( $this->isWhere( $_row ) ){ $_rs[] = $_row; }
            #    长度限制
            if(  count($_rs) >= $limit && $limit > 0  ){ break; }
        }
        #    字段过滤
        foreach( (array)$_rs as $_row ){
            foreach( $field as $item ){ $row[$item] = $_row[$item]; }
            $rs[] = $row;
        }
        $this->clear();
        return  $rs ;
    }

    #    添加一条数据
    function add( $arg ){
        if( is_array($arg) ){ $this->data($arg); }
        $data = $this->focusData;
        $data['_id'] = $this->lastid()+1;
        $data['_ts'] = time();

        #    过滤不存在的字段数据
        foreach( $this->tableField as $k => $v ){
            if( empty($data[$v]) ){
                $save[] = "";
            }else{
                $save[] = $data[$v];
            }
        }
        fseek( $this->link ,0,SEEK_END );
        return fputcsv( $this->link,$save );
    }

    #    修改、删除一条数据，只允许传入 一个数组作为数据。
    function save( $arg ){
        if( is_array( $arg) ){  $this->data($arg); }
        $hasChange = false;

        #    临时数据文件
        $tmp_path = J( $this->tablePre ,'tmp_'.time() );
        $tmp_link = fopen( $tmp_path, 'w');
        fputcsv( $tmp_link ,  $this->tableField );

        #    遍历每行数据
        while ( $row = fgetcsv(  $this->link ) ) {
            foreach( $row as $k => $v ){  $_row[ $this->tableField[$k] ] = $v; }
            $hasChange = true;
            if( $this->isWhere($_row) ){
                $hasChange = true;

                #    条件修改，-1 为删除，跳过写入
                if( $arg !== -1 ){ fputcsv( $tmp_link , array_merge( $_row ,$this->focusData) ); }
            }else{
                fputcsv( $tmp_link , $row);
            }
        }

        #    关闭临时文件
        fclose( $tmp_link );
        if( $hasChange ){
            fclose( $this->link );
            unlink( $this->tablePath );
            rename( $tmp_path ,$this->tablePath );
        }else{
            unlink( $tmp_path );
        }
        return $hasChange;
    }

    #    统计总行数
    function count(){
        fseek( $this->link , 0);
        while ( fgets(  $handle ) ){ $line++;}
        return  $line-1;
    }

    #    取得最后一行的ID
    function lastid(){
        $line = 0;
        fseek( $this->link , 0);
        while( fgets( $this->link ) ){ $line++;}
        fseek( $this->link , 0);
        while( $line-- ){ $row = fgetcsv( $this->link ); }
        foreach( $this->tableField as $k => $f){ if( $f == '_id' ){ return $row[$k]; } }
        return  0;
    }

    #    清理条件，初始化各种条件
    function clear(){
        $this->focusLimit = -1;
        $this->focusWhere = array();
        $this->focusField = false;
    }

    #    查询支持的条件限制
    function where( $arg ){
        #    条件限制 = < > ，多个条件连接时 以多个 M()->where()->where() 连接
        preg_match('/(.*)([=<>])(.*)/',$arg,$match);
        $this->focusWhere[] = array('key'=> trim($match[1]), 'op'=>$match[2],'val'=>trim($match[3]));
        return $this;
    }
    function limit( $arg ){
        $this->focusLimit = $arg;
        return $this;
    }
    function field( $arg ){
        $this->focusField = is_array( $arg ) ? $arg : explode(',',$arg);
        return $this;
    }
    function data( $arg ){
        $arg = is_array( $arg ) ? $arg : explode(',',$arg);
        #    如果数据字段没有键，则加上
        if(  preg_match( '/\d+/', implode( '',array_keys($arg) ) ) ){
            if( count($arg)>count($this->tableField)-2 ){ $arg = array_slice( $arg, 0,count($this->tableField)-2); }
            $argl = count($arg);
            $data = array_combine( array_slice($this->tableField,2,$argl) ,$arg );
        }
        $this->focusData = $data;
        return $this;
    }

    #    创建数据文件,参数为 字段，返回 Bool
    function create( $arg = Null ){
        if( !$arg ){
            $field = $this->focusField ;
        }else{
            $field = is_array( $arg ) ? $arg : explode(',',$arg) ;
        }
        $field = array_flip( $field );
        unset($field['_id']);
        unset($field['_ts']);
        $field = array_keys($field);
        $field = array_unshift($field, "_id", "_ts");
        $state = false;
        if( !file_exists($this->tablePath ) ){
            $handle = @fopen( $this->tablePath, 'w') ;
            if( $handle ){
                fputcsv( $handle ,  $field );
                $state  = true;
            }
            fclose($handle);
            chmod( $this->tablePath , 0700);
        }
        return $state;
    }
    #    删除数据表，返回 Bool
    function drop( ){
        fclose( $this->link );
        return unlink( $this->tablePath );
    }

    #    条件判断运算，确认输入的数据符合当前 focusWhere 的条件，支持 = 、 > 、< 运算符，返回 Bool
    function isWhere( $row ){
        $wheres = $this->focusWhere;

        foreach( $wheres as $where ){
            switch ( $where['op'] ){
                case '=' :
                    if( !($row[$where['key']] == $where['val']) ){ return false;};
                break;
                case '>' :
                    if( !($row[$where['key']] > $where['val']) ){ return false;}
                break;
                case '<' :
                    if( !($row[$where['key']] < $where['val'] ) ){ return false ;}
                break;
            }
        }
        return true;
    }
}
*/