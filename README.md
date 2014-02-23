#DESCRIPTION

##简介

因需要开发一个小小的功能却不得不引入庞大的框架，这种情况非常糟糕。所以最开始的目标是在一个文件内就实现简单的MVC功能以及一些方便的数据库操作、并且加入一些开发调试的快捷方法。

现在很显然不是，看起来至少需要2个文件了，一个 index.php 一个 mvc.php (ps 除非你想把逻辑写在 index.php 中，否则最好还是拆到 tpl 中吧)

现在有两个项目是依赖于这个项目存在的；一个博客程序，一个MarkDown文档平台。

##名词解释

- 控制器：指 act 目录下的各 控制器 文件
- 方法：指动作的某个具体的方法- 


##单文件应用 Single File

- /index.php
	- 入口文件，以及逻辑处理都在这里
- /mvc.php
	- 框架文件

##单项目目录结构 Single Project

这种目录结构适合在虚拟主机上使用，一般部署单个项目

- /index.php
	- 入口文件
- /config.php
    - 配置文件，一般需包含多个环境的判断以及相应的配置信息
- /act	
	- 可选，控制器目录（若无则直接执行对应控制器的 display 方法）
- /tpl
	- 模板视图目录，其下可建立多个子目录以区分主题，默认的主题目录为（ .）即为当前目录
- /tpl/./index	
	- 模板目录下默认主题的 index 控制器对应的视图目录
- /tpl/./index/index.html
	- index控制器index方法对应的默认视图，模板内可以采用PHP语法以及 inclue 其他模板
- /tpl/./Public
	- 模板目录下默认主题的 静态公共资源文件，模板内引用资源都需要使用 ../Public/ 的前缀来定位
- /mod 
	- 可选，数据模型目录
- /com
	- 框架目

##多项目共用一个com框架文件的目录结构 Multiple projects

这种配置适合有独立的服务器，可以设置网站根目录到某个具体的目录。

- /app-blog
- /app-blog/index.php
	- 	博客项目
- /app-xdoc/
- /app-xdoc/index.php
	- 	文档项目
- /com
	- 框架目录

##访问路由处理
目前支持两种模式

 - /?m={ActionName}&a={FunctionName}
 	- {ActionName} 即为 Action 文件，例如 indexAction.php 就是 ?m=index
 	- {FuncitionName} 即为 Action 文件内的方法



##特性

###支持命令行模式

这是我最喜欢的特性，有时候程序需要进行定时处理，尽管可以使用 URL 请求来触发，但如果是一个需要运行持续时间较长的操作，直接 URL 请求触发的方式则不太合适，需要做更多的逻辑处理。

####命令行下参数的传入和获取

- 传入：php index.php name=value name2=value2

- 获取：在命令行模式下，所有的参数都被传入到全局的 $_GET 中，你可以使用通用方法 A('tools')->args() 来获得


###控制器访问权限控制

默认所有控制器允许所有用户进行所有操作，可以通过在控制中增加 assessControl 方法。通过方法的返回值，确定是否允许访问。例如

	adminUserAction.php
	
	class adminUserAction extends Action{
	
		function assessControl(){
			
			return false;
		}
		
		#	删除
		function delete(){
			
		}
		
	}
		


###智能处理视图文件

###数据读取的链式操作

###入口文件对框架文件的自动引用

入口文件会对3个地方进行查询以引入 com 框架资源文件

- ./mvc.php
- ./com/mvc.php
- ./../com/mvc.php

首先会假设，当前目录即是框架目录，会引用当前目录内的 mvc.php

在当前目录下存在 com  目录时，会自动在其中查找框架资源

否则会尝试去上一个层级去查找。这样，在切换单项目，多项目类型的目录结构时，不需要做出其他修改，只需要移动 com 目录即可。

###不写控制器文件

