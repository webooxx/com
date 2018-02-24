<?php

/**
 * ?                    --> app/control/IndexController.php index();
 * ?r=a                 --> app/control/IndexController.php a();
 * ?r=a/b               --> app/control/aController.php b();
 * ?r=a/b/c             --> app/control/a/bController.php c();
 * ?r=a/b/c...          --> app/control/a/b/cController.php ..();
 */
namespace {

    error_reporting(0);

    function A( $moduleName , $nameSpace ){
        $nsArray = null;
        if( !empty( $nameSpace) ){
            $nsArray = explode( '\\' , $nameSpace );
        }
        return Framework\Core::getExecutive( $moduleName , $type = 'controller' , $nsArray );
    }
    function M( $tableName , $nameSpace = null , $tablePrefix = null){
        $tableNameList = explode(' ',$tableName);
        $firstTable = $tableNameList[0];
        $tablePrefix = empty( $tablePrefix ) ? Framework\Core::$config['TABLE_PREFIX'] : $tablePrefix;

        $model = Framework\Core::getExecutive( $firstTable , 'model', $nameSpace );
        $model -> table( $tableName );
        $model -> prefix( $tablePrefix );
        return $model;
    }
    // @todo cache
    function C( string $k , string $v ){

    }

    function dump($arg){
        @header("Content-type:text/html;charset=utf-8");
        echo '<pre>';
        var_dump($arg);
        echo '</pre>';
    }
    function ddump($arg){
        @header("Content-type:text/html;charset=utf-8");
        echo '<pre>';
        var_dump($arg);
        die('</pre>');
    }
    function json($arg){
        @header("Content-type:text/json;charset=utf-8");
        echo json_encode($arg);
    }
    function djson($arg){
        @header("Content-type:text/json;charset=utf-8");
        die(json_encode($arg));
    }
    function pre($arg){
        @header("Content-type:text/html");
        echo '<pre>';
        print_r($arg);
        echo('</pre>');
    }
}

namespace Framework {

class Core {

    public static $config    = array();        //  全局参数
    public static $request   = array();        //  请求内容
    public static $router    = array();        //  控制器执行信息
    public static $executive = array();        //  所有控制器实例缓存

    public static function init( $config = array() ){
        self::setConfig( $config );
        self::setRequest();
        self::setRouter();

        $class = self::getExecutive( self::$router['module'] );
        return $class -> framework_call_from_core_router( self::$router );

    }

    private static function setConfig( $config ){
        $baseConfig = array(
            'ROUTER_KEY'   => 'r',
            'APP_NAME'     => 'app',
            'TABLE_PREFIX' => '',
            'MYSQL_MASTER' => array(),
            'MYSQL_SLAVES' => array(),
        );
        self::$config =  $config + $baseConfig;
    }

    private static function setRequest(){
        if( !empty( $argv ) ){
            $argLength = count($argv);
            for ($i = 1; $i < $argLength; $i++) {
                $arg = explode('=', $argv[$i]);
                $_GET[$arg[0]] = $arg[1];
            }
            $_REQUEST = $_GET;
        }
        self::$request = $_REQUEST;
    }

    private static function setRouter(){
        $finalRouter = array(
            'prefix' => array( self::$config['APP_NAME'] ),  //  controller 下的子模块目录，需要使用 namespace
            'module' => 'index',
            'method' => 'index'
        );
        if( empty(self::$request['r']) ){
            self::$router = $finalRouter;
            return null;
        }

        $splitRouter       = explode('/', self::$request['r'] );
        $splitRouterLength = count($splitRouter);

        if( $splitRouterLength === 1 ){
            $finalRouter['method'] = $splitRouter[0];
        }
        if( $splitRouterLength === 2 ){
            $finalRouter['module'] = $splitRouter[0];
            $finalRouter['method'] = $splitRouter[1];
        }
        if( $splitRouterLength > 2 ){
            $finalRouter['prefix'] = array_slice( $splitRouter, 0, -2 );
            $finalRouter['module'] = array_slice( $splitRouter, -2, 1 )[0];
            $finalRouter['method'] = array_slice( $splitRouter, -1 )[0];
            array_unshift( $finalRouter['prefix'] , self::$config['APP_NAME'] );
        }

        self::$router= $finalRouter;
        return null;
    }


