<?php
/**
 * @name ox
 * @class 框架核心类
 * @description 每个请求都由 ox::init() 方法发起
 */

class ox {

    /**
     * @name ox::$c 框架核心类的配置信息存储
     * @property {Array}
     */
    static $c = array();  #   配置
    /**
     * @name ox::$m 框架核心类的控制器模块缓存
     * @property {Array}
     */
    static $m = array();  #   模块
    /**
     * @name ox::$l 框架核心类的日志堆栈
     * @property {Array}
     */
    static $l = array();  #   日志

    /**
     * @name ox::c 配置信息存取管理
     * @function
     * @param  $n {String} 配置的名称
     * @option $v {Any}  配置的值
     * @return {Any} 对应配置信息的值
     * @description 框架配置配置存取方法，快捷方法 C( $n , $v ) 即包装了该方法
     */
    static function c( $n,$v = NULL ){
        return $v === NULL ? ox::$c[$n] : ox::$c[$n] = $v;
    }
    /**
     * @name ox::l 日志记录展现方法
     * @function
     * @param  $msg {Any} 日志信息，包括不限于基本数据类型
     * @option $level {Int}  日志记录时的级别，一般来说 info 为1 , warring 为2 , error 为3
     * @option $show  {Int}  默认为0，如果大于 0 ，那么会立刻展现 该级别以下，C('LOG_SHOWBASE') 级(含)以上的日志信息
     * @return {Boolean} True
     * @description 如果配置中没有 LOG_SHOWBASE ,那么默认为0；在上线之后，可以把 LOG_SHOWBASE 设置为 3，只显示错误级别的信息
     * 目前的展现方法只使用了 json_encode ，还没有使用日志模块进行输出
     */
    #   日志管理
    static function l( $msg , $level = 1 , $show = 0 ){
        ox::$l[] = array(
            'MSG'=> $msg,
            'LEVEL'=> $level,
            'TIME' => date('Y-m-d H:i:s'),
            'INFO' => array_slice( debug_backtrace() ,0,2),
        );
        if( $show > 0 ){
            $json = array();
            foreach (ox::$l as $l ) {
                if( $l['LEVEL'] >= (int)ox::c('LOG_SHOW_BASE') &&  $l['LEVEL']  <= $show ){
                    $json[]  = $l;
                }
            }
            die( json_encode( array_reverse( (array)$json )) );
        }
        return true;
    }
    /**
     * @name ox::m 模块调用方法
     * @function
     * @param $req {Array}  控制器模块请求参数
     * @param $req['m'] {String}    请求的控制器模块名称
     * @param $req['a'] {String}    请求的模块方法
     * @return {ActionInstance} 控制器实例
     * @description 框架模块调用处理，快捷方法 A( $Module ) 即包装了该方法
     */
    public static function m( $req ){

        $m = $req['m']; #   请求的模块
        $a = $req['a']; #   请求的模块
        $s = ox::c('PATH_APP');    #   控制器模块目录的父目录
        $class = $m.ox::c('DEC_ACT_EXT');

        if( ox::$m[$m] ){
            ox::l( '模块已缓存!' , 2 );
            return ox::$m[$m];
        }else{
            if( class_exists( $class ) ){
                ox::$m[$m] = new $class;
                ox::l( '模块已经预加载了!', 2 );
            }else{

                #   模块文件
                $act = $class.'.php';
                $act_app = realpath( ox::c('PATH_APP').'/'.ox::c('DIR_ACT').'/'.$act );
                $act_pub = realpath( ox::c('PATH_PUB').'/'.ox::c('DIR_ACT').'/'.$act );
                $act = $act_app ? $act_app : $act_pub;
                if( !$act_app && $act_pub ){
                    $s = ox::c('PATH_PUB');
                }

                #   仍然没有模块文件，对应用目录下的模板目录进行侦测
                if(!$act){
                    ox::l( '模块不存在!', 2 );
                    $tpl = realpath( ox::c('PATH_APP').'/'.ox::c('DIR_TPL').'/'.ox::c('TPL_THEME').'/'.$m );
                    !$tpl ? ox::l( '模板也不存在!' , 3 , 3 ) : ox::$m[$m] = new Action;
                }else{
                    ox::l( '模块存在!', 1 );
                    include_once( $act );
                    ox::$m[$m] = new $class;
                }

            }
        }
        ox::l( '运行 ' .$m.'Action -> '.$a , 1 );
        ox::$m[$m]->Module_Name = $m;
        ox::$m[$m]->Method_Name = $a;
        ox::$m[$m]->Module_From = $s;
        return ox::$m[$m];
    }
    /**
     * @name ox::r 路由处理
     * @function
     * @param $GET {Array}  默认为浏览器的GET参数
     * @return {Array} 返回一个解析了浏览器GET参数后的数组，格式为： array( 'm' => '控制器模块' , 'a'=> '方法' )
     * @description
     */
    #   路由处理 返回 array( m=> '' , a=> '' )
    static function r( $GET ){
        $req_r = ox::c('DEF_REQ_KEY_RUT');
        $req_a = ox::c('DEF_REQ_KEY_ACT');
        $req_m = ox::c('DEF_REQ_KEY_MOD');
        if( isset($GET[$req_r]) ){
            $r = explode('/', $GET[$req_r] );
            $m = $r[0];
            $a = $r[1];
        }else{
            $m = $GET[$req_m];
            $a = $GET[$req_a];
        }
        return array(
            'm' => empty($m) || is_numeric( $m) ? ox::c('DEF_MOD') : $m,
            'a' => empty($a) || is_numeric( $a) ? ox::c('DEF_ACT') : $a,
        );
    }
    
