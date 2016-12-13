<?php

/**
 * 框架启动器
 */
final class mvc
{

    /**
     * @var array 配置
     */
    static $config = array();

    /**
     * @var array 模块
     */
    static $module = array();

    /**
     * @var array 框架运行日志
     */
    static $log = array();

    /**
     * 框架运行时的配置存取
     * @param string $n 配置名称
     * @param null $v 值
     * @return null 返回设置的值
     */
    static function config($n = '', $v = null)
    {
        return $v === null ? mvc::$config[$n] : mvc::$config[$n] = $v;
    }

    /**
     * 框架入口函数
     * @param string $argv 命令行模式下的参数传入 ,若有  php index.php r=index/test id=1
     * @param array $_cfg 用户参数配置
     */
    static function init($argv = '', $_cfg = array())
    {
        self::log('框架 init 开始');
        /**
         * 载入默认的配置 & 合并自定义配置
         */
        $src_cfg = require_once 'src/config.php';
        self::$config = array_merge($src_cfg, $_cfg);

        /**
         * 命令行模式下, 参数完全复制到 $_GET/$_REQUEST 对象中
         */
        if (count($argv) > 1) {
            $al = count($argv);
            for ($i = 1; $i < $al; $i++) {
                $arg = explode('=', $argv[$i]);
                $_GET[$arg[0]] = $arg[1];
            }
            $_REQUEST = $_GET;

            self::config('MODE_CMD', true);
        }

        /**
         * 运行时重设
         */
        self::config('PATH_APP', PATH_APP);
        // self::config('PATH_PUB', PATH_COM);
        self::config('PATH_COM', PATH_COM);

        $route = self::route($_GET);
        self::log('路由结果 ' . $route['m'] . ' -> ' . $route['a']);
        $module = self::module($route);

        $module->moduleName = $route['m'];
        $module->methodName = $route['a'];
        $module->className = $route['m'] . self::config('DEF_ACT_EXT');

        /**
         * 进入 Action 的外部调用
         */
        $module->_out__call($route);

        self::log('框架 init 结束');

        if (isset($_GET['debugger'])) {
            echo '<hr>';
            dump(self::$log);
        }
    }

    /**
     * 根据请求参数加载需要的模块
     * @param array $route 路由结果,包括一个 m ( module )、一个 a ( act )
     * @return Action|mixed
     * @throws Exception
     */
    static function module($route = array())
    {


        $moduleName = $route['m'] . self::config('DEF_ACT_EXT');

        /**
         * 尝试读取已缓存的模块（此处缓存 $moduleFile）
         */
        if (isset(self::$module[$moduleName])) {
            self::log('返回已经缓存控制器:' . $moduleName);
            return self::$module[$moduleName];
        }

        /**
         * 尝试引入控制器模块文件 - 1
         */
        $moduleFile = realpath(self::config('PATH_APP') . '/' . self::config('DIR_ACT') . '/' . $moduleName . '.php');
        $moduleFile_share = self::getShareAppFile(self::config('DIR_ACT') . '/' . $moduleName . '.php');
        $moduleFile = $moduleFile ? $moduleFile : $moduleFile_share;

        if ($moduleFile && include_once($moduleFile)) {
            self::log('控制器加载:' . $moduleFile);
            self::$module[$moduleName] = new $moduleName();
            self::$module[$moduleName]->modulePath = dirname($moduleFile);
            self::$module[$moduleName]->_inside_call_setConfigTemplateURL();
            return self::$module[$moduleName];
        }


        /**
         * 尝试获取默认对应的模版文件 - 2
         * 为了逻辑清晰：初始化的 默认模板查找，和 Action->_out__call 分开， _out__call 方法不存在（文件存在）时，也会尝试展现默认的模版
         */
        self::log('没有可用的控制器: ' . $moduleName . ' ,尝试展现模板 ');


        $templateName = self::config('DIR_TPL') . '/' . self::config('TPL_THEME') . '/' . $route['m'] . '/' . $route['a'] . self::config('DEF_TPL_EXT');
        $templateFile = realpath(self::config('PATH_APP') . '/' . $templateName);


        self::log('模板路径: ' . $templateName);

        if ($templateFile) {
            self::$module[$moduleName] = new Action();
            self::$module[$moduleName]->modulePath = realpath(self::config('PATH_APP')) . '/' . self::config('DIR_ACT');
            self::$module[$moduleName]->_inside_call_setConfigTemplateURL();
            return self::$module[$moduleName];
        }
        throw new Exception("没有可用的控制器: " . $moduleName . ' ,也没有可以展现的模板: ' . $templateName, 1);
    }

