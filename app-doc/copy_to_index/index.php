<?php
define('_DIR_',dirname($_SERVER['SCRIPT_FILENAME']));
define('_MVC_',_DIR_.'/ooxx.php');
define('_CFG_',_DIR_.'/conf.php');
$app = include_once(_MVC_);$app->run( include_once(_CFG_),$argv );
?>