    #   载入控制器、模型 实例
    public static function getExecutive( $moduleName , $type = 'controller' , $nsArray = null ){

        $nsArray = empty($nsArray) ? self::$router['prefix'] :  $nsArray;

        $nameSpace = implode( $nsArray ,'\\');
        $className = ucfirst( $moduleName ). ucfirst($type);

        $fullClassName = '\\'.$nameSpace.'\\'.$className;

        if( !empty(self::$executive[$fullClassName]) ){
            return self::$executive[$fullClassName];
        }
        $fullFilePath =  array(
            'app'  => $nsArray[0],
            'type' => $type,
            'ns'   => implode( array_slice( $nsArray ,1) ,'/'),
            'file' => $className . '.php'
        );
        //  未加载控制器类，尝试引入文件
        if( !class_exists( $fullClassName , false ) && $type === 'controller'){
            $fullFilePath = array_filter( $fullFilePath );
            $fullFilePath = implode( $fullFilePath , '/' );
            $realFilePath = realpath( $fullFilePath );
            if( !$realFilePath ){
                throw new \Exception("Can't find the module file: ".$fullFilePath, 1);
                return null;
            }
            include_once($realFilePath);
        }
        //  未加载数据模型，默认给出MysqlMOdel
        if( !class_exists( $fullClassName , false ) && $type === 'model'){
            $Instance = new MysqlModel();
            self::$executive[$fullClassName] = $Instance;
            return $Instance;
        }

        //  实例化改类，并返回
        $Instance = new $fullClassName();
        self::$executive[$fullClassName] = $Instance;
        return $Instance;
    }

}


/**
 * 控制器
 */
class Controller {
    /**
     * 权限验证，从外部（浏览器）开始执行时，会先执行此函数，如果返回不为 true 则 throw 一个错误或者执行 auth_fail_handler
     */
    public function auth(  $funcName ){
        return $funcName && true;
    }
    final public function __call( $name , $args ){
        $internalCallNameList = array(
            'framework_call_from_core_router'
        );

        if( !in_array( $name , $internalCallNameList ) ){
            throw new \Exception("Internal invalid call name: ".$funcName, 1);
            return null;
        }
        if( $name === 'framework_call_from_core_router' ){
            $funcName = $args[0]['method'];
            if ( in_array($funcName, get_class_methods(__CLASS__)) ) {
                throw new \Exception("External illegal call framework method: ".$funcName , 1);
                return null;
            }
            if ( $this->auth($funcName) !== true ) {
                throw new \Exception("Identity auth check failure!", 1);
                return null;
            }
            if( method_exists( $this, $funcName ) ){
                die( call_user_func_array( array( $this, $funcName ), $args) );
            }else{
                $callPath = implode($args[0]['prefix'],'\\').'\\'.$args[0]['module'].'Controller->'.$args[0]['method'];
                throw new \Exception("An empty method call: ".$callPath, 1);
                return null;
            }
        } // end framework_call_from_core_router
    }
}

/**
 * 链式操作SQL
 */
class ChainSqlModel {

    public $operate = array(
        'table'  => '',
        'prefix' => '',
        'field'  => '',
        'where'  => '',
        'group'  => '',
        'order'  => '',
        'limit'  => '',
        'having' => '',
        'data'   => '',
    );

    function query( $sql ){}

    final function getRealable(){
        $list   = $this->operate['table'];
        $prefix = $this->operate['prefix'];
        $sqlStr = array();
        foreach( $list as $table ){
            $sqlStr[] = str_replace( '$$__tablePrefix__$$' , $prefix , $table);
        }
        return implode(' , ',$sqlStr);
    }

