<?php 

#   不同环境
if( $_SERVER['host'] == '127.0.0.1'){
    define('ENV','develop');
}elseif( $_SERVER['host'] == 'www.baidu.com'){
    define('ENV','online');
}else{
     define('ENV','develop');
}

#   不同配置
$config['develop'] = array(
        'DB_PREFIX'=> '',               #    数据库表前缀
        'DB_HOST' => '127.0.0.1:3306',
        'DB_NAME' => 'test',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => '',
    );
$config['online'] = array(
    );

return (array)$config[ ENV ];