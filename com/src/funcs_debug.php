<?php  #   开发调试函数
function dump ($arg) { @header("Content-type:text/html"); echo '<pre>'; var_dump($arg) ; echo '</pre>' ; }
function ddump($arg) { @header("Content-type:text/html"); echo '<pre>'; var_dump($arg) ; die( '</pre>'); }
function json ($arg) { @header("Content-type:text/json"); echo json_encode($arg) ; }
function djson($arg) { @header("Content-type:text/json"); die( json_encode($arg)); }