    /**
     * 获取共享目录中的文件
     * @param string $file 文件名
     * @return bool|string  如果所有共享目录中都不存在文件,返回false,否则返回真实的文件路径
     */
    static function getShareAppFile($file = '')
    {
        $paths = explode(',', self::config('PATH_PUB'));
        $result = false;
        foreach ($paths as $value) {
            $result = realpath($value . $file);
            if ($result) {
                return $result;
            }
        }
        return $result;
    }

    /**
     * 根据 $_GET 分析需要执行的模块
     * @param array $GET
     * @return array
     */
    static function route($GET = array())
    {
        self::log('进入路由处理');
        $req_r = self::config('DEF_REQ_KEY_RUT');
        $req_a = self::config('DEF_REQ_KEY_ACT');
        $req_m = self::config('DEF_REQ_KEY_MOD');
        if (isset($GET[$req_r])) {
            $r = explode('/', $GET[$req_r]);
            $m = isset($r[0]) ? htmlspecialchars(trim($r[0])) : self::config('DEF_MOD');
            $a = isset($r[1]) ? htmlspecialchars(trim($r[1])) : self::config('DEF_ACT');
            if (isset($r[2])) {
                $args = array();
                $len = count($r);
                for ($i = 2; $i < $len; $i += 2) {
                    if ($r[$i] === '') {
                        continue;
                    }
                    $args[$r[$i]] = isset($r[$i + 1]) ? $r[$i + 1] : null;
                }
                $_GET = array_merge($_GET, $args);
            }
        } else {
            $m = isset($GET[$req_m]) ? htmlspecialchars(trim($GET[$req_m])) : self::config('DEF_MOD');
            $a = isset($GET[$req_a]) ? htmlspecialchars(trim($GET[$req_a])) : self::config('DEF_ACT');
        }
        return array(
            'm' => $m,
            'a' => $a,
        );
    }

    /**
     * 框架运行时的日志
     * @param string $msg
     * @param string $prefix
     */
    static function log($msg = '', $prefix = '[Log]')
    {
        $mt = explode(' ', microtime());
        $trace = debug_backtrace();
        $trace = $trace[0];
        $info = $trace['file'] . ' :: ' . $trace['line'];
        $msg = $prefix . ' ' . $msg;
        self::$log[] = array(
            'date' => date('Y-m-d H:i:s', $mt[1]) . ' ' . substr($mt[0], 2),
            'file' => $info,
            'text' => $msg,
        );
    }

    /**
     * 异常处理
     * @param Exception $e
     * @return null
     */
    static function exception($e)
    {
        self::log($e->getMessage(), '[Exception:' . $e->getCode() . ']');
        self::dieLog();
    }

    /**
     * 运行时错误处理
     * @param int $errNo 错误号
     * @param string $errStr 错误信息
     * @param string $errFile 错误文件
     * @param int $errLine 错误文件行号
     * @return boolean
     */
    static function error($errNo, $errStr, $errFile, $errLine)
    {
        self::log($errStr . ' from ' . substr($errFile, strlen(PATH_APP) + 1) . ' line ' . $errLine, '[Warring:' . $errNo . ']');
        return true;
    }

    /**
     * 致命错误处理
     * @return null
     */
    static function shutdown()
    {

        $e = error_get_last();
        $msg = $e['message'] . ' from ' . substr($e['file'], strlen(PATH_APP) + 1) . ' line ' . $e['line'];
        $prefix = '[FATAL ERROR:' . $e['type'] . ']';

        if ($e) {
            self::log($msg, $prefix);
            self::dieLog();
        }
    }

    /**
     * 致命错误结束后的【回显、记录、发送处理】
     * error_log($msg,1,"xuzhenhua@baidu.com","From: robot@mvc.com");
     * @return null
     */
    static function dieLog()
    {
        djson(self::$log);
    }
}


/**
 * 时间、错误、异常处理
 */
@date_default_timezone_set("PRC");
error_reporting(0);
set_exception_handler('mvc::exception');
set_error_handler('mvc::error');
register_shutdown_function('mvc::shutdown');

/**
 * 尝试在 inc 目录下找到合适的类
 */
spl_autoload_register(function ($class) {
    include 'inc/' . $class . '/' . $class . '.class.php';
});
/**
 * 快速存取配置
 * @param string $n
 * @param null $v
 * @return mixed|null
 */
function C($n = '', $v = null)
{
    return mvc::config($n, $v);
}

/**
 * 快速调用Action Module
 * @param string $m
 * @return Action|mixed
 */
function A($m = '')
{
    $route = mvc::route(array(mvc::config('DEF_REQ_KEY_MOD') => $m));
    mvc::log('内部调用模块: ' . $m);
    return mvc::module($route);
}

#   必要的控制器
require_once 'src/Action.php';


#   可选数据模型
include_once 'src/MysqlModel.php';


#   可选快捷函数
include_once 'src/funcs_debug.php';