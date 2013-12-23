<?php
/**
 * @fileOverview 简单的PHP-MVC框架
 * @author webooxx@gmail.com
 * @version 0.0.0.3
 */

#    框架入口核心类
class ooxx {

    #    系统配置参数,可通过传入的参数修改 可以使用 C 快捷函数安全读取 可以使用 S 快捷函数进行重设
    private static $argCfgs = array(

        #    项目默认设置

        'DIR_APP'=> 'app',              #    项目目录
        'DIR_ACT'=> 'act',              #    控制器目录
        'DIR_INC'=> 'inc',              #    公共类目录名
        'DIR_MVC'=> 'mvc',              #    框架资源目录名

        'DIR_TPL'=> 'tpl',              #    模板目录            相对于 /app/ 项目目录
		'DIR_COM'=> '.tc',              #    模板编译目录         相对于 /app/ 项目目录
        'DIR_THEME'=> '.',              #    模板主题目录         相对于 /app/tpl/ 模板目录,默认不启用主题目录,即模板目录就是默认的主题目录

        #    模板默认设置

        'TPL_ENGINE'=> 'php',           #    模板引擎类型,默认支持php语法,设置为false时,原样输出,支持关键字、include(不会使用编译目录)
        'TPL_LEFT_DELIMITER' => '<!--{',#    模板引擎 左分界符
        'TPL_RIGHT_DELIMITER'=> '}-->' ,#    模板引擎 右分界符

        #    数据库设置

        'DB_ENGINE'=> 'Mysql',          #    数据库引擎类型,目前支持 Mysql , Csv
        'DB_PREFIX'=> '',               #    数据库表前缀,如果是 Csv 数据库类型,则是数据表文件前缀
        'DB_HOST' => '127.0.0.1:3306',  #    数据库服务器,如果是 Csv 数据库类型,则是文件类型 默认 file 本地文件  bcs 则通过 F 函数读取拼接后的路径
        'DB_NAME' => 'test',            #    使用的数据库,如果是 Csv 数据库类型,默认是 csv , 拼接后的文件路径是 /app/csv/
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => '',
        'DB_DEFCHART' => 'UTF8',

        #    核心设置

        'DEF_REQ_KEY_RUT'=> 'r',        #    从 $_GET['r'] 中取得需要运行的模块类和方法,格式为 Module/Act 或 Module , 默认为 Module/index

        'DEF_REQ_KEY_MOD'=> 'm',        #    从 $_GET['m'] 中取得需要运行的模块类
        'DEF_REQ_KEY_ACT'=> 'a',        #    从 $_GET['a'] 中取得模块类需要运行的方法

        'DEF_MOD'=> 'index',            #    默认请求的模块类
        'DEF_ACT'=> 'index',            #    默认执行的模块方法
        'DEC_ACT_EXT'=> 'Action',       #    默认模块类名后缀,例： indexAction.php

        #    项目运行时变量

        'URL_INDEX'  => '.',            #    首页目录的URL 模板关键字 ../../ 会自动转换成该路径,//127.0.0.1/
        'URL_PUBLIC' => '.',            #    模板Public目录的URL 模板关键字 ../Public/  会自动替换成该路径,类似 //127.0.0.1/app/tpl/default/Public/

        #    初始化自动设置路径变量

        'PATH_NOW'=> '.',               #    项目 index.php 的目录路径
        'PATH_MVC'=> '.',               #    项目 框架资源 路径,根据 DIR_MVC 自动设置,当项目调用了不存在的 Act、Inc 资源时,会尝试从这个目录内读取
        'PATH_APP'=> '.',               #    项目 起始目录 路径,根据 DIR_APP 自动设置
        'PATH_COM'=> '.',               #    项目 模板编译 路径,根据 DIR_COM 自动设置,如果目录不可写则尝试定位到临时目录

        #    系统其他设置
        'SYS_VERIFY_FUNC' => '',        #    系统,验证函数设置,字符串,例如rbac:check,执行时传入一个参数数组 array( 'mod'=> 模块名 , 'act'=> , 方法名  )
        'SYS_CURRENT_MOD' => '',        #    系统,当前的模块名,执行模块时重设
        'SYS_CURRENT_ACT' => '',        #    系统,当前的方法名,执行方法时重设
        'SYS_COMMAND_MOD' => false,     #    程序是否是以命令行模式调用,默认为false,在命令行中调用时会自动设置为true,此模式下,所有的参数将被挂载到名为 $_GET 的对象上,注意:参数需要键名
        'ENV_LOCALHOST'   => false,     #    程序是否是在本地执行,当域名为127.0.0.1时自动设置为true
    );
    #    全局共享参数,通过快捷函数 C 进行管理
    private static $setCfgs = array();

