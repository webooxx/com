<?php

class User extends MysqlModel{

    function __construct(){

        $this->table('user_custom');

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