    /**
     * @name ox::init 框架运行入口
     * @function
     * @option $cfgs {Array}  运行时配置信息，一个带有若干键值的，如果不传入默认为一个空的数组，
     * @param  $argv {Array}  入口文件执行init时自带的参数，该参数的作用是在命令行模式下传入对应的参数，
     * @description  初始化调用时，会根据参数执行对应的控制器模块和方法
     * 在命令行模式下的参数会被全部传入到 $_GET 对象中，命令行运行的格式类似： php index.php r=index/test id=1
     * 参数请参考 @see ox::c
     */
    static function init( $argv , $cfgs = array() ){

        @date_default_timezone_set("PRC");
        ox::$c = array(

        #   入口处理
         
        'COMMAND_MODE'  => false,             #   命令行模式
        'LOG_SHOW_BASE' => 1,                 #   日志信息的展现级别, 1info 2warring 3error；上线后选3


        #    数据库设置
        'DB_ENGINE'=> 'Mysql',          #    数据库引擎类型，目前支持 Mysql ， Csv 类型
        'DB_PREFIX'=> '',               #    数据库表前缀
        'DB_HOST' => '127.0.0.1:3306',
        'DB_NAME' => 'test',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => '',
        'DB_DEFCHART' => 'UTF8',

        #    模板默认设置
        'TPL_THEME' => '.',              #    模板主题目录,为一个 . 则默认不使用主题目录，模板目录即为主题目录    相对于 /app/tpl 项目目录
        'TPL_ENGINE'=> 'php',            #    默认PHP，如果是 smarty ，则保持引入了 smarty 模块接口，因为会调用 A('smarty')->fetch( $path , $assign )，返回编译后的模板代码
        'TPL_LEFT_DELIMITER' => '<!--{', #    模板变量左分界符
        'TPL_RIGHT_DELIMITER'=> '}-->' , #    模板变量右分界符

        #   运行时自动重设的模板关键路径
        'TPL_URL_ROOT'     => '.',           #   index.php  入口URL
        'TPL_URL_PUBLIC'   => '.',           #   Public公共目录URL
        'TPL_URL_RELATIVE' => '.',           #   模板当前位置的URL
 


        #    项目默认设置，不建议修改
        'DIR_APP'=> '.'  ,              #    APP目录
        'DIR_ACT'=> 'act',              #    控制器目录               相对于 /app/ 项目目录
        'DIR_TPL'=> 'tpl',              #    模板目录
        'DIR_MOD'=> 'mod',              #    数据模型目录
        'DIR_INC'=> 'inc',              #    公共类引用目录


        #    核心设置，不建议修改
        'DEF_REQ_KEY_RUT'=> 'r',        #    从 $_GET['r'] 中取得需要运行的模块类和方法，格式为 Mod/Act 或 Mod - 默认为 Mod/index
        'DEF_REQ_KEY_MOD'=> 'm',        #    从 $_GET['m'] 中取得需要运行的模块类
        'DEF_REQ_KEY_ACT'=> 'a',        #    从 $_GET['a'] 中取得模块类需要运行的方法
        'DEF_MOD'=> 'index',            #    默认请求的模块类
        'DEF_ACT'=> 'index',            #    默认执行的模块方法
        'DEC_ACT_EXT'=> 'Action',       #    默认模块类名后缀，例： indexAction.php

        #   自动重设路径，只读
        'PATH_APP'=> '.',               #    框架运行时自动设置
        'PATH_COM'=> '.',               #    项目入口文件绝对路径、项目组件路径
        'PATH_PUB'=> '.',               #    公共的 INC，ACT，MOD 目录
    );

        ox::c('PATH_PUB',PATH_COM);
        #    命令行模式 将参数完全复制到 $_GET 对象中
        if( count($argv) > 1 ){
            $al = count($argv); ox::$c['COMMAND_MODE'] = true;
            for($i=1;$i<$al;$i++){
                $arg = explode('=',$argv[$i]); $_GET[$arg[0]]=$arg[1]; 
            } 
            $_REQUEST = $_GET;
         }

        #   合并用户自定义参数
        ox::$c = array_merge( ox::$c , $cfgs);

        #   路径
        ox::c('PATH_APP',PATH_APP);
        ox::c('PATH_COM',PATH_COM);
        if(defined('DIR_APP')){
            ox::c('DIR_APP',DIR_APP);
        }

        #   分析请求对象
        $req = ox::r( $_GET );
        #    实例化请求 的 控制器模块,需要控制器自行处理 
        $ins = ox::m( $req );
        $ins->_call_( $req );
    }

}
/**
 * @name C 配置信息存取快捷方法
 * @function
 * @short
 * @return {Any} 参数存取的值
 * @description 详细功能参考 @see ox::c
 * 
 * @example 读取模板的主题目录名
 * C('TPL_THEME');
 * 
 * @example 设置模板的主题目录名
 * C('TPL_THEME', $myTheme );
 */