    private static $modules = array();

    function __construct(){ @date_default_timezone_set("PRC"); }

    #    初始化
    function init( $argv , $cfgs = array() ){

        #    命令行模式,将参数完全复制到 $_GET 和 $_REQUEST 对象中去
        if( count($argv) > 1 ){ $al = count($argv); ooxx::$argCfgs['SYS_COMMAND_MOD'] = true; for($i=1;$i<$al;$i++){ $arg = explode('=',$argv[$i]); $_GET[$arg[0]]=$arg[1]; } $_REQUEST = $_GET;}

        #    本地访问模式
        if(  $_SERVER['HTTP_HOST'] == '127.0.0.1' ){ ooxx::$argCfgs['ENV_LOCALHOST'] = true; }
        #    合并传入的参数与系统默认设置的参数

        ooxx::$argCfgs = array_merge( ooxx::$argCfgs , (array)$cfgs);

        ooxx::$argCfgs['PATH_NOW'] = dirname($_SERVER[SCRIPT_FILENAME]);
        ooxx::$argCfgs['PATH_APP'] = ooxx::joinp( ooxx::$argCfgs['PATH_NOW'] , ooxx::$argCfgs['DIR_APP'] );
        ooxx::$argCfgs['PATH_MVC'] = ooxx::joinp( ooxx::$argCfgs['PATH_NOW'] , ooxx::$argCfgs['DIR_MVC'] );
        ooxx::$argCfgs['PATH_COM'] = ooxx::joinp( ooxx::$argCfgs['PATH_APP'] , ooxx::$argCfgs['DIR_COM'] );

        #    初始化模板编译目录
        if( ooxx::$argCfgs['TPL_ENGINE'] != 'none' ){
                if( !@mkdir( ooxx::$argCfgs['PATH_COM'] , 0700)  ){
                    ooxx::$argCfgs['PATH_COM'] = sys_get_temp_dir();
                }
        }

        $m_key = ooxx::cfg('DEF_REQ_KEY_MOD');
        $a_key = ooxx::cfg('DEF_REQ_KEY_ACT');
        $r_key = ooxx::cfg('DEF_REQ_KEY_RUT');

        if( !empty($_GET[$r_key]) ){
            $r = explode('/', $_GET[$r_key] );
            $m = $r[0];
            $a = $r[1];
        }else{
            $m = $_GET[$m_key];
            $a = $_GET[$a_key];
        }

        $m = empty($m) ? ooxx::cfg('DEF_MOD') : $m ;
        $a = empty($a) ? ooxx::cfg('DEF_ACT') : $a ;

        #    实例化请求的控制器模块类后魔术调用相应方法
        $i = ooxx::mod($m,$a);
        $i->_ActCall_($a,$m);

    }

    #    模块管理
    public static function mod($m , $a=Null){

        ooxx::$argCfgs['SYS_CURRENT_MOD'] = $m;                 #    记录当前的模块名,浏览器入口、A() 均可执行到此处
        if( ooxx::$modules[$m] ){ return ooxx::$modules[$m];}

        else{

            $n = $m.ooxx::cfg('DEC_ACT_EXT');
            $actFile = realpath( ooxx::joinp(  ooxx::cfg('PATH_APP'), ooxx::cfg('DIR_ACT'), $n.'.php' ) );

            #    如果找不到控制器模块文件,将尝试去系统自带的控制器中去找
            if(!$actFile){
                $actFile = realpath( ooxx::joinp(  ooxx::cfg('PATH_MVC') ,ooxx::cfg('DIR_ACT'), $n.'.php' ) );
            }

            #    如果找不到对应系统控制器模块文件,尝试直接展现模板
            if( !$actFile ){
                $tplFile = realpath( ooxx::joinp(  ooxx::cfg('PATH_APP'), ooxx::cfg('DIR_TPL'),$m ,$a.'.html') );
                if($tplFile){
                    ooxx::$modules[$m] = new Action;
                    ooxx::$modules[$m]->mod_name=$m;
                    return ooxx::$modules[$m];
                }else{
                    log( 'Action '.$m. ' is non-existent!' ,3);
                }
            }

            include_once( $actFile );
            ooxx::$modules[$m] = new $n;
            ooxx::$modules[$m]->mod_name=$m;
            return ooxx::$modules[$m];
        }
    }

