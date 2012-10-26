<?php
define('_DIR_',dirname($_SERVER['SCRIPT_FILENAME']));
$app = include_once(_DIR_.'/../ooxx.php');$app->run(include_once(_DIR_.'/conf.php'),$argv);
?>