function C( $n ,$v = NULL ){
    return ox::c($n,$v);
}

#   控制器模块
# 控制器基类
/**
 * @name Action 控制器基类
 * @class
 * @description
 * - 所有的应用程序的控制器模块，均继承此基类，该类没有构造函数，所以 控制器 的代码逻辑中允许自由编写构造函数。
 * - 调用了一个不存在的控制器时，会尝试去执行框架自带的对应控制器。如果也不存在对应的默认控制器，则会报错。
 * - 调用了控制器中不存在的一个方法时，会尝试去展现对应方法的模板。如果也不存在模板，则会报错。
 * @example 一个自定义实现的控制器代码
 * 
 * class indexAction extends Action{
 *     function __construct(){  }
 *     function index(){
 *         echo '默认首页';
 *     }
 * }
 * 
 */
class Action {

    public $Module_From    = '.';
    public $Module_Name    = '.';
    public $Method_Name    = '.';

    public $Tpl_Variables  = array();
    public $Layout_Name    = false;

    /**
     * @name Action->display 模板展现方法
     * @function
     * @option $name {String} 模板名称，模板参数允许【 null 】【 Method.html 】【 Module/Method.html 】【  THEME/Module/Method.html  】
     * @option $data {Array}  模板变量，是一个键值对的数组
     * @description 执行这个方法后，页面会直接die出编译后的模板文件信息
     */
    function _action_display( $args = array() ){

        $name = $args[0] ? $args[0] : null;
        $data = $args[1] ? $args[1] : null;
        if(!is_null($data)){
            foreach($data as $k=>$v){
                $this->assign($k,$v);
            }
        }
        @header("Content-type:text/html");
        die( $this->fetch( $name) );
    }

