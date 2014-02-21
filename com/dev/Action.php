<?php
#    控制器管理器
class Action {

    #    魔术方法，以处理 A 找不到模块的情况，以及浏览器入口处理
    function __call( $name , $args ){

var_dump( count(ox::cfg('SYS_REQ')) );
        #   只有浏览器的请求才会经过这里，应该把
        if( $name == '_call_' ){
            $args = $args[0];
            $function = $args['a'];
            if( method_exists($this, $function) ){

                //var_dump( method_exists( parent, $function );
               

                 return $this->$function();
            }
           

        }
        // S('URL_INDEX' , rtrim( 'http://'.J( $_SERVER['HTTP_HOST'], dirname( $_SERVER['SCRIPT_NAME'] )  ) ,'\\/' ).'/' );
        // S('URL_PUBLIC',C('URL_INDEX').J(  C('DIR_APP'), C('DIR_TPL'), C('DIR_THEME'),'Public' ) .'/') ;

        // $fun_name = $this->fun_name = $args[0];

        // #    处理浏览器入口执行的魔术调用
        // if( $method == '_ActCall_' ){

        //     #    验证处理浏览器的访问
        //     if(  C('SYS_VERIFY_FUNC')  ){
        //         $val = explode( ':', C('SYS_VERIFY_FUNC') );
        //         $mod = ooxx::mod($val[0]);
        //         #    调用验证方法，错误信息应当在验证方法中输出。
        //         if( !$mod->$val[1]( array('mod'=>$args[1],'act'=>$args[0])) )  {
        //             return false;
        //         }
        //     }
        //     #    验证通过，将执行的方法名从魔术调用修改为需要执行的方法名
        //     $method = $fun_name;
        // }

        // #    处理A调用，$this->do()调用，浏览器入口验证通过后执行的方法
        // if( $method != '_ActCall_' ){
        //     if( method_exists($this, $fun_name) ){
        //          return $this->$fun_name();
        //     }else{
        //         #    尝试直接展现对应方法的模板
        //         $tplpath = realpath( $this->_tplpath($method) );
        //         return $tplpath ? $this->display() : die(  $this->mod_name.'Action->'.$method.' is non-existent!');
        //     }
        // }
    }
    private function test(){
        echo test;

    }

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
}