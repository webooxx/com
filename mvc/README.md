#ooxx-php MVC框架文档

目标：在一个文件内实现基本可用的PHP框架。

## 特性
 * 一个核心文件
 * MYSQL支持链式调用读取数据
 * 支持简单文本格式CSV数据库
 * 支持简单模板，允许快速输出和插入PHP语句
 * 简单的快捷函数
 * 支持命令行模式调用
 
## 控制器(Action)方法

 - display( $template )
 - featch( $template )
 - assign( $name ,$value)
 - header( $type )

##	数据模型(Model)方法

数据模型支持链式调用，你可以写的类似这样 `M('user')->field('id,username,email')->where('id=1')->find0();`

不支持重型、抽象的模型类。也就是没类似 `/app/mod/userModel.php` 的文件来定义数据模型

 - field( $field )
 - where( $where )
 - find()
 - find0()
 - findAll()

## 模板(Template)方法、关键字

 - include $path
 	- 引用一个模板
 	- $path 是一个字符串可以支持 **$THEME:$ACTION:** $HTML.html 加黑部分可选
 - ../../
 	- 关键字，当前网站根目录路径类似于 `http:://127.0.0.1/`
 - ../Public/
  	- 关键字，当前网站根公共目录目录路径类似于 `http:://127.0.0.1/tpl/default/Public/`


## 框架快捷函数

一般在控制器中使用，例如要过滤提交的数据，你可以写成这样 `$args = A('tools')->args('id:int,usernmae:20,email:20') `

- A($moduleName)
	- 模块间调用
	- 示例 `$args = A('tools')->args('id:int');` 此段代码调用了 **toolsAction** 类中的 **args** 方法
	- A调用 **tools** 模块执行时 toolsAction->args 方法内的 的 **$this->mod_name** 为 **tools**
	- 如果当前act目录中没有对应模块,会去 mvc 目录中查找,都没有就报错。
	- 返回一个对应的实例化后的 **Action** 类,可以直接调用其方法。

- C($name,$value)
	- 全局参数设置
	- 用此方法设置的值在程序运行期间全局可用。
	- 不传入 **$value** 时,为读取模式
	- 传入的 **$value** 为 **NULL** 时,会删除这个变量,如果 **$name** 是系统变量,无效。
	- 读取了一个不存在的 **$name** 返回 **NULL**
	- 可以读取预设的系统变量如 `C('TPL_URL_INDEX')` 得到当前的首页的 http 路径

- \* F($path,$content)
	- 快捷存储函数
	- 一个参数读，两个参数写入
	- $path 参数为路径字符串，若设置为 `F('BCS:/test.Csv')` 类似的格式过 `BCSAction->get('/text.Csv')` 来得到内容

- I($name)
    - include 的快捷方式
	- 快捷载入一个类,会自动在  app/inc mvc/inc 下寻找 $name.Class.php 的文件引入
	- 只会引入一次

- J( $p1,$p2 …)
	- 路径合并函数
	- 可以传入若干个参数,函数会自动合并处理各种 . / 的问题,返回的路径末尾不包括 / 
	
- M($table)
	- 快速使用一个数据表
	- 需要预先在参数中设定数据库相关信息
       - 'DB_ENGINE'=> 'Mysql',          #   数据库擎类型,目前支持 mysql , csv 类型
       - 'DB_PREFIX'=> '',               #   数据库表前缀,如果是CSV类型数据库此项相当于数据文件存放目录(相对于项目主目录),~~可以使用云存储~~
       - 'DB_HOST' => '127.0.0.1:3306',
       - 'DB_NAME' => 'test',
       - 'DB_USERNAME' => 'root',
       - 'DB_PASSWORD' => '',
       - 'DB_DEFCHART' => 'UTF8',	
	
- S($name,$value)
	- 系统参数设置
	- 用法和C一样,专门用于重设系统变量,谨慎使用。


- dump($var)
	- 输出一段信息,被包含在 **pre** 标签中
	
- ddump($var)
	- 输出一段信息,被包含在 **pre** 标签中,并且随后 **die** 掉

- json($var)
	- 把变量 json_encode 后输出
- jsond($var)
	- 把变量 json_encode 后输出,并且随后 **die** 掉


## 配置项(Config)

所有配置项均可以通过 **C($name)** 获得,通过 **S($name,$value)** 重设

* 博客程序典型配置

		code…

* 个人mardkdown文档程序配置项

		code…

