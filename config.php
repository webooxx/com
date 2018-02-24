<?php
return array(
    'MYSQL_MASTER' => array(
        'host' => '144.202.9.252',
        'port' => '3306',
        'dbname'   => 'iwenjuan_dev',
        'username' => 'iwenjuan_dev',
        'password' => 'iw_0224',
    ),
    'MYSQL_SLAVES' => array(
        array(
            'host' => '144.202.9.252',
            'port' => '3306',
            'dbname'   => 'iwenjuan_dev',
            'username' => 'iwenjuan_dev',
            'password' => 'iw_0224',
        )
    ),
);
