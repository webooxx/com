<?php
error_reporting(0);

$mvc_src = realpath( dirname ( __FILE__ ).'/src/ox.php'  );
$mvc_min = realpath( dirname ( __FILE__ ).'/mvc.min.php' );

require_once( $mvc_min ? $mvc_min : $mvc_src );

return  ox;