<?php # 模型基类
/**
 * @file 数据模型支持
 * @description  该模块提供一个数据模型基类 Model ，同时提供一个快捷方法 M( $modelName ) ，并且是 Mysql、Csv 数据模型的基类
 */

/**
 * @name Model 数据模型基类
 * @class
 * @description  基本数据模型
 * - 所有自定义的数据模型都不应该直接继承该类，而应该继承 通用数据模型类 ；例如 MysqlModel 或者 CsvModel
 * - 自定义数据模型 应该对应一个数据表的记录，而非一种 通用数据类型 的记录。
 * - 数据模型基类 中定义了一系列的SQL操作，使得 数据模型 实例 可以使用链式的语法来构建SQL
 * - 定义了必须由 通用数据模型 实现的方法；query 和 connect
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
class Model
{

    private static $instances;

    public $handle;   #  链接对象
    public $operate;  #  操作栈

    /**
     * @name Model ::getInstance 数据模型实例获取（内部方法）
     * @param bool $table 表名,默认为空，可以传入字符串
     * @param null $engine 数据库类型，如果不传入，默认使用配置中 DB_ENGINE 指定的类型；对于自定义模型，此参数无意义；自定义数据模型的类型，由其继承的类决定
     * @return string 返回一个自定义模型的实例或者通用数据模型的实例
     *
     * - 数据模型调用处理，快捷方法  M( $modelName ) 即包装了该方法
     * - 基本数据模型 和 通用数据模型均没有构造器部分的代码，自定义数据模型可以在构造器中自由编写代码，如指定 数据模型的 真实表名、数据库指针
     * - 调用改方法时，如果实例没有初始化则会进行一次初始化操作，初始化操作会载入配置中的数据库信息；
     * - 如果是 通用模型 或者 自定义模型，并且没有设置 $this->operate['table'] 的话；会执行一次 $this->table( $table )
     */
    public static function getInstance($table = false, $engine = Null)
    {
        $engine = ($engine ? $engine : mvc::config('DB_ENGINE')) . 'Model';
        #   是否已缓存
        $cache_name = $table;
        if (isset(Model::$instances[$cache_name]) && $ins = Model::$instances[$cache_name]) {
            $ins->table($table);
            return $ins;
        }
        #   检测自定义模型
        $mod_app = realpath(mvc::config('PATH_APP') . '/' . mvc::config('DIR_MOD') . '/' . $table . '.php');
        $mod_pub = mvc::getShareAppFile('/' . mvc::config('DIR_MOD') . '/' . $table . '.php');
        $mod = $mod_app ? $mod_app : $mod_pub;

        $ins = $mod;

        if ($mod) {
            #   自定义模型
            require($mod);
            $ins = new $cache_name;
        } else {
            #   通用模型
            $ins = new $engine;
        }
        if (method_exists($ins, 'connect')) {
            $ins->handle = $ins->connect($ins->db());
            $ins->table($table);
        }

        Model::$instances[$cache_name] = $ins;
        return $ins;
    }

    function __call($act, $args = array())
    {
        $arg = isset($args[0]) ? $args[0] : array();
        switch ($act) {

            /**
             * @name Model ->db 数据库信息设置
             * @function
             * @option $name {String|Number} 可选，传入的字符或者数字，对应 DBS 的键
             * @description
             * @return {Array|Instance} 根据参数，返回数据库配置信息或者实例
             * - 在不传入参数的时候，会返回当前选择的数据库配置信息
             * - 传入参数后，会更新数据库的 键指向 ，并且会重新执行 $this->connect() 方法; @see Action->connect
             */

            #   数据库选择、读取
            case 'db':

                #   重新选择了数据，需要重新连接
                if ($arg) {
                    $this->handle = $this->connect($args);
                    return $arg;
                } else {
                    return array(
                        'DB_ENGINE'   => mvc::config('DB_ENGINE'),
                        'DB_PREFIX'   => mvc::config('DB_PREFIX'),
                        'DB_HOST'     => mvc::config('DB_HOST'),
                        'DB_NAME'     => mvc::config('DB_NAME'),
                        'DB_USERNAME' => mvc::config('DB_USERNAME'),
                        'DB_PASSWORD' => mvc::config('DB_PASSWORD'),
                        'DB_CHART'    => mvc::config('DB_CHART'),
                        'DB_PORT'     => mvc::config('DB_PORT'),
                    );
                }
                break;
            /**
             * @name Model ->add 构建插入数据的SQL，并且执行
             * @function
             * @option $args {Array} 可选，可以传入一个数组作为插入的数据字段
             * @description
             * @return {Boolean} 由继承 的的通用模型 决定返回值
             */
            case 'add':             #   增
                if ($arg) {
                    $this->data($arg);
                }
                $sql[] = 'INSERT INTO';
                $sql[] = $this->operate['table'];
                #    有键名的数据
                if (array_keys($this->operate['data']) !== range(0, count($this->operate['data']) - 1)) {
                    $sql[] = '( `' . implode('`,`', array_keys($this->operate["data"])) . '` )';
                }

                foreach ($this->operate['data'] as $k => $v) {
                    $_data[] = is_string($v) ? '\'' . $v . '\'' : $v;
                }
                $sql[] = 'VALUES (' . implode(',', $_data) . ') ';
                return $this->query(implode(' ', $sql));
                break;
            /**
             * @name Model ->del 构建删除数据的SQL，并且执行
             * @alias Model->delete
             * @function
             * @option $args {Array} 可选，可以传入一个数组作为删除数据的条件
             * @return {Boolean} 由继承 的的通用模型 决定返回值
             * @description 如果没有传入参数作为删除条件，并且也没有where设置，那么删除语句将不会执行，并且抛出一个错误日志
             */
            case 'del':
            case 'delete':          #   删
                if ($arg) {
                    $this->where($arg);
                }
                if (empty($this->operate['where'])) {
                    if ($this->operate['debug'] == 1) {
                        mvc::log('MQL查询错误！不允许无条件删除', 3, 3);
                    }
                    return false;
                }
                $sql[] = 'DELETE FROM';
                $sql[] = $this->operate['table'];
                $sql[] = 'WHERE ' . $this->operate['where'];
                $sql[] = empty($this->operate['limit']) ? ' LIMIT 1 ' : 'LIMIT ' . $this->operate['limit'];
                return $this->query(implode(' ', $sql));
                break;
            case 'deleteAll':          #   删
                if ($arg) {
                    $this->where($arg);
                }
                if (empty($this->operate['where'])) {
                    if ($this->operate['debug'] == 1) {
                        mvc::log('MQL查询错误！不允许无条件删除', 3, 3);
                    }
                    return false;
                }
                $sql[] = 'DELETE FROM';
                $sql[] = $this->operate['table'];
                $sql[] = 'WHERE ' . $this->operate['where'];
                return $this->query(implode(' ', $sql));
                break;
            /**
             * @name Model ->save 构建更新数据的SQL，并且执行
             * @function
             * @option $args {Array} 可选，可选，可以传入一个有键值对的数组作为更新的数据，也可以传入一个字符串，其中允许带有一个字段相关的运算符
             * @description 参数中没有提到字段的数据不会被修改。如果没有传入参数作为更新条件，并且也没有where设置，那么更新语句将不会执行，并且抛出一个错误日志
             */
            case 'save':            #   改
                if ($arg) {
                    $this->data($arg);
                }
                if (empty($this->operate['where'])) {
                    if ($this->operate['debug'] == 1) {
                        mvc::log('MQL查询错误！不允许无条件更新', 3, 3);
                    }
                    return false;
                }
                foreach ($this->operate['data'] as $k => $v) {

                    if (substr($v, 0, 1) === substr($v, -1) && substr($v, -1) === '`') {
                        $kvs[] = '`' . $k . '` = ' . substr($v, 1, -1);
                    } else {
                        $kvs[] = '`' . $k . '` = ' . (is_string($v) ? '\'' . $v . '\'' : $v);
                    }
                }
                $sql[] = 'UPDATE';
                $sql[] = $this->operate['table'];
                $sql[] = 'SET';
                $sql[] = implode(',', $kvs);
                $sql[] = 'WHERE ' . $this->operate['where'];
                $sql[] = empty($this->operate['limit']) ? ' ' : 'LIMIT ' . $this->operate['limit'];
                return $this->query(implode(' ', $sql));
                break;

            /**
             * @name Model ->find 构建查询数据的SQL，并且执行
             * @function
             * @option $args {Int} 可选，可以传入需要查询的记录的条目数量，默认为1
             * @return {Array} 返回单条记录或者记录集合
             * @description 查询条目数量为一时，返回这条记录本身，多条记录则返回，这些记录组成的数组
             */
            case 'find':            #   查
                $result = $this->findAll($arg ? $arg : array());
                return (count($result) == 1) ? $result[0] : $result;
                break;
            /**
             * @name Model ->findAll 构建多条查询数据的SQL，并且执行
             * @function
             * @option $args {Int} 可选，可以传入查询条件
             * @return {Array} 记录集合
             * @description 如果$args为空，则返回所有满足条件的记录
             */
            case 'findAll':
                if ($arg) {
                    $this->where($arg);
                }
                $sql[] = 'SELECT';
                $sql[] = empty($this->operate['field']) ? '*' : $this->operate['field'];
                $sql[] = 'FROM';
                $sql[] = $this->operate['table'];
                $sql[] = $this->operate['where'] ? 'WHERE ' . $this->operate['where'] : '';
                $sql[] = $this->operate['group'] ? 'GROUP BY ' . $this->operate['group'] : '';
                $sql[] = $this->operate['order'] ? 'ORDER BY ' . $this->operate['order'] : '';
                $sql[] = $this->operate['limit'] ? 'LIMIT ' . $this->operate['limit'] : '';
                $sql[] = $this->operate['having'] ? 'HAVING ' . $this->operate['having'] : '';
                return $this->query(implode(' ', $sql));
                break;
            case 'count' :

                if ($arg) {
                    $this->where($arg);
                }
                $sql[] = 'SELECT';
                $sql[] = ' count(*) as c ';
                $sql[] = 'FROM';
                $sql[] = $this->operate['table'];
                $sql[] = $this->operate['where'] ? 'WHERE ' . $this->operate['where'] : '';
                $result = $this->query(implode(' ', $sql));
                return $result[0]['c'];
                break;
            /**
             * @name Model ->table 设置操作栈的table
             * @function
             * @param $args {String} 可以以逗号分割，传入多个表名
             * @return {Instace} 返回实例
             * @description 压入操作栈前，会为表名加上前缀
             */
            case 'table':
                if (count(explode(',', $arg)) > 1) {
                    $_tableA = explode(',', $arg);
                } else {
                    $_tableA[] = $arg;
                }
                foreach ($_tableA as $item) {
                    $tableMap = preg_split('/(\s+as\s+|\s+)/i', trim($item));
                    $_tableB[] = '`' . mvc::config('DB_PREFIX') . trim($tableMap[0]) . '`' . (count($tableMap) > 1 ? ' AS ' . trim($tableMap[1]) : '');
                }
                $this->operate['table'] = implode(' , ', $_tableB);
                break;
            /**
             * @name Model ->where 设置操作栈的where
             * @function
             * @param $args {String} 可以以逗号分割，传入多个表名
             * @return {Instace} 返回实例
             * @description 没有设置where那么在更新和删除记录的时候会受到限制
             */
            case 'where':
                if ($arg === 1 || $arg === true) {
                    $this->operate[$act] = '1=1';
                } else if (is_string($arg)) {
                    $this->operate[$act] = $arg;
                } else if (is_array($arg)) {
                    foreach ($arg as $k => $v) {
                        $kvs[] = '`' . trim(addslashes($k)) . '` = ' . (is_int($v) ? $v : '\'' . addslashes($v) . '\'');
                    }
                    $this->operate[$act] = implode(' and ', (array)$kvs);
                }
                break;
            /**
             * @name Model ->data 设置操作栈的data
             * @function
             * @param $args {Array} 一个条数据条目的数组
             * @return {Instance} 返回实例
             * @description 参数必须是有键值对的数组；参数的key和value会被 addslashes
             */
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
            /**
             * @name Model ->field 设置操作栈的field
             * @alias Model->select
             * @function
             * @param $args {String} 字段字符串
             * @return {Instance} 返回实例
             * @description 字段选择操作
             */
            case 'select':
            case 'field':
                $this->operate['field'] = $arg;
                break;
            /**
             * @name Model ->limit 设置操作栈的limit
             * @function
             * @param  $start {String} 如果没有$end且不是以逗号分割的字符串，那么$start为0，这个参数会被设置为$end
             * @option $end   {String} 如果有这个参数，那么$start，$end即为limit的设置
             * @return {Instance} 返回实例
             * @description 数据条目限制
             */
            case 'limit':
                if (is_array($arg)) {
                    $this->operate['limit'] = implode(',', $arg);
                }
                if (is_string($arg) && !empty($arg)) {
                    $limit = explode(',', $arg);
                    if (count($limit) == 1) {
                        array_unshift($limit, 0);
                    }
                    $this->operate['limit'] = implode(',', $limit);
                }
                break;

            /**
             * @name Model ->query 查询操作
             * @function
             * @param  $sql {String} SQL字符串
             * @return {Any} 由子类确定返回值
             * @description 必须由子类实现，否则将抛出一个错误日志
             */
            case 'query':
                /**
                 * @name Model ->connect 数据库连接操作
                 * @function
                 * @return {Any} 由子类确定返回值
                 * @description  返回的值会被设置到$this->handel中，必须由子类实现，否则将抛出一个错误日志
                 */
            case 'connect':
                mvc::log('子类未实现操作 ' . $act . ' !', 99, 99);
                break;
            /**
             * @name Model ->other 其他的操作
             * @function
             * @param  $args {String} 参数
             * @return {Instance} 返回实例
             * @description 未知的操作会被，以操作名为键，参数为值压入操作栈
             */
            default:
                $this->operate[$act] = $arg;
                break;
        }
        return $this;
    }

}

/**
 * @name 数据模型获取快捷方法
 * @function
 * @short
 * @param bool $t
 * @param null $e
 * @return string 返回数据模型实例
 *
 * 详细功能参考 @see Model::getInstance
 * @example 使用通用数据模型
 * M('user');
 * M('user','Csv');
 */
function M($t = false, $e = null)
{
    return Model::getInstance($t, $e);
}