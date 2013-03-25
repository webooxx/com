<?php
class rbacAction extends Action{
    
    /**
     * 执行时间
     * 1.配置文件中SYS_VALIDATE_FN设置为一个模块的方法时，返回为false则拒绝继续执行
     *      function __construct(){ C('SYS_VALIDATE_FN','rbac:check'); }
     * 2.在控制器中以A方法快捷调用，返回为false则表示验证失败
     *      function __construct(){ if(!A('rbac')->check()){die('验证失败')}; }
     */
    
    /**
     * 需求场景
     * 1.简单条件验证：拒绝访问、命令行模式访问、本机访问
     * 2.单用户登录验证：不验证操作资源，只要登录成功用户即有权限
     * 3.多用户操作资源验证
     */
     
    /**
     * 相关表解释
     * user 记录用户信息,关键字段:id,username
     * role 记录用户角色信息,关键字段: id,name
     * u2r  user to role，用户与角色对应关系，可以多对多,关键字段:id,uid,rid
     * object 操作对象表，记录所有的资源和操作，可以与模块和方法对应。关键字段:id,module,action。模块和资源支持*通配符
     * r2o  role to object，角色与操作对象对应关系，可以多对多。关键字段:id,rio,oid
     * session 辅助表，记录用户会话信息。可选择不开启，使用系统的session。
     */
    
    #   拒绝客户端请求验证模式
    function __construct(){ 
      C('SYS_VERIFY_FUNC','rbac:reject');
      C('SYS_RBAC_TABLE_PREFIX','rbac_');       #   设置权限相关表前缀
      C('SYS_RBAC_SESSION_TYPE','SYS');         #   会话类型，SYS 为系统SESSION，MEM为MemCache模式（依赖缓存对象），TABLE为内存表（依赖数据库）
    }
    
    #   --- 简单条件验证
    
    #   拒绝访问
    function reject(){ return false; }
    
    #   限制在命令行模式
    function onlyCmd(){ return C('MODE_CMD') == false; }
    
    #   --- 用户登录验证
    function isSignin(){
        return !is_null( $this->SES('uid') );
    }
    
    #   --- 多用户RBAC权限验证 , 传入一个数组，[ 资源/模块 , 方法/操作 ]
    function check( $resource ){
        
        #   在 SESSION / 系统内存中 存储用户的 UID 以及相应的 RESOURCES 信息，以及 RIDS,OIDS 信息
        if( $this->SES('uid') == 0 ){ return true;}
        $allowResource = $this->SES('reosurce'); 
        $verifyResource[] = '*->*';
        $verifyResource[] = $resource['module'].'->*';
        $verifyResource[] = '*->'.$resource['action'];
        $verifyResource[] = implode('->', '$resource');
        
        #   资源中的模块和操作支持通配符 * 
        return count( array_intersect( $allowResource , $verifyResource) ) > 0;
    }
    
    #   --- 辅助函数
    
    #   直接获得UID为0的ROOT权限,UID为0可直接通过所有验证
    function root(){ $this->SES('uid',0);}
    
    #   登录授权
    function author_signin($uid){
        $rids = $this->getU2r($uid);
        $oids = $this->getR2o($rids);
        $reosurce = $this->getReosurce($oids);
        $this->SES('uid',$uid);
        $this->SES('rids',$rids);
        $this->SES('oids',$oids);
        $this->SES('reosurce',$reosurce);
        return $oids;
    }
    #   登出清理
    function author_signout(){
        $this->SES('uid',NULL);
        $this->SES('rids',NULL);
        $this->SES('oids',NULL);
        $this->SES('reosurce',NULL);
        return NULL;
    }

