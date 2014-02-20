<?php
/**
    百度云存储控制器
    @引用依赖    inc/bcs/.*
*/
class bcsAction extends Action{

    function __construct(){
        I( 'bcs/bcs' );
        $host = 'bcs.duapp.com';
        $ak   = C('BCS_AK');
        $sk   = C('BCS_SK');
        C('BCS_INSTANCE' ,new BaiduBCS ( $ak, $sk, $host ) );
        $this->bucket( C('BCS_DEF_BUCKET') );
    }
    function index(){

        $tempName = 'test-'.time();

        // $json['测试仓库-创建'] = $this->create($tempName);
        // $json['测试仓库-存在'] = $this->exists($tempName);
        // $json['仓库列表[0]'] = $this->buckets();

        // $json['测试仓库-文件保存'] = $this->bucket($tempName)->save('/'.$tempName.'.txt','测试内容'.$tempName);
        // $json['测试仓库-文件列表'] = $this->bucket($tempName)->findAll();
        // $json['测试仓库-文件内容'] = $this->bucket($tempName)->read('/'.$tempName.'.txt');
        // $json['测试仓库-文件删除'] = $this->bucket($tempName)->delete('/'.$tempName.'.txt');
        // $json['测试仓库-删除'] =  $this->drop($tempName);
        
        $json['仓库列表[1]'] = $this->buckets();
        $json['仓库[1]-文件列表'] = $this->bucket( 'thumbsdb' )->findAll();
        $json['仓库[1]-文件列表-详细模式'] = $this->bucket( 'thumbsdb' )->detail(true)->findAll();
        $json['仓库[1]-文件[1]-内容'] = $this->bucket( $json['仓库列表[1]'][0] )->read( $json['仓库[1]-文件列表'][0] );
        $json['文件请求测试'] = j( C('TPL_URL_INDEX') ,'index.php?m=bcs&a=httpFile&bucket='.$json['仓库列表[1]'][0].'&object='.urlencode($json['仓库[1]-文件列表'][0] ));

        djson($json);
    }


    #   设定
    function bucket( $arg ){ $this->bucket = $arg; $this->prefix = '/';  $this->detail = false; return $this; }
    function prefix( $arg ){ $this->prefix = $arg; return $this; }
    function detail( $arg ){ $this->detail = $arg; return $this; }

    #   仓库操作需要传入仓库名参数，文件操作则通过 A('bcs')->bucket($name) 来设定仓库名

    #   创建仓库 bool
    function create($bucket){
        $this->bucket = $bucket;
        $acl = BaiduBCS::BCS_SDK_ACL_TYPE_PRIVATE;
        try{
            @C('BCS_INSTANCE')->create_bucket ( $bucket , $acl);
            return true;
        } catch ( Exception $e ) {
            return false;
        }
    }
    #   删除仓库 bool
    function drop($bucket){
        $this->clear($bucket);
        $result = @C('BCS_INSTANCE')->delete_bucket( $bucket );
        return $result->status == 200;
    }
    #   清空仓库 bool
    function clear($bucket,$timeout=5){
        $this->bucket = $bucket;
        if( $timeout < 0){ return false;}
        $files = $this->findAll();
        foreach($files as $file){ @C('BCS_INSTANCE')->delete_object( $bucket, $file );}
        $files = $this->findAll();
        if( count($files)>0 ){ return $this->drop( $bucket ,$timeout-1 );}
        return true;
    }
    #   仓库列表 []
    function buckets(){
        $result =  @C('BCS_INSTANCE')->list_bucket();
        $result = json_decode($result->body,true);
        if( !$result ){ return array(); }
		if( $this->detail ){ return $result; }
		foreach($result as $item){ $bucket[] = $item['bucket_name'];}
        return $bucket;
    }
    #   仓库是否存在检测 bool
    function exists($bucket){
        return in_array($bucket, $this->buckets());
    }

    #   保存文件
    function save( $object,$content ){
        $result = @C('BCS_INSTANCE')->create_object_by_content( $this->bucket, $object ,$content );
        return $result->status == 200;
    }
    #   读取文件 filecontent / false
    function read( $object ){
        $file = @C('BCS_INSTANCE')->get_object( $this->bucket, $object );
        return $file->status == 200 ? $file->body : false;
    }
    #   上传文件 bool
    function upload( $object,$path ){
        $opt = array ();
        $opt ['acl'] = BaiduBCS::BCS_SDK_ACL_TYPE_PUBLIC_WRITE;
        $opt [BaiduBCS::IMPORT_BCS_LOG_METHOD] = "bs_log";
        $opt ['curlopts'] = array ( CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 1800 );
        try{
            @C('BCS_INSTANCE')->create_object ( $this->bucket, $object, $path, $opt );
            return true;
        } catch ( Exception $e ) {
            return false;
        }
    }
    #   上传目录
    function uploadDir( $bucket,$path ){
        $opt = array ( "prefix" => $this->prefix, "has_sub_directory" => true,  BaiduBCS::IMPORT_BCS_PRE_FILTER => "pre_filter",  BaiduBCS::IMPORT_BCS_POST_FILTER => "post_filter" );
        @C('BCS_INSTANCE')->upload_directory ( $thisbucket, $path, $opt );
    }
    #   删除文件
    function delete( $object ){
        $result = @C('BCS_INSTANCE')->delete_object($this->bucket, $object);
        return $result->status == 200;
    }
    #   文件列表
    function findAll( $match = '' ){
        $objects = array();
        $start   = 0;
        $size    = 100;
        while( $list = @C('BCS_INSTANCE')->list_object ( $this->bucket , array( 'start'=> $start, 'limit'=>$size,'prefix'=>$this->prefix) ) ){
            $part =  json_decode($list->body,true);

            if( count($part['object_list'] ) == 0 ){ break ;}
            foreach( $part['object_list'] as $item){
                if( !$match || preg_match( $match , $item['object'] )  ){
					$objects[] = $this->detail ? $item : $item['object'] ;
                }
            }
            $start = $start+$size;
        }
        return $objects;
    }
    #   文件重命名
    function mv( $object , $objectCopy){
        $source = array (
                'bucket' => $this->bucket,
                'object' => $object );
        $dest = array (
                'bucket' => $this->bucket,
                'object' => $objectCopy  );
        $response =  @C('BCS_INSTANCE')->copy_object ( $source, $dest );
        return $response->isOK();
    }

    #   HTTP读取文件请求
    function httpFile(){
        $args = A('tools')->filter('bucket,object',$_GET);
        $content = $this->bucket($args['bucket'])->read($args['object']);
        if( $content ){
            $info = pathinfo($path);
            header("Content-type:".A('tools')->cType($info['extension']));
            die($content);
        }else{
            header("HTTP/1.1 404 Not Found");
        }
    }


}