<?php

#   入口,仅用于 引入框架文件 和 项目配置文件
define('PATH_NOW',dirname ( __FILE__ ) );
$app = require(PATH_NOW.'/mvc/ooxx.php');
$app->init( $argv ,include( PATH_NOW.'/app/cfg/main.php' ) );
