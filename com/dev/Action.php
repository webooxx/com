<?php #    控制器基类
class Action {

    public $Module_From    = '.';
    public $Module_Name    = '.';
    public $Method_Name    = '.';
    public $Tpl_Storage    = array();

    #   模板展现
    function _action_display( $args = array() ){

        $name = $args[0] ? $args[0] : null;
        $data = $args[1] ? $args[1] : null;
        if(!is_null($data)){
            foreach($data as $k=>$v){
                $this->assign($k,$v);
            }
        }
        @header("Content-type:text/html");
        die( $this->fetch( $name) );
        
    }
    #   模板获取计算
    function _action_fetch( $args = array() , $isInc = false ){

        $name = $args[0] ? $args[0] : null;

        #   模板参数允许【 null 】【 Method.html 】【 Module/Method.html 】【  THEME/Module/Method.html  】
    
        $path_info = array( ox::c('PATH_APP') , ox::c('DIR_TPL') , ox::c('TPL_THEME') , '3::MODULE' , '4::METHOD.html/Filename'  );

        $path_info[3] = $this->Module_Name;
        $path_info[4] = $this->Method_Name . '.html';

        if( !is_null($name) ){

            $name_split = explode('/', $name);
            switch ( count($name_split) ) {
                case '1':
                    $path_info[4] = $name_split[0];
                break;
                case '2':
                    $path_info[3] = $name_split[0];
                    $path_info[4] = $name_split[1];
                break;
                case '3':
                    $path_info[2] = $name_split[0];
                    $path_info[3] = $name_split[1];
                    $path_info[4] = $name_split[2];
                break;
            }
        }

        ox::c('URL_ROOT'     , '//'.$_SERVER['HTTP_HOST'] . rtrim( substr(  ox::c('PATH_APP') , strlen( $_SERVER['DOCUMENT_ROOT'] ) ) ,'/' ).'/'  );
        ox::c('URL_PUBLIC'   , ox::c('URL_ROOT')  . implode('/', array(  ox::c('DIR_TPL') ,  ox::c('TPL_THEME') ,'Public/' ) ) );
        ox::c('URL_RELATIVE' , trim(dirname( array_pop( explode( ox::c('PATH_APP') , $path_final) )), '/' ) );

        $path_final = realpath( implode('/', $path_info ) );


        if( !$path_final ){
            array_shift($path_info);
            return 'Template: <font color="red">'. implode('/', $path_info ) . '</font> is non-existent!' ;
        }
  
        #   如果存在模板文件
        $content = file_get_contents( $path_final );
        #   模板关键字替换
        $content = str_replace( array('../Public/','../../','./' ),  array(  ox::c('URL_PUBLIC' ),ox::c('URL_ROOT' ),ox::c('URL_RELATIVE' ) ) ,$content  );

        #   处理引用
        $content = preg_replace_callback('/'.ox::c('TPL_LEFT_DELIMITER').'\s?include\s+([^}]*)\s?'.ox::c('TPL_RIGHT_DELIMITER').'/', array('self','_fetch_inc_callback'), $content );

        $content = preg_replace('/('.ox::c('TPL_LEFT_DELIMITER').')\s*\$(.*?);?\s*('.ox::c('TPL_RIGHT_DELIMITER').')/','<?php echo \$$2; ?>',$content);
        $content = preg_replace('/('.ox::c('TPL_LEFT_DELIMITER').')\s*(.*?);?\s*('.ox::c('TPL_RIGHT_DELIMITER').'){1}/','<?php $2; ?>',$content);

        #   模板变量释放
        if( !$isInc ){
           
            $tmpfname = tempnam(sys_get_temp_dir(),"oxTpl_");
            $handle = fopen($tmpfname, "w");
            fwrite($handle, $content);
            fclose($handle);

                if( ox::c('TPL_ENGINE') == 'php' ){
                    extract($this->Tpl_Storage );
                    $content = include( $tmpfname );
                }else{
                    $content = A( ox::c('TPL_ENGINE') )->fetch( $tmpfname , $this->Tpl_Storage );
                }

            unlink($tmpfname);  
        }
        return $content;
    }
    #   模板赋值
    function _action_assign($args = array()){
        $n = $args[0] ? $args[0] : null;
        $v = isset($args[1]) ? $args[1] : null;
        return $v===Null ?  $this->Tpl_Storage[$n] :  $this->Tpl_Storage[$n] = $v;
    }
    function _fetch_inc_callback( $arg ){ return $this->_action_fetch( array( trim($arg[1]) ),true ); }

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
                #   尝试展现模板 Line:136
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
}