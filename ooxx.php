<?php
/**
 * @fileOverview 简单的PHP-MVC框架
 * @author webooxx@gmail.com
 * @version 12.10.24.0
 */
 
#   框架入口核心类
class ooxx {
    public  static $setCfgs;
    private static $argCfgs = array(
    
        'REQ_MOD_KEY'=> 'm',        #   浏览器GET请求，模块文件名
        'REQ_ACT_KEY'=> 'a',        #   浏览器GET请求，模块文件中的方法名
        
        'SYS_ACT_EXT'=> 'Action',   #   模块类名后缀，文件后缀则是在后面增加.php

        'DEF_MOD'=> 'index',        #   默认请求的模块文件名
        'DEF_ACT'=> 'index',        #   默认执行的模块方法
        
        'DIR_DIR'=> '.' ,                    #  入口文件目录路径，实例化时重设
        'DIR_APP'=> '.',                     #  项目程序目录
        'DIR_ACT'=> 'act',                   #  项目程序-模块文件目录
        'DIR_TPL'=> 'tpl',                   #  项目程序-模板文件目录
        'DIR_INC'=> 'inc',                   #  项目程序-引用文件目录
        
        'SET_TPL_THEME' => 'default',    #  模板主题目录
        'SET_TPL_ENGINE'=> 'default',    #  模板引擎类型
        'SET_SQL_TYPE'  => 'mysql',      #  模板引擎类型
        'SET_VAL_FUNC'  => false,        #  设置是否开启函数验证功能
        
        'SET_DB_ENGINE'  => 'Mysql',     #  数据库引擎
        'SET_DB_PREFIX'  => '',          #  数据表名前缀
        'SET_DB_NAME' => 'test',
        'SET_DB_HOST' => '127.0.0.1',
        'SET_DB_PORT' => '3306',
        'SET_DB_USERNAME' => 'root',
        'SET_DB_PASSWORD' => '',
        'SET_DB_DEFCHART' => 'UTF8',     #   默认字符集
        
        'SET_CURRENT_MOD' => '',         #   当前的模块名
       
        
        #    模板参数设置，默认
        'SET_TPL_CONF' =>array(
            'LEFT_DELIMITER'  => '<!--{',
            'RIGHT_DELIMITER' => '}-->',
            'COMPILE_DIR'     => 'comple'
        ),
        
        'CMD_MOD'   => false,            #    命令行模式，默认为fasle，此模式下，所有参数被挂到 $_GET 对象上
        'DEV_MOD'   => false             #    开发模式，默认为fasle，此模式下不抑制错误；尽可能的输出错误信息
    );
    private static $actions;
   
    function __construct(){ @date_default_timezone_set("PRC"); ooxx::set('DIR_DIR',dirname($_SERVER[SCRIPT_FILENAME])); }
    #   执行框架
    function run( $cfgs = array() , $argv = array() ){
    
        #   命令行模式,将参数完全传输到 $_GET对象中去
        if( count($argv) > 1 ){ $al = count($argv); ooxx::set('CMD_MOD',true); for($i=1;$i<$al;$i++){ $arg = explode('=',$argv[$i]); $_GET[$arg[0]]=$arg[1]; } }
        #   直接压入传入的参数
        foreach($cfgs as $k=>$v){ ooxx::set($k,$v); }
        
        $m_key = ooxx::get('REQ_MOD_KEY');
        $a_key = ooxx::get('REQ_ACT_KEY');
        $m = empty($_GET[$m_key]) ? ooxx::get('DEF_MOD') : $_GET[$m_key];
        $a = empty($_GET[$a_key]) ? ooxx::get('DEF_ACT') : $_GET[$a_key];
        
        #    实例化请求的控制器模块方法
        $i = ooxx::mod($m);
        #   Magic Call , 传入 function name
        $i->_call_($a);
    }
    #   参数配置成对读写函数，只能写以SET开头的变量名
    public static function get($n)   { return isset(ooxx::$argCfgs[$n]) ? ooxx::$argCfgs[$n] : false ; }
    public static function set($n,$v){ return ooxx::$argCfgs[$n] = $v; }
    #   模块管理
    public static function mod($m){
        C('SET_CURRENT_MOD',$m);
        if( ooxx::$actions[$m] ){ return ooxx::$actions[$m];}
        else{
            include_once( joinp(  ooxx::get('DIR_DIR'), ooxx::get('DIR_APP'), ooxx::get('DIR_ACT'), $m.ooxx::get('SYS_ACT_EXT').'.php' ) );
            $i = $m.ooxx::get('SYS_ACT_EXT');
            ooxx::$actions[$m] = new $i;
            ooxx::$actions[$m]->mod_name=$m;
            return ooxx::$actions[$m];
        }
    }
}

