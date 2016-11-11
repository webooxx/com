<?php
/**
 * @file 应用入口文件
 *
 * 两种文件结构
 * -   CURRENT 模式的文件结构
 *
 * /index.php
 * /com
 * /app
 * /more-share
 *
 * -    OUTSIDE 模式的文件结构
 *
 * /index.php ( <?php require('app/index.php'); )
 * /app/index.php
 * /com/
 *
 *
 */
define('PATH_APP', dirname(__FILE__));

define('PATH_COM_CURRENT', realpath(PATH_APP . '/com'));
define('PATH_COM_OUTSIDE', realpath(PATH_APP . '/../com'));

define('PATH_COM', (PATH_COM_CURRENT ? PATH_COM_CURRENT : PATH_COM_OUTSIDE));

require_once(PATH_COM . '/mvc.php');

/**
 * 第一个参数 , 传入命令行模式下的参数调用: index.php name=value , 代码中 $_GET['name'] == 'value'
 * 第二个参数为配置信息,可以是一个数组,也可以通过引用一个文件获取,如:  include_once(PATH_APP.'/inc/config.php')
 */
mvc::init(isset($argv) ? $argv : array(), array());
