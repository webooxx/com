<?php
/**
 * @fileOverview 简单的PHP-MVC框架
 * @author weboox@gmail.com
 * @version 0.1.0.0
 */

#    框架入口核心类
class ox {

    static $c;  #   配置
    static $m;  #   模块
    
    public static function c( $n,$v = NULL ){
        return $v === NULL ? ox::$c[$n] : ox::$c[$n] = $v;
    }

    #   路由处理 返回 array( m=> '' , a=> '' )
    static function route( $_GET ){

        if( ox::$c['ROUTE'] ){return ox::$c['ROUTE']( $_GET );}

        $req_r = ox::c('DEF_REQ_KEY_RUT');
        $req_a = ox::c('DEF_REQ_KEY_ACT');
        $req_m = ox::c('DEF_REQ_KEY_MOD');


        if( isset($_GET[$r_key]) ){
            $r = explode('/', $_GET[$req_r] );
            $m = $r[0];
            $a = $r[1];
        }else{
            $m = $_GET[$req_m];
            $a = $_GET[$req_a];
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
        ox::c('PATH_ROOT',PATH_ROOT);
        ox::c('PATH_COM' ,PATH_COM );

        #   分析请求对象
        $req = ox::route( $_GET );
    
        #    实例化请求 的 控制器模块,需要控制器自行处理 
        $ins = ox::module( $req );
        $ins->_call_( $req );

    }

    #    模块管理
    public static function module( $req ){

        $m = $req['m']; #   请求的模块
        $a = $req['a']; #   请求的方法

        ox::$c['SYS_REQ'][] = $req;

        $class = $m.ox::c('DEC_ACT_EXT');

        // if( class_exists( $class ) ){
        //     ox::$modules[$m] = new $class;
        // }
        if( ox::$m[$m] ){
            return ox::$m[$m];
        }else{

            #   模块文件
            $act = $class.'.php';
            $act_app = realpath( ox::c('PATH_ROOT').'/'.ox::c('DIR_ACT').'/'.$act );
            $act_com = realpath( ox::c('PATH_COM' ).'/'.ox::c('DIR_ACT').'/'.$act );

            $act = $act_app ? $act_app : $act_com;

            if(!$act){
                $tpl = '/'.ox::c('DIR_TPL').'/'.$m.'/'.$a.'html' ;
                $tpl_app = ( ox::c('PATH_ROOT').$tpl);
                $tpl_com = ( ox::c('PATH_COM').$tpl );
                $act = $tpl_app ? $tpl_app : $tpl_com;
            }

            include_once( $act );

            ox::$m[$m] = new $n;

var_dump( $tpl );
die();
            #    如果找不到控制器模块文件，将尝试去系统自带的控制器中去找

            #    如果找不到对应系统控制器模块文件,尝试直接展现模板
            if( !$actFile ){
                $tplFile = realpath( ox::joinp(  ox::cfg('PATH_APP'), ox::cfg('DIR_TPL'),$m ,$a.'.html') );
                if($tplFile){
                    ox::$modules[$m] = new Action;
                    ox::$modules[$m]->mod_name=$m;
                    return ox::$modules[$m];
                }else{
                    die( 'Action '.$m. ' is non-existent!' );
                }
            }

            include_once( $actFile );
            ox::$modules[$m] = new $n;
            ox::$modules[$m]->mod_name=$m;
            return ox::$modules[$m];
        }
    }

   
    #    管理全局参数配置

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

require('dev/Action.php');
require('dev/Model.php');

require('dev/MysqlModel.php');
require('dev/CsvModel.php');




#    效率函数
function dump ($arg) { @header("Content-type:text/html"); echo '<pre>'; var_dump($arg) ; echo '</pre>' ; }
function ddump($arg) { @header("Content-type:text/html"); echo '<pre>'; var_dump($arg) ; die( '</pre>'); }
function json ($arg) { @header("Content-type:text/json"); echo json_encode($arg) ; }
function djson($arg) { @header("Content-type:text/json"); die( json_encode($arg)); }
function log4j($msg) { echo "[".date('Y-m-d H:i:s')."]".( C('SYS_COMMAND_MOD') ?  $msg."\n" : '<p>'.$msg.'</p>' );}
function o2a($obj){ $result = array(); if(!is_array($obj)){ if($var = get_object_vars($obj)){ foreach($var as $key => $value){ $result[$key] = o2a($value); } } else{ return $obj; } } else{ foreach($obj as $key => $value){ $result[$key] = o2a($value); } } return $result; }
 #    路径合并函数
function joinp(){
    $paths = func_get_args();
    foreach( $paths as $item){  $temp[] = trim($item,"/"); }
    return ( preg_match('/^\//',$paths[0]) ? '/' : '' ).join('/', $temp);
}

#    快捷方式
function A( $n = NULL ){ return is_null($n) ? ox::$modules( C('DEF_MOD') ) : ox::$modules( $n ); }

function C( $n = NULL,$v = NULL ){ return ox::cfg($n,$v);}
function F( $n , $v = NULL){
    $n = explode('::', $n);
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
function J(){ $args = func_get_args(); return  call_user_func_array(array('ox', 'joinp'), $args );}
function M( $table = Null, $type = Null ){ return Model::getInstace( $table , $type); }
function S( $n = NULL,$v = NULL ){ return ox::set($n,$v);}

return  ox;