当我们只想创建一个HTML，并且使用JS来开发一些特效。我们可以不必要创建控制器目录或者文件；可以只需要编写好相应的模板文件，然后可以立刻访问看到效果！当你想加入后台的操作，随时欢迎。例如：

	
	/app/tpl/index/index.html
	/app/tpl/index/note.html
	
	/app.tpl/Public/js/core.js
	
	index.html 的访问: localhost/
	note.html  的访问: localhost/?a=note

###共享框架的控制器

com 目录下也有一个完整的 act 目录，这些目录中的控制器逻辑也是可以使用的。这需要用到一个名为 A 的快捷方法。例如：

	在 /app/act/indexAction.php 中希望获得经过过滤后的$_GET数据

## 控制器 Action

话说不应该是  controller	么？因为 Action 好记，所以就当做是他吧。

### display()
### fetch()
### assign()

## 模板 Template

###模板语法

####变量输出

	<!--{$value}-->

####自定义输出

	<!--{; echo json_encode($value) ;}-->


	<!--{; echo json_encode($value) ;}-->

####自定义结构

	<!--{; if( $a = 1 ){ ;}-->
		htmlA
	<!--{; }else{ ;}-->
		htmlB		
	<!--{; } ;}-->

####模板文件引用

	<!--{ include inc-header.html }-->
	这里是body开始之后
		
	这里是body结束之前
	<!--{ include inc-footer.html }-->


引用的文件名是一个字符串可以支持 **$THEME:$ACTION:** $HTML.html 加黑部分可选

	default:note:index.html   # 路径解析为 /app/tpl/default/note/index.html
	

####布局文件支持

以下例子展现了访问 index.html 的时候使用布局文件来免去引用头部文件和尾部文件之苦

**layout.html 内容**
	
	<html>
	....
		<body>
			<!--{ $content }-->
		</body>
	</html>

**方法一：在 index.html 中设置**

	<!--{ $this->layout = 'layout.html' } -->;
	
	some content;

**方法二：在 indexAction 中设置**

	$this->layout = 'layout.html'

####模板关键字


## 数据模型 Model

### table( `name` )

获得数据对象，返回的对象也是一个Model对象，所以支持后续的链式操作

	new Model()->table( 'user' )
	
	new Model( 'table' )
	
	M('user')

	都返回一个 Model 对象，其中已经设定了操作表为 user 

####访问 csv 类型的数据表

	M('csv:user')

会使用默认配置中的 网站目录下 csv 目录下的 user.csv 文件



### select( String | Array ) / field(  String | Array )

选择字段，本步操作只会更新将要执行操作的数据字段部分

	M('user')->select('username')

	返回一个 Model 对象
 
### where( String | Array )

选择条件

### limit( {limit} )

条目限定

### find( [{condition}] ) / findAll( {limit} , [{condition}] )

查询操作，返回一个，和返回所有。

返回一条记录时，可以传入限定条件，和where一样。

返回所有记录时，默认是返回0-100条记录。
 

##快捷方法

### A 

调用其他控制器，包括 com 内的控制器

	例： 
	$args = A('tools')->args('id:int,usrename:str(32),password:sql(32)') ;

		#	$args['id']			数值类型的ID
		#	$args['username']	最长32的位字符
		#	$args['password']	最长32位的字符，同时进行了sql过滤，可以安全的用于构建SQL查询字符串

### M

使用数据模型，支持默认的 Mysql，CSV类型数据。和自定义的模型文件

	例：
	$user = M('user')->find('id=2');

### 通用全局变量操作 G

	读取 G('PATH_ROOT');
	设置 G('TPL_THEME','UED',true);		#	TPL_THEME 是已存在的系统变量，所以需要增加 ture 修饰符以强制覆盖
	删除 G('RecourdNumber',NULL)


### 通用文件操作 F

通用文件存取函数，支持不同的协议。目前本地文件读写，BCS云存储文件读写。文件查找

本地文件读取的路径默认相对于 PATH_ROOT 网站的根目录

####读取本地文件
	
	F('csv/user.csv')->read();

####读取BCS云存储的文件

	F('bcs://user.csv')->read();

