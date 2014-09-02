<?php

class user extends CsvModel{

    function __construct(){
    }

    function structure(){
        return array(
            'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT' ,
            'username' => 'varchar(1000) NOT NULL',
            'password' => 'varchar(128) NOT NULL',
            'ct' => 'datetime DEFAULT NULL COMMENT \'创建时间\'',
        );
    }

    function mapType(){
        return array(
            '0' => '游客',
            '1' => '投稿人',
            '3' => '编辑',
            '9' => '管理员',
        );
    }

}