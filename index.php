<?php
define('PATH_NOW',dirname ( __FILE__ ) );
define('PATH_MVC',PATH_NOW.'/mvc');

$app = require(PATH_MVC.'/ooxx.php');$app->init( $argv ,include( PATH_NOW.'/xconfig.php' ) );
