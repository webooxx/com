<?php
class indexAction extends Action{

    function __construct(){
		C('DOC_EXT','txt');
	}

	function test(){
		// dump($_GET);
		// dump($this);
		// dump( C('PATH_NOW') );
		// dump( file_get_contents( C('PATH_NOW').'/app.conf' ));
		// dump( file_get_contents( C('PATH_NOW').'/sys/act/indexAction.php' ));
		
		$realRoot = J( C('PATH_NOW') ,C('DIR_DOC'));	#	服务器文档根目录
		$news = $this->newsFile($realRoot , strlen( $realRoot ) );
		json($news);
	}

	function index(){
	
		$intoPath = $_GET['p'] ? $_GET['p']  : '/';		#	传入的参数
		
		$realRoot = J( C('PATH_NOW') ,C('DIR_DOC'));	#	服务器文档根目录
		$realPath = J( $realRoot , $intoPath);		#	服务器文档实际路径

		#	如果文件不存在
		if( !file_exists($realPath) ){ return $this->display( '404.html'); }
		
		$pathInfo = pathinfo($realPath);
		
		if( !is_dir( $realPath ) ){
			$ext = $pathInfo['extension'];
			$con = file_get_contents($realPath);
			#	如果不是MarkDown文件
			if( $ext != C('DOC_EXT') ){
				header("content-type:".$this->ext2type('.'.$ext).";charset=utf-8");
				die( $con );
			}

			
			#	处理Tags
			$con = preg_replace( '/(?:^|\n)@(\w+)\s([^\n]+)/',"<span class=\"tags $1\">$1:$2</span>",$con);
			
			#	如果文档不是以H1开头的则自动插入文件名
			
			if( !preg_match( '/^\#{1}/',$con) ){
				$con  = '#'.$pathInfo['filename']."\n".$con;
			}
			
			I('Markdown');
			$content = Markdown( $con );
			
			$tplName    = 'doc.html';
			$currentDir = dirname( $intoPath );
			$this->assign( 'content' , $content );
			
		}else{
			$tplName    = 'dir.html';
			$currentDir = $intoPath;
		}

		$tree = $this->tree($realRoot , strlen( $realRoot ) ,'/^[^_\.].*/');
		$file = $this->file($realPath , strlen( $realRoot ) ,'/^[^_\.].*/');
		
		$news = $this->newsFile($realRoot , strlen( $realRoot ) );
		
		#	模板参数
		
		$this->assign( 'realRoot' ,$realRoot );
		$this->assign( 'tree' ,$tree );
		$this->assign( 'file' ,$file );
		$this->assign( 'news' ,$news );
		$this->assign( 'path' , $intoPath == '/' ? array() : (array)explode('/',  substr( $realPath,strlen($realRoot)+1  )) );
		$this->assign( 'cdir' , $currentDir);

		$this->assign( 'title' , "webooxx's markwiki online - ".substr($realPath,strlen($realRoot) ) );
		$this->assign( 'description' ,"webooxx's markwiki online, markdown files , normal documents." );
		$this->assign( 'author' ,"webooxx@gmail.com" );
	
		$this->display($tplName);
	}
	
	#	取得最近更新的文件
	function newsFile( $realPath , $cutLen = 0 ){

		$scan = $this->scand($realPath,'/\.('.C('DOC_EXT').'|html|htm)$/');
		foreach( $scan as $item ){
			$files[$item] = filemtime($item);
		}

		arsort($files);
		
		
		foreach( $files as $item => $k ){
		
			$file['time'] = date('m-d',$k);
			$file['path'] = substr( $item , $cutLen ) ;
			$file['name'] = array_pop( explode('/', $file['path'] )  );
			$file['name'] = array_shift( explode('.', $file['name'] )  );
			$file['info'] = 'Document '.$file['name'].' update at '. date('Y-m-d H:i:s',$k);;
			$result[] = $file;
		}
		return  array_slice($result, 0, 5); 
	}
	
