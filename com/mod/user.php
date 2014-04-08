<?php

class user extends Model{

    function __construct(){

      

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