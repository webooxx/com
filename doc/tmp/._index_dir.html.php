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
		<?php for( $i=0;$i<count( $path) ;$i++ ){; ?>
			<a title="<?php echo $i; ?>" href="/<?php echo implode( '/' ,array_slice( $path ,0,$i+1) ); ?>" ><?php echo $path[$i]; ?></a> /
		<?php }; ?>
	</div>
<div id="content">

	<div id="main">
	<?php if( $mode =='dir' ) {; ?>
		<div id="dir"  class="clearfix">
		
		
		<?php if( count($dirs)>0 ) {; ?>
			<h3>目录</h3>
			<ul class="clearfix" id="dir_dirs">
				
			<?php foreach( $dirs as $item ){; ?>
				<li><a href="<?php echo $item['path']; ?>"><div><?php echo $item['name']; ?><br><span> <?php echo $item['items']; ?> items</span></div></a></li>
			<?php }; ?>
			</ul>
		<?php }; ?>
		
			<h3>MarkDown文档</h3>
		<?php if( count($docs)>0 ) {; ?>
			<ul class="clearfix">
			<?php foreach( $docs as $item ){; ?>
				<li><a href="<?php echo $item['path']; ?>"><?php echo $item['name']; ?></a></li>
			<?php }; ?>
			</ul>
		<?php }else{; ?>
			暂无文档
		<?php }; ?>


			
		<?php if( count($imgs)>0 ) {; ?>
			<h3>图像</h3>
			<ul class="clearfix" id="dir_imgs">
				
			<?php foreach( $imgs as $item ){; ?>
				<li><a href="<?php echo $item['path']; ?>"><img width="100%" onload="setSize(this)" src="<?php echo $item['path']; ?>"></a></li>
			<?php }; ?>
			</ul>
		<?php }; ?>
			
		<?php if( count($other)>0 ) {; ?>
			<h3>其他</h3>
			<ul class="clearfix">
				
			<?php foreach( $other as $item ){; ?>
				<li><a href="/doc/ge/magic网站/测试">Tangram2问题修改相关文档</a></li>
			<?php }; ?>
			</ul>
		<?php }; ?>
			
			
		</div>
	<?php }; ?>
	<?php if( $mode =='art' ) {; ?>
	<div id="art"  class="clearfix">
		<?php echo $content ; ?>
	</div>
	<?php }; ?>
	</div>
	

	<div id="sidebar">
	
		
		<h3> 目录结构 </h3>
		<ul>
		<?php foreach( $tree as $item ){; ?>
			<li><a href="<?php echo $item['path']; ?>" style="text-indent:<?php echo $item['level']-1; ?>em"><?php echo $item['name']; ?></a></li>
		<?php }; ?>
		</ul>
		
		<h3> 文档 </h3>
		
		<?php if( count($docs)>0 ) {; ?>
			<ul>
			<?php foreach( $docs as $item ){; ?>
				<li><a href="<?php echo $item['path']; ?>" style="text-indent:<?php echo $item['level']; ?>em"><?php echo $item['name']; ?></a></li>
			<?php }; ?>
			</ul>
		
		<?php }else{; ?>
			暂无文档
		<?php }; ?>
		

	</div>
</div>

<script type="text/javascript">
	function setSize( el ){
		el.style.Width = '120px'
	}
</script>

<!-- footer starts -->      
<div id="footer">
    <p>&copy; 2012 webooxx.com All Rights Reserved.</p>            

<!-- footer ends-->
</div>
<!-- wrap ends here -->
</div>

</body>
</html>
