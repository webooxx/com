<?php
define('PATH_NOW',dirname ( __FILE__ ) );
define('PATH_MVC',PATH_NOW.'/mvc');

#	不同项目目录下拥有自己的 config 文件，如果切换项目可直接将 config.php 文件覆盖至本目录
include( PATH_NOW.'/config.php' );

$app = require_once(PATH_MVC.'/ooxx.php');$app->init( _configs() ,$argv );