    function _action_layout( $name = array() ){
        $this->Layout_Name = $name[0];
    }
    function _action_layout_release( $content ){
        $name = $this->Layout_Name; 
        $this->Layout_Name = false;

        $this->assign('content',$content);
        $content = $this->fetch( $name );

        return $content;
    }
    /**
     * @name Action->fetch 模板编译方法
     * @function
     * @option $name {String} 模板名称
     * @return {String} 返回模板编译后的字符串
     * @description 
     * - 模板编译时会自动替换模板中的关键字，array('../Public/','../../','./' ) 替换为 TPL_URL_PUBLIC 、TPL_URL_ROOT、TPL_URL_RELATIVE
     * - 模板编译时会自动处理模板中的 include 关键字(注意需要有模板界符以及不要循环引用)
     * - 模板变量设置可以参考 @see Action->display 中设置，也可以参考 @ses Action->assign
     * - 模板编译引擎目前只支持PHP语法
     */
    function _action_fetch( $args = array() , $isInc = false ){

        $name = $args[0] ? $args[0] : null;

        #   模板参数允许【 null 】【 Method.html 】【 Module/Method.html 】【  THEME/Module/Method.html  】
    
        $path_info = array( ox::c('PATH_APP') , ox::c('DIR_TPL') , ox::c('TPL_THEME') , '3::MODULE' , '4::METHOD.html/Filename'  );

        $path_info[3] = $this->Module_Name;
        $path_info[4] = $this->Method_Name . '.html';

        if( !is_null($name) ){

            $name_split = explode('/', $name);
            switch ( count($name_split) ) {
                case '1':
                    $path_info[4] = $name_split[0];
                break;
                case '2':
                    $path_info[3] = $name_split[0];
                    $path_info[4] = $name_split[1];
                break;
                case '3':
                    $path_info[2] = $name_split[0];
                    $path_info[3] = $name_split[1];
                    $path_info[4] = $name_split[2];
                break;
            }
        }

        #   会尝试在共享目录中寻找模板文件
        $path_final = realpath( implode('/', $path_info ) );
        $path_info[0] = ox::c('PATH_PUB');
        $path_final_pub = realpath( implode('/', $path_info ) );

        $path_final = $path_final ? $path_final :$path_final_pub;

        if( !$path_final ){
            array_shift($path_info);
            return 'Template: <font color="red">'. implode('/', $path_info ) . '</font> is non-existent!' ;
        }

        $_root  = $_SERVER['DOCUMENT_ROOT'];              # '/Users/lyn/wwwroot/ue.baidu.com/10'
        $_uri   = dirname($_SERVER[SCRIPT_NAME]).'/';     # '/doll/'

        $_dir_tpl_theme = ltrim(str_replace('/./','/',ox::c('DIR_APP').'/'.ox::c('DIR_TPL').'/'.ox::c('TPL_THEME').'/'),'./');

        $_dir_public   = $_dir_tpl_theme.'/Public/';
        $_dir_relative = $_dir_tpl_theme.$path_info[2].'/'.$path_info[3].'/';

        ox::c('TPL_URL_ROOT'     , '//'.$_SERVER['HTTP_HOST'] . $_uri )  ;
        ox::c('TPL_URL_PUBLIC'   , ox::c('TPL_URL_ROOT') .str_replace(array('/./','//'),'/',$_dir_public.'/')) ;
        ox::c('TPL_URL_RELATIVE' , ox::c('TPL_URL_ROOT') .str_replace(array('/./','//'),'/',$_dir_relative));


        #   如果存在模板文件
        $content = file_get_contents( $path_final );

        #   模板关键字替换
        $content = preg_replace('/(href=")(?!http)(\.\.\/Public\/)([^"]+)(")/', "$1".ox::c('TPL_URL_PUBLIC' )."$3 $4",$content );
        $content = preg_replace('/(src=")(?!http)(\.\.\/Public\/)([^"]+)(")/', "$1".ox::c('TPL_URL_PUBLIC' )."$3 $4",$content );

        $content = preg_replace('/(href=")(?!http)(\.\/)([^"]+)(")/', "$1".ox::c('TPL_URL_RELATIVE' )."$3 $4",$content );
        $content = preg_replace('/(src=")(?!http)(\.\/)([^"]+)(")/', "$1".ox::c('TPL_URL_RELATIVE' )."$3 $4",$content );

        $content = preg_replace('/(href=")(?!http)(\.\.\/\.\.\/)([^"]+)(")/', "$1".ox::c('TPL_URL_ROOT' )."$3 $4",$content );
        $content = preg_replace('/(src=")(?!http)(\.\.\/\.\.\/)([^"]+)(")/', "$1".ox::c('TPL_URL_ROOT' )."$3 $4",$content );

        #   处理引用，引用文件不存在时，由PHP的include默认错误进行处理
        $content = preg_replace_callback('/'.ox::c('TPL_LEFT_DELIMITER').'\s?include\s+([^}]*)\s?'.ox::c('TPL_RIGHT_DELIMITER').'/', array('self','_fetch_inc_callback'), $content );

        $content = preg_replace('/('.ox::c('TPL_LEFT_DELIMITER').')\s*\$(.*?);?\s*('.ox::c('TPL_RIGHT_DELIMITER').')/','<?php echo \$$2; ?>',$content);
        $content = preg_replace('/('.ox::c('TPL_LEFT_DELIMITER').')\s*(.*?);?\s*('.ox::c('TPL_RIGHT_DELIMITER').'){1}/','<?php $2; ?>',$content);
        

        #   模板变量释放
        if( !$isInc ){
           
            $tmpfname = tempnam(sys_get_temp_dir(),"oxTpl_");
            $handle = fopen($tmpfname, "w");


            fwrite($handle, $content);
            fclose($handle);
                if( ox::c('TPL_ENGINE') == 'php' ){
                    
                    $content = '';
                    extract($this->Tpl_Variables );

                    ob_start();
                        $content = include( $tmpfname );
                        $content = ob_get_contents();
                        if( $this->Layout_Name ){
                            $content = $this->_action_layout_release($content);
                        }
                    ob_end_clean();
                    
                }else{
                    $content = A( ox::c('TPL_ENGINE') )->fetch( $tmpfname , $this->Tpl_Variables );
                }

            unlink($tmpfname);  
        }

        return $content;
    }

