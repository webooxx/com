<?php return
array(

        #   入口处理
         
        'COMMAND_MODE'  => false,             #   命令行模式
        'LOG_SHOW_BASE' => 1,                 #   日志信息的展现级别, 1info 2warring 3error；上线后选3


        #    数据库设置
        'DB_ENGINE'=> 'Mysql',          #    数据库引擎类型，目前支持 Mysql ， Csv 类型
        'DB_PREFIX'=> '',               #    数据库表前缀
        'DB_HOST' => '127.0.0.1:3306',
        'DB_NAME' => 'test',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => '',
        'DB_DEFCHART' => 'UTF8',

        #    模板默认设置
        'TPL_THEME' => '.',              #    模板主题目录,为一个 . 则默认不使用主题目录，模板目录即为主题目录    相对于 /app/tpl 项目目录
        'TPL_ENGINE'=> 'php',            #    默认PHP，如果是 smarty ，则保持引入了 smarty 模块接口，因为会调用 A('smarty')->fetch( $path , $assign )，返回编译后的模板代码
        'TPL_LEFT_DELIMITER' => '<!--{', #    模板变量左分界符
        'TPL_RIGHT_DELIMITER'=> '}-->' , #    模板变量右分界符

        #   运行时自动重设的模板关键路径
        'TPL_URL_ROOT'     => '.',           #   index.php  入口URL
        'TPL_URL_PUBLIC'   => '.',           #   Public公共目录URL
        'TPL_URL_RELATIVE' => '.',           #   模板当前位置的URL
 


        #    项目默认设置，不建议修改
        'DIR_ACT'=> 'act',              #    控制器目录               相对于 /app/ 项目目录
        'DIR_TPL'=> 'tpl',              #    模板目录
        'DIR_MOD'=> 'mod',              #    数据模型目录
        'DIR_INC'=> 'inc',              #    公共类引用目录


        #    核心设置，不建议修改
        'DEF_REQ_KEY_RUT'=> 'r',        #    从 $_GET['r'] 中取得需要运行的模块类和方法，格式为 Mod/Act 或 Mod - 默认为 Mod/index
        'DEF_REQ_KEY_MOD'=> 'm',        #    从 $_GET['m'] 中取得需要运行的模块类
        'DEF_REQ_KEY_ACT'=> 'a',        #    从 $_GET['a'] 中取得模块类需要运行的方法
        'DEF_MOD'=> 'index',            #    默认请求的模块类
        'DEF_ACT'=> 'index',            #    默认执行的模块方法
        'DEC_ACT_EXT'=> 'Action',       #    默认模块类名后缀，例： indexAction.php

        #   自动重设路径，只读
        'PATH_APP'=> '.',               #    框架运行时自动设置
        'PATH_COM'=> '.',               #    项目入口文件绝对路径、项目组件路径
    );