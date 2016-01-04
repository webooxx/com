<?php

/**
 * @name ox
 * @class 框架核心类
 * @description 每个请求都由 ox::init() 方法发起
 */
class ox
{

    /**
     * @name ox ::$c 框架核心类的配置信息存储
     * @property {Array}
     */
    static $c = array();  #   配置
    /**
     * @name ox ::$m 框架核心类的控制器模块缓存
     * @property {Array}
     */
    static $m = array();  #   模块
    /**
     * @name ox ::$l 框架核心类的日志堆栈
     * @property {Array}
     */
    static $l = array();  #   日志

    /**
     * @name ox ::c 配置信息存取管理
     * @function
     * @param  $n {String} 配置的名称
     * @option $v {Any}  配置的值
     * @return {Any} 对应配置信息的值
     * @description 框架配置配置存取方法，快捷方法 C( $n , $v ) 即包装了该方法
     */
    static function c($n, $v = NULL)
    {
        return $v === NULL ? ox::$c[$n] : ox::$c[$n] = $v;
    }

    /**
     * 框架日志中心
     * @param string $msg 日志信息，包括不限于基本数据类型
     * @param int $level 日志等级
     * @param  bool $isDeath 框架在严重错误时传入此项，可以停止运行
     * @return bool
     * @description
     * 配置 LOG_SHOW_BASE 默认为 1 ；在上线之后，可以把 LOG_SHOW_BASE 设置为 3，只显示 >=3 (错误级)的信息
     * 参数 $outLeastLevel 的作用，如果只希望显示当前这条日志信息，可以 ox::l( $msg , 99 , 99) ，那么显示的日志  $log >= 3 <= 99
     * 目前的展现方法只使用了 json_encode ，还没有使用日志模块进行输出
     */
    static function l($msg, $level = 1, $isDeath = false)
    {
        $mt = explode(' ', microtime());
        $log['time'] = date('Y-m-d H:i:s', $mt[1]) . ' ' . substr($mt[0], 1);
        $log['msg'] = $msg;
        $log['level'] = $level;

        if ($level > 1) {             #   非一般的日志，需要跟踪Trace信息
            $trace = debug_backtrace();
            foreach ($trace as $a) {
                $log['trace'][] = $a['file'] . ' : ' . $a['line'] . ' | ' . $a['class'] . $a['type'] . $a['function'] . '(' . implode(',', $a['args']) . ')';
            }
        }
        ox::$l[] = $log;
        if ($isDeath) {               #   程序无法继续运行，需要停止，并且展现 > 这个级别 的信息（或者 > LOG_SHOW_BASE 的定义）
            $json[] = 'ox::l goto death!';
            foreach (ox::$l as $l) {
                if ($l['level'] >= (int)ox::c('LOG_SHOW_BASE')) {
                    unset($l['level']);
                    $json[] = $l;
                }

            }
            die((string)json_encode($json));
        }
        return true;
    }


    public static function p($append)
    {
        $paths = explode(',', ox::c('PATH_PUB'));

        $result = false;
        foreach ($paths as $value) {
            $result = realpath($value . $append);
            if ($result) {
                return $result;
            }
        }

        return $result;
    }

