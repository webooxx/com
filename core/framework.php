<?php

/**
 * ?                    --> app/control/IndexController.php index();
 * ?r=a                 --> app/control/IndexController.php a();
 * ?r=a/b               --> app/control/aController.php b();
 * ?r=a/b/c             --> app/control/a/bController.php c();
 * ?r=a/b/c...          --> app/control/a/b/cController.php ..();
 */
namespace {

}

namespace Framework {

class Core {

    private static $config    = array();        //  全局参数
    private static $request   = array();        //  请求内容
    public  static $executive = array();        //  控制器执行信息

    public  static $executiveInstance = array();

    public static function init( $config = array() ){
        self::setConfig( $config );
        self::setRequest();
        self::setExecutive();

        $instance = self::getExecutiveInstance();
        $funcName = self::$executive['method'];
        return $instance -> $funcName();

    }

    private static function setConfig( $config ){
        $baseConfig = array(
            'ROUTER_KEY' => 'r',
            'APP_NAME'   => 'app',
        );
        self::$config =  $config + $baseConfig;
    }

    private static function setRequest(){
        if( !is_null( $argv ) ){
            $argLength = count($argv);
            for ($i = 1; $i < $argLength; $i++) {
                $arg = explode('=', $argv[$i]);
                $_GET[$arg[0]] = $arg[1];
            }
            $_REQUEST = $_GET;
        }
        self::$request = $_REQUEST;
    }

    private static function setExecutive(){
        $routeStr        = self::$request['r'];
        $finalExecutive  = array(
            'prefix' => array( self::$config['APP_NAME'] ),  //  controller 下的子模块目录，需要使用 namespace
            'module' => 'index',
            'method' => 'index'
        );
        if( is_null( $routeStr )){
            self::$executive = $finalExecutive;
            return null;
        }

        $splitRouter       = explode('/', self::$request['r'] );
        $splitRouterLength = count($splitRouter);

        if( $splitRouterLength === 1 ){
            $finalExecutive['method'] = $splitRouter[0];
        }
        if( $splitRouterLength === 2 ){
            $finalExecutive['module'] = $splitRouter[0];
            $finalExecutive['method'] = $splitRouter[1];
        }
        if( $splitRouterLength > 2 ){
            $finalExecutive['prefix'] = array_slice( $splitRouter, 0, -2 );
            $finalExecutive['module'] = array_slice( $splitRouter, -2, 1 )[0];
            $finalExecutive['method'] = array_slice( $splitRouter, -1 )[0];
            array_unshift( $finalExecutive['prefix'] , self::$config['APP_NAME'] );
        }

        self::$executive = $finalExecutive;
        return null;
    }

    private static function getExecutiveInstance(){

        $nameSpace = implode( self::$executive['prefix'] ,'\\');
        $className = ucfirst( self::$executive['module'] ).'Controller';

        $fullClassName = '\\'.$nameSpace.'\\'.$className;

        if( self::$executiveInstance[$fullClassName] ){
            return self::$executiveInstance[$fullClassName];
        }
        $fullFilePath =  array(
            'app'  => self::$executive['prefix'][0],
            'ctrl' => 'controller',
            'ns'   => implode( array_slice(self::$executive['prefix'],1) ,'/'),
            'file' => $className . '.php'
        );


        //  未加载类，尝试引入文件
        if( !class_exists( $fullClassName , false ) ){

            $fullFilePath = array_filter( $fullFilePath );
            $fullFilePath = implode( $fullFilePath , '/' );
            $realFilePath = realpath( $fullFilePath );

            if( !$realFilePath ){
                throw new \Exception("Can't find the module file: ".$fullFilePath, 1);
                return null;
            }
            include_once($realFilePath);
        }

        //  实例化改类，并返回
        $Instance = new $fullClassName();
        self::$executiveInstance[$fullClassName] = $Instance;
        return $Instance;
    }

}


/**
 * 控制器
 */
class Controller
{
    public $filePath = '';

    /**
     * 权限验证，从外部（浏览器）开始执行时，会先执行此函数，如果返回不为 true 则 throw 一个错误或者执行 auth_fail_handler
     * @var [type]
     */
    public functoin auth( $funcName ){
        return $funcName && true;
    }
}




}


namespace Share {

class Utils {
    function ok(){ echo 'ok'; }
}

}
