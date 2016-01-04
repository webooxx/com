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
     * Mysql通用数据模型数据库连接方法
     * @description 执行时会通过$this->db()获得数据库信息
     * @name MysqlModel->connect
     * @param {Array} $db 数据库配置数组
     * @return resource 返回一个MYSQL数据库连接
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
     * Mysql通用数据模型数据库查询方法
     * @name MysqlModel->query
     * @description 即使查询返回了一个空数据也会返回一个数组，每次执行都会清空操作栈
     * @param {String} $sql 在Mysql中执行查询字符串
     * @return array|resource 读取类操作返回一个数组，增删改返回Boolean
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
            ox::l($sql,3);
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
            if (empty($tableName)) {
                return false;
            }

            $dbName = (empty($dbName)) ? C('DB_NAME') : $dbName;

            if (!preg_match('#^' . C('DB_PREFIX') . '#', $tableName)) {
                $tableName = C('DB_PREFIX').$tableName;
            }

            /*$sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
                    WHERE TABLE_SCHEMA = '{$dbName}' AND TABLE_NAME = '{$tableName}';";*/
            $sql = "SHOW TABLES LIKE '{$tableName}'";
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

    /**
     * delete table record
     * created by xiazhiqiang, 2015-05-27
     * @param string $tableName : table name
     * @param string $primaryField : unique index field
     * @param array $keys : delete index value array
     * @return boolean
     */
    public function deleteRecord($tableName = '', $primaryField = '', $keys = array())
    {
        try {
            if (empty($tableName) || empty($primaryField) || !is_array($keys) || empty($keys)) {
                throw new Exception('Param error.', 1);
            }

            if (!preg_match('#^' . C('DB_PREFIX') . '#', $tableName)) {
                $tableName = C('DB_PREFIX').$tableName;
            }

            $sql = 'DELETE FROM ' . $tableName . ' WHERE ' . $primaryField . ' IN (' . implode(',', $keys) . ')';

            if (!$this->query($sql)) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * insert many record into table
     * @author xiazhiqiang, 2015-07-21
     * @param string $tableName : talbe name
     * @param array $data : insert data array
     * @param string $onUpdate : insert ON DUPLICATE KEY UPDATE clause string such as a=a+1
     * @return mixed : if successfully, return last insert id, otherwise return false
     */
    public function insertRecord($tableName = '', $data = array(), $onUpdate = '')
    {
        try {
            if (empty($tableName) || count($data) < 1) {
                throw new Exception('Param error.', 1);
            }

            if (!preg_match('#^' . C('DB_PREFIX') . '#', $tableName)) {
                $tableName = C('DB_PREFIX').$tableName;
            }

            $columns = $insertValues = array();
            foreach ($data as $value) {
                foreach ($value as $k => $v) {
                    if (!isset($columns[$k])) {
                        $columns[$k] = '';
                    }
                }
            }

            foreach ($data as $value) {

                $tmpArr = array();
                foreach ($columns as $k => $v) {
                    $tmpArr[] = (isset($value[$k])) ? "'".addslashes($value[$k])."'" : "''";
                }

                $insertValues[] = '(' . implode(',', $tmpArr) . ')';
            }
            unset($data, $tmpArr);

            $columns = array_keys($columns);
            if (empty($onUpdate)) {
                $sql = 'INSERT INTO ' . $tableName . '(' . implode(',', $columns) . ') VALUES ' . implode(',', $insertValues);
            } else {
                $sql = 'INSERT INTO ' . $tableName . '(' . implode(',', $columns) . ') VALUES ' . implode(',', $insertValues) . ' ON DUPLICATE KEY UPDATE ' . $onUpdate;
            }

            if ($this->query($sql)) {
                return $this->lastId();
            } else {
                throw new Exception('Insert users error.', 1);
            }
        } catch (Exception $e) {
            return false;
        }
    }

}