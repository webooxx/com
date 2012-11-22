<!DOCTYPE html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="description" content="<?php echo $description; ?>" />
<title><?php echo $title; ?></title>
<link rel="stylesheet" href="//127.0.0.1/www/webooxx.com/1/_sys/doc/tpl/./Public/css/default.css" type="text/css" />
<link rel="stylesheet" href="//127.0.0.1/www/webooxx.com/1/_sys/doc/tpl/./Public/css/layout.css" type="text/css" />

</head>

<body>
<!-- wrap starts here -->
<div id="wrap">

	<!--header -->
	<div id="header">
		路径：<a href="/">/</a>
		<?php ;$link = "/"; ?>
		<?php foreach( $path as $item ){; ?>
			<?php ;$link = "/".$item; ?>
			<a href="<?php echo $link; ?>" ><?php echo $item; ?></a>  <?php echo $dirSeparator; ?>
		<?php }; ?>
	</div>
<div id="content">


	<div id="sidebar">
		
		<h3> 侧边列表 </h3>
		<h3> 文档列表 </h3>
			<ul>
				<a href="#"a href="/doc/ge/工具平台/流程">文档</a>
				<a href="#">文档</a>
				<a href="#">文档</a>
				<a href="#">文档</a>
			</ul>
		<h3> 项目目录 </h3>
		<h3> 标签列表 </h3>
	
		
	</div>
	<div id="main">
		<div id="article">

			<h1><?php echo $title; ?></h1>
			<h2>访问此页的方式</h2>

			<p>http://domain/index.php?m=index&a=index</p>

			<h2>路径关键字</h2>
			<ul>
				<li>index.php 的根目录【..&#47;../】//127.0.0.1/www/webooxx.com/1/</li>
				<li>相对主题模板目录下的Public目录【 ..&#47;Public/】//127.0.0.1/www/webooxx.com/1/_sys/doc/tpl/./Public/</li>
			</ul>
			
		</div>
	</div>
	

	
</div>
<!--
<script type="text/javascript"></script>
-->
<!-- footer starts -->      
<div id="footer">
    <p>&copy; 2012 webooxx.com All Rights Reserved.</p>            

<!-- footer ends-->
</div>
<!-- wrap ends here -->
</div>

</body>
</html>