	#	取得当前目录下的文件列表
	function file( $realPath , $cutLen = 0 ,$match = Null ){
	
		$dirs = array();
		$news = array();
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
			$file['info'] = array();
			
			#	获得目录下成员数
			if( is_dir( $item ) ){
				$_scan = $this->scan( $item , $match );
				$file['info'][] = (int)count( $_scan ).' items';
				$dirs[] = $file; 
			}else{
				#	将文件分类
				
				
				
				if( preg_match( '/\.'.C('DOC_EXT').'$/' ,$file['name'] ) ){
					$file['name'] = array_shift( explode('.', $file['name'] )  );
					
					$file['info'][] = 'time: '.$this->ftime( filemtime($item) );
					$file['info'][] = 'tags: '.$this->gettags($item);
					
					$docs[] = $file;
				}else if( preg_match( '/\.(jpg|jpeg|gif|png|bpm)/i' ,$file['name'] ) ){
				
					$file['info'][] = $file['name'];
					$file['info'][] = 'size: '.$this->fbytes(filesize($item));
					
					$imgs[] = $file;
				}else{
					$file['info'][] = 'size: '.$this->fbytes(filesize($item));
					$other[] = $file;
				}
				
			}
		}
		return array('dirs'=>$dirs ,'docs'=>$docs ,'imgs'=>$imgs ,'other'=>$other );
	}
	
	#	取得目录树	
	function tree( $path ,  $cutLen = 0 , $regexp = '/.*/' ,  $flat = 0 , $result = array() ,  $level = 0 ){
		$level = $level+1;
		$dirs  = scandir( $path );
		foreach($dirs as $d) {
			$realpath = J( $path ,$d );
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
	
	
	#	唯一ID
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
                $real = J($path,$d);
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
	
	#	取得文档的tag
	function gettags($path = null){
		return '-';
	}
	
	#	美化时间
	function ftime($time){
		$now = time();
		$sub = $now - $time;
		
		if($sub<60){return 'Just now';}
		
		$sub = floor($sub/60);					#	分钟
		if($sub<60){return $sub.' min ago';}
		
		$sub = floor($sub/60);					#	小时
		if($sub<24){return $sub.' hour ago';}
		
		$sub = floor($sub/24);					#	天
		if($sub<30){return $sub.' day ago';}
		
		$sub = floor($sub/30);					#	月
		if($sub<12){return $sub.' month ago';}
		
		$sub = floor($sub/12);					#	年
		return $sub.' year ago';
		
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
			".php"=>"text/txt",
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
			//".png"=>"application/x-png",
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
	
	#	sinaapp 专用缓存处理
	function mmc(){
		$key=$_GET['key'];
		$mmc=memcache_init();
		echo memcache_get($mmc,$key);
	}
	
	function fbytes($a_bytes) {
		if ($a_bytes < 1024) {
			return $a_bytes .' B';
		} elseif ($a_bytes < 1048576) {
			return round($a_bytes / 1024, 2) .'KB';
		} elseif ($a_bytes < 1073741824) {
			return round($a_bytes / 1048576, 2) . 'MB';
		} elseif ($a_bytes < 1099511627776) {
			return round($a_bytes / 1073741824, 2) . 'GB';
		} elseif ($a_bytes < 1125899906842624) {
			return round($a_bytes / 1099511627776, 2) .'TB';
		} elseif ($a_bytes < 1152921504606846976) {
			return round($a_bytes / 1125899906842624, 2) .'PB';
		} elseif ($a_bytes < 1180591620717411303424) {
			return round($a_bytes / 1152921504606846976, 2) .'EB';
		} elseif ($a_bytes < 1208925819614629174706176) {
			return round($a_bytes / 1180591620717411303424, 2) .'ZB';
		} else {
			return round($a_bytes / 1208925819614629174706176, 2) .'YB';
		}
	}
}
