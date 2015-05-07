<?php

class indexAction extends Action{

    function __construct(){
        $this->assign('title','ox build');
    }

    function filterAssess(){

        #   必须在本地才可以进行build
        if( $_SERVER['HTTP_HOST']!='127.0.0.1' && $_SERVER['HTTP_HOST']!='ueassess.int.baidu.com' ){
            die('filter Assess!');
            return false;
        }
        return true;
    }

    function index(){

        //ddump( f::init('ox.php' ,0,false, array("src/funcss_debug.php")  ));
        //ddump( f::init('ox.php' , false , 'mvc.min.php' ));

        $relation = f::init('ox.php' , true);
        ksort($relation);
        $option[] = '<style>body{padding:50px;}</style><body>';
        $option[] = '<h2>生成应用程序目录</h2>';
        $option[] = '<p><label><input type="radio" name="fs" />单文件应用</label><label><input type="radio" name="fs" />完整应用目录</label></p>';
        $option[] = '<p><label><input type="checkbox" name="in" />包含单独的框架文件</label></p>';
        $option[] = '<p><label><input type="checkbox" name="act" />包含简单的控制器和视图</label></p>';
        $option[] = '<p><label><input type="checkbox" name="mod" />包含简单的模型</label></p>';
        $option[] = '<p><label>应用名<input type="input" name="name" /></label></p>';


        $option[] = '<p><input type="submit"/></p>';
        $option[] = '<form action="?r=build/out" method="post"><h2>生成框架文件（选中需要排除的组件）</h2><ul>';
        foreach($relation as $k=>$r){
            if( strstr($k,'require') ){
                $disabled='disabled';
            }else{
                $disabled='';
            }

            $option[] = '<li> <label><input '.$disabled.' type="checkbox" name="ignore[]" value="'.$k.'">'.$k.'</label></li>';
        }
        $option[] = '<li><input type="submit"/></li>';
        $option[] = '</ul></form></body>';
        echo implode('',$option);
    }
    function out(){
        echo( f::init('ox.php' ,0, 'mvc.min.php', (array)$_POST['ignore']  ));
    }

}



class f
{
    static $imported = array();
    static $resource = array('php');
    static $dirs_map = array('php'=>'src');

    static function  init( $f = false ,$relation = false , $output =false ,$ignore=false )
    {
        if($ignore){
            foreach($ignore as $_src){
                f::$imported[$_src] = 'ignore';
            }
        }
        $files = explode(",", str_replace(array('../', '//'), '', trim($f)));

        //  确认当前的资源类型
        $file_ext = array_pop(explode(".", $files[0]));
        if (in_array($file_ext, f::$resource)) {
            f::$resource = $file_ext;
        } else {
            f::$resource = f::$resource[0];
        }

        $content = f::importing($files);

        if($relation){
            return f::$imported;
        }
        if($output){
            return file_put_contents( $output ,"<?php\r\n".$content);
        }

        return $content;
    }


    static function importing($sApi, $deep = 0)
    {
        $output = '';
        foreach ($sApi as $api) {


            $dir = f::$dirs_map[f::$resource];
            $dir = $dir ? $dir : f::$resource;

            $api = $dir . '/' . trim($api);
            if (isset(f::$imported[$api])) {
                dump($api);
                continue;
            }

            if (array_pop(explode(".", $api)) != f::$resource) {
                continue;
            }

            if (is_file($api)) {

                $content = file_get_contents($api);
                preg_match_all("/\s*(?:require_once|include_once)\(\'([^\n]+)\'\);/ies", $content, $match);

                foreach ((array)$match[1] as $k => $v) {

                    $_api = explode(',', trim($v));
                    $_src = trim($match[0][$k]);

                    if (isset(f::$imported[$_src])) {
                        $content = str_replace($_src,'', $content);
                        continue ;
                    }
                    f::$imported[$_src] = true;
                    $content = str_replace($_src, f::importing($_api, $deep + 1), $content);
                }
            } else {
                $content = "/*" . $api . " is 404! */";
            }
            $output .= ltrim(ltrim($content,'<?php'),'<?php return');

        }
        return trim($output);
    }
}
