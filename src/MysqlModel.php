<?php # Mysql类
require_once('Model.php');

/**
 * @file Mysql通用数据模型支持
 * @description  该模块提供一个Mysql通用数据模型类l
 */
/**
 * @name MysqlModel
 * @class Mysql通用数据模型类
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
        if(  $this->operate['debug'] == 1 ){
            ddump($sql);
        }
        $this->operate = array('table'=>$this->operate['table']);
        #    每次查询过后清理查询参数条件
        $sql = trim($sql);
        $resource = @mysql_query( $sql, $this->handle );
        if(!$resource){
            ox::l(mysql_error(),3);
            ox::l('MYSQL查询错误!!',98,99);
        }
        if( is_resource( $resource ) ){
            while( $row = mysql_fetch_assoc( $resource )) { $result[] = $row; }
            return (array)$result;
        }
        return $resource;
    }
    
    function lastId(){
        return mysql_insert_id($this->handle);
    }

    /**
     * justify whether table exists in database.
     * created by xiazhiqiang, 2015-04-21
     * @param string $tableName : table name
     * @param string $dbName : database name
     * @return boolean
     */
    public function isTableExists($tableName = '', $dbName = '')
    {
        try {
            if (!$tableName || !$dbName) {
                return false;
            }

            $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
                    WHERE TABLE_SCHEMA = '{$dbName}' AND TABLE_NAME = '{$tableName}';";
            $result = $this->query($sql);

            if (empty($result)) {
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * drop table
     * created by xiazhiqiang, 2015-04-21
     * @param string $tableName : table name
     * @return boolean
     */
    public function dropTable($tableName = '')
    {
        try {
            if (!$tableName) {
                return false;
            }

            $sql = "DROP TABLE IF EXISTS `{$tableName}`;";
            $result = $this->query($sql);

            if ($result) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }
}