    #   取得 用户的 角色ID 序列
    function getU2r($uid) {
        $rids   = array();
        $where  = array('uid' => (int)$uid );
        $record = M(C('SYS_RBAC_TABLE_PREFIX') . 'u2r')->field('rid')->where($where) -> findAll();
        foreach($record as $r){
            $rids[] = $r['rid'];
        }
        return array_unique($rids);
    }
    #   取得角 色序列的 资源ID 序列
    function getR2o( $rids = array() ){
        $oids       = array();
        $records    = array();
        $objects    = array();
        foreach ($rids as $id ){
            $records = array_merge( M( C('SYS_RBAC_TABLE_PREFIX').'r2o' )->field('oid')->where('rid='.$id)->findAll(), $records );
        }
        foreach($records as $record){
            $oids[] = $record['oid'];
        }
        return array_unique($oids);
        
    }
    #   转换资源序列id为资源操作字符s
    function getReosurce( $oids=array() ){
        if(count($oids)<1){return $oids;}
        $record = M( C('SYS_RBAC_TABLE_PREFIX').'object' )->field('module,action')->where('id in('.implode(',',$oids).')')->findAll();
        foreach ($record as $key => $value) {
            $reosurce[] = $value['module'].'->'.$value['action'];
        }
        return $reosurce;
    }
    
    #   SESSION处理函数,$val 若为NULL则删除数据条目，而不是设置为NULL;可以使用 系统session、内存表、memcache
    function SES( $key , $val = false){
        $SES_PROCESS = 'SES_'.C('SYS_RBAC_SESSION_TYPE');
        return $this->$SES_PROCESS($key ,$val);
    }
    function SES_SYS( $key , $val = false ){
        session_start();
        if( $val === false){
            return $_SESSION[C('SYS_RBAC_TABLE_PREFIX').$key];
        }
        if( is_null( $val ) ){
            unset( $_SESSION[C('SYS_RBAC_TABLE_PREFIX').$key] );
            return $_SESSION[C('SYS_RBAC_TABLE_PREFIX').$key];
        }
        return $_SESSION[C('SYS_RBAC_TABLE_PREFIX').$key] = $val;
    }
    function SES_TABLE( $key , $val = false ){}
    function SES_MEM( $key , $val = false ){}

    #   初始化权限相关数据表
    function initDb(){

    }
  	
