<?php 

#   自定义配置
return array(

    'DB_ENGINE'=> 'Mysql',               #    数据库引擎类型，目前支持 Mysql ， Csv 类型
    'DB_PREFIX'=> 'lw_',                 #    数据库表前缀
    'DB_HOST' => '127.0.0.1:3306',
    'DB_NAME' => 'lifeword',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => 'root',
    'DB_DEFCHART' => 'UTF8',
    'DBS' => array(
        array(
            'DB_HOST' => '127.0.0.2:3306',
        ),
    ),
);