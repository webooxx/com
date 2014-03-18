<?php
/**
 * @fileOverview 简单的PHP-MVC框架
 * @author weboox@gmail.com
 * @version 0.1.0.0
 */
define('LEVEL_INFO', 1);
define('LEVEL_WARRING', 2);
define('LEVEL_ERROR', 3);

#    框架入口核心类
class ox {

    static $c = array();  #   配置
    static $m = array();  #   模块
    static $l = array();  #   日志
    
    #   配置管理
    static function c( $n,$v = NULL ){
        return $v === NULL ? ox::$c[$n] : ox::$c[$n] = $v;
    }

    #   日志管理
    static function l( $msg , $level = 1 , $show = 0 ){
        ox::$l[] = array(
            'LEVEL'=> $level,
            'TIME' => date('Y-m-d H:i:s'),
            'INFO' => array_slice( debug_backtrace() ,0,2),

        );
        if( $show > 0 ){
            foreach (ox::$l as $l ) {
                if( $l['LEVEL'] >= ox::c('LOGBASE') &&  $l['LEVEL']  <= $show ){
                    $json[]  = $l;
                }
            }
            die( json_encode( array_reverse( $json )) );
        }
        return true;
    }

    #   模块管理
    public static function m( $req ){

        $m = $req['m']; #   请求的模块
        $a = $req['a']; #   请求的模块
        $s = ox::c('PATH_APP');    #   控制器模块目录的父目录
        $class = $m.ox::c('DEC_ACT_EXT');

        if( ox::$m[$m] ){
            ox::l( 'Module Class is loaded!' , LEVEL_WARRING );
            return ox::$m[$m];
        }else{
            if( class_exists( $class ) ){
                ox::$m[$m] = new $class;
                ox::l( 'Module Class in single!', LEVEL_WARRING );
            }else{

                #   模块文件
                $act = $class.'.php';
                $act_app = realpath( ox::c('PATH_APP').'/'.ox::c('DIR_ACT').'/'.$act );
                $act_com = realpath( ox::c('PATH_COM').'/'.ox::c('DIR_ACT').'/'.$act );
                $act = $act_app ? $act_app : $act_com;
                if( $act_com ){
                    $s = ox::c('PATH_COM');
                }

                #   仍然没有模块文件，对应用目录下的模板目录进行侦测
                if(!$act){
                    ox::l( 'Module is non-existent!', LEVEL_WARRING );
                    $tpl = realpath( ox::c('PATH_APP').'/'.ox::c('DIR_TPL').'/'.ox::c('TPL_THEME').'/'.$m );
                    !$tpl ? ox::l( 'Module & Template is non-existent!' , LEVEL_ERROR , 3 ) : ox::$m[$m] = new Action;
                }else{
                    ox::l( 'Module is existent!', LEVEL_INFO );
                    include_once( $act );
                    ox::$m[$m] = new $class;
                }

            }
        }
        ox::l( $m.' -> '.$a .' from '. $s, LEVEL_INFO );
        ox::$m[$m]->Module_Name = $m;
        ox::$m[$m]->Module_From = $s;
        ox::$m[$m]->Method_Name = $a;
        return ox::$m[$m];
    }

    #   路由处理 返回 array( m=> '' , a=> '' )
    static function route( $GET ){
        if( ox::$c['ROUTE'] ){return ox::$c['ROUTE']( $GET );}
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
    
    #    框架运行
    static function init( $argv , $cfgs = array() ){

        @date_default_timezone_set("PRC");
        ox::$c = require('dev/cfg.php');

        #    命令行模式 将参数完全复制到 $_GET 对象中
        if( count($argv) > 1 ){
            $al = count($argv); ox::$cfg['CMDMODE'] = true; 
            for($i=1;$i<$al;$i++){
                $arg = explode('=',$argv[$i]); $_GET[$arg[0]]=$arg[1]; 
            } 
            $_REQUEST = $_GET;
         }

        #   合并用户自定义参数
        ox::$c = array_merge( ox::$c , $cfgs);

        #   路径
        ox::c('PATH_APP',PATH_APP);
        ox::c('PATH_COM',PATH_COM );

        #   分析请求对象
        $req = ox::route( $_GET );
        #    实例化请求 的 控制器模块,需要控制器自行处理 
        $ins = ox::m( $req );
        $ins->_call_( $req );
    }

}

#   控制器模块
require_once('dev/Action.php');
#   控制器模块
require_once('dev/Model.php');
#   开发调试函数
require_once('dev/funcs_debug.php');
#    快捷方式
require_once('dev/funcs_short.php');

return  ox;