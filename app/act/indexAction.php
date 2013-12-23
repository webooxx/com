<?php
 class indexAction extends Action{

    function __construct(){

        C( 'DIR_WORKSPACE',J( C('PATH_NOW') ,'develop' ) );

        C( 'DIR_SOURCE',J( C('DIR_WORKSPACE') ,'source') );
        C( 'DIR_BUILD',J( C('DIR_WORKSPACE') ,'build') );
        C( 'DIR_OUTPUT',J( C('DIR_WORKSPACE') ,'output') );

    }

    function index(){

        $build = A('tools')->scan('develop/build/');
        $this->display('index.html',array(
            'title' => 'hello,word!',
            'build' =>$build
        ));
    }

    function build(){

        $args = A('tools')->args('name,code:int,outputDir');
        $config = json_decode(F($args['name']),true);
        $json = array();

        $files = $config['files'];
        foreach( $files as $output => $components ){

            foreach($components as $k=>$component){
                $sp = explode('::ignore::', $component);
                $import = explode(',',$sp[0]);
                $ignore = explode(',',$sp[1]);
                $components[$k] = $this->import($import,$ignore);
            }
            $files[$output] = $components;
        }
        $pathinfo = pathinfo($args['name']);
        $outputDir = $args['outputDir']?$args['outputDir']:$pathinfo['filename'].'-'.date('Y.m.d-H.i.s');
        $insertBefore = "/** $outputDir */\r\n";
        foreach( $files as $file => $components ){


            $outfile = J( C( 'DIR_OUTPUT') , $outputDir ,$file );
            $content = $insertBefore.implode("\r\n",$components);



            if (!$args['code']) {
                mkdir(dirname($outfile), 0777, true);
                chmod(dirname($outfile), 0777);
                if($pathinfo['extension'] == 'php'){
                    $content = "<?php\r\n$content\r\n?>";
                }
            }
            if( $args['code']){
                $json[$outfile] = "\r\n\r\n# -------- 文件输出 -------- $outfile\r\n\r\n".$content;
            }else{
                $json[$outfile] = F( $outfile,$content ) ;
            }

        }
        if( $args['code']){
            $this->display('code.html',array(
                'code' => implode("\n\r\n\r",$json)
            ));
        }else{
            $json[] = J(C('URL_INDEX'),'develop/output/',$outputDir);
            djson($json);
        }

    }

    function import( $files , $ignore=array() ){
        $this->imported = array();
        foreach($ignore as $f){
            $this->imported [$f] = 'ignore';
        }
        return $this->importing( $files);
    }

    function importing( $files , $result = ''){
        foreach($files as $file){
            if( $this->imported[$file] ){ continue; }
                $this->imported[$file] = 'imported';
                /// trim 需要改进

                $content = trim( preg_replace("/(^<\?php)|(\?>$)/", "", trim(F( J( C('DIR_SOURCE') ,$file ))) ) );
                preg_match_all("/\/\/\/\s?import\s+([\w\-\$]+(\.[\w\-\$]+)*);?/ies", $content, $match);
                foreach( (array)$match[1] as $k=>$v){
                    $content = str_replace( $match[0][$k], $this->importing( array($v) ,$content), $content );
                }
           $output .= $content;
        }
        return $output;
    }
}