    #   初始化数据库
    private function rbacSql(){
        $sql =  <<<SQLSTR
            
            -- phpMyAdmin SQL Dump
            -- version 4.0.0-beta1
            -- http://www.phpmyadmin.net
            --
            -- 主机: 127.0.0.1
            -- 生成日期: 2013 年 03 月 25 日 21:10
            -- 服务器版本: 5.6.10
            -- PHP 版本: 5.3.15
            
            SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
            SET time_zone = "+00:00";
            
            
            /*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
            /*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
            /*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
            /*!40101 SET NAMES utf8 */;
            
            --
            -- 数据库: `xuzhenhua`
            --
            
            -- --------------------------------------------------------
            
            --
            -- 表的结构 `wb_category`
            --
            
            CREATE TABLE IF NOT EXISTS `wb_category` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `pid` int(11) NOT NULL DEFAULT '0',
              `name` varchar(128) NOT NULL DEFAULT '未分类',
              `list` int(11) NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='分类表' AUTO_INCREMENT=4 ;
            
            --
            -- 转存表中的数据 `wb_category`
            --
            
            INSERT INTO `wb_category` (`id`, `pid`, `name`, `list`) VALUES
            (1, 0, '[ROOT]文章根分类', 0),
            (2, 1, '未分类', 0),
            (3, 0, '[FINAL]页面根分类', 0);
            
            -- --------------------------------------------------------
            
            --
            -- 表的结构 `wb_comment`
            --
            
            CREATE TABLE IF NOT EXISTS `wb_comment` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `id_post` int(11) NOT NULL COMMENT '发布的文章ID',
              `id_user` int(11) NOT NULL COMMENT '管理账号，如果有的话',
              `author_name` varchar(512) NOT NULL COMMENT '评论作者',
              `author_email` varchar(512) NOT NULL,
              `author_url` varchar(512) NOT NULL,
              `author_ip` varchar(128) NOT NULL,
              `ts_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `status` int(11) NOT NULL DEFAULT '1' COMMENT '1,显示,0审核,-1删除或不允许显示',
              `pid` int(11) NOT NULL COMMENT '引用上级评论',
              `content` text NOT NULL COMMENT '评论字数限制，65525个字符',
              KEY `id` (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='评论表' AUTO_INCREMENT=3 ;
            
            --
            -- 转存表中的数据 `wb_comment`
            --
            
            INSERT INTO `wb_comment` (`id`, `id_post`, `id_user`, `author_name`, `author_email`, `author_url`, `author_ip`, `ts_create`, `status`, `pid`, `content`) VALUES
            (1, 1, 1, '徐先生', 'a@a.c', 'xuzhenhua.com', '', '2012-11-06 15:22:04', 1, 0, '您好，这是一条评论。<br />要删除评论，请先登录，然后再查看这篇文章的评论。在那里，您可以看到编辑或者删除评论的选项。'),
            (2, 1, 0, 'Jim', 'Jim@baidu.com', '', '', '2013-02-23 16:22:42', 1, 1, 'so good');
            
            -- --------------------------------------------------------
            
            --
            -- 表的结构 `wb_option`
            --
            
            CREATE TABLE IF NOT EXISTS `wb_option` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `key` varchar(256) NOT NULL,
              `val` varchar(512) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='配置表' AUTO_INCREMENT=5 ;
            
            --
            -- 转存表中的数据 `wb_option`
            --
            
            INSERT INTO `wb_option` (`id`, `key`, `val`) VALUES
            (1, 'blog_name', '徐振华的博客'),
            (2, 'blog_desc', '网络,程序,设计,生活 —— 被记住的梦想才有意义'),
            (3, 'blog_post_size', '20'),
            (4, 'admin_id', '1');
            
            -- --------------------------------------------------------
            
            --
            -- 表的结构 `wb_post`
            --
            
            CREATE TABLE IF NOT EXISTS `wb_post` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `caption` varchar(256) NOT NULL,
              `content` mediumtext NOT NULL,
              `intro` varchar(1000) NOT NULL,
              `tags` varchar(512) NOT NULL,
              `ts_modify` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `ts_create` datetime NOT NULL,
              `count_visit` int(11) NOT NULL,
              `count_comment` int(11) NOT NULL,
              `id_user` int(11) NOT NULL COMMENT '用户编号',
              `id_cate` int(11) NOT NULL COMMENT '分类编号',
              `commentStatu` int(11) NOT NULL DEFAULT '1' COMMENT '1允许评论,0默认不显示,-1不允许发表评论',
              `status` int(11) NOT NULL DEFAULT '1' COMMENT '1正常显示0审核中-1已删除',
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='内容发布表' AUTO_INCREMENT=3 ;
            
            --
            -- 转存表中的数据 `wb_post`
            --
            
            INSERT INTO `wb_post` (`id`, `caption`, `content`, `intro`, `tags`, `ts_modify`, `ts_create`, `count_visit`, `count_comment`, `id_user`, `id_cate`, `commentStatu`, `status`) VALUES
            (1, '世界，你好！', '<p style="color:red">写一句话就够了</p>\r\n', '写一句话就够了', '', '2013-03-12 17:36:13', '2013-03-12 01:33:59', 14, 2, 1, 2, 1, 1),
            (2, '关于', '<center><h2>个人档案</h2></center>\r\n这是示范页面。页面和博客文章不同，它的位置是固定的，通常会在站点导航栏显示。很多用户都创建一个“关于”页面，向访客介绍自己。例如，个人博客通常有类似这样的介绍：\r\n\r\n<blockquote><p>欢迎！我白天是个邮递员，晚上就是个有抱负的演员。这是我的博客。我住在天朝的帝都，有条叫做 Jack 的狗。</p></blockquote>\r\n\r\n... 公司博客可以这样写：\r\n\r\n<blockquote><p>XYZ Doohickey 公司成立于 1971 年，自从建立以来，我们一直向社会贡献着优秀 doohicky。我们的公司总部位于天朝魔都，有着超过两千名员工，对魔都政府税收有着巨大贡献。</p></blockquote>\r\n\r\n您可以访问<a href="http://127.0.0.1/www/wordpress/wp-admin/">仪表盘</a>，删除本页面，然后添加您自己的内容。祝您使用愉快！', '', '', '2013-03-12 17:36:31', '2013-03-11 01:34:06', 1, 0, 1, 3, -1, 1);
            
            -- --------------------------------------------------------
            
            --
            -- 表的结构 `wb_rbac_object`
            --
            
            CREATE TABLE IF NOT EXISTS `wb_rbac_object` (
              `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '操作标识',
              `name` varchar(11) NOT NULL DEFAULT '未命名操作' COMMENT '对象名称',
              `module` varchar(128) NOT NULL COMMENT '模块名',
              `action` varchar(128) NOT NULL COMMENT '操作名',
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='操作对象' AUTO_INCREMENT=2 ;
            
            --
            -- 转存表中的数据 `wb_rbac_object`
            --
            
            INSERT INTO `wb_rbac_object` (`id`, `name`, `module`, `action`) VALUES
            (1, '管理模块所有权限', 'admin', '*');
            
            -- --------------------------------------------------------
            
            --
            -- 表的结构 `wb_rbac_r2o`
            --
            
            CREATE TABLE IF NOT EXISTS `wb_rbac_r2o` (
              `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '索引',
              `rid` int(11) NOT NULL COMMENT '角色ID',
              `oid` int(11) NOT NULL COMMENT '操作对象ID',
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='角色/操作引对象 授权表' AUTO_INCREMENT=2 ;
            
            --
            -- 转存表中的数据 `wb_rbac_r2o`
            --
            
            INSERT INTO `wb_rbac_r2o` (`id`, `rid`, `oid`) VALUES
            (1, 1, 1);
            
            -- --------------------------------------------------------
            
            --
            -- 表的结构 `wb_rbac_role`
            --
            
            CREATE TABLE IF NOT EXISTS `wb_rbac_role` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(64) NOT NULL COMMENT '角色名称',
              `disabled` int(11) NOT NULL DEFAULT '0' COMMENT '禁用',
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='权限控制-角色表' AUTO_INCREMENT=2 ;
            
            --
            -- 转存表中的数据 `wb_rbac_role`
            --
            
            INSERT INTO `wb_rbac_role` (`id`, `name`, `disabled`) VALUES
            (1, '管理员', 0);
            
            -- --------------------------------------------------------
            
            --
            -- 表的结构 `wb_rbac_u2r`
            --
            
            CREATE TABLE IF NOT EXISTS `wb_rbac_u2r` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `uid` int(100) NOT NULL COMMENT '用户ID',
              `rid` int(11) NOT NULL COMMENT '角色ID',
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='权限控制-用户角色映射表' AUTO_INCREMENT=2 ;
            
            --
            -- 转存表中的数据 `wb_rbac_u2r`
            --
            
            INSERT INTO `wb_rbac_u2r` (`id`, `uid`, `rid`) VALUES
            (1, 1, 1);
            
            -- --------------------------------------------------------
            
            --
            -- 表的结构 `wb_user`
            --
            
            CREATE TABLE IF NOT EXISTS `wb_user` (
              `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户编号',
              `username` varchar(64) NOT NULL COMMENT '登录帐号',
              `nickname` varchar(256) NOT NULL COMMENT '昵称',
              `password` varchar(128) NOT NULL COMMENT '登录密码',
              `email` varchar(256) NOT NULL COMMENT '邮箱',
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='用户基本信息表' AUTO_INCREMENT=2 ;
            
            --
            -- 转存表中的数据 `wb_user`
            --
            
            INSERT INTO `wb_user` (`id`, `username`, `nickname`, `password`, `email`) VALUES
            (1, 'webooxx', 'Lyn_振华', '', 'webooxx@gmail.com');
            
            /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
            /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
            /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;



SQLSTR;
    return $sql;
    }


}