    /**
     * @name Action->assign 模板变量赋值
     * @function
     * @param $name  {String} 变量名
     * @param $value {Any} 变量值
     * @return {Any} 返回$value
     */
    #   模板赋值
    function _action_assign($args = array()){
        $n = $args[0] ? $args[0] : null;
        $v = isset($args[1]) ? $args[1] : null;
        return $v===Null ?  $this->Tpl_Variables[$n] :  $this->Tpl_Variables[$n] = $v;
    }
    function _fetch_inc_callback( $arg ){ return $this->_action_fetch( array( trim($arg[1]) ),true ); }

    #    魔术方法，以处理 A 找不到模块的情况，以及浏览器入口处理
    function __call( $name , $args ){

        $m = $this->Module_Name;

        #   浏览器的请求
        if( $name == '_call_' ){
            $a = $args[0]['a'];
            #   需要验证的话
            if(  method_exists($this, 'filterAssess') && !$this->filterAssess($a) ){
                ox::l('模块 filterAssess 验证不通过!',99,99);
            }
            if( method_exists($this, $a) ){
                ox::l('浏览器调用 '.$m.'->'.$a.' 已准备!');
                 return $this->$a();
            }else{
                ox::l('浏览器调用 '.$m.'->'.$a. ' 不存在!', 2 );
                #   尝试展现模板 Line:136
            }
        }else{
            #   模块调用请求处理
            $realname = '_action_'.$name ;
            $a = $name;
            if( method_exists($this, $realname) ){
                ox::l('模块调用 '.$a.'  已准备!');
                return $this->$realname( $args );
            }else{
                ox::l('模块调用 '.$a. ' 不存在!', 2 );
                #   尝试展现对应方法的模板
            }
        }
        #   尝试展现模板
        $template = realpath( $this->Module_From.'/'.ox::c('DIR_TPL').'/'.ox::c('TPL_THEME').'/'.$m.'/'.$a.'.html' );

        ox::l('尝试展现模板 '.$template ,1 );
        if( $template ){
            return $this->display();
        }else{
            ox::l( '模块方法和模板都不存在!' , 3 , 3 ) ;
        }
    }
}
/**
 * @name A 控制器模块获取快捷方法
 * @function
 * @short
 * @return {Instace} 返回控制器实例
 * @description 详细功能参考 @see ox::m
 * @example 调用其他控制器模块
 * A('tools')->args();
 */
function A( $m = '' ){ 
    $route = ox::r( array('m'=>$m) );
    ox::l( '调用模块: '.$route['m'] );
    return ox::m( $route );
}

#
# CSV模型
# 模型基类
/**
 * @file 数据模型支持
 * @description  该模块提供一个数据模型基类 Model ，同时提供一个快捷方法 M( $modelName ) ，并且是 Mysql、Csv 数据模型的基类
 */