#   控制器类
class Action {
    
    #   魔术方法，以处理 A 找不到模块的情况，以及浏览器入口处理
    function __call( $do , $args ){
    
        $this->fun_name = $fun_name = $args[0];
        if( $do=='_call_' ){ 
            #   验证模块；策略：主要面对客户的访问处理。A方法不受影响；能执行A必定有PHP权限，能在模板中写PHP，也一定有PHP权限
            if(  C('SET_VAL_FUNC')  ){
                $val = explode( ':', C('SET_VAL_FUNC') );
                $mod = ooxx::mod($v[0]);
                #   调用验证方法，默认传入一个参数 array( mod 模块名 , fun 方法名);
                if( !$mod->$val[1]( array('mod'=>$args[1],'fun'=>$args[0])) )  { die(); }
            }
            $this->$fun_name(); 
        }
        else{
             header('HTTP/1.1 404 Not Found');
             die(  $this->mod_name.'->'.$this->fun_name.' is not found.');
        }
    }
    
    #   集成模板功能：数据赋值、视图展现操作
    function assign($n,$v=Null){ return $v===Null ?  $this->tpl_vars[$n] :  $this->tpl_vars[$n] = $v; }
    function display($tpl=Null){ @header("Content-type:text/html"); die( $this->fetch($tpl) );}
    function fetch($tpl=Null,$isInc=false){
        
        #   模板路径
        $tplpath = Action::tplpath($tpl,$this->mod_name,$this->fun_name);
        #   模板配置
        $tplconf = C('SET_TPL_CONF');
        #   数据赋值
        $tpldata = $this->tpl_vars;
        #   原始模板文件
        $readtpl = Action::readtpl( joinp(C('DIR_DIR'), C('DIR_APP'), C('DIR_TPL'),$tplpath) );
        #   编译文件路径
        $comple_file = joinp(C('DIR_DIR'), C('DIR_APP'),$tplconf['COMPILE_DIR'],str_replace('/','_',$tplpath) .'.php');
        
        switch( C('SET_TPL_THEME') ){
        
            case 'default' :
                #   处理引用
                $readtpl = preg_replace_callback('/'.$tplconf['LEFT_DELIMITER'].'\s?include\s+([^}]*)\s?'.$tplconf['RIGHT_DELIMITER'].'/', array('self','fetch_inc_callback'), $readtpl );
                #   处理变量
                $readtpl = preg_replace('/('.$tplconf['LEFT_DELIMITER'].')(.*)('.$tplconf['RIGHT_DELIMITER'].')/','<?php echo $2; ?>',$readtpl);
                if( $isInc == false ){
                    @file_put_contents( $comple_file , $readtpl );
                    extract($tpldata);
                    $readtpl = include( $comple_file);
                }
            break;
            case 'smarty' :
                #   暂不处理Smarty类型的模板
            break;
        }       
        return  $readtpl;
    }
    function fetch_inc_callback( $arg ){ return $this->fetch( trim($arg[1]),true ); }
    
