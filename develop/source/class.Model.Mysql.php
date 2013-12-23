<?php

#    MYSQL数据模型类
class MysqlModel extends Model{
    public  $opt = array();

    private $sqlserver;
    private $sqlusername;
    private $sqlpassword;
    private $sqlnewlink;
    private $sqlselectdb;

    function __construct(){
        $this->sqlserver   = C('DB_HOST');
        $this->sqlusername = C('DB_USERNAME');
        $this->sqlpassword = C('DB_PASSWORD');
        $this->sqlselectdb = C('DB_NAME');
        $this->newlink('default');
    }

    #    创建一个MYSQL RESOURCE 链接
    private function newlink( $sqlnewlink = false ){
        $this->connect_id = @mysql_connect($this->sqlserver, $this->sqlusername, $this->sqlpassword , $sqlnewlink ? $sqlnewlink : time() );
        if($this->connect_id){
            @mysql_select_db($this->sqlselectdb) or $this->error( '数据库连接错误！ ');
            @mysql_query('set names "'.C('DB_DEFCHART').'"') or $this->error( '设置字符集错误！ ');
        }else{
            $this->error( '服务器连接错误！ ' );
            return $this;
        }
        return $this;
    }

    #    有条件的显示错误信息
    private function error($msg){ echo '<p><b>Mysql Error</b> '. $msg .'</p>';}    #    取消了 mysql_error() 的信息展现，因为出错的时候可以用 debug(1) 来获得 SQL

    #    自动构建SQL
    function __call( $do,$args = array() ){

        #    自动参数分配支持：field、limit、group、order、having、debug
        $first = $args[0];
        switch ( $do ) {

            #    执行查询，参数必须为 String , 非查询操作将返回 Boolean 查询操作将返回表结构的数组
            case 'query':
                $first = trim($first);
                if( $this->opt['debug'] ){ ddump( $first ); }
                $this->query_result = @mysql_query($first, $this->connect_id);
                #    每次查询过后清理查询参数条件
                $this->opt = array();
                if(!$this->query_result){ return $this->error('SQL查询错误！');}
                if( is_resource( $this->query_result ) ){
                    while( $row = mysql_fetch_assoc( $this->query_result )) { $result[] = $row; }
                    return $result;
                }

                return $this->query_result;
            break;

            #    设置SQL：表名
            case 'table':
                if( count(explode(',',$first))>1 ){
                    $_tableA = explode(',',$first);
                }else{
                    $_tableA[] = $first;
                }
                foreach( $_tableA as $item ){
                    $tableMap = preg_split('/(\s+as\s+|\s+)/i',trim($item));
                    $_tableB[] = '`'.C('DB_PREFIX').trim($tableMap[0]).'`'.(count($tableMap) >1 ? ' AS '.trim($tableMap[1]) : '');
                }
                $this->opt['table'] = implode( ' , ',$_tableB );
            break;

            #    设置查询条件
            case 'where':
                #    目前只支持数组and关联
                if( is_string($first) ){
                    $this->opt[$do] = $first;
                }else if( is_array($first) ) {
                    foreach ( $first as $k => $v) {
                        $kvs[] = '`'.addslashes($k).'` = '. ( is_int( $v ) ?  $v : '\''.addslashes($v).'\'' );
                    }
                    $this->opt[$do] = implode(' and ',$kvs);
                }
            break;

            #    设置SQL：数据，用于 增、改 的操作
            case 'data';
                #    处理字段和数据的特殊字符 addslashes

                if( array_keys( $first  ) !== range(0, count( $first  ) - 1) ){
                    foreach( $first as $k => $v ){
                        $data[addslashes($k)]= is_string($v) ? addslashes($v) : $v;
                    }
                    $first = $data;
                }
                $this->opt[$do] = $first;
            break;

            #    执行：增
            case 'add';
                if( $first ){ $this->data($first); }
                $sql[] = 'INSERT INTO';
                $sql[] = $this->opt['table'];
                #    有键名的数据
                if( array_keys( $this->opt['data']  ) !== range(0, count( $this->opt['data']  ) - 1) ){
                    $sql[] = '( `'.implode('`,`' ,array_keys( $this->opt["data"] ) ).'` )';
                }

                foreach( $this->opt['data'] as $k => $v ){
                     $_data[] = is_string($v) ? '\'' . $v .'\'' : $v;
                }
                $sql[] = 'VALUES (' . implode(',', $_data) . ') ';
                return   $this->query( implode(' ',$sql) );
            break;
            #    执行：删
            case 'del' :
                if( $first ){ $this->where($first); }
                if( empty( $this->opt['where'] ) ){ $this->error('SQL查询错误！不允许无条件删除！'); return $this; }
                $sql[] = 'DELETE FROM';
                $sql[] = $this->opt['table'];
                $sql[] = 'WHERE '.$this->opt['where'];
                $sql[] = empty( $this->opt['limit'] ) ? ' LIMIT 1 ' : 'LIMIT ' .$this->opt['limit'];
                return $this->query( implode(' ',$sql) );
            break;
            #    执行：改
            case 'save':
                if( $first ){ $this->data($first); }
                if( empty( $this->opt['where'] ) ){ $this->error('SQL查询错误！不允许无条件修改！'); return $this; }
                foreach ($this->opt['data'] as $k => $v) {  $kvs[] = '`'.$k.'` = \''.$v.'\''; }
                $sql[] = 'UPDATE';
                $sql[] = $this->opt['table'];
                $sql[] = 'SET';
                $sql[] = implode(',', $kvs);
                $sql[] = 'WHERE '.$this->opt['where'];
                $sql[] = empty( $this->opt['limit'] ) ? ' LIMIT 1 ' : 'LIMIT ' .$this->opt['limit'];
                return $this->query( implode(' ',$sql) );
            break;
            #    执行：查，默认：LIMIT 1
            case 'find':
                return $this->findAll( $first ? $first : '1' );
            break;
            case 'find0':
                $data =  $this->findAll( $first ? $first : '1' );
                return is_array($data) ? $data[0] : $data;
            break;
            #    执行：查，默认所有
            case 'findAll':
                if( $first ){ $this->limit($first); }
                $sql[] = 'SELECT';
                $sql[] = empty($this->opt['field']) ? '*' : $this->opt['field'] ;
                $sql[] = 'FROM';
                $sql[] = $this->opt['table'];
                $sql[] = $this->opt['where']  ? 'WHERE '   .$this->opt['where'] : '';
                $sql[] = $this->opt['group']  ? 'GROUP BY '.$this->opt['group'] : '';
                $sql[] = $this->opt['order']  ? 'ORDER BY '.$this->opt['order'] : '';
                $sql[] = $this->opt['limit']  ? 'LIMIT '   .$this->opt['limit'] : '';
                $sql[] = $this->opt['having'] ? 'HAVING '  .$this->opt['having'] : '';
                return $this->query( implode(' ',$sql) );
            break;

            #    快捷：统计
            case 'count':
                $this->field( 'COUNT('. ($first?$first:'*')  . ') AS `count` ' );
                $count = $this->findAll();
                return (int)$count[0]['count'];
            break;

            default:
                $this->opt[$do] = $first;
            break;
        }
        return $this;
    }
}
?>
