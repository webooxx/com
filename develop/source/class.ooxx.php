<?php
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
    /// import class.ooxx.defCfgs.php


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

        /// import if.command.php;

        ooxx::$defCfgs = array_merge( ooxx::$defCfgs , (array)$cfgs);

        #   运行时路径
        ooxx::$defCfgs['PATH_NOW'] = dirname($_SERVER[SCRIPT_FILENAME]);
        ooxx::$defCfgs['PATH_APP'] = ooxx::joinp( ooxx::$defCfgs['PATH_NOW'] , ooxx::$defCfgs['DIR_APP'] );
        ooxx::$defCfgs['PATH_MVC'] = ooxx::joinp( ooxx::$defCfgs['PATH_NOW'] , ooxx::$defCfgs['DIR_MVC'] );
        ooxx::$defCfgs['PATH_COM'] = ooxx::joinp( ooxx::$defCfgs['PATH_APP'] , ooxx::$defCfgs['DIR_COM'] );

        /// import if.tpl_path_com.php;

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