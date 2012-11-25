<?php
class indexAction extends Action{

    function __construct(){}

	function test(){
		dump($_GET);
		dump($this);
		dump( C('DIR_DIR') );
		dump( file_get_contents( C('DIR_DIR').'/app.conf' ));
		dump( file_get_contents( C('DIR_DIR').'/sys/act/indexAction.php' ));
	}
	
	function index(){		
		
		$intoPath = $_GET['p'] ? $_GET['p']  : '/';		#	传入的参数
		
		$realRoot = joinp( C('DIR_DIR') ,C('DIR_DOC'));	#	服务器文档根目录
		$realPath = joinp( $realRoot , $intoPath);		#	服务器文档实际路径
		
		
		#	如果文件不存在
		if( !file_exists($realPath) ){ return $this->display( '404.html'); }
		
		
		if( !is_dir( $realPath ) ){
			$ext = array_pop( explode('.',$realPath) );
			$con = file_get_contents($realPath);
			#	如果不是MarkDown文件
			if( $ext != C('EXT_DOC') ){
				header("content-type:".$this->ext2type('.'.$ext).";charset=utf-8");
				die( $con );
			}
			I('Markdown');
			
			$tplName    = 'doc.html';
			$currentDir = dirname( $intoPath );
			$this->assign( 'con' , Markdown( $con ) );
			
		}else{
			$tplName    = 'dir.html';
			$currentDir = $intoPath;
		}

		$tree = $this->tree($realRoot ,  strlen( $realRoot ) ,'/^[^_\.].*/');
		$file = $this->file($realPath , strlen( $realRoot ) ,'/^[^_\.].*/');
		
		$this->assign( 'tree' ,$tree );
		$this->assign( 'file' ,$file );
		$this->assign( 'path' , $intoPath == '/' ? array() : (array)explode('/',  substr( $realPath,strlen($realRoot)+1  )) );
		$this->assign( 'cdir' , $currentDir);

		$this->assign( 'title' , "简单的Markdocs Online Path:".substr($realPath,strlen($realRoot) ) );
		$this->assign( 'description' ,"简单的在线Markdown文档,网络,程序,设计,生活" );
	
		$this->display($tplName);
	}
	
	#	取得当前目录下的文件列表
	function file( $realPath , $cutLen ,$match ){
	
		$dirs = array();
		$docs = array();
		$imgs = array();
		$other= array();
		
		if( is_dir( $realPath ) ){
			$scan = $this->scan( $realPath , $match );
		}else{
			$scan = $this->scan( dirname( $realPath), $match );
		
		}
		foreach( $scan as $item ){
			$file['path'] = substr( $item , $cutLen ) ;
			$file['name'] = array_pop( explode('/', $file['path'] )  );
			$file['mtime'] = date ("Ymd-H:i:s",  filemtime($item ));
			$file['stat']  = stat($item );
			
			if( is_dir( $item ) ){
				$_scan = $this->scan( $item , '/^[^_\.].*$/' );
				$file['items'] = (int)count( $_scan );
				$dirs[] = $file; 
			}else{
				if( preg_match( '/\.'.C('EXT_DOC').'$/' ,$file['name'] ) ){
					$file['name'] = array_shift( explode('.', $file['name'] )  );
					$docs[] = $file;
				}else if( preg_match( '/\.(jpg|jpeg|gif|png|bpm)/i' ,$file['name'] ) ){
					$imgs[] = $file;
				}else{
					$other[] = $file;
				}
			}
		}
		return array('dirs'=>$dirs ,'docs'=>$docs ,'imgs'=>$imgs ,'other'=>$other ,);
	}
	