    final function __call( $name, $args ){

        $arg = isset($args[0]) ? $args[0] : array();

        //  设定 ，执行 ，修饰
        switch ( $name ) {
            //  --->    设定 - 表
            case 'table':
                if (count(explode(',', $arg)) > 1) {
                    $_tableA = explode(',', $arg);
                } else {
                    $_tableA[] = $arg;
                }
                foreach ($_tableA as $item) {
                    $tableMap = preg_split('/(\s+as\s+|\s+)/i', trim($item));
                    $_tableB[] = '`$$__tablePrefix__$$' .  trim($tableMap[0]) . '`' . (count($tableMap) > 1 ? ' AS ' . trim($tableMap[1]) : '');
                }
                $this->operate['table'] = $_tableB;
                break;
            //  --->    设定 - 表 - 前缀
            case 'prefix':
                $this->operate['prefix'] = $arg;
                break;

            //  --->    设定 - 字段
            case 'select':
            case 'field':
                $this->operate['field'] = $arg;
                break;

            //  --->    设定 - 条件
            case 'where':
                if ($arg === 1 || $arg === true) {
                    $this->operate['where'] = '1=1';
                } else if (is_string($name)) {
                    $this->operate['where'] = $arg;
                } else if (is_array($name)) {
                    foreach ($arg as $k => $v) {
                        $kvs[] = '`' . trim(addslashes($k)) . '` = ' . (is_int($v) ? $v : '\'' . addslashes($v) . '\'');
                    }
                    $this->operate['where'] = implode(' and ', (array)$kvs);
                }
                break;
            //  --->    设定 - 数据
            case 'data':
                if (is_string($arg)) {
                    $arg = explode(',', trim($arg));
                    foreach ($arg as $k => $v) {
                        $one = explode('=', trim($v));
                        $data[trim($one[0])] = trim($one[1]);
                    }

                } else if (array_keys($arg) !== range(0, count($arg) - 1)) {
                    foreach ($arg as $k => $v) {
                        $data[addslashes($k)] = is_string($v) ? addslashes($v) : $v;
                    }
                }
                $arg = $data;
                $this->operate[$act] = $arg;
                break;

            //  --->    执行 - 新增
            case 'add':
                if ($arg) {
                    $this->data($arg);
                }
                $sql[] = 'INSERT INTO';
                $sql[] = $this->getRealable();

                #    有键名的数据
                if (array_keys($this->operate['data']) !== range(0, count($this->operate['data']) - 1)) {
                    $sql[] = '( `' . implode('`,`', array_keys($this->operate["data"])) . '` )';
                }

                foreach ($this->operate['data'] as $k => $v) {
                    $_data[] = is_string($v) ? '\'' . $v . '\'' : $v;
                }
                $sql[] = 'VALUES (' . implode(',', $_data) . ') ';
                return $this->query( $sql );
                break;

            //  --->    执行 - 删除
            case 'del':
            case 'delete':
                if ($arg) {
                    $this->where($arg);
                }
                if (empty($this->operate['where'])) {
                    throw new \Exception("Not allowed to delete all the data!", 1);
                    return null;
                }
                $sql[] = 'DELETE FROM';
                $sql[] = $this->getRealable();
                $sql[] = 'WHERE ' . $this->operate['where'];
                $sql[] = empty($this->operate['limit']) ? ' LIMIT 1 ' : 'LIMIT ' . $this->operate['limit'];
                return $this->query( $sql );
                break;
            case 'deleteAll':
                if ($arg) {
                    $this->where($arg);
                }
                if (empty($this->operate['where'])) {
                    throw new \Exception("Not allowed to delete all the data!", 1);
                    return null;
                }
                $sql[] = 'DELETE FROM';
                $sql[] = $this->getRealable();
                $sql[] = 'WHERE ' . $this->operate['where'];
                return $this->query( $sql );
                break;

            //  --->    执行 - 修改
            case 'save':
                if ($arg) {
                    $this->data($arg);
                }
                if (empty($this->operate['where'])) {
                    throw new \Exception("Not allowed to update all the data!", 1);
                    return null;
                }
                foreach ($this->operate['data'] as $k => $v) {
                    if (substr($v, 0, 1) === substr($v, -1) && substr($v, -1) === '`') {
                        $kvs[] = '`' . $k . '` = ' . substr($v, 1, -1);
                    } else {
                        $kvs[] = '`' . $k . '` = ' . (is_string($v) ? '\'' . $v . '\'' : $v);
                    }
                }
                $sql[] = 'UPDATE';
                $sql[] = $this->getRealable();
                $sql[] = 'SET';
                $sql[] = implode(',', $kvs);
                $sql[] = 'WHERE ' . $this->operate['where'];
                $sql[] = empty($this->operate['limit']) ? ' ' : 'LIMIT ' . $this->operate['limit'];
                return $this->query( $sql );
                break;

            //  --->    执行 - 查找
            case 'find':
                $this->limit(1);
                $result = $this->findAll($arg ? $arg : array());
                return (count($result) == 1) ? $result[0] : $result;
                break;
            case 'findAll':
                if ($arg) {
                    $this->where($arg);
                }
                $sql[] = 'SELECT';
                $sql[] = empty($this->operate['field']) ? '*' : $this->operate['field'];
                $sql[] = 'FROM';
                $sql[] = $this->getRealable();

                $sql[] = $this->operate['where'] ? 'WHERE ' . $this->operate['where'] : '';
                $sql[] = $this->operate['group'] ? 'GROUP BY ' . $this->operate['group'] : '';
                $sql[] = $this->operate['order'] ? 'ORDER BY ' . $this->operate['order'] : '';
                $sql[] = $this->operate['limit'] ? 'LIMIT ' . $this->operate['limit'] : '';
                $sql[] = $this->operate['having'] ? 'HAVING ' . $this->operate['having'] : '';
                return $this->query( $sql );
                break;

            //  --->    执行 - 查找 - 统计
            case 'count' :
                if ($arg) {
                    $this->where($arg);
                }
                $sql[] = 'SELECT';
                $sql[] = ' count(*) as c ';
                $sql[] = 'FROM';
                $sql[] = $this->getRealable();
                $sql[] = $this->operate['where'] ? 'WHERE ' . $this->operate['where'] : '';
                $result = $this->query( $sql );
                return $result[0]['c'];
                break;

            //  --->    修饰 - 条目限定
            case 'limit':
                if (is_array($arg)) {
                    $this->operate['limit'] = implode(',', $arg);
                }
                if( is_int($arg) ){
                    $this->operate['limit'] = '0,'.$arg;
                }
                if (is_string($arg) && !empty($arg)) {
                    $limit = explode(',', $arg);
                    if (count($limit) == 1) {
                        array_unshift($limit, 0);
                    }
                    $this->operate['limit'] = implode(',', $limit);
                }
                break;
            default:
                $this->operate[$act] = $arg;
                break;
        }
        return $this;
    }
}

/**
 * MysqlModel
 */
class MysqlModel extends ChainSqlModel{

