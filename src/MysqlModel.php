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
class MysqlModel extends Model
{

    protected $tableName = '';

    /**
     * Mysql通用数据模型数据库连接方法
     *
     * @param $db
     * @return mysqli
     * @throws Exception
     */
    function connect($db)
    {
        $link = null;
        $link = mysqli_connect($db['DB_HOST'], $db['DB_USERNAME'], $db['DB_PASSWORD'], $db['DB_NAME'], $db['DB_PORT']);

        if (!$link) {
            throw new Exception('Connect Error (' . mysqli_connect_errno() . ') '
                . mysqli_connect_error(), 1);
        }
        mysqli_autocommit($link, true);
        $link->query('set names "' . $db['DB_CHART'] . '"');

        return $link;
    }

    /**
     * @param $sql
     * @return array
     * @throws Exception
     */
    function query($sql)
    {
        if ($this->operate['debug'] == 1) {
            ddump($sql);
        }
        $this->operate = array('table' => $this->operate['table']);
        #    每次查询过后清理查询参数条件
        $sql = trim($sql);
        $resource = mysqli_query($this->handle, $sql);


        if (!$resource) {
            mvc::log('SQL: ' . $sql, 3);
            throw new Exception(mysqli_error($this->handle), 1);
        }
        $result = array();
        while ($row = mysqli_fetch_assoc($resource)) {
            $result[] = $row;
        }
        mysqli_free_result($resource);

        return $result;
    }

    function lastId()
    {
        return mysqli_insert_id($this->handle);
    }

    /**
     * 查看表结构，不存在的表返回 false
     * @return array|bool
     */
    function desc()
    {
        $sql = "DESC " . $this->operate['table'];
        $resource = mysqli_query($this->handle, $sql);
        if (!$resource) {
            return false;
        }
        $result = array();
        while ($row = mysqli_fetch_assoc($resource)) {
            $result[] = $row;
        }
        mysqli_free_result($resource);

        return $result;
    }

    /**
     * 返回表名
     * @return mixed
     */
    function getTable()
    {
        return $this->operate['table'];
    }


    /**
     * 删除表
     * @return array|bool
     */
    function drop()
    {
        $sql = "DROP TABLE IF EXISTS " . $this->operate['table'];
        $resource = mysqli_query($this->handle, $sql);
        if (!$resource) {
            return false;
        }
        $result = array();
        while ($row = mysqli_fetch_assoc($resource)) {
            $result[] = $row;
        }
        mysqli_free_result($resource);

        return true;
    }

    /**
     * 复制结构到新表（会重建index）
     * @param string $tableName
     * @return array|bool
     * @throws Exception
     */
    function copyStructureTo($tableName = '')
    {
        $newTable = mvc::config('DB_PREFIX') . trim($tableName);
        if (strlen($tableName) < 1) {
            throw  new Exception ('必须指定新表名!');
        }
        if (M($newTable)->desc() != false) {
            throw  new Exception ('要复制创建的新表已经存在！');
        }
        $sql = 'CREATE TABLE IF NOT EXISTS' . '`' . $newTable . '`' . ' LIKE ' . $this->operate['table'];
        $resource = mysqli_query($this->handle, $sql);
        if (!$resource) {
            return false;
        }
        $result = array();
        while ($row = mysqli_fetch_assoc($resource)) {
            $result[] = $row;
        }
        mysqli_free_result($resource);

        return true;
    }

    /**
     * 关闭连接
     */
    function __destruct()
    {
        if ($this->handle) {
            mysqli_close($this->handle);
        }
    }
}