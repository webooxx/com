<?php
#    控制器管理器
class Action {

    public $Module_From    = '.';
    public $Module_Name    = '.';
    public $Method_Name = '.';

    #   模板展现
    function _action_display( $name , $storage){
        $this->fetch();
        
    }
    #   模板获取计算
    function _action_fetch(){
        ox::l('[Action->fetch] display '.$this->Module_Name.'/'.$this->Method_Name ,99,99 );
    }
    #   模板赋值
    function _action_assign(){

    }

    #    魔术方法，以处理 A 找不到模块的情况，以及浏览器入口处理
    function __call( $name , $args ){

        $m = $this->Module_Name;

        #   浏览器的请求
        if( $name == '_call_' ){
            $a = $args[0]['a'];
            #   需要验证的话
            if(  method_exists($this, 'filterAssess') && !$this->filterAssess($a) ){
                ox::l('[Action->_call_] Permission denied!',99,99);
            }            
            if( method_exists($this, $a) ){
                ox::l('[Action->_call_] Broswer Call Method '.$m.'->'.$a.' Ready to run!');
                 return $this->$a();
            }else{
                ox::l('[Action->_call_] Broswer Call Method '.$m.'->'.$a. ' is non-existent!', LEVEL_WARRING );
                #   尝试展现模板
            }
        }else{
            #   模块调用请求处理
            $realname = '_action_'.$name ;
            $a = $name;
            if( method_exists($this, $realname) ){
                ox::l('[Action->__call] Call Method '.$a.' Ready to run!');
                return $this->$realname( $args );
            }else{
                ox::l('Action->__call] Call Method '.$a. ' is non-existent!', LEVEL_WARRING );
                #   尝试展现对应方法的模板
            }
        }
        #   尝试展现模板
        $template = realpath( $this->Module_From.'/'.ox::c('DIR_TPL').'/'.ox::c('TPL_THEME').'/'.$m.'/'.$a.'.html' );

        ox::l(' Try to display Template '.$template ,LEVEL_INFO );
        if( $template ){
            return $this->display();
        }else{
            ox::l( 'Module & Template is non-existent!' , LEVEL_ERROR , 3 ) ;
        }
    }

/*
    #    集成模板功能：数据赋值、视图展现操作，执行 $this
    function assign($n,$v=Null){ return $v===Null ?  $this->tpl_vars[$n] :  $this->tpl_vars[$n] = $v; }
    function display($tpl=Null,$data=Null){
        if(!is_null($data)){
            foreach($data as $k=>$v){
                $this->assign($k,$v);
            }
        }
        @header("Content-type:text/html");
        die( $this->fetch($tpl) );
    }
    function fetch($tpl=Null,$isInc=false){

        #    处理 $tpl 参数，有可能：[ default:admin:login.html ] [ admin:login.html ] [ login.html ] [ login ]
        $tplpath = call_user_func_array(array($this, '_tplpath'), array_reverse ( explode(":",$tpl) ) );
        if( !realpath( $tplpath ) ){ return 'Template '. $tplpath . ' is non-existent!';}
        $tplpath = realpath( $tplpath );

        #    模板源码，关键字替换 [ ../Public/ ] [ ../../ ] 、处理引用，include 支持 与 $tpl 一样的参数
        $tplread = str_replace( array('../../','../Public/' ),  array( C('URL_INDEX'),C('URL_PUBLIC') ) ,F($tplpath) );

        $tplread = preg_replace_callback('/'.C('TPL_LEFT_DELIMITER').'\s?include\s+([^}]*)\s?'.C('TPL_RIGHT_DELIMITER').'/', array('self','_fetch_inc_callback'), $tplread );

        #    模板变量数据
        $tpldata = (array)$this->tpl_vars;
        #    模板编译文件路径
        $name_comple =  substr( $tplpath,strlen(C('PATH_COM'))+1 );
        $path_comple = J( C('PATH_COM'),'com_'.str_replace( array('/','\\',':'),'_', $name_comple ) .'.php');

        #    合并所有的include后（排除在include时候多次计算）
        if( !$isInc ){
            switch( C('TPL_ENGINE') ){
                case 'ooxx' :
                    #    模板替换，变量输出、PHP语句
                    $tplread = preg_replace('/('.C('TPL_LEFT_DELIMITER').')\s*\$(.*?);?\s*('.C('TPL_RIGHT_DELIMITER').')/','<?php echo \$$2; ?>',$tplread);
                    $tplread = preg_replace('/('.C('TPL_LEFT_DELIMITER').')\s*(.*?);?\s*('.C('TPL_RIGHT_DELIMITER').'){1}/','<?php $2; ?>',$tplread);

                    F( $path_comple , $tplread );

                    extract($tpldata);
                    $tplread = include( $path_comple);
                    unlink($path_comple);
                break;
                case 'smarty' :
                    #    使用A调用获得内容
                break;
            }
        }
        return  $tplread;
    }
    function _fetch_inc_callback( $arg ){ return $this->fetch( trim($arg[1]),true ); }

    #    计算Tpl的相对路径，支持不同的模板名、模块名、主题名，格式->  [主题名:][模块名:][方法名:].html ，参数传入为反方向传入；
    function _tplpath( $method = Null, $module = Null, $theme = Null){
        $method = $method ? $method : $this->fun_name ;
        $module = $module ? $module : $this->mod_name ;
        $theme  = $theme  ? $theme  : C('DIR_THEME') ;
        $method = explode('.',$method);
        $ext    = '.'.( $method[1] ? $method[1] : 'html' );
        $method = $method[0];
        return J( C('PATH_APP'),C('DIR_TPL'),$theme,$module,$method.$ext);
    }
    */
}