    #    路径合并函数
    public static function joinp(){
        $paths = func_get_args();
        foreach( $paths as $item){  $temp[] = trim($item,"/"); }
        return ( preg_match('/^\//',$paths[0]) ? '/' : '' ).join('/', $temp);
    }

    #    管理全局参数配置
    public static function cfg($n,$v = NULL){
        return ooxx::$argCfgs[$n] ? ooxx::$argCfgs[$n] : ( $v === NULL ? ooxx::$setCfgs[$n] : ooxx::$setCfgs[$n] = $v  );
    }
    #    重写系统参数配置
    public static function set($n,$v){
        return ooxx::$argCfgs[$n] = $v ;
    }
    #    直接在内存中存取数据
    public static function shmop($n,$v = NULL,$delete = false){
        $shmid = shmop_open($n, "w", 0, 102400);
        if( is_null($v) ){
            $back = shmop_write($shmid, $v, 0);
        }else{
            $size = shmop_size($shmid);
            $back = shmop_read($shmid, 0, $size);
        }
        if( $delete ){
            shmop_delete($shmid);
            shmop_close($shmid);
        }
        return $back;
    }
}

#    控制器管理器
class Action {

    #    魔术方法,以处理 A 找不到模块的情况,以及浏览器入口处理
    function __call( $method , $args ){

        S('URL_INDEX' , rtrim( 'http://'.J( $_SERVER['HTTP_HOST'], dirname( $_SERVER['SCRIPT_NAME'] )  ) ,'\\/' ).'/' );
        S('URL_PUBLIC',C('URL_INDEX').J(  C('DIR_APP'), C('DIR_TPL'), C('DIR_THEME'),'Public' ) .'/') ;

        $fun_name = $this->fun_name = $args[0];

        #    处理浏览器入口执行的魔术调用
        if( $method == '_ActCall_' ){

            #    验证处理浏览器的访问
            if(  C('SYS_VERIFY_FUNC')  ){
                $val = explode( ':', C('SYS_VERIFY_FUNC') );
                $mod = ooxx::mod($val[0]);
                #    调用验证方法,错误信息应当在验证方法中输出。
                if( !$mod->$val[1]( array('mod'=>$args[1],'act'=>$args[0])) )  {
                    return false;
                }
            }
            #    验证通过,将执行的方法名从魔术调用修改为需要执行的方法名
            $method = $fun_name;
        }

        #    处理A调用,$this->do()调用,浏览器入口验证通过后执行的方法
        if( $method != '_ActCall_' ){
            if( method_exists($this, $fun_name) ){
                 return $this->$fun_name();
            }else{
                #    尝试直接展现对应方法的模板
                $tplpath = realpath( $this->_tplpath($method) );
                return $tplpath ? $this->display() : log(  $this->mod_name.'Action->'.$method.' is non-existent!',3);
            }
        }
    }

