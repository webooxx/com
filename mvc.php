<?php
/**
 * @file 框架引入文件
 * @date 14.5.5
 * @description 当存在 框架编译后的文件 mvc.min.php 时，会优先引用编译后的文件，否则会从源码中从 ox.php 开始进行逐个引用
 * 编译时
 *  require_once 的文件，将会被自动引入
 *  include_once 的文件，将根据参数，进行控制引入
 *
 *  require、include 作为代码运行时引入的内容
 */
error_reporting(0);

$mvc_src = realpath( dirname ( __FILE__ ).'/src/ox.php'  );
$mvc_min = realpath( dirname ( __FILE__ ).'/mvc.min.php' );

require_once( $mvc_min ? $mvc_min : $mvc_src );

return  ox;