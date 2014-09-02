<?php
/**
 * @file 应用入口文件
 *
 * 用于 引入框架文件 和 项目配置文件
 */
define('PATH_APP',dirname ( __FILE__ ) );

define('PATH_COM_INSIDE', realpath( PATH_APP.'/com') );
define('PATH_COM_OUTSIDE',realpath( PATH_APP.'/../com') );

define('PATH_COM' , ( PATH_COM_INSIDE ? PATH_COM_INSIDE : PATH_COM_OUTSIDE ) );

require_once(PATH_COM.'/mvc.php');

ox::init( $argv  );