####从URL中读取内容

	F('http://user.csv')->read();

####创建/修改内容

	#	写入临时文件到头像图片
	F('avatar/20130101-webooxx.png')->save( F('/tmp/tyfkuglkjg')->read() );

####增量增加模式修改内容

	#	添加一条日志信息到日记中
	F('sitelog/20130101.log')->append('[13:09]	some log info!');
	F('sitelog/20130101.log')->add('[13:09]	some log info!');

####搜索文件/目录

指定目录后，可以搜索所有的文件，如果需要遍历，则在 find() 中传入一个 true

	F( targetDir ).match( Regexp )->find();
	F( targetDir ).unmatch( Regexp )->find();
	
只搜索目录
	
	F( targetDir )->findDir();
	
只搜索文件

	F( targetDir )->findFile();

####删除文件

	F('filePath')->del();
	F('filePath')->del();

别名

	F( filePath )->delete();
	F( filePath )->rm();

####重命名/移动文件

	F('filePath')->rename( source , target );
	F('filePath')->mv( source ,target );


###S

持久会话的读写。自动支持：

1. PHP的SESSION功能
2. 数据库的SESSION表操作
3. 本地的CSV文件支持


##效率函数

###调试 ddump /  djson

###路径合并 joinp


##特殊文件说明

### 入口文件 index.php

### 框架文件 mvc.php

包含所有的框架逻辑，默认配置。

### 控制器类型的文件 indexAtion.php

控制器类型的文件必须是存放在控制器目录下， 有命名规范： **控制器名Action.php**

#### 引用文件

##配置和常量


配置一般写在配置文件中，可以在程序运行的时候使用C(name,value)来动态更改。

例如模板目录就可以通过 C('TPL_THEME') 来修改

所有的配置在 mvc.php 中都有默认的设置

###常量

	PATH_ROOT	站点根目录
	PATH_COM	框架目录

###运行时全局变量

可以通过通用变量存取函数 G() 获得

###数据库配置

    #    数据库设置


    'DB_HOST' => '127.0.0.1:3306',
    'DB_NAME' => 'test',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => '',
    

###框架默认推荐配置

不推荐修改这些默认设置

	#	数据库
    
    'DB_ENGINE'=> 'Mysql',          #    数据库引擎类型，目前支持 Mysql ， Csv 类型
    'DB_PREFIX'=> '',               #    数据库表前缀    
    'DB_DEFCHART' => 'UTF8',
    
	#	目录设定，相对于 app 目录

	'DEF_DIR_ACT'=> 'act',               #    默认控制器目录
	'DEF_DIR_MOD'=> 'mod',               #    数据模型目录
	'DEF_DIR_TPL'=> 'tpl',               #    模板目录
	'DEF_DIR_INC'=> 'inc',               #    引用文件目录名
	
	#	模板设定
	
	'TPL_THEME' => '.',              #    默认模板主题目录，设置为 . 即为模板目录
	'TPL_ENGINE'=> 'php',            #    模板引擎类型。设置为 none 则是原样输出，但支持include以及路径关键字
	'TPL_LEFT_DELIMITER' => '<!--{', #    模板变量左分界符
	'TPL_RIGHT_DELIMITER'=> '}-->' , #    模板变量右分界符

	#    核心设置

	'DEF_REQ_KEY_RUT'=> 'r',        #    从 $_GET['r'] 中取得需要运行的模块类和方法，格式为 Mod/Act 或 Mod - 默认为 Mod/index
	'DEF_REQ_KEY_MOD'=> 'm',        #    从 $_GET['m'] 中取得需要运行的模块类
	'DEF_REQ_KEY_ACT'=> 'a',        #    从 $_GET['a'] 中取得模块类需要运行的方法

	'DEF_MOD'=> 'index',            #    默认请求的模块类
	'DEF_ACT'=> 'index',            #    默认执行的模块方法
	'DEC_ACT_EXT'=> 'Action',       #    默认控制器文件后缀，例： indexAction.php
	

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