    /**
     * @name ox ::m 模块调用方法
     * @function
     * @param $req {Array}  控制器模块请求参数
     * @param $req ['m'] {String}    请求的控制器模块名称
     * @param $req ['a'] {String}    请求的模块方法
     * @return {ActionInstance} 控制器实例
     * @description 框架模块调用处理，快捷方法 A( $Module ) 即包装了该方法
     */
    public static function m($req)
    {

        $m = $req['m']; #   请求的模块
        $a = $req['a']; #   请求的模块
        $s = ox::c('PATH_APP');    #   控制器模块目录的父目录
        $class = $m . ox::c('DEF_ACT_EXT');

        if (ox::$m[$m]) {
            ox::l($m . '模块已缓存!');
            return ox::$m[$m];
        } else {

            if (class_exists($class)) {
                ox::$m[$m] = new $class;
                ox::l($m . '模块已经预加载了!');
            } else {

                #   模块文件
                $act = $class . '.php';
                ox::l('准备从APP和PUB的ACT目录下查找模块文件' . $act, 1);
                $act_app = realpath(ox::c('PATH_APP') . '/' . ox::c('DIR_ACT') . '/' . $act);
                $act_pub = ox::p('/' . ox::c('DIR_ACT') . '/' . $act);

                $act = $act_app ? $act_app : $act_pub;
                if (!$act_app && $act_pub) {
                    $s = ox::c('PATH_PUB');
                }

                #   仍然没有模块文件，对应用目录下的模板目录进行侦测
                if (!$act) {
                    ox::l('控制器模块文件不存在,尝试展现APP和PUB的ACT目录下的模板!');
                    $tpl = realpath(ox::c('PATH_APP') . '/' . ox::c('DIR_TPL') . '/' . ox::c('TPL_THEME') . '/' . $m);
                    !$tpl ? ox::l('没有找到模板文件，系统无法运行!', 3, true) : ox::$m[$m] = new Action;
                } else {
                    ox::l('准备引入控制器模块文件!' . $m, 1);
                    include_once($act);
                    ox::$m[$m] = new $class;
                }

            }
        }
        ox::l('运行 ' . $m . 'Action -> ' . $a, 1);
        ox::$m[$m]->Module_Name = $m;
        ox::$m[$m]->Method_Name = $a;
        ox::$m[$m]->Module_From = $s;
        return ox::$m[$m];
    }

    /**
     * 路由处理
     * @param array $GET 一般是一个$_GET对象，其中包含 r、m，（或者根据配置参数不同）分别是 控制器模块名，和方法名
     * @return array 返回 array( m=> '' , a=> '' )
     */
    static function r($GET)
    {
        $req_r = ox::c('DEF_REQ_KEY_RUT');
        $req_a = ox::c('DEF_REQ_KEY_ACT');
        $req_m = ox::c('DEF_REQ_KEY_MOD');
        if (isset($GET[$req_r])) {
            $r = explode('/', $GET[$req_r]);
            $m = $r[0];
            $a = $r[1];
        } else {
            $m = $GET[$req_m];
            $a = $GET[$req_a];
        }
        return array(
            'm' => htmlspecialchars(empty($m) || is_numeric($m) ? ox::c('DEF_MOD') : $m),
            'a' => htmlspecialchars(empty($a) || is_numeric($a) ? ox::c('DEF_ACT') : $a),
        );
    }

    /**
     * @name ox ::init 框架运行入口
     * @function
     * @option array $cfgs   运行时配置信息，一个带有若干键值的，如果不传入默认为一个空的数组，
     * @param  array $argv 入口文件执行init时自带的参数，该参数的作用是在命令行模式下传入对应的参数，
     * @description  初始化调用时，会根据参数执行对应的控制器模块和方法
     * 在命令行模式下的参数会被全部传入到 $_GET 对象中，命令行运行的格式类似： php index.php r=index/test id=1
     * 参数请参考 @see ox::c
     */
    static function init($argv, $cfgs = array())
    {

        @date_default_timezone_set("PRC");
        ox::$c = require_once('cfg.php');

        ox::c('PATH_PUB', PATH_COM);
        #    命令行模式 将参数完全复制到 $_GET 对象中
        if (count($argv) > 1) {
            $al = count($argv);
            ox::$c['COMMAND_MODE'] = true;
            for ($i = 1; $i < $al; $i++) {
                $arg = explode('=', $argv[$i]);
                $_GET[$arg[0]] = $arg[1];
            }
            $_REQUEST = $_GET;
        }

        #   合并用户自定义参数
        ox::$c = array_merge(ox::$c, $cfgs);

        #   路径
        ox::c('PATH_APP', PATH_APP);
        ox::c('PATH_COM', PATH_COM);
        if (defined('DIR_APP')) {
            ox::c('DIR_APP', DIR_APP);
        }

        #   分析请求对象
        $req = ox::r($_GET);
        #    实例化请求 的 控制器模块,需要控制器自行处理 
        $ins = ox::m($req);
        $ins->_call_($req);
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
function C($n, $v = NULL)
{
    return ox::c($n, $v);
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