    private $link ;
    function getLink( $config ){

        if( empty($this->link) ){
            $this->link = @mysqli_connect($config['host'], $config['username'], $config['password'], $config['dbname'], $config['port']);
            if (!$this->link) {
                die('Connect Error: ' . mysqli_connect_error());
            }
            mysqli_query($this->link,  'SET NAMES UTF8');
        }
        return $this->link;
    }
    function __destruct(){
        if ( !empty($this->link) ) {
            mysqli_close($this->link);
        }
    }
    function getConfig( $sql ){

        $master = Core::$config['MYSQL_MASTER'];
        $slaves = Core::$config['MYSQL_SLAVES'];

        if( substr($sql,0,6) === 'SELECT' ){
            return $master;
        }else{
            $count = count( $slaves );
            if( $count  === 0 ){
                return $master;
            }
            return $slaves[ time() % $count ];
        }
    }

    function query( $sqlArray ){
        $sqlArr = array_filter($sqlArray);
        $sqlStr = implode( ' ' , $sqlArr);
        if( empty(!$this->operate['debugger']) ){
            die( $sqlStr);
        }
        $config   = $this->getConfig( $sqlStr );
        $link     = $this->getLink( $config );
        $resource = mysqli_query($link, $sqlStr, MYSQLI_STORE_RESULT);

        if( empty($resource)){
            throw new Exception(mysqli_error($this->handle), 1);
        }

        $result = array();
        while ($row = mysqli_fetch_assoc($resource)) {
            $result[] = $row;
        }
        mysqli_free_result($resource);

        return $result;

    }
    function lastId(){
        return mysqli_insert_id($this->link);
    }
}


}
