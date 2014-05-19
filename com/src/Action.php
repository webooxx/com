<?php
/**
 * @name Action 控制器基类
 * @class
 * @description
 * - 所有的应用程序的控制器模块，均继承此基类，该类没有构造函数，所以 控制器 的代码逻辑中允许自由编写构造函数。
 * - 调用了一个不存在的控制器时，会尝试去执行框架自带的对应控制器。如果也不存在对应的默认控制器，则会报错。
 * - 调用了控制器中不存在的一个方法时，会尝试去展现对应方法的模板。如果也不存在模板，则会报错。
 * @example 一个自定义实现的控制器代码
 * 
 * class indexAction extends Action{
 *     function __construct(){  }
 *     function index(){
 *         echo '默认首页';
 *     }
 * }
 * 
 */
class Action {

    public $Module_From    = '.';
    public $Module_Name    = '.';
    public $Method_Name    = '.';
    public $Tpl_Variables    = array();

    /**
     * @name Action->display 模板展现方法
     * @function
     * @option $name {String} 模板名称，模板参数允许【 null 】【 Method.html 】【 Module/Method.html 】【  THEME/Module/Method.html  】
     * @option $data {Array}  模板变量，是一个键值对的数组
     * @description 执行这个方法后，页面会直接die出编译后的模板文件信息
     */
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
    /**
     * @name Action->fetch 模板编译方法
     * @function
     * @option $name {String} 模板名称
     * @return {String} 返回模板编译后的字符串
     * @description 
     * - 模板编译时会自动替换模板中的关键字，array('../Public/','../../','./' ) 替换为 TPL_URL_PUBLIC 、TPL_URL_ROOT、TPL_URL_RELATIVE
     * - 模板编译时会自动处理模板中的 include 关键字(注意需要有模板界符以及不要循环引用)
     * - 模板变量设置可以参考 @see Action->display 中设置，也可以参考 @ses Action->assign
     * - 模板编译引擎目前只支持PHP语法
     */
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
        $path_final = realpath( implode('/', $path_info ) );

        ox::c('TPL_URL_ROOT'     , str_replace( '/./','/', '//'.$_SERVER['HTTP_HOST'] . rtrim( substr(  ox::c('PATH_APP') , strlen( $_SERVER['DOCUMENT_ROOT'] ) ) ,'/' ).'/'  )) ;
        ox::c('TPL_URL_PUBLIC'   , str_replace( '/./','/',ox::c('TPL_URL_ROOT')  . implode('/', array(  ox::c('DIR_TPL') ,  ox::c('TPL_THEME') ,'Public/' ) ) ) );
        ox::c('TPL_URL_RELATIVE' , str_replace( '/./','/',trim(dirname( array_pop( explode( ox::c('PATH_APP') , $path_final) )), '/' ) .'/' ));

        if( !$path_final ){
            array_shift($path_info);
            return 'Template: <font color="red">'. implode('/', $path_info ) . '</font> is non-existent!' ;
        }

        #   如果存在模板文件
        $content = file_get_contents( $path_final );
        #   模板关键字替换
  
        $content = preg_replace('/(href=")(?!http)(\.\.\/Public\/)([^"]+)(")/', "$1".ox::c('TPL_URL_PUBLIC' )."$3 $4",$content );
        $content = preg_replace('/(src=")(?!http)(\.\.\/Public\/)([^"]+)(")/', "$1".ox::c('TPL_URL_PUBLIC' )."$3 $4",$content );

        $content = preg_replace('/(href=")(?!http)(\.\/)([^"]+)(")/', "$1".ox::c('TPL_URL_PUBLIC' )."$3 $4",$content );
        $content = preg_replace('/(src=")(?!http)(\.\/)([^"]+)(")/', "$1".ox::c('TPL_URL_RELATIVE' )."$3 $4",$content );

        $content = preg_replace('/(href=")(?!http)(\.\.\/\.\.\/)([^"]+)(")/', "$1".ox::c('TPL_URL_ROOT' )."$3 $4",$content );
        $content = preg_replace('/(src=")(?!http)(\.\.\/\.\.\/)([^"]+)(")/', "$1".ox::c('TPL_URL_ROOT' )."$3 $4",$content );

        #   处理引用，引用文件不存在时，由PHP的include默认错误进行处理
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
                    extract($this->Tpl_Variables );
                    $content = include( $tmpfname );
                }else{
                    $content = A( ox::c('TPL_ENGINE') )->fetch( $tmpfname , $this->Tpl_Variables );
                }

            unlink($tmpfname);  
        }
        return $content;
    }

    /**
     * @name Action->assign 模板变量赋值
     * @function
     * @param $name  {String} 变量名
     * @param $value {Any} 变量值
     * @return {Any} 返回$value
     */
    #   模板赋值
    function _action_assign($args = array()){
        $n = $args[0] ? $args[0] : null;
        $v = isset($args[1]) ? $args[1] : null;
        return $v===Null ?  $this->Tpl_Variables[$n] :  $this->Tpl_Variables[$n] = $v;
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
                ox::l('模块 filterAssess 验证不通过!',99,99);
            }
            if( method_exists($this, $a) ){
                ox::l('浏览器调用 '.$m.'->'.$a.' 已准备!');
                 return $this->$a();
            }else{
                ox::l('浏览器调用 '.$m.'->'.$a. ' 不存在!', 2 );
                #   尝试展现模板 Line:136
            }
        }else{
            #   模块调用请求处理
            $realname = '_action_'.$name ;
            $a = $name;
            if( method_exists($this, $realname) ){
                ox::l('模块调用 '.$a.'  已准备!');
                return $this->$realname( $args );
            }else{
                ox::l('模块调用 '.$a. ' 不存在!', 2 );
                #   尝试展现对应方法的模板
            }
        }
        #   尝试展现模板
        $template = realpath( $this->Module_From.'/'.ox::c('DIR_TPL').'/'.ox::c('TPL_THEME').'/'.$m.'/'.$a.'.html' );

        ox::l('尝试展现模板 '.$template ,1 );
        if( $template ){
            return $this->display();
        }else{
            ox::l( '模块和模板都不存在!' , 3 , 3 ) ;
        }
    }
}
/**
 * @name A 控制器模块获取快捷方法
 * @function
 * @short
 * @return {Instace} 返回控制器实例
 * @description 详细功能参考 @see ox::m
 * @example 调用其他控制器模块
 * A('tools')->args();
 */
function A( $m = '' ){ 
    $route = ox::r( array('m'=>$m) );
    ox::l( '调用模块: '.$route['m'] );
    return ox::m( $route );
}