	#	取得文档整体目录树	
	function tree( $path ,  $cutLen = 0 , $regexp = '/.*/' ,  $flat = 0 , $result = array() ,  $level = 0 ){
		$level = $level+1;
		$dirs  = scandir( $path );
		foreach($dirs as $d) {
			$realpath = joinp( $path ,$d );
			$current  =  array();
			if( !is_dir($realpath) ){ continue ;}
			
			if ($d == '.' || $d == '..') {}
			else if( preg_match( $regexp , $d ) ){
				if( is_int( $flat ) ){
					$id = $this->gid('tree');
					$result[] = array( 'id'=>$id,  'pid'=> $flat, 'name' => $d , 'path' => substr( $realpath ,$cutLen ) ,'level'=>$level  );
					$result   = $result + $this -> tree($realpath ,$cutLen , $regexp, $id , $result ,$level);
				}else{
					$result[] =  array( 'name' => $d , 'path' => substr( $realpath ,$cutLen ) , 'child' => $this->tree( $realpath ,$cutLen , $regexp  , $flat ,$result  )  );
				}
			}
		}
		return $result;
	}
	
	
	
	function gid( $type='default' ){
		return $this->gid[ $type] = (int)$this->gid[ $type]+1;
	}
	
    #	普通搜索
    function scan($path , $match = Null){ return $this->scand( $path , $match  , array() , false);}
    #	遍历搜索
    function scand( $path , $match = Null , $result = array() ,$rec = true) {
        $dirs = (array)scandir( $path );
        foreach($dirs as $d) {
            if ($d == '.' || $d == '..') {}
            else{
                $real = joinp($path,$d);
                if(is_null($match)){  $result[] = $real ;}else{ preg_match( $match , $d)==1 && $result[]= $real ;}
                if(is_dir($real) && $rec )  {  $result = $result + $this -> scand( $real,$match, $result,$rec ); }
            }
        }
        return $result;
    }
	
	#	错误信息
	function err404(){
		die('没有找到文件!');
	}
	