    #   计算Tpl的相对路径
    private static function tplpath($tpl,$mod_name,$fun_name){
        #   得到路径 tpl格式 主题名:模块名:文件名 , 文件名默认为 [方法名+.html]
        if($tpl==Null){
            $path =  joinp(   C('SET_TPL_THEME') , $mod_name , $fun_name.'.html' ) ;
        }else{
            #   泪奔~~~！写完看不懂了。
            $path = array_reverse ( explode(":",$tpl) );
            $back = array($fun_name.".html",$mod_name,C('SET_TPL_THEME'));
            for($i=0;$i<3;$i++){ $path[$i] && $back[$i] = $path[$i]; }
            $path = join('/' ,array_reverse ( $back ) );
        }
        return $path;
    }
    #   取得原始的Tpl内容
    private static function readtpl($tplpath){
        #   模板源码，关键字处理  1> ../Public/ 2> ../../
        $url_index  = '//'.joinp( $_SERVER['HTTP_HOST'], dirname( $_SERVER['SCRIPT_NAME'] )  ) .'/';
        $url_public = $url_index.joinp( C('DIR_APP'),C('DIR_TPL'),C('SET_TPL_THEME'),'Public' ) .'/';       
        return str_replace( array('../../','../Public/' ),  array($url_index,$url_public) ,file_get_contents($tplpath) );
    }
}

#   通用数据模型抽象类
class Model{
    private static $instaces;
    public  static function getInstace( $table = Null , $engine = Null ){
        $engine  = ( $engine ? $engine : C('SET_DB_ENGINE') ).'Model';
        $table   = $table  ? $table  : C('SET_CURRENT_MOD');
        #   缓存模型对象，免得每次都新建一个
        $instace =  Model::$instaces[$engine] ?  Model::$instaces[$engine] : Model::$instaces[$engine] = new $engine;
        return $instace->table($table);
    }
    //function table($table){ $this->opt['table'] = C('SET_DB_PREFIX').$table; return $this;}
    
    
    
}

#   MYSQL数据模型类
class MysqlModel extends Model{
    public  $opt = array();
    
    private $sqlserver;
    private $sqlusername;
    private $sqlpassword;
    private $sqlnewlink;
    private $sqlselectdb;
    
    function __construct(){
        $this->sqlserver   = C('SET_DB_HOST').':'. C('SET_DB_PORT');
        $this->sqlusername = C('SET_DB_USERNAME');
        $this->sqlpassword = C('SET_DB_PASSWORD');
        $this->sqlselectdb = C('SET_DB_NAME');
        $this->newlink('default');
    }
    
    #   创建一个MYSQL RESOURCE 链接
    private function newlink( $sqlnewlink = false ){
        $this->connect_id = @mysql_connect($this->sqlserver, $this->sqlusername, $this->sqlpassword , $sqlnewlinks ? $sqlnewlinks : time() );
        if($this->connect_id){
            @mysql_query('set names "'.C('SET_DB_DEFCHART').'"') or $this->error( '设置字符集错误！ ');
            @mysql_select_db($this->sqlselectdb) or $this->error( '数据库连接错误！ ');
        }else{
            $this->error( '服务器连接错误！ ' );
            return $this; 
        }
        return $this;
    }
    
    #   有条件的显示错误信息
    private function error($msg){ echo '<p><b>Mysql Error</b> '.( C('DEV_MOD') ?   $msg . mysql_error() : $msg ).'</p>';}
    
    function test(){
        ddump($this);
    }
    
