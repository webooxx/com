
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ox 框架构建程序</title>
    <link rel="stylesheet" href="../Public/css/base.css">
    <link rel="stylesheet" href="../Public/css/main.css">
    <script src="../Public/jquery.min.js"></script>
</head>
<body>
<header id="header">
    <section class="nav">
        <ul>
            <li>
                <a href="?r=index/index" class="logo ">
                        <i class="word">x</i>
                        <i class="word">o</i>
                        <i class="word">x</i>
                        <i class="word">o</i>
                        <i class="word">x</i>
                        <i class="word">o</i>
                        <i class="word">x</i>
                        <i class="word">o</i>
                        <i class="word">x</i>
                </a>
            </li>

            <li>
                <a href="?r=index/index" class="active"> 构建应用程序 </a>
            </li>
            <li>
                <a href="?r=index/buildFramework"> 生成框架文件缓存 </a>
            </li>
            <li>
                <a href="?r=index/packageApp"> 打包应用程序 </a>
            </li>
        </ul>
    </section>
</header>

<div class="wrap clear">
    <h2 class="caption">构建应用程序</h2>
    <div class="content">
        <ul>
            <li>
                <span class="label">应用程序名/目录</span>
                <span class="input"><input type='text' value='' name="appName" id="appName"></span>
            </li>
        </ul>
    </div>
</div>

<footer >
    ©2016 webooxx.com
</footer>
</body>
</html>