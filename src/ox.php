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
                    $json[]  = $l['MSG'];
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
        $class = $m.ox::c('DEF_ACT_EXT');

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
        ox::$c = require_once('cfg.php');

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
require_once('Action.php');

#
include_once('CsvModel.php');

#   MySql 数据库模型
include_once('MysqlModel.php');

#   开发调试函数
include_once('funcs_debug.php');

#   快捷函数
include_once('funcs_short.php');