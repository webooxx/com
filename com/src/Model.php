<?php
#    数据模型基类
#       数据模型类依然使用 ox:m 来进行管理，这里只用于构建基本的模型
#       
#       支持 M( Table , schem )
#       
#       支持 mod/userModel.php    数据模型自定义（侦测数据源存在[、数据字段完整、数据字段格式校验、]数据表自动构建）
#       
#       区分读写（只读模式、读写分离）一个写，多个读
#
#       多数据库支持
#
#
#   M('user')->db(2)->findAll();
#
#

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
            #   自定义模型
            include_once( $mod );
            Model::$instaces[$cache_name] = new $cache_name;
        }else{
            Model::$instaces[$cache_name] = new $engine;
        }
        #   设置参数配置
        return Model::$instaces[$cache_name]->_init_( $table );
    }

    public $db;
    public $dbKey = -1;

    public $handle;   #  链接对象
    public $operate;  #  操作栈

    function __call( $act , $args = array() ){
        $arg = $args[0];
        switch ( $act ) {

            #   初始化操作
            case '_init_handle' :
                $this->operate = array();

                #   多库结构
                $this->db = ox::c('DBS');
                $this->db[-1] = array(
                    'DB_ENGINE'=> ox::c('DB_ENGINE'),
                    'DB_PREFIX'=> ox::c('DB_PREFIX'),
                    'DB_HOST' => ox::c('DB_HOST'),
                    'DB_NAME' => ox::c('DB_NAME'),
                    'DB_USERNAME' => ox::c('DB_USERNAME'),
                    'DB_PASSWORD' => ox::c('DB_PASSWORD'),
                    'DB_DEFCHART' => ox::c('DB_DEFCHART'),
                );

                #@todo 处理 custom model 中自定表、库的情况，控制器 M 设置表、类型、库的情况

                if( !$this->handle ){
                    $this->handle = $this->connect();
                }

                break;

            #   数据库选择
            case 'db':
                $this->dbKey = $arg;
                break;



            #   构建最终SQL
            case 'add':             #   增
                if( $arg ){ $this->data($arg); }
                $sql[] = 'INSERT INTO';
                $sql[] = $this->operate['table'];
                #    有键名的数据
                if( array_keys( $this->operate['data']  ) !== range(0, count( $this->operate['data']  ) - 1) ){
                    $sql[] = '( `'.implode('`,`' ,array_keys( $this->operate["data"] ) ).'` )';
                }

                foreach( $this->operate['data'] as $k => $v ){
                    $_data[] = is_string($v) ? '\'' . $v .'\'' : $v;
                }
                $sql[] = 'VALUES (' . implode(',', $_data) . ') ';
                return   $this->query( implode(' ',$sql) );
                break;

            case 'del':             #   删
            case 'delete':
                if( $arg ){ $this->where($arg); }
                if( empty( $this->operate['where'] ) ){
                    if(  $this->operate['debug'] == 1 ){
                        ox::l('MQL查询错误！不允许无条件删除',3,3);
                    }
                    return false;
                }
                $sql[] = 'DELETE FROM';
                $sql[] = $this->operate['table'];
                $sql[] = 'WHERE '.$this->operate['where'];
                $sql[] = empty( $this->operate['limit'] ) ? ' LIMIT 1 ' : 'LIMIT ' .$this->operate['limit'];
                return $this->query( implode(' ',$sql) );
                break;

            case 'save':            #   改
                if( $arg ){ $this->data($arg); }
                if( empty( $this->operate['where'] ) ){
                    if(  $this->operate['debug'] == 1 ){
                        ox::l('MQL查询错误！不允许无条件删除',3,3);
                    }
                    return false;
                }
                foreach ($this->operate['data'] as $k => $v) {  $kvs[] = '`'.$k.'` = \''.$v.'\''; }
                $sql[] = 'UPDATE';
                $sql[] = $this->operate['table'];
                $sql[] = 'SET';
                $sql[] = implode(',', $kvs);
                $sql[] = 'WHERE '.$this->operate['where'];
                $sql[] = empty( $this->operate['limit'] ) ? ' LIMIT 1 ' : 'LIMIT ' .$this->operate['limit'];
                return $this->query( implode(' ',$sql) );
                break;

            case 'find':            #   查
                $arg = $arg ? $arg : '1';
                $result = $this->findAll( $arg ? $arg : '1' );
                return ( count($result) == 1 ) ? $result[0] :$result ;
                break;
            case 'findAll':
                if( $arg ){ $this->limit($arg); }
                $sql[] = 'SELECT';
                $sql[] = empty($this->operate['field']) ? '*' : $this->operate['field'] ;
                $sql[] = 'FROM';
                $sql[] = $this->operate['table'];
                $sql[] = $this->operate['where']  ? 'WHERE '   .$this->operate['where'] : '';
                $sql[] = $this->operate['group']  ? 'GROUP BY '.$this->operate['group'] : '';
                $sql[] = $this->operate['order']  ? 'ORDER BY '.$this->operate['order'] : '';
                $sql[] = $this->operate['limit']  ? 'LIMIT '   .$this->operate['limit'] : '';
                $sql[] = $this->operate['having'] ? 'HAVING '  .$this->operate['having'] : '';
                return $this->query( implode(' ',$sql) );
                break;

            #   选项设定
            case 'table':
                if( count(explode(',',$arg))>1 ){
                    $_tableA = explode(',',$arg);
                }else{
                    $_tableA[] = $arg;
                }
                foreach( $_tableA as $item ){
                    $tableMap = preg_split('/(\s+as\s+|\s+)/i',trim($item));
                    $_tableB[] = '`'.ox::c('DB_PREFIX').trim($tableMap[0]).'`'.(count($tableMap) >1 ? ' AS '.trim($tableMap[1]) : '');
                }
                $this->operate['table'] = implode( ' , ',$_tableB );
                break;

            case 'where':
                if( is_string($arg) ){
                    $this->operate[$act] = $arg;
                }else if( is_array($arg) ) {
                    foreach ( $arg as $k => $v) {
                        $kvs[] = '`'.addslashes($k).'` = '. ( is_int( $v ) ?  $v : '\''.addslashes($v).'\'' );
                    }
                    $this->operate[$act] = implode(' and ',$kvs);
                }
                break;
            case 'data':
                if( array_keys( $arg  ) !== range(0, count( $arg  ) - 1) ){
                    foreach( $arg as $k => $v ){
                        $data[addslashes($k)]= is_string($v) ? addslashes($v) : $v;
                    }
                    $arg = $data;
                }
                $this->operate[$act] = $arg;
                break;

            case 'select':
            case 'field':
                $this->operate['field'] = $arg;
                break;
            case 'limit':
                if( $args[1] ){
                    $this->operate['limit'] = implode( ',', $args );
                }else{
                    $limit = explode(',', $arg);
                    if( count($limit) == 1 ){
                        array_unshift( $limit , 0 );
                    }
                    $this->operate['limit'] = implode( ',',$limit );
                }
                break;
            case 'group':
                $this->operate[$act] = $arg;
                break;

            #   检测
            case 'isConnect':

                break;
            case 'isExist':

                break;


            #   必须实现的方法
            case 'query':
            case 'connect':
                ox::l('子类未实现操作 '. $act .'!',99,99);
                break;

            default:
                $this->operate[$act] = $arg;
                break;
        }
        return $this;
    }

}

function M( $t = false , $e = null ){
    return Model::getInstace( $t , $e );
}