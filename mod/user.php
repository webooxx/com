<?php

class user extends MysqlModel{

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

    function findByUserName() {
        $where['username'] = $this->getUuapUser();
        $user              = $this->where($where)->find();

        if (!$user) {
            $data['username'] = $where['username'];
            $data['ct']       = date('Y-m-d H:i:s');

            $this->debug(0)->add($data);
            $user = $this->where($where)->debug(0)->find();
        }
        return $user;
    }

    #   获得 uuap 账户信息
    function getUuapUser() {
        if (ENV == 'online') {
            require('./bae_config.php');
            require_once(BAE_LIB_PATH . '/BaeCasSession.class.php');
            BaeCasConfigure::setConf('10.36.53.6', 'jsfeedbackdb_w', 'N2tstLHxZVIg3hHn', 'ns_bae_jsfeedbackdb', '4551');
            $obj = BaeCasSession::getInstance('uuap.baidu.com', 443);
            if(!$obj->isAuth()){$obj->auth(); };
            return $obj->user();
        }
        else {
            I('uuap-php/develop');
            return phpCAS::getUser();
        }
    }

}