所有配置项:

	#    项目默认设置
	
	'DIR_APP'=> 'app',              #   项目目录
	'DIR_ACT'=> 'act',              #   控制器目录
	'DIR_INC'=> 'inc',              #   公共类目录名
	'DIR_MVC'=> 'mvc',              #   框架资源目录名
	
	'DIR_TPL'=> 'tpl',              #   模板目录             相对于 /app/ 项目目录
	'DIR_COM'=> '.tc',              #   模板编译目录         相对于 /app/ 项目目录
	'DIR_THEME'=> '.',              #   模板主题目录,为一个 . 则默认不使用主题目录，模板目录即为主题目录    相对于 /app/tpl 项目目录
	
	#    模板默认设置
	
	'TPL_ENGINE'=> 'none',          #   模板引擎类型，目前支持 none 原样输出（不使用编译目录，支持静态变量、include），default 内置的模板引擎，smarty 暂不支持
	'TPL_LEFT_DELIMITER' => '<!--{',#   模板变量左分界符
	'TPL_RIGHT_DELIMITER'=> '}-->' ,#   模板变量右分界符
	
	#    数据库设置
	
	'DB_ENGINE'=> 'Mysql',          #   数据库引擎类型，目前支持 Mysql ， Csv 类型
	'DB_PREFIX'=> '',               #   数据库表前缀，如果是 Csv 数据库类型,表前缀此项相当于数据文件存放目录,相对于 /app/,使用 F 快捷函数读取，意味着你可以使用云存储的数据
	'DB_HOST' => '127.0.0.1:3306',
	'DB_NAME' => 'test',
	'DB_USERNAME' => 'root',
	'DB_PASSWORD' => '',
	'DB_DEFCHART' => 'UTF8',
	
	#   核心设置
	
	'DEF_REQ_KEY_RUT'=> 'r',        #   从 $_GET['r'] 中取得需要运行的模块类和方法，格式为 Mod/Act 或 Mod - 默认为 Mod/index
	
	'DEF_REQ_KEY_MOD'=> 'm',        #   从 $_GET['m'] 中取得需要运行的模块类
	'DEF_REQ_KEY_ACT'=> 'a',        #   从 $_GET['a'] 中取得模块类需要运行的方法
	
	'DEF_MOD'=> 'index',            #   默认请求的模块类
	'DEF_ACT'=> 'index',            #   默认执行的模块方法
	'DEC_ACT_EXT'=> 'Action',       #   默认模块类名后缀，例： indexAction.php
	
	#    项目运行时变量
	
	'URL_INDEX'  => '.',            #   首页目录的URL 模板关键字 ../../ 会自动转换成该路径,http://127.0.0.1/
	'URL_PUBLIC' => '.',            #   模板Public目录的URL 模板关键字 ../Public/  会自动替换成该路径,类似 http://127.0.0.1/app/tpl/default/Public/
	
	#    初始化自动设置路径变量
	
	'PATH_NOW'=> '.',               #   项目 index.php 的目录路径
	'PATH_MVC'=> '.',               #   项目 框架资源 路径,根据 DIR_MVC 自动设置,当项目调用了不存在的 Act、Inc 资源时，会尝试从这个目录内读取
	'PATH_APP'=> '.',               #   项目 主目录   路径,根据 DIR_APP 自动设置
	'PATH_COM'=> '.',               #   项目 模板编译 路径,根据 DIR_COM 自动设置,如果目录不可写则尝试定位到临时目录
	
	#   系统其他设置
	'SYS_VERIFY_FUNC' => '',        #   系统，验证函数设置，格式为字符串，例如rbac:check，执行时传入一个参数数组 array( 'mod'=> 模块名 , 'act'=> , 方法名  )
	'SYS_CURRENT_MOD' => '',        #   系统，当前的模块名,执行模块时重设
	'SYS_CURRENT_ACT' => '',        #   系统，当前的方法名,执行方法时重设
	'SYS_COMMAND_MOD' => false,     #   程序是否是以命令行模式调用，默认为false,在命令行中调用时会自动设置为true,此模式下，所有的参数将被挂载到名为 $_GET 的对象上,注意:参数需要键名
	
	

## 框架自带模块支持

在这里集成了常用的函数，以及一些通用的业务逻辑，纯粹属于外挂代码，删除无任何影响。

### sessionAction

 会话模块
 
### rbacAction

权限验证模块

* reject
###toolsAction

工具包模块

* args
	* 参数过滤
* ext2type
	* 后缀转对应的Content-Type
* scand
	* 目录扫描,支持**高级正则**

## 框架自带类支持

同上

 * Markdown
 * BCS