	#	给出对应的 ContentType
	function ext2type( $ext ){	
		$b = array( ".*"=>"application/octet-stream",
			".001"=>"application/x-001",
			".301"=>"application/x-301",
			".323"=>"text/h323",
			".906"=>"application/x-906",
			".907"=>"drawing/907",
			".a11"=>"application/x-a11",
			".acp"=>"audio/x-mei-aac",
			".ai"=>"application/postscript",
			".aif"=>"audio/aiff",
			".aifc"=>"audio/aiff",
			".aiff"=>"audio/aiff",
			".anv"=>"application/x-anv",
			".asa"=>"text/asa",
			".asf"=>"video/x-ms-asf",
			".asp"=>"text/asp",
			".asx"=>"video/x-ms-asf",
			".au"=>"audio/basic",
			".avi"=>"video/avi",
			".awf"=>"application/vnd.adobe.workflow",
			".biz"=>"text/xml",
			".bmp"=>"application/x-bmp",
			".bot"=>"application/x-bot",
			".c4t"=>"application/x-c4t",
			".c90"=>"application/x-c90",
			".cal"=>"application/x-cals",
			".cat"=>"application/s-pki.seccat",
			".cdf"=>"application/x-netcdf",
			".cdr"=>"application/x-cdr",
			".cel"=>"application/x-cel",
			".cer"=>"application/x-x509-ca-cert",
			".cg4"=>"application/x-g4",
			".cgm"=>"application/x-cgm",
			".cit"=>"application/x-cit",
			".class"=>"java/*",
			".cml"=>"text/xml",
			".cmp"=>"application/x-cmp",
			".cmx"=>"application/x-cmx",
			".cot"=>"application/x-cot",
			".crl"=>"application/pkix-crl",
			".crt"=>"application/x-x509-ca-cert",
			".csi"=>"application/x-csi",
			".css"=>"text/css",
			".cut"=>"application/x-cut",
			".dbf"=>"application/x-dbf",
			".dbm"=>"application/x-dbm",
			".dbx"=>"application/x-dbx",
			".dcd"=>"text/xml",
			".dcx"=>"application/x-dcx",
			".der"=>"application/x-x509-ca-cert",
			".dgn"=>"application/x-dgn",
			".dib"=>"application/x-dib",
			".dll"=>"application/x-msdownload",
			".doc"=>"application/msword",
			".dot"=>"application/msword",
			".drw"=>"application/x-drw",
			".dtd"=>"text/xml",
			".dwf"=>"Model/vnd.dwf",
			".dwf"=>"application/x-dwf",
			".dwg"=>"application/x-dwg",
			".dxb"=>"application/x-dxb",
			".dxf"=>"application/x-dxf",
			".edn"=>"application/vnd.adobe.edn",
			".emf"=>"application/x-emf",
			".eml"=>"message/rfc822",
			".ent"=>"text/xml",
			".epi"=>"application/x-epi",
			".eps"=>"application/x-ps",
			".eps"=>"application/postscript",
			".etd"=>"application/x-ebx",
			".exe"=>"application/x-msdownload",
			".fax"=>"image/fax",
			".fdf"=>"application/vnd.fdf",
			".fif"=>"application/fractals",
			".fo"=>"text/xml",
			".frm"=>"application/x-frm",
			".g4"=>"application/x-g4",
			".gbr"=>"application/x-gbr",
			".gcd"=>"application/x-gcd",
			".gif"=>"image/gif",
			".gl2"=>"application/x-gl2",
			".gp4"=>"application/x-gp4",
			".hgl"=>"application/x-hgl",
			".hmr"=>"application/x-hmr",
			".hpg"=>"application/x-hpgl",
			".hpl"=>"application/x-hpl",
			".hqx"=>"application/mac-binhex40",
			".hrf"=>"application/x-hrf",
			".hta"=>"application/hta",
			".htc"=>"text/x-component",
			".htm"=>"text/html",
			".html"=>"text/html",
			".htt"=>"text/webviewhtml",
			".htx"=>"text/html",
			".icb"=>"application/x-icb",
			".ico"=>"image/x-icon",
			".ico"=>"application/x-ico",
			".iff"=>"application/x-iff",
			".ig4"=>"application/x-g4",
			".igs"=>"application/x-igs",
			".iii"=>"application/x-iphone",
			".img"=>"application/x-img",
			".ins"=>"application/x-internet-signup",
			".isp"=>"application/x-internet-signup",
			".IVF"=>"video/x-ivf",
			".java"=>"java/*",
			".jfif"=>"image/jpeg",
			".jpe"=>"image/jpeg",
			".jpe"=>"application/x-jpe",
			".jpeg"=>"image/jpeg",
			".jpg"=>"image/jpeg",
			//".jpg"=>"application/x-jpg",
			".js"=>"application/x-javascript",
			".jsp"=>"text/html",
			".la1"=>"audio/x-liquid-file",
			".lar"=>"application/x-laplayer-reg",
			".latex"=>"application/x-latex",
			".lavs"=>"audio/x-liquid-secure",
			".lbm"=>"application/x-lbm",
			".lmsff"=>"audio/x-la-lms",
			".ls"=>"application/x-javascript",
			".ltr"=>"application/x-ltr",
			".m1v"=>"video/x-mpeg",
			".m2v"=>"video/x-mpeg",
			".m3u"=>"audio/mpegurl",
			".m4e"=>"video/mpeg4",
			".mac"=>"application/x-mac",
			".man"=>"application/x-troff-man",
			".math"=>"text/xml",
			".mdb"=>"application/msaccess",
			".mdb"=>"application/x-mdb",
			".mfp"=>"application/x-shockwave-flash",
			".mht"=>"message/rfc822",
			".mhtml"=>"message/rfc822",
			".mi"=>"application/x-mi",
			".mid"=>"audio/mid",
			".midi"=>"audio/mid",
			".mil"=>"application/x-mil",
			".mml"=>"text/xml",
			".mnd"=>"audio/x-musicnet-download",
			".mns"=>"audio/x-musicnet-stream",
			".mocha"=>"application/x-javascript",
			".movie"=>"video/x-sgi-movie",
			".mp1"=>"audio/mp1",
			".mp2"=>"audio/mp2",
			".mp2v"=>"video/mpeg",
			".mp3"=>"audio/mp3",
			".mp4"=>"video/mp4",
			".mpa"=>"video/x-mpg",
			".mpd"=>"application/-project",
			".mpe"=>"video/x-mpeg",
			".mpeg"=>"video/mpg",
			".mpg"=>"video/mpg",
			".mpga"=>"audio/rn-mpeg",
			".mpp"=>"application/-project",
			".mps"=>"video/x-mpeg",
			".mpt"=>"application/-project",
			".mpv"=>"video/mpg",
			".mpv2"=>"video/mpeg",
			".mpw"=>"application/s-project",
			".mpx"=>"application/-project",
			".mtx"=>"text/xml",
			".mxp"=>"application/x-mmxp",
			".net"=>"image/pnetvue",
			".nrf"=>"application/x-nrf",
			".nws"=>"message/rfc822",
			".odc"=>"text/x-ms-odc",
			".out"=>"application/x-out",
			".p10"=>"application/pkcs10",
			".p12"=>"application/x-pkcs12",
			".p7b"=>"application/x-pkcs7-certificates",
			".p7c"=>"application/pkcs7-mime",
			".p7m"=>"application/pkcs7-mime",
			".p7r"=>"application/x-pkcs7-certreqresp",
			".p7s"=>"application/pkcs7-signature",
			".pc5"=>"application/x-pc5",
			".pci"=>"application/x-pci",
			".pcl"=>"application/x-pcl",
			".pcx"=>"application/x-pcx",
			".pdf"=>"application/pdf",
			".pdf"=>"application/pdf",
			".pdx"=>"application/vnd.adobe.pdx",
			".pfx"=>"application/x-pkcs12",
			".pgl"=>"application/x-pgl",
			".pic"=>"application/x-pic",
			".pko"=>"application-pki.pko",
			".pl"=>"application/x-perl",
			".plg"=>"text/html",
			".pls"=>"audio/scpls",
			".plt"=>"application/x-plt",
			".png"=>"image/png",
			".png"=>"application/x-png",
			".pot"=>"applications-powerpoint",
			".ppa"=>"application/vs-powerpoint",
			".ppm"=>"application/x-ppm",
			".pps"=>"application-powerpoint",
			".ppt"=>"applications-powerpoint",
			".ppt"=>"application/x-ppt",
			".pr"=>"application/x-pr",
			".prf"=>"application/pics-rules",
			".prn"=>"application/x-prn",
			".prt"=>"application/x-prt",
			".ps"=>"application/x-ps",
			".ps"=>"application/postscript",
			".ptn"=>"application/x-ptn",
			".pwz"=>"application/powerpoint",
			".r3t"=>"text/vnd.rn-realtext3d",
			".ra"=>"audio/vnd.rn-realaudio",
			".ram"=>"audio/x-pn-realaudio",
			".ras"=>"application/x-ras",
			".rat"=>"application/rat-file",
			".rdf"=>"text/xml",
			".rec"=>"application/vnd.rn-recording",
			".red"=>"application/x-red",
			".rgb"=>"application/x-rgb",
			".rjs"=>"application/vnd.rn-realsystem-rjs",
			".rjt"=>"application/vnd.rn-realsystem-rjt",
			".rlc"=>"application/x-rlc",
			".rle"=>"application/x-rle",
			".rm"=>"application/vnd.rn-realmedia",
			".rmf"=>"application/vnd.adobe.rmf",
			".rmi"=>"audio/mid",
			".rmj"=>"application/vnd.rn-realsystem-rmj",
			".rmm"=>"audio/x-pn-realaudio",
			".rmp"=>"application/vnd.rn-rn_music_package",
			".rms"=>"application/vnd.rn-realmedia-secure",
			".rmvb"=>"application/vnd.rn-realmedia-vbr",
			".rmx"=>"application/vnd.rn-realsystem-rmx",
			".rnx"=>"application/vnd.rn-realplayer",
			".rp"=>"image/vnd.rn-realpix",
			".rpm"=>"audio/x-pn-realaudio-plugin",
			".rsml"=>"application/vnd.rn-rsml",
			".rt"=>"text/vnd.rn-realtext",
			".rtf"=>"application/msword",
			".rtf"=>"application/x-rtf",
			".rv"=>"video/vnd.rn-realvideo",
			".sam"=>"application/x-sam",
			".sat"=>"application/x-sat",
			".sdp"=>"application/sdp",
			".sdw"=>"application/x-sdw",
			".sit"=>"application/x-stuffit",
			".slb"=>"application/x-slb",
			".sld"=>"application/x-sld",
			".slk"=>"drawing/x-slk",
			".smi"=>"application/smil",
			".smil"=>"application/smil",
			".smk"=>"application/x-smk",
			".snd"=>"audio/basic",
			".sol"=>"text/plain",
			".sor"=>"text/plain",
			".spc"=>"application/x-pkcs7-certificates",
			".spl"=>"application/futuresplash",
			".spp"=>"text/xml",
			".ssm"=>"application/streamingmedia",
			".sst"=>"application-pki.certstore",
			".stl"=>"application/-pki.stl",
			".stm"=>"text/html",
			".sty"=>"application/x-sty",
			".svg"=>"text/xml",
			".swf"=>"application/x-shockwave-flash",
			".tdf"=>"application/x-tdf",
			".tg4"=>"application/x-tg4",
			".tga"=>"application/x-tga",
			".tif"=>"image/tiff",
			".tif"=>"application/x-tif",
			".tiff"=>"image/tiff",
			".tld"=>"text/xml",
			".top"=>"drawing/x-top",
			".torrent"=>"application/x-bittorrent",
			".tsd"=>"text/xml",
			".txt"=>"text/plain",
			".uin"=>"application/x-icq",
			".uls"=>"text/iuls",
			".vcf"=>"text/x-vcard",
			".vda"=>"application/x-vda",
			".vdx"=>"application/vnd.visio",
			".vml"=>"text/xml",
			".vpg"=>"application/x-vpeg005",
			".vsd"=>"application/vnd.visio",
			".vsd"=>"application/x-vsd",
			".vss"=>"application/vnd.visio",
			".vst"=>"application/vnd.visio",
			".vst"=>"application/x-vst",
			".vsw"=>"application/vnd.visio",
			".vsx"=>"application/vnd.visio",
			".vtx"=>"application/vnd.visio",
			".vxml"=>"text/xml",
			".wav"=>"audio/wav",
			".wax"=>"audio/x-ms-wax",
			".wb1"=>"application/x-wb1",
			".wb2"=>"application/x-wb2",
			".wb3"=>"application/x-wb3",
			".wbmp"=>"image/vnd.wap.wbmp",
			".wiz"=>"application/msword",
			".wk3"=>"application/x-wk3",
			".wk4"=>"application/x-wk4",
			".wkq"=>"application/x-wkq",
			".wks"=>"application/x-wks",
			".wm"=>"video/x-ms-wm",
			".wma"=>"audio/x-ms-wma",
			".wmd"=>"application/x-ms-wmd",
			".wmf"=>"application/x-wmf",
			".wml"=>"text/vnd.wap.wml",
			".wmv"=>"video/x-ms-wmv",
			".wmx"=>"video/x-ms-wmx",
			".wmz"=>"application/x-ms-wmz",
			".wp6"=>"application/x-wp6",
			".wpd"=>"application/x-wpd",
			".wpg"=>"application/x-wpg",
			".wpl"=>"application/-wpl",
			".wq1"=>"application/x-wq1",
			".wr1"=>"application/x-wr1",
			".wri"=>"application/x-wri",
			".wrk"=>"application/x-wrk",
			".ws"=>"application/x-ws",
			".ws2"=>"application/x-ws",
			".wsc"=>"text/scriptlet",
			".wsdl"=>"text/xml",
			".wvx"=>"video/x-ms-wvx",
			".xdp"=>"application/vnd.adobe.xdp",
			".xdr"=>"text/xml",
			".xfd"=>"application/vnd.adobe.xfd",
			".xfdf"=>"application/vnd.adobe.xfdf",
			".xhtml"=>"text/html",
			".xls"=>"application/-excel",
			".xls"=>"application/x-xls",
			".xlw"=>"application/x-xlw",
			".xml"=>"text/xml",
			".xpl"=>"audio/scpls",
			".xq"=>"text/xml",
			".xql"=>"text/xml",
			".xquery"=>"text/xml",
			".xsd"=>"text/xml",
			".xsl"=>"text/xml",
			".xslt"=>"text/xml",
			".xwd"=>"application/x-xwd",
			".x_b"=>"application/x-x_b",
			".x_t"=>"application/x-x_t" );
		return $b[$ext];
	}
}
