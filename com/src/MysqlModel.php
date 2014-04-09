<?php
require_once('Model.php');
/**
 * @file Mysql通用数据模型支持
 * @description  该模块提供一个Mysql通用数据模型类l
 */
/**
 * @name MysqlModel Mysql通用数据模型类
 * @class
 * @extends Model
 * @description  
 *  
 * @example 一个自定义实现的模型代码
 * 
 * class user extends MysqlModel{
 *     function __construct(){  }
 *     function getUserById(){
 *         
 *     }
 * }
 * 
 */
class MysqlModel extends Model{
    /**
     * @name MysqlModel->connect Mysql通用数据模型数据库连接方法
     * @function
     * @return {LINK} 返回一个MYSQL数据库连接
     * @description 执行时会通过$this->db()获得数据库信息
     */
    function connect( $db ){
        if( $db['DB_ENGINE'] == 'Mysql' ){
            $handle = @mysql_connect( $db['DB_HOST'] , $db['DB_USERNAME'] ,  $db['DB_PASSWORD'] , time() );
        }
        if($handle){
            @mysql_select_db( $db['DB_NAME'] ) or ox::l( '没有找到数据库!',99,99);
            @mysql_query('set names "'.$db['DB_DEFCHART'].'"') or  ox::l( '字符集设置错误!',2 );
        }else{
            ox::l( '无法连接到服务器!',99,99);
        }
        return $handle;
    }
    /**
     * @name MysqlModel->query Mysql通用数据模型数据库查询方法
     * @function
     * @option $sql {String} 在Mysql中执行查询字符串
     * @return {Array|Boolean} 读取类操作返回一个数组，增删改返回Boolean
     * @description 即使查询返回了一个空数据也会返回一个数组，每次执行都会清空操作栈
     */
    function query( $sql ){
        $this->operate = array();
        #    每次查询过后清理查询参数条件
        $sql = trim($sql);
        $resource = @mysql_query( $sql, $this->handle );
        if(!$resource){
            ox::l(mysql_error(),3);
            if(  $this->operate['debug'] == 1 ){
                ox::l('MYSQL查询错误!',3,3);
            }else{
                die('MYSQL查询错误!');
            }
        }
        if( is_resource( $resource ) ){
            while( $row = mysql_fetch_assoc( $resource )) { $result[] = $row; }
            return (array)$result;
        }
        return $resource;
    }
}