    #    集成模板功能：数据赋值、视图展现操作
    function assign($n,$v=Null){ return $v===Null ?  $this->tpl_vars[$n] :  $this->tpl_vars[$n] = $v; }
    function display($tpl=Null,$data=Null){
        if(!is_null($data)){
            foreach($data as $k=>$v){
                $this->assign($k,$v);
            }
        }
        @header("Content-type:text/html");
        echo $this->fetch($tpl) ;
        exit(0);
    }
    function fetch($tpl=Null,$isInc=false){

        #    处理 $tpl 参数,有可能：[ default:admin:login.html ] [ admin:login.html ] [ login.html ] [ login ]
        $tplpath = call_user_func_array(array($this, '_tplpath'), array_reverse ( explode(":",$tpl) ) );
        if( !realpath( $tplpath ) ){ return 'Template '. $tplpath . ' is non-existent!';}
        $tplpath = realpath( $tplpath );

        #    模板源码,关键字替换 [ ../Public/ ] [ ../../ ] 、处理引用,include 支持 与 $tpl 一样的参数
        $tplread = str_replace( array('../../','../Public/' ),  array( C('URL_INDEX'),C('URL_PUBLIC') ) ,F($tplpath) );

        $tplread = preg_replace_callback('/'.C('TPL_LEFT_DELIMITER').'\s?include\s+([^}]*)\s?'.C('TPL_RIGHT_DELIMITER').'/', array('self','_fetch_inc_callback'), $tplread );

        #    模板变量数据
        $tpldata = (array)$this->tpl_vars;
        #    模板编译文件路径
        $name_comple =  substr( $tplpath,strlen(C('PATH_COM'))+1 );
        $path_comple = J( C('PATH_COM'),'com_'.str_replace( array('/','\\',':'),'_', $name_comple ) .'.php');

        #    合并所有的include后（排除在include时候多次计算）
        if( !$isInc ){
            switch( C('TPL_ENGINE') ){
                case 'ooxx' :
                    #    模板替换,变量输出、PHP语句
                    $tplread = preg_replace('/('.C('TPL_LEFT_DELIMITER').')\s*\$(.*?);?\s*('.C('TPL_RIGHT_DELIMITER').')/','<?php echo \$$2; ?>',$tplread);
                    $tplread = preg_replace('/('.C('TPL_LEFT_DELIMITER').')\s*(.*?);?\s*('.C('TPL_RIGHT_DELIMITER').'){1}/','<?php $2; ?>',$tplread);

                    F( $path_comple , $tplread );

                    extract($tpldata);
                    $tplread = include( $path_comple);
                    unlink($path_comple);
                break;
                case 'smarty' :
                    #    使用A调用获得内容
                break;
            }
        }
        return  $tplread;
    }
    function _fetch_inc_callback( $arg ){ return $this->fetch( trim($arg[1]),true ); }

    #    计算Tpl的相对路径,支持不同的模板名、模块名、主题名,格式->  [主题名:][模块名:][方法名:].html ,参数传入为反方向传入；
    function _tplpath( $method = Null, $module = Null, $theme = Null){
        $method = $method ? $method : $this->fun_name ;
        $module = $module ? $module : $this->mod_name ;
        $theme  = $theme  ? $theme  : C('DIR_THEME') ;
        $method = explode('.',$method);
        $ext    = '.'.( $method[1] ? $method[1] : 'html' );
        $method = $method[0];
        return J( C('PATH_APP'),C('DIR_TPL'),$theme,$module,$method.$ext);
    }
}

