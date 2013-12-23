<?php
/** test */
/**
 * @class ooxx 核心类
 * 提供一系列的核心静态方法,框架的每次运行都会执行一次 ooxx::init()
 *
 */

class ooxx {

    function __construct(){ @date_default_timezone_set("PRC"); }

    #    模块管理器
    private static $modsMgr = array();

    #   运行时日志
    private static $runLogs = array();

    #   用户设置的全局变量
    private static $setCfgs = array();

    #   默认配置
    private static $defCfgs = array(
         #    项目默认设置

         'DIR_APP'=> 'app',              #    项目目录
         'DIR_ACT'=> 'act',              #    控制器目录
         'DIR_INC'=> 'inc',              #    公共类目录名
         'DIR_MVC'=> 'mvc',              #    框架资源目录名

         'DIR_TPL'=> 'tpl',              #    模板目录            相对于 /app/ 项目目录
         'DIR_COM'=> '.tc',              #    模板编译目录         相对于 /app/ 项目目录
         'DIR_THEME'=> '.',              #    模板主题目录,为一个 . 则默认不使用主题目录，模板目录即为主题目录    相对于 /app/tpl 项目目录

         #    模板默认设置

         'TPL_ENGINE'=> 'ooxx',          #    模板引擎类型，目前支持 none 原样输出（不使用编译目录，支持静态变量、include），ooxx 内置的模板引擎，smarty 暂不支持
         'TPL_LEFT_DELIMITER' => '<!--{',#    模板变量左分界符
         'TPL_RIGHT_DELIMITER'=> '}-->' ,#    模板变量右分界符

         #    数据库设置

         'DB_ENGINE'=> 'Mysql',          #    数据库引擎类型，目前支持 Mysql ， Csv 类型
         'DB_PREFIX'=> '',               #    数据库表前缀，如果是 Csv 数据库类型,表前缀此项相当于数据文件存放目录,相对于 /app/,使用 F 快捷函数读取，意味着你可以使用云存储的数据
         'DB_HOST' => '127.0.0.1:3306',
         'DB_NAME' => 'test',
         'DB_USERNAME' => 'root',
         'DB_PASSWORD' => '',
         'DB_DEFCHART' => 'UTF8',

         #    核心设置

         'DEF_REQ_KEY_RUT'=> 'r',        #    从 $_GET['r'] 中取得需要运行的模块类和方法，格式为 Mod/Act 或 Mod - 默认为 Mod/index

         'DEF_REQ_KEY_MOD'=> 'm',        #    从 $_GET['m'] 中取得需要运行的模块类
         'DEF_REQ_KEY_ACT'=> 'a',        #    从 $_GET['a'] 中取得模块类需要运行的方法

         'DEF_MOD'=> 'index',            #    默认请求的模块类
         'DEF_ACT'=> 'index',            #    默认执行的模块方法
         'DEC_ACT_EXT'=> 'Action',       #    默认模块类名后缀，例： indexAction.php

         #    项目运行时变量

         'URL_INDEX'  => '.',            #    首页目录的URL 模板关键字 ../../ 会自动转换成该路径,http://127.0.0.1/
         'URL_PUBLIC' => '.',            #    模板Public目录的URL 模板关键字 ../Public/  会自动替换成该路径,类似 http://127.0.0.1/app/tpl/default/Public/

         #    初始化自动设置路径变量

         'PATH_NOW'=> '.',               #    项目 index.php 的目录路径
         'PATH_MVC'=> '.',               #    项目 框架资源 路径,根据 DIR_MVC 自动设置,当项目调用了不存在的 Act、Inc 资源时，会尝试从这个目录内读取
         'PATH_APP'=> '.',               #    项目 主目录   路径,根据 DIR_APP 自动设置
         'PATH_COM'=> '.',               #    项目 模板编译 路径,根据 DIR_COM 自动设置,如果目录不可写则尝试定位到临时目录

         #    系统其他设置
         'SYS_VERIFY_FUNC' => '',        #    系统，验证函数设置，格式为字符串，例如rbac:check，执行时传入一个参数数组 array( 'mod'=> 模块名 , 'act'=> , 方法名  )
         'SYS_CURRENT_MOD' => '',        #    系统，当前的模块名,执行模块时重设
         'SYS_CURRENT_ACT' => '',        #    系统，当前的方法名,执行方法时重设
         'SYS_COMMAND_MOD' => false,     #    程序是否是以命令行模式调用，默认为false,在命令行中调用时会自动设置为true,此模式下，所有的参数将被挂载到名为 $_GET 的对象上,注意:参数需要键名
         'ENV_LOCALHOST'   => false,     #    程序是否是在本地执行，当域名为127.0.0.1时自动设置为true
     );


    #   核心函数 - 路径合并
    public static function joinp(){
        $paths = func_get_args();
        foreach( $paths as $item){  $temp[] = trim($item,"/"); }
        return ( preg_match('/^\//',$paths[0]) ? '/' : '' ).join('/', $temp);
    }

    #    核心函数 - 配置、全局参数读写
    public static function cfg($n,$v = NULL){
        return ooxx::$defCfgs[$n] ? ooxx::$defCfgs[$n] : ( $v === NULL ? ooxx::$setCfgs[$n] : ooxx::$setCfgs[$n] = $v  );
    }

