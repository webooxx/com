<?php
define('PATH_NOW',dirname($_SERVER['SCRIPT_FILENAME']));
define('PATH_MVC',PATH_NOW.'/mvc');

#   定义系统框架文件和配置文件
define('FILE_MVC',PATH_MVC.'/ooxx.php');
include( PATH_NOW.'/cfg-txtdoc.php' );

$app = include(FILE_MVC);$app->init( _configs() ,$argv );