    #   自动构建SQL
    function __call( $do,$args = array() ){
	
        $first = $args[0];
        switch ( $do ) {
		
            #   执行查询，参数必须为 String , 非查询操作将返回 Boolean 查询操作将返回表结构的数组
            case 'query':
				if( $this->opt['debug'] ){ dump( $first ); }
                $this->query_result = @mysql_query($first, $this->connect_id);
                if(!$this->query_result){ return $this->error('SQL查询错误！');}
                if( is_resource( $this->query_result ) ){
                    while( $row = mysql_fetch_assoc( $this->query_result )) { $result[] = $row; }
                    return $result;
                }
                return $this->query_result;
            break;
			
            #   设置SQL：表名
            case 'table':
                $this->opt['table'] = '`'.C('SET_DB_PREFIX').$first.'`';
            break;
			
            // #   设置SQL：字段、条件、长度限制、分组，默认处理中已处理
            // case 'field':	#	'id,name'
            // case 'where':	#	'a=b and c=d'
            // case 'limit':	#	'1' , '1,2'
            // case 'group':	#	'a'
            // case 'order':	#	'b asc'
            // case 'having':	#	'b asc'
            // case 'debug':	#	true
                // $this->opt[$do] = $first;
            // break;
			
			#   设置SQL：数据，用于 增、改 的操作
			case 'data';
				#	处理字段和数据的特殊字符 addslashes
				if( array_keys( $first  ) !== range(0, count( $first  ) - 1) ){
					foreach( $first as $k => $v ){
						$data[addslashes($k)]=addslashes($v);
					}
					$first = $data;
				}
				$this->opt[$do] = $first;
			break;
			
			#   执行：增
			case 'add';
				if( $first ){ $this->data($first); }
				$sql[] = 'INSERT INTO';
				$sql[] = $this->opt['table'];
				#	有键名的数据
				if( array_keys( $this->opt['data']  ) !== range(0, count( $this->opt['data']  ) - 1) ){
					$sql[] = '( `'.implode('`,`' ,array_keys( $this->opt["data"] ) ).'` )';
				}
				$sql[] = 'VALUES ( \'' .implode('\',\'' , $this->opt['data'] ).'\' ) ';
				return   $this->query( implode(' ',$sql) );
			break;
			#   执行：删
			case 'del' :
                if( $first ){ $this->where($first); }
                if( empty( $this->opt['where'] ) ){ $this->error('SQL查询错误！不允许无条件删除！'); return $this; }
                $sql[] = 'DELETE FROM';
                $sql[] = $this->opt['table'];
                $sql[] = 'WHERE '.$this->opt['where'];
                $sql[] = empty( $this->opt['limit'] ) ? ' LIMIT 1 ' : 'LIMIT ' .$this->opt['limit'];
                return $this->query( implode(' ',$sql) );
			break;
			#   执行：改
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
            #   执行：查，默认：LIMIT 1
            case 'find':
				return $this->findAll( $first ? $first : '1' );
			break;
			#   执行：查，默认所有
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
            
			#	快捷：统计
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


#   效率函数
function dump ($arg) { @header("Content-type:text/html"); echo '<pre>'; var_dump($arg) ; echo '</pre>' ; }
function ddump($arg) { @header("Content-type:text/html"); echo '<pre>'; var_dump($arg) ; die( '</pre>'); }
function json ($arg) { @header("Content-type:text/json"); echo json_encode($arg) ; }
function djson($arg) { @header("Content-type:text/json"); die( json_encode($arg)); }
function log4j($msg) { echo "[".date('Y-m-d H:i:s')."]".( C('CMD_MOD') ?  $msg."\n" : '<p>'.$msg.'</p>' );}
function joinp() {
    $args = func_get_args();
    $paths = array_map(create_function('$p', 'return trim($p,"/");'), $args);return ( preg_match('/^\//',$args[0]) ? '/' : '' ).join('/', $paths);
}
function O2A($o){ $r = array(); if(!is_array($o)){ if($var = get_object_vars($o)){ foreach($var as $key => $value){ $r[$key] = O2A($value); } } else{ return $obj; } } else{ foreach($o as $key => $value){ $r[$key] = O2A($value); } } return $r; }
#   快捷方式
function A( $n ){ return is_null($n) ? ooxx::mod( C('DEF_MOD') ) : ooxx::mod( $n ); }
function C( $n = NULL,$v = NULL ){
    return ($v == NULL ) ? ( ooxx::get($n) !== Null ? ooxx::get($n) : ooxx::$setCfgs[$n] ) : ( ooxx::get($n) && preg_match('/^SET_/',$n) ? ooxx::set($n,$v) : ooxx::$setCfgs[$n] = $v );
}
function M( $table = Null, $type = Null ){ return Model::getInstace( $table , $type); }
function I( $n=Null ){ return include_once(joinp( ooxx::get('DIR_APP'),'inc',$n.'.class.php' )); }

return new ooxx;