    #    核心函数 - 模块管理
    public static function mod($m , $a=Null){

        ooxx::$defCfgs['SYS_CURRENT_MOD'] = $m;                 #    记录当前的模块名，浏览器入口、A() 均可执行到此处
        if( ooxx::$modsMgr[$m] ){ return ooxx::$modsMgr[$m];}

        else{

            $n = $m.ooxx::cfg('DEC_ACT_EXT');
            $actFile = realpath( ooxx::joinp(  ooxx::cfg('PATH_APP'), ooxx::cfg('DIR_ACT'), $n.'.php' ) );

            #    尝试执行系统自带的控制器
            if(!$actFile){
                $actFile = realpath( ooxx::joinp(  ooxx::cfg('PATH_MVC') ,ooxx::cfg('DIR_ACT'), $n.'.php' ) );
            }

            #    尝试直接展现模板
            if( !$actFile ){
                $tplFile = realpath( ooxx::joinp(  ooxx::cfg('PATH_APP'), ooxx::cfg('DIR_TPL'),$m ,$a.'.html') );
                if($tplFile){
                    ooxx::$modsMgr[$m] = new Action;
                    ooxx::$modsMgr[$m]->mod_name=$m;
                    return ooxx::$modsMgr[$m];
                }else{
                    ooxx::log( 'Action module '.$m. ' is non-existent!',3 );
                }
            }

            include_once( $actFile );
            ooxx::$modsMgr[$m] = new $n;
            ooxx::$modsMgr[$m]->mod_name=$m;
            return ooxx::$modsMgr[$m];
        }
    }

    #   核心函数 - 日志管理 1 note , 2 warring , 3 error
    public function log($msg ,$level = 1,$json=false){
        ooxx::$runLog[] = "[$level] ".date("Y-m-d H:i:s")." - ".$msg;
        if( $level === 3){
            if($json){die(json_encode(ooxx::$runLog) );}
            $out = implode( (ooxx::cfg('SYS_COMMAND_MOD')?"\n":"<br />" ), ooxx::$runLog );
            die($out);
        }
    }

    #    初始化
    function init( $argv , $cfgs = array() ){

        #    命令行模式,将参数完全复制到 $_GET 对象中去
        if( count($argv) > 1 ){
            $al = count($argv);
            ooxx::$configs['SYS_COMMAND_MOD'] = true;
            for($i=1;$i<$al;$i++){ $arg = explode('=',$argv[$i]);
                $_GET[$arg[0]]=$arg[1];
            }
            $_REQUEST = $_GET;
        }

        ooxx::$defCfgs = array_merge( ooxx::$defCfgs , (array)$cfgs);

        #   运行时路径
        ooxx::$defCfgs['PATH_NOW'] = dirname($_SERVER[SCRIPT_FILENAME]);
        ooxx::$defCfgs['PATH_APP'] = ooxx::joinp( ooxx::$defCfgs['PATH_NOW'] , ooxx::$defCfgs['DIR_APP'] );
        ooxx::$defCfgs['PATH_MVC'] = ooxx::joinp( ooxx::$defCfgs['PATH_NOW'] , ooxx::$defCfgs['DIR_MVC'] );
        ooxx::$defCfgs['PATH_COM'] = ooxx::joinp( ooxx::$defCfgs['PATH_APP'] , ooxx::$defCfgs['DIR_COM'] );

        #    设定模板编译目录
        if( ooxx::$configs['TPL_ENGINE'] != 'none' ){
                if( !@mkdir( ooxx::$configs['PATH_COM'] , 0700)  ){
                    ooxx::$configs['PATH_COM'] = sys_get_temp_dir();
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
}
#    控制器模块基类
class Action {

    #    魔术方法，以处理 A 找不到模块的情况，以及浏览器入口处理
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
                #    调用验证方法，错误信息应当在验证方法中输出。
                if( !$mod->$val[1]( array('mod'=>$args[1],'act'=>$args[0])) )  {
                    return false;
                }
            }
            #    验证通过，将执行的方法名从魔术调用修改为需要执行的方法名
            $method = $fun_name;
        }

        #    处理A调用，$this->do()调用，浏览器入口验证通过后执行的方法
        if( $method != '_ActCall_' ){
            if( method_exists($this, $fun_name) ){
                 return $this->$fun_name();
            }else{
                #    尝试直接展现对应方法的模板
                $tplpath = realpath( $this->_tplpath($method) );
                return $tplpath ? $this->display() : die(  $this->mod_name.'Action->'.$method.' is non-existent!');
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
        die( $this->fetch($tpl) );
    }
    function fetch($tpl=Null,$isInc=false){

        #    处理 $tpl 参数，有可能：[ default:admin:login.html ] [ admin:login.html ] [ login.html ] [ login ]
        $tplpath = call_user_func_array(array($this, '_tplpath'), array_reverse ( explode(":",$tpl) ) );
        if( !realpath( $tplpath ) ){ return 'Template '. $tplpath . ' is non-existent!';}
        $tplpath = realpath( $tplpath );

        #    模板源码，关键字替换 [ ../Public/ ] [ ../../ ] 、处理引用，include 支持 与 $tpl 一样的参数
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
                    #    模板替换，变量输出、PHP语句
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

    #    计算Tpl的相对路径，支持不同的模板名、模块名、主题名，格式->  [主题名:][模块名:][方法名:].html ，参数传入为反方向传入；
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
#    数据模型基类
class Model{
    private static $instaces;
    public  static function getInstace( $table = Null , $engine = Null ){
        $engine  = ( $engine ? $engine : C('DB_ENGINE') ).'Model';
        $table   = $table  ? $table  : C('SYS_CURRENT_MOD');
        #    缓存模型对象，免得每次都新建一个
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
function log(){

}

return ooxx;
?>