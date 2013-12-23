<?php
/** test */
define('PATH_NOW',dirname ( __FILE__ ) );
define('PATH_MVC',PATH_NOW.'/mvc');

$config = array();1

$app = require(PATH_MVC.'/ooxx.php');$app->init( $argv , $config );
?>