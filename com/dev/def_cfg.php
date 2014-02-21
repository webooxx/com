<?php return
array(

        #   默认配置
        
        'CMDMODE' => false,             #   命令行模式
        'REOUTE'  => false,             #   路由处理，可以设置为一个函数。输入值为 $_GET，需要输出一个 array( 'm'=>'' , 'a'=>'' )

        #   路径
        'PATH_ROOT'=> '.',              #    项目 index.php 的目录路径
        'PATH_APP' => '.',              #    项目 主目录   路径,根据 DIR_APP 自动设置
        
        'PATH_COM' => '.',              #    项目 
        'PATH_TMP' => '.',              #    项目 模板编译 路径


        #    核心设置
        'DEF_REQ_KEY_RUT'=> 'r',        #    从 $_GET['r'] 中取得需要运行的模块类和方法，格式为 Mod/Act 或 Mod - 默认为 Mod/index
        'DEF_REQ_KEY_MOD'=> 'm',        #    从 $_GET['m'] 中取得需要运行的模块类
        'DEF_REQ_KEY_ACT'=> 'a',        #    从 $_GET['a'] 中取得模块类需要运行的方法

        'DEF_MOD'=> 'index',            #    默认请求的模块类
        'DEF_ACT'=> 'index',            #    默认执行的模块方法
        'DEC_ACT_EXT'=> 'Action',       #    默认模块类名后缀，例： indexAction.php


        #    项目默认设置

        'DIR_ACT'=> 'act',              #    控制器目录


        #   系统运行时变量
        'SYS_REQ'=> array()             #   记录请求

        'DIR_INC'=> 'inc',              #    公共类目录名
        'DIR_MVC'=> 'mvc',              #    框架资源目录名

        'DIR_TPL'=> 'tpl',              #    模板目录             相对于 /app/ 项目目录
        'DIR_COM'=> '.tc',              #    模板编译目录         相对于 /app/ 项目目录
        'DIR_THEME'=> '.',              #    模板主题目录,为一个 . 则默认不使用主题目录，模板目录即为主题目录    相对于 /app/tpl 项目目录

        #    模板默认设置

        'TPL_ENGINE'=> 'ooxx',          #    模板引擎类型，目前支持 none 原样输出（不使用编译目录，支持静态变量、include），ooxx 内置的模板引擎，smarty 暂不支持
        'TPL_LEFT_DELIMITER' => '<!--{',#    模板变量左分界符
        'TPL_RIGHT_DELIMITER'=> '}-->' ,#    模板变量右分界符

        #    数据库设置

        'DB_ENGINE'=> 'Mysql',          #    数据库引擎类型，目前支持 Mysql ， Csv 类型
        'DB_PREFIX'=> '',               #    数据库表前缀，如果是 Csv 数据库类型,表前缀此项相当于数据文件存放目录,相对于 /app/,使用 F 快捷函数读取，意味着你可以使用云存储的数据
        'DB_HOST' => '127.0.0.1:3306',
        'DB_NAME' => 'test',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => '',
        'DB_DEFCHART' => 'UTF8',





        #    项目运行时变量

        'URL_INDEX'  => '.',            #    首页目录的URL 模板关键字 ../../ 会自动转换成该路径,http://127.0.0.1/
        'URL_PUBLIC' => '.',            #    模板Public目录的URL 模板关键字 ../Public/  会自动替换成该路径,类似 http://127.0.0.1/app/tpl/default/Public/

        #    初始化自动设置路径变量



        #    系统其他设置
        'SYS_VERIFY_FUNC' => '',        #    系统，验证函数设置，格式为字符串，例如rbac:check，执行时传入一个参数数组 array( 'mod'=> 模块名 , 'act'=> , 方法名  )
        'SYS_CURRENT_MOD' => '',        #    系统，当前的模块名,执行模块时重设
        'SYS_CURRENT_ACT' => '',        #    系统，当前的方法名,执行方法时重设
        'SYS_COMMAND_MOD' => false,     #    程序是否是以命令行模式调用，默认为false,在命令行中调用时会自动设置为true,此模式下，所有的参数将被挂载到名为 $_GET 的对象上,注意:参数需要键名
        'ENV_LOCALHOST'   => false,     #    程序是否是在本地执行，当域名为127.0.0.1时自动设置为true
    );