<?php
/**
 * @file 框架引入文件
 * @description 当存在 框架编译后的文件时，会优先引用编译后的文件，否则会从源码中进行逐个引用
 * 编译时，require_once 为自动引入的内容，include_once 为可选引入的内容，可选内容可以根据参数进行控制，require、include 作为代码运行时引入的内容
 */
error_reporting(0);

$mvc_src = realpath( dirname ( __FILE__ ).'/src/ox.php'  );
$mvc_min = realpath( dirname ( __FILE__ ).'/mvc.min.php' );

require_once( $mvc_min ? $mvc_min : $mvc_src );

return  ox;