#    数据模型管理器
class Model{
    private static $instaces;
    public  static function getInstace( $table = Null , $engine = Null ){
        $engine  = ( $engine ? $engine : C('DB_ENGINE') ).'Model';
        $table   = $table  ? $table  : C('SYS_CURRENT_MOD');
        #    缓存模型对象,免得每次都新建一个
        $instace =  Model::$instaces[$engine] ?  Model::$instaces[$engine] : Model::$instaces[$engine] = new $engine;
        return $instace->table($table);
    }
}

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
    private function error($msg){ echo '<p><b>Mysql Error</b> '. $msg .'</p>';}    #    取消了 mysql_error() 的信息展现,因为出错的时候可以用 debug(1) 来获得 SQL

    #    自动构建SQL
    function __call( $do,$args = array() ){

        #    自动参数分配支持：field、limit、group、order、having、debug
        $first = $args[0];
        switch ( $do ) {

            #    执行查询,参数必须为 String , 非查询操作将返回 Boolean 查询操作将返回表结构的数组
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

            #    设置SQL：数据,用于 增、改 的操作
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
            #    执行：查,默认：LIMIT 1
            case 'find':
                return $this->findAll( $first ? $first : '1' );
            break;
            case 'find0':
                $data =  $this->findAll( $first ? $first : '1' );
                return is_array($data) ? $data[0] : $data;
            break;
            #    执行：查,默认所有
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

#    CSV数据模型类
class CsvModel extends Model{
    public  $opt = array();

    #    处理数据路径
    function __construct(){
        $this->tablePre = J( C('PATH_APP'),C('DB_PREFIX') );
        $this->tableExt = '.csv';
        if( !realpath( $this->tablePre ) ){
            mkdir( $this->tablePre , 0700);
        }
        $this->focusLimit = -1;
    }

    #    设置表名,创建空数据文件,$table 参数必有
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

    #    查询,第一个参数为 字段列表,返回一个带有键的数组
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

    #    修改、删除一条数据,只允许传入 一个数组作为数据。
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

                #    条件修改,-1 为删除,跳过写入
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

    #    清理条件,初始化各种条件
    function clear(){
        $this->focusLimit = -1;
        $this->focusWhere = array();
        $this->focusField = false;
    }

    #    查询支持的条件限制
    function where( $arg ){
        #    条件限制 = < > ,多个条件连接时 以多个 M()->where()->where() 连接
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
        #    如果数据字段没有键,则加上
        if(  preg_match( '/\d+/', implode( '',array_keys($arg) ) ) ){
            if( count($arg)>count($this->tableField)-2 ){ $arg = array_slice( $arg, 0,count($this->tableField)-2); }
            $argl = count($arg);
            $data = array_combine( array_slice($this->tableField,2,$argl) ,$arg );
        }
        $this->focusData = $data;
        return $this;
    }

    #    创建数据文件,参数为 字段,返回 Bool
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
    #    删除数据表,返回 Bool
    function drop( ){
        fclose( $this->link );
        return unlink( $this->tablePath );
    }

    #    条件判断运算,确认输入的数据符合当前 focusWhere 的条件,支持 = 、 > 、< 运算符,返回 Bool
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

#    效率函数
function dump ($arg) { @header("Content-type:text/html"); echo '<pre>'; var_dump($arg) ; echo '</pre>' ; }
function ddump($arg) { @header("Content-type:text/html"); echo '<pre>'; var_dump($arg) ; die( '</pre>'); }
function json ($arg) { @header("Content-type:text/json"); echo json_encode($arg) ; }
function djson($arg) { @header("Content-type:text/json"); die( json_encode($arg)); }
function log($msg = Null ,$level = 0) {
    $log = C('__MVCRUNLOG');
    $log[] = "[".date('Y-m-d H:i:s')."] - " . $level . ' - ' . $msg ;
    C('__MVCRUNLOG',$log);
    if( $msg === Null || $level === 3){
        die ( implode( C('SYS_COMMAND_MOD') ? "\n" : "<br />", $log) );
    }
}
function o2a($obj){ $result = array(); if(!is_array($obj)){ if($var = get_object_vars($obj)){ foreach($var as $key => $value){ $result[$key] = o2a($value); } } else{ return $obj; } } else{ foreach($obj as $key => $value){ $result[$key] = o2a($value); } } return $result; }

#    快捷方式
function A( $n = NULL ){ return is_null($n) ? ooxx::mod( C('DEF_MOD') ) : ooxx::mod( $n ); }
function C( $n = NULL,$v = NULL ){ return ooxx::cfg($n,$v);}
function F( $n , $v = NULL){
    $n = explode(':', $n);
    if( count($n)>1 ){
        return is_null($v) ? A($n[0])->get( $n[1] ) : A($n[0])->put( $n[1] );
    }else{
        return is_null($v) ? file_get_contents( $n[0] ) : file_put_contents( $n[0] , $v );
    }
}
function I( $n=Null ){
    $p1 =  realpath( J( C('PATH_APP'), C('DIR_INC'),$n.'.class.php') );
    $p2 =  realpath( J( C('PATH_MVC'), C('DIR_INC'),$n.'.class.php') );
    if( $p1 ){ return include_once( $p1 ); }
    if( $p2 ){ return include_once( $p2 ); }
    echo 'Class '.$n.' is non-existent!';
}
function J(){ $args = func_get_args(); return  call_user_func_array(array('ooxx', 'joinp'), $args );}
function M( $table = Null, $type = Null ){ return Model::getInstace( $table , $type); }
function S( $n = NULL,$v = NULL ){ return ooxx::set($n,$v);}

return new ooxx;