/**
 * @name Modle 数据模型基类
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

class Model{

    private static $instances;

    public $handle;   #  链接对象
    public $operate;  #  操作栈

    /**
     * @name Model::getInstance 数据模型实例获取
     * @function
     * @param  $table {String} 表名
     * @option $engine {String} 数据库类型，如果不传入，默认使用配置中 DB_ENGINE 指定的类型；对于自定义模型，此参数无意义；自定义数据模型的类型，由其继承的类决定
     * @return {MoldeInstance} 返回一个自定义模型的实例或者通用数据模型的实例
     * @description 
     * - 数据模型调用处理，快捷方法  M( $modelName ) 即包装了该方法
     * - 基本数据模型 和 通用数据模型均没有构造器部分的代码，自定义数据模型可以在构造器中自由编写代码，如指定 数据模型的 真实表名、数据库指针
     * - 调用改方法时，如果实例没有初始化则会进行一次初始化操作，初始化操作会载入配置中的数据库信息；
     * - 如果是 通用模型 或者 自定义模型，并且没有设置 $this->operate['table'] 的话；会执行一次 $this->table( $table )
     */
    public  static function getInstance( $table = false , $engine = Null ){
        $engine  = ( $engine ? $engine : ox::c('DB_ENGINE') ).'Model';
        #   是否已缓存
        $cache_name = $table;
        if( $ins = Model::$instances[$cache_name] ){
            $ins->table( $table );
            return $ins;
        }
        #   检测自定义模型
        $mod_app = realpath( ox::c('PATH_APP').'/'.ox::c('DIR_MOD').'/'.$table.'.php'  );
        $mod_pub = realpath( ox::c('PATH_PUB').'/'.ox::c('DIR_MOD').'/'.$table.'.php'  );
        $mod = $mod_app ? $mod_app : $mod_pub ;
        
        $ins = $mod;

        if( $mod ){
            #   自定义模型
            require( $mod );
            $ins = new $cache_name;
        }else{
            #   通用模型
            $ins = new $engine;
        }
        $ins->handle = $ins->connect( $ins->db() );
        $ins->table( $table );
        Model::$instances[$cache_name] = $ins;
        return $ins;
    }

    function __call( $act , $args = array() ){
        $arg = $args[0];
        switch ( $act ) {

            /**
             * @name Model->db 数据库信息设置
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
                if( $arg ){
                    $this->handle = $this->connect( $args );
                    return $arg;
                }else{
                    return array(
                        'DB_ENGINE'=> ox::c('DB_ENGINE'),
                        'DB_PREFIX'=> ox::c('DB_PREFIX'),
                        'DB_HOST' => ox::c('DB_HOST'),
                        'DB_NAME' => ox::c('DB_NAME'),
                        'DB_USERNAME' => ox::c('DB_USERNAME'),
                        'DB_PASSWORD' => ox::c('DB_PASSWORD'),
                        'DB_DEFCHART' => ox::c('DB_DEFCHART'),
                    );
                }
                break;
            /**
             * @name Model->add 构建插入数据的SQL，并且执行
             * @function
             * @option $args {Array} 可选，可以传入一个数组作为插入的数据字段
             * @description  
             * @return {Boolean} 由继承 的的通用模型 决定返回值
             */
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
            /**
             * @name Model->del 构建删除数据的SQL，并且执行
             * @alias Model->delete
             * @function
             * @option $args {Array} 可选，可以传入一个数组作为删除数据的条件
             * @return {Boolean} 由继承 的的通用模型 决定返回值
             * @description 如果没有传入参数作为删除条件，并且也没有where设置，那么删除语句将不会执行，并且抛出一个错误日志
             */
            case 'del':             
            case 'delete':          #   删
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
            /**
             * @name Model->save 构建更新数据的SQL，并且执行
             * @function
             * @option $args {Array} 可选，可选，可以传入一个有键值对的数组作为更新的数据
             * @description 参数中没有提到字段的数据不会被修改。如果没有传入参数作为更新条件，并且也没有where设置，那么更新语句将不会执行，并且抛出一个错误日志
             */
            case 'save':            #   改
                if( $arg ){ $this->data($arg); }
                if( empty( $this->operate['where'] ) ){
                    if(  $this->operate['debug'] == 1 ){
                        ox::l('MQL查询错误！不允许无条件更新',3,3);
                    }
                    return false;
                }
                foreach ($this->operate['data'] as $k => $v) {  $kvs[] = '`'.$k.'` = \''.$v.'\''; }
                $sql[] = 'UPDATE';
                $sql[] = $this->operate['table'];
                $sql[] = 'SET';
                $sql[] = implode(',', $kvs);
                $sql[] = 'WHERE '.$this->operate['where'];
                $sql[] = empty( $this->operate['limit'] ) ? ' ' : 'LIMIT ' .$this->operate['limit'];
                return $this->query( implode(' ',$sql) );
                break;

            /**
             * @name Model->find 构建查询数据的SQL，并且执行
             * @function
             * @option $args {Int} 可选，可以传入需要查询的记录的条目数量，默认为1
             * @return {Array} 返回单条记录或者记录集合
             * @description 查询条目数量为一时，返回这条记录本身，多条记录则返回，这些记录组成的数组
             */
            case 'find':            #   查
                $result = $this->findAll( $arg ? $arg : 1 );
                return ( count($result) == 1 ) ? $result[0] :$result ;
                break;
            /**
             * @name Model->findAll 构建多条查询数据的SQL，并且执行
             * @function
             * @option $args {Int} 可选，可以传入需要查询的记录的条目数量
             * @return {Array} 记录集合
             * @description 如果$args为空，则返回所有满足条件的记录
             */
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
            case 'count' :

                if( $arg ){ $this->where($arg); }
                $sql[] = 'SELECT';
                $sql[] = ' count(*) as c ';
                $sql[] = 'FROM';
                $sql[] = $this->operate['table'];
                $sql[] = $this->operate['where']  ? 'WHERE '   .$this->operate['where'] : '';
                $result = $this->query( implode(' ',$sql) );
                return $result[0]['c'];
                break;
            /**
             * @name Model->table 设置操作栈的table
             * @function
             * @param $args {String} 可以以逗号分割，传入多个表名
             * @return {Instace} 返回实例
             * @description 压入操作栈前，会为表名加上前缀
             */
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
            /**
             * @name Model->where 设置操作栈的where
             * @function
             * @param $args {String} 可以以逗号分割，传入多个表名
             * @return {Instace} 返回实例
             * @description 没有设置where那么在更新和删除记录的时候会受到限制
             */
            case 'where':
                if( is_string($arg) ){
                    $this->operate[$act] = $arg;
                }else if( is_array($arg) ) {
                    foreach ( $arg as $k => $v) {
                        $kvs[] = '`'.trim(addslashes($k)).'` = '. ( is_int( $v ) ?  $v : '\''.addslashes($v).'\'' );
                    }
                    $this->operate[$act] = implode(' and ',(array)$kvs);
                }
                break;
            /**
             * @name Model->data 设置操作栈的data
             * @function
             * @param $args {Array} 一个条数据条目的数组
             * @return {Instace} 返回实例
             * @description 参数必须是有键值对的数组；参数的key和value会被 addslashes
             */
            case 'data':
                if( array_keys( $arg  ) !== range(0, count( $arg  ) - 1) ){
                    foreach( $arg as $k => $v ){
                        $data[addslashes($k)]= is_string($v) ? addslashes($v) : $v;
                    }
                    $arg = $data;
                }
                $this->operate[$act] = $arg;
                break;
            /**
             * @name Model->field 设置操作栈的field
             * @alias Model->select
             * @function
             * @param $args {String} 字段字符串
             * @return {Instace} 返回实例
             * @description 字段选择操作
             */
            case 'select':
            case 'field':
                $this->operate['field'] = $arg;
                break;
            /**
             * @name Model->limit 设置操作栈的limit
             * @function
             * @param  $start {String} 如果没有$end且不是以逗号分割的字符串，那么$start为0，这个参数会被设置为$end
             * @option $end   {String} 如果有这个参数，那么$start，$end即为limit的设置
             * @return {Instace} 返回实例
             * @description 数据条目限制
             */
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

            /**
             * @name Model->query 查询操作
             * @function
             * @param  $sql {String} SQL字符串
             * @return {Any} 由子类确定返回值
             * @description 必须由子类实现，否则将抛出一个错误日志
             */
            case 'query':
            /**
             * @name Model->connect 数据库连接操作
             * @function
             * @return {Any} 由子类确定返回值
             * @description  返回的值会被设置到$this->handel中，必须由子类实现，否则将抛出一个错误日志
             */
            case 'connect':
                ox::l('子类未实现操作 '. $act .' !',99,99);
                break;
            /**
             * @name Model->other 其他的操作
             * @function
             * @param  $args {String} 参数
             * @return {Instace} 返回实例
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
 * @name M 数据模型获取快捷方法
 * @function
 * @short
 * @return {Instace} 返回数据模型实例
 * @description 详细功能参考 @see Model::getInstance
 * @example 使用通用数据模型
 * M('user');
 * M('user','Csv');
 */
function M( $t = false , $e = null ){
    return Model::getInstance( $t , $e );
}
/**
 * @file Csv通用数据模型支持
 * @description  该模块提供一个Csv通用数据模型类
 */
/**
 * @name CsvModel
 * @class Csv通用数据模型类
 * @extends Model
 * @description  CsvModel 支持在本地以文本形式读写通用的Csv文件数据，可以免除数据库的需求
 * 约定第一行作为字段信息
 */
class CsvModel extends Model{

    #   确认 主机连接/目录
    function connect( $db ){

        $path = ox::c('PATH_APP') .'/'. $db['DB_NAME'] ;
        $this->operate['PATH_DB'] = $path;
        if( !realpath($path) && ! mkdir( $path , 0755) ){
            ox::l( $this->operate['PATH_DB'].'不存在，且 项目目录不具备写权限!'  ,3, 3);
        }
        

        return 'connected';
    }

    #   确认文件并且设置handle
    function table($table){

        $path = $this->operate['PATH_DB'] .'/'.$table.'.csv' ;
        $this->operate['PATH_TABLE'] = $path;

        if( !file_exists($path) && !$this->initTable() ){
            ox::l( $this->operate['PATH_TABLE'].' 不存在，且数据目录不具备写权限!'  ,3, 3);
        }

        $this->setHandle();


        return $this;
    }

    /**
     * 初始化数据表
     * @return Boolean 如果自定义模型有structure方法，那么会从这个方法的
     */
    function initTable(){
        $path = $this->operate['PATH_TABLE'];
        if( method_exists($this, 'structure') ){
            $keys = array_keys( $this->structure() );
           if( touch($path) ){
                $this->setHandle();
                return file_put_contents($path,implode(',', $keys));
           }
        }
        return false;
    }

    /**
     * 设置数据模型的handle，在打开一个文件前，如果已经有了handle则会先关闭
     * @param string $mode 可选，打开模式，默认为rw
     */
    function setHandle( $mode = "rw"){
        $file = $this->operate['PATH_TABLE'];
        if( $this->handle ){
            fclose($file);
        }
        $this->handle = fopen( $file ,$mode);
    }

    function query(){}
}
/*
#    CSV数据模型类
class CsvModel extends Model{
    public  $opt = array();

    #    处理数据路径
    function __construct(){
        $this->tablePre = J( ox::c('PATH_APP'),ox::c('DB_PREFIX') );
        $this->tableExt = '.csv';
        if( !realpath( $this->tablePre ) ){
            mkdir( $this->tablePre , 0700);
        }
        $this->focusLimit = -1;
    }

    #    设置表名，创建空数据文件，$table 参数必有
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

    #    查询，第一个参数为 字段列表，返回一个带有键的数组
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

    #    修改、删除一条数据，只允许传入 一个数组作为数据。
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

                #    条件修改，-1 为删除，跳过写入
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

    #    清理条件，初始化各种条件
    function clear(){
        $this->focusLimit = -1;
        $this->focusWhere = array();
        $this->focusField = false;
    }

    #    查询支持的条件限制
    function where( $arg ){
        #    条件限制 = < > ，多个条件连接时 以多个 M()->where()->where() 连接
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
        #    如果数据字段没有键，则加上
        if(  preg_match( '/\d+/', implode( '',array_keys($arg) ) ) ){
            if( count($arg)>count($this->tableField)-2 ){ $arg = array_slice( $arg, 0,count($this->tableField)-2); }
            $argl = count($arg);
            $data = array_combine( array_slice($this->tableField,2,$argl) ,$arg );
        }
        $this->focusData = $data;
        return $this;
    }

    #    创建数据文件,参数为 字段，返回 Bool
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
    #    删除数据表，返回 Bool
    function drop( ){
        fclose( $this->link );
        return unlink( $this->tablePath );
    }

    #    条件判断运算，确认输入的数据符合当前 focusWhere 的条件，支持 = 、 > 、< 运算符，返回 Bool
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
*/

#   MySql 数据库模型
# Mysql类


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
            dump($sql);
            ddump($this);
        }
        $this->operate = array('table'=>$this->operate['table']);
        #    每次查询过后清理查询参数条件
        $sql = trim($sql);
        $resource = @mysql_query( $sql, $this->handle );
        if(!$resource){
            ox::l(mysql_error(),3);
            if(  $this->operate['debug'] == 1 ){
                ox::l('MYSQL查询错误!',3,3);
            }else{
                die('MYSQL查询错误!');
            }
        }
        if( is_resource( $resource ) ){
            while( $row = mysql_fetch_assoc( $resource )) { $result[] = $row; }
            return (array)$result;
        }
        return $resource;
    }
}

#   开发调试函数
#   开发调试函数
function dump ($arg) { @header("Content-type:text/html"); echo '<pre>'; var_dump($arg) ; echo '</pre>' ; }
function ddump($arg) { @header("Content-type:text/html"); echo '<pre>'; var_dump($arg) ; die( '</pre>'); }
function json ($arg) { @header("Content-type:text/json"); echo json_encode($arg) ; }
function djson($arg) { @header("Content-type:text/json"); die( json_encode($arg)); }
function olog( $msg , $level = 1 , $show = 0){ return ox::l($msg,$level,$show); }

#   快捷函数
# 快捷函数

/**
 * 路径合并函数
 * @return string
 */
function J(){
    $args = func_get_args();
    foreach( $args as $item){  $temp[] = trim($item,"/"); }
    return ( preg_match('/^\//',$paths[0]) ? '/' : '' ).join('/', $temp);
}


/**
 * @param null $n
 * @return mixed
 */
function I( $n=Null ){
     $p1 = C('PATH_APP').'/'. C('DIR_INC').'/'.$n.'.class.php'  ;
     $p2 = C('PATH_COM').'/'. C('DIR_INC').'/'.$n.'.class.php'  ;
     if( realpath(  $p1 ) ){ return include_once( $p1 ); }
     if( realpath(  $p2 ) ){ return include_once( $p2 ); }
     echo 'Class '.$n.' is non-existent! in '.$p1;
 }