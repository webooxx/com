<?php
class rbacAction extends Action{
    #   拒绝客户端请求验证模式
    function __construct(){ 
      C('VAILD_FUNC','rbac:reject');
    }
	
  	# @todo 实现对数据库信息是否正确的判断
  	# @todo 实现Session模块，替代对系统的Session
  	# @todo 实现安装模式，部署：RBAC数据库、SESSION数据库
  	
  	#	只允许命令行下运行的验证模式
  	function cmdmod(){
  		if( C('CMD_MOD') !== true ){
  			echo json_encode( array('status'=>-1,'msg'=>'只允许在命令行模式下访问。') );
  			return false;
  		}
  		return true;
  	}

    /**
     * 验证登录，并保存cookie密钥
     */
    function login($password){
        // $pwd = '93db512293c14db3';
        
        $pswd = M('cms_content')->where('id=372')->find();
        $pwd = $pswd['0']['content'];

        if ( md5($password) == $pwd ) {
          $day = date('ymd');
          $md5Key = md5($pwd.$day);
          return setcookie('magic_logined', $md5Key);

        } else {
          return false;
        }
    }

    /**
     * 验证密码
     */
    function passport($what) {
    		if($what['fun']=='login'){
            return true;
        }

    		date_default_timezone_set('PRC');

        $pswd = M('cms_content')->where('id=372')->find();
        $pwd = $pswd['0']['content'];

    		$day = date('ymd');
    		$md5 = md5($pwd.$day);
    		$result = $_COOKIE['magic_logined'] == $md5;

    		if(!$result){
            A('admin')->login();
    		}
    		return true;
    }

      #   宽松授权验证，允许执行未知的对象操作
    function check( $args , $openMode = true){
        if( is_null( $_SESSION['rbac']['uid']  )){  $_SESSION['rbac']['uid']=2 ;}   #   匿名访客
        #if( $_SESSION['rbac']['uid'] == 1){ return true; }                         #   root 特权用户
        #unset( $_SESSION['rbac']['author'] );                                      #   debug
        if( is_null( $_SESSION['rbac']['author'] ) ){
            #   初始化授权列表
            $roles =  $this->getRole( $_SESSION['rbac']['uid'] );
            $_SESSION['rbac']['author'] = $this->getAuthorization( $roles ) ;
        }
        if( is_null( $_SESSION['rbac']['author'][ implode(':', $args) ] ) ){
            #   没有在授权范围内
            $_SESSION['rbac']['author'][ implode(':', $args) ] = -1;
        }
        #权限判断
        $code = $_SESSION['rbac']['author'][ implode(':', $args) ] ;
        if( $code >= 1 || $code == -1 && $openMode ){
            return true;
        }
        else{
            $msg = array(
                '-1' => array('status'=>-1,'msg'=>'访问对象未在授权范围内，拒绝访问!'),
                '0'  => array('status'=>0 ,'msg'=>'访问对象未被授权，拒绝访问!'),
            );
            echo json_encode( $msg [$code]) ;
            return false;
        }
    }
    #   严格授权验证，不允许执行未知的对象操作
    function checkin(  $args ){
        return $this->check($args , false);
    }
    #   拒绝访问
    function reject(){
        return false;
    }
    #   所有允许
    function accept(){
        return true;
    }
    #   清空授权列表
    function clear(){
        unset( $_SESSION['rbac']['author'] ); 
    }

    #	取得用户角色列表
    private function getRole( $uid = 0 ){
        $uid = is_int( $uid ) ? $uid : intval($uid);
    	return M('rbac_u2r')->where( "uid=$uid" )->findAll();
    }
    #	取得角色序列所有的授权对象列表
    private function getAuthorization( $roles = array() ){
    	$author = array();
        $roles  = is_array( $roles ) ? $roles : array() ;
    	foreach ($roles as $role) {
            $rid = $role['rid'];
            $ret = M('rbac_r2o')->where( "rid=$rid  ")->findAll();
            if($ret){ 
                $oid=$ret[0]['oid']; 
                $object = M('rbac_object')->where("oid=$oid")->find();
                $object = $object[0]['act'].':'. $object[0]['fun'];
                $code   = (int)$ret[0]['enable'] ;
                $author[ $object ] =  $author[ $object ] | $code; 
            }
    	}
        return $author;
    }
    #   取得执行对象id
    private function getObjectid( $act , $fun){
        $ret = M('rbac_object')->where("act='$act' and fun='$fun'")->find();
        return $ret ? $ret[0]['oid'] : -1;
    }

    #   初始化数据库
    private function rbacSql(){
        $sql =  <<<SQLSTR
-- phpMyAdmin SQL Dump
-- version 2.11.9.2
-- http://www.phpmyadmin.net
--
-- 主机: 127.0.0.1:3306
-- 生成日期: 2012 年 04 月 27 日 05:55
-- 服务器版本: 5.1.28
-- PHP 版本: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- 数据库: `rbac`
--

-- --------------------------------------------------------

--
-- 表的结构 `rbac_object`
--

CREATE TABLE IF NOT EXISTS `rbac_object` (
  `oid` int(11) NOT NULL AUTO_INCREMENT COMMENT '操作标识',
  `name` varchar(11) NOT NULL DEFAULT '操作名称' COMMENT '操作算子名称',
  `act` varchar(128) NOT NULL COMMENT '控制器名',
  `fun` varchar(128) NOT NULL COMMENT '方法名',
  PRIMARY KEY (`oid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='操作对象' AUTO_INCREMENT=3 ;

--
-- 导出表中的数据 `rbac_object`
--

INSERT INTO `rbac_object` (`oid`, `name`, `act`, `fun`) VALUES
(1, '展现默认首页', 'index', 'index'),
(2, '默认控制器测试方法', 'index', 'test');

-- --------------------------------------------------------

--
-- 表的结构 `rbac_r2o`
--

CREATE TABLE IF NOT EXISTS `rbac_r2o` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '索引',
  `rid` int(11) NOT NULL COMMENT '角色标识',
  `oid` int(11) NOT NULL COMMENT '操作对象',
  `enable` int(11) NOT NULL DEFAULT '1' COMMENT '授权可用标识',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='角色/操作引对象 授权表' AUTO_INCREMENT=2 ;

--
-- 导出表中的数据 `rbac_r2o`
--

INSERT INTO `rbac_r2o` (`id`, `rid`, `oid`, `enable`) VALUES
(1, 2, 2, 0);

-- --------------------------------------------------------

--
-- 表的结构 `rbac_role`
--

CREATE TABLE IF NOT EXISTS `rbac_role` (
  `rid` int(11) NOT NULL AUTO_INCREMENT COMMENT '角色标识',
  `rname` varchar(100) NOT NULL COMMENT '角色名称',
  `rbase` int(11) NOT NULL DEFAULT '0' COMMENT '角色基数',
  `enable` int(11) NOT NULL DEFAULT '1' COMMENT '角色可用标识',
  PRIMARY KEY (`rid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='角色表' AUTO_INCREMENT=100 ;

--
-- 导出表中的数据 `rbac_role`
--

INSERT INTO `rbac_role` (`rid`, `rname`, `rbase`, `enable`) VALUES
(1, 'root', 0, 1),
(2, 'anyone', 0, 1);

-- --------------------------------------------------------

--
-- 表的结构 `rbac_u2r`
--

CREATE TABLE IF NOT EXISTS `rbac_u2r` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(100) NOT NULL COMMENT '用户标识',
  `rid` int(11) NOT NULL COMMENT '角色标识',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='用户角色映射表' AUTO_INCREMENT=3 ;

--
-- 导出表中的数据 `rbac_u2r`
--

INSERT INTO `rbac_u2r` (`id`, `uid`, `rid`) VALUES
(1, 1, 1),
(2, 2, 2);

-- --------------------------------------------------------

--
-- 表的结构 `rbac_user`
--

CREATE TABLE IF NOT EXISTS `rbac_user` (
  `uid` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户标识',
  `username` varchar(100) NOT NULL COMMENT '用户姓名',
  `password` varchar(256) DEFAULT NULL COMMENT '登录密码',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='用户表' AUTO_INCREMENT=100 ;

--
-- 导出表中的数据 `rbac_user`
--

INSERT INTO `rbac_user` (`uid`, `username`, `password`) VALUES
(1, 'root', NULL),
(2, 'anyone', NULL);
SQLSTR;
    return $sql;
    }
    function root(){
        $_SESSION['rbac']['uid'] = 1;
    }

}