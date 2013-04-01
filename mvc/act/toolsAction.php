<?php
class toolsAction extends Action{
	
	#   拒绝客户端访问模式
	function __construct(){C('SYS_VERIFY_FUNC','rbac:reject');}

    #	普通搜索,支持正则匹配路径
    function scan($path , $match = null){
      $dirs = scandir( $path );
      foreach($dirs as $d) {
            if ($d == '.' || $d == '..') {}
            else{
                $real = $path.'/'.$d;
                if(is_null($match)){  $result[]=$real;}else{ preg_match( $match , $real)==1 && $result[]=$real;}
            }
        }
        return $result;
    }
	
    #	遍历搜索,支持正则匹配
    function scand( $path , $match = null , $result = array()) {
        $dirs = scandir( $path );
        foreach($dirs as $d) {
            if ($d == '.' || $d == '..') {}
            else{
                $real = $path.'/'.$d;
                if(is_null($match)){  $result[]=$real;}else{ preg_match( $match , $real)==1 && $result[]=$real;}
                if(is_dir($real))  {  $result = $result + $this -> scand( $real,$match, $result ); }
            }
        }
        return $result;
    }

    #	遍历删除
    function deldir($dir) {
        $dh=@opendir($dir) ;
        while ($file=@readdir($dh)) {
            if($file!="." && $file!="..") {
                $fullpath=$dir."/".$file;
                if(!is_dir($fullpath)) { unlink($fullpath); } else { $this->deldir($fullpath);}
            }
        }
        @closedir($dh);
        return @rmdir($dir);
    }
    
	#	遍历拷贝目录
    function copyd($fromdir,$todir){
		if (!file_exists($fromdir)){ return false;  }
		if (!eregi('/$',$fromdir)) { $fromdir=$fromdir.'/'; }
		if (!eregi('/$',$todir))   { $todir=$todir.'/';  }
		if (!file_exists($todir))  { @mkdir($todir); }
		$handle=@opendir($fromdir);
		while (($filename = @readdir($handle))!== false){
			if (@filetype($fromdir.$filename)=='dir') {
				if($subnum<32 and $filename!='.' and $filename!='..') { $this->copyd($fromdir.$filename.'/',$todir.$filename.'/',$subnum); }
			}else{
				@copy($fromdir.$filename,$todir.$filename);
				$mtime=@filemtime($fromdir.$filename);
				@touch($todir.$filename,$mtime);
			}
		}
		@closedir($handle);
		return true;
	}
    
	#	curl请求
    function curl($url , $post = false){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if( $post !== false ){curl_setopt($ch, CURLOPT_POST, 1);curl_setopt($ch, CURLOPT_POSTFIELDS, $post) ;}
		$back = curl_exec($ch);
		curl_close($ch);
		return $back;
    }

	#	参数过滤,若input中没有对应的key,则输出的key为null,默认的 $input 为 $_REQUEST
	#	表达式参考: 数值转换 id:int, SQL字符过滤 title:sql, 字符反转(支持中文) code:strrev , 换行与html换行 html:nl2br , html:br2nl
	function args( $exp , $input = false ){
		$input = $input ? $input : $_REQUEST;
		$blocks = explode(',', $exp );
		$output = array();
		foreach( $blocks as $block ){
			$block = explode(':',$block);
			$value = isset ( $input[$block[0]] ) ? $input[$block[0]] : NULL ;
			switch($block[1]){
			
				case 'int' :
					$value = (int)$value;
				break;
				case 'str' :
					$value = (string)$value;
				break;
				
				case 'addslashes' :
				case 'sql' :
					$value = addslashes($value);
				break;
				case 'html' :
					$value = htmlspecialchars($value);
				break;
							
				case 'strrev' :
					$value = $this->str_rev_gb($value);
				break;
				
				case 'nl2br' :
					$value = nl2br($value);
					$value = str_replace( array("\n","\r"), '', $value);
				break;
				
				case 'br2nl' :
					$value = str_replace( "<br\s*/{0,1}>", "\r\n", $value);
				break;
				
				default:
					$value = $value;
				break;
			}
			$output[$block[0]] = $value;
		}
		return $output;
	}

	function filter( $filter, $data){ return $this->args($filter,$data); }

	#	反转字符UTF8
	function str_rev_gb($str){
		if(!is_string($str)||!mb_check_encoding($str,'UTF-8')){ exit("输入类型不是UTF8类型的字符串"); }
		$array=array();
		$l=mb_strlen($str,'UTF-8');
		for($i=0;$i<$l;$i++){ $array[]=mb_substr($str,$i,1,'UTF-8');}
		krsort($array);
		return implode($array);
	}
	
	#    字符串中文截取
	function str_sub_gb($sourcestr,$cutlength){
       $returnstr=''; 
       $i=0; 
       $n=0; 
       $str_length=strlen($sourcestr); 
        while (($n<$cutlength) and ($i<=$str_length)){ 
            $temp_str=substr($sourcestr,$i,1); 
            $ascnum=Ord($temp_str); 
            if ($ascnum>=224){ 
                $returnstr=$returnstr.substr($sourcestr,$i,3);         
                $i=$i+3;
                $n++;
            }elseif ($ascnum>=192){ 
                $returnstr=$returnstr.substr($sourcestr,$i,2);
                $i=$i+2;
                $n++;
            }elseif ($ascnum>=65 && $ascnum<=90){ 
                $returnstr=$returnstr.substr($sourcestr,$i,1); 
                $i=$i+1;
                $n++;
            }else{
            $returnstr=$returnstr.substr($sourcestr,$i,1); 
                $i=$i+1;
                $n=$n+0.5;
            } 
        }
        if ($str_length>$cutlength){ $returnstr = $returnstr . "..."; }
        return $returnstr;
    }
	
	
	#	中转ajax的跨域请求
	function proxy(){
		$args = A('tools')->args( 'url',$_POST );
		echo file_get_contents( $post['url'] );
	}

	#	根据后缀名返回ContentType
	function cType( $ext ){	
		$lib = array("*"=>"application/octet-stream","001"=>"application/x-001","301"=>"application/x-301","323"=>"text/h323","906"=>"application/x-906","907"=>"drawing/907","a11"=>"application/x-a11","acp"=>"audio/x-mei-aac","ai"=>"application/postscript","aif"=>"audio/aiff","aifc"=>"audio/aiff","aiff"=>"audio/aiff","anv"=>"application/x-anv","asa"=>"text/asa","asf"=>"video/x-ms-asf","asp"=>"text/asp","asx"=>"video/x-ms-asf","au"=>"audio/basic","avi"=>"video/avi","awf"=>"application/vnd.adobe.workflow","biz"=>"text/xml","bmp"=>"application/x-bmp","bot"=>"application/x-bot","c4t"=>"application/x-c4t","c90"=>"application/x-c90","cal"=>"application/x-cals","cat"=>"application/s-pki.seccat","cdf"=>"application/x-netcdf","cdr"=>"application/x-cdr","cel"=>"application/x-cel","cer"=>"application/x-x509-ca-cert","cg4"=>"application/x-g4","cgm"=>"application/x-cgm","cit"=>"application/x-cit","class"=>"java/*","cml"=>"text/xml","cmp"=>"application/x-cmp","cmx"=>"application/x-cmx","cot"=>"application/x-cot","crl"=>"application/pkix-crl","crt"=>"application/x-x509-ca-cert","csi"=>"application/x-csi","css"=>"text/css","cut"=>"application/x-cut","dbf"=>"application/x-dbf","dbm"=>"application/x-dbm","dbx"=>"application/x-dbx","dcd"=>"text/xml","dcx"=>"application/x-dcx","der"=>"application/x-x509-ca-cert","dgn"=>"application/x-dgn","dib"=>"application/x-dib","dll"=>"application/x-msdownload","doc"=>"application/msword","dot"=>"application/msword","drw"=>"application/x-drw","dtd"=>"text/xml","dwf"=>"Model/vnd.dwf","dwf"=>"application/x-dwf","dwg"=>"application/x-dwg","dxb"=>"application/x-dxb","dxf"=>"application/x-dxf","edn"=>"application/vnd.adobe.edn","emf"=>"application/x-emf","eml"=>"message/rfc822","ent"=>"text/xml","epi"=>"application/x-epi","eps"=>"application/x-ps","eps"=>"application/postscript","etd"=>"application/x-ebx","exe"=>"application/x-msdownload","fax"=>"image/fax","fdf"=>"application/vnd.fdf","fif"=>"application/fractals","fo"=>"text/xml","frm"=>"application/x-frm","g4"=>"application/x-g4","gbr"=>"application/x-gbr","gcd"=>"application/x-gcd","gif"=>"image/gif","gl2"=>"application/x-gl2","gp4"=>"application/x-gp4","hgl"=>"application/x-hgl","hmr"=>"application/x-hmr","hpg"=>"application/x-hpgl","hpl"=>"application/x-hpl","hqx"=>"application/mac-binhex40","hrf"=>"application/x-hrf","hta"=>"application/hta","htc"=>"text/x-component","htm"=>"text/html","html"=>"text/html","htt"=>"text/webviewhtml","htx"=>"text/html","icb"=>"application/x-icb","ico"=>"image/x-icon","ico"=>"application/x-ico","iff"=>"application/x-iff","ig4"=>"application/x-g4","igs"=>"application/x-igs","iii"=>"application/x-iphone","img"=>"application/x-img","ins"=>"application/x-internet-signup","isp"=>"application/x-internet-signup","IVF"=>"video/x-ivf","java"=>"java/*","jfif"=>"image/jpeg","jpe"=>"image/jpeg","jpe"=>"application/x-jpe","jpeg"=>"image/jpeg","jpg"=>"image/jpeg","js"=>"application/x-javascript","jsp"=>"text/html","la1"=>"audio/x-liquid-file","lar"=>"application/x-laplayer-reg","latex"=>"application/x-latex","lavs"=>"audio/x-liquid-secure","lbm"=>"application/x-lbm","lmsff"=>"audio/x-la-lms","ls"=>"application/x-javascript","ltr"=>"application/x-ltr","m1v"=>"video/x-mpeg","m2v"=>"video/x-mpeg","m3u"=>"audio/mpegurl","m4e"=>"video/mpeg4","mac"=>"application/x-mac","man"=>"application/x-troff-man","math"=>"text/xml","mdb"=>"application/msaccess","mdb"=>"application/x-mdb","mfp"=>"application/x-shockwave-flash","mht"=>"message/rfc822","mhtml"=>"message/rfc822","mi"=>"application/x-mi","mid"=>"audio/mid","midi"=>"audio/mid","mil"=>"application/x-mil","mml"=>"text/xml","mnd"=>"audio/x-musicnet-download","mns"=>"audio/x-musicnet-stream","mocha"=>"application/x-javascript","movie"=>"video/x-sgi-movie","mp1"=>"audio/mp1","mp2"=>"audio/mp2","mp2v"=>"video/mpeg","mp3"=>"audio/mp3","mp4"=>"video/mp4","mpa"=>"video/x-mpg","mpd"=>"application/-project","mpe"=>"video/x-mpeg","mpeg"=>"video/mpg","mpg"=>"video/mpg","mpga"=>"audio/rn-mpeg","mpp"=>"application/-project","mps"=>"video/x-mpeg","mpt"=>"application/-project","mpv"=>"video/mpg","mpv2"=>"video/mpeg","mpw"=>"application/s-project","mpx"=>"application/-project","mtx"=>"text/xml","mxp"=>"application/x-mmxp","net"=>"image/pnetvue","nrf"=>"application/x-nrf","nws"=>"message/rfc822","odc"=>"text/x-ms-odc","out"=>"application/x-out","p10"=>"application/pkcs10","p12"=>"application/x-pkcs12","p7b"=>"application/x-pkcs7-certificates","p7c"=>"application/pkcs7-mime","p7m"=>"application/pkcs7-mime","p7r"=>"application/x-pkcs7-certreqresp","p7s"=>"application/pkcs7-signature","pc5"=>"application/x-pc5","pci"=>"application/x-pci","pcl"=>"application/x-pcl","pcx"=>"application/x-pcx","pdf"=>"application/pdf","pdf"=>"application/pdf","pdx"=>"application/vnd.adobe.pdx","pfx"=>"application/x-pkcs12","pgl"=>"application/x-pgl","pic"=>"application/x-pic","pko"=>"application-pki.pko","pl"=>"application/x-perl","plg"=>"text/html","pls"=>"audio/scpls","plt"=>"application/x-plt","png"=>"image/png","pot"=>"applications-powerpoint","ppa"=>"application/vs-powerpoint","ppm"=>"application/x-ppm","pps"=>"application-powerpoint","ppt"=>"applications-powerpoint","ppt"=>"application/x-ppt","pr"=>"application/x-pr","prf"=>"application/pics-rules","prn"=>"application/x-prn","prt"=>"application/x-prt","ps"=>"application/x-ps","ps"=>"application/postscript","ptn"=>"application/x-ptn","pwz"=>"application/powerpoint","r3t"=>"text/vnd.rn-realtext3d","ra"=>"audio/vnd.rn-realaudio","ram"=>"audio/x-pn-realaudio","ras"=>"application/x-ras","rat"=>"application/rat-file","rdf"=>"text/xml","rec"=>"application/vnd.rn-recording","red"=>"application/x-red","rgb"=>"application/x-rgb","rjs"=>"application/vnd.rn-realsystem-rjs","rjt"=>"application/vnd.rn-realsystem-rjt","rlc"=>"application/x-rlc","rle"=>"application/x-rle","rm"=>"application/vnd.rn-realmedia","rmf"=>"application/vnd.adobe.rmf","rmi"=>"audio/mid","rmj"=>"application/vnd.rn-realsystem-rmj","rmm"=>"audio/x-pn-realaudio","rmp"=>"application/vnd.rn-rn_music_package","rms"=>"application/vnd.rn-realmedia-secure","rmvb"=>"application/vnd.rn-realmedia-vbr","rmx"=>"application/vnd.rn-realsystem-rmx","rnx"=>"application/vnd.rn-realplayer","rp"=>"image/vnd.rn-realpix","rpm"=>"audio/x-pn-realaudio-plugin","rsml"=>"application/vnd.rn-rsml","rt"=>"text/vnd.rn-realtext","rtf"=>"application/msword","rtf"=>"application/x-rtf","rv"=>"video/vnd.rn-realvideo","sam"=>"application/x-sam","sat"=>"application/x-sat","sdp"=>"application/sdp","sdw"=>"application/x-sdw","sit"=>"application/x-stuffit","slb"=>"application/x-slb","sld"=>"application/x-sld","slk"=>"drawing/x-slk","smi"=>"application/smil","smil"=>"application/smil","smk"=>"application/x-smk","snd"=>"audio/basic","sol"=>"text/plain","sor"=>"text/plain","spc"=>"application/x-pkcs7-certificates","spl"=>"application/futuresplash","spp"=>"text/xml","ssm"=>"application/streamingmedia","sst"=>"application-pki.certstore","stl"=>"application/-pki.stl","stm"=>"text/html","sty"=>"application/x-sty","svg"=>"text/xml","swf"=>"application/x-shockwave-flash","tdf"=>"application/x-tdf","tg4"=>"application/x-tg4","tga"=>"application/x-tga","tif"=>"image/tiff","tif"=>"application/x-tif","tiff"=>"image/tiff","tld"=>"text/xml","top"=>"drawing/x-top","torrent"=>"application/x-bittorrent","tsd"=>"text/xml","txt"=>"text/plain","uin"=>"application/x-icq","uls"=>"text/iuls","vcf"=>"text/x-vcard","vda"=>"application/x-vda","vdx"=>"application/vnd.visio","vml"=>"text/xml","vpg"=>"application/x-vpeg005","vsd"=>"application/vnd.visio","vsd"=>"application/x-vsd","vss"=>"application/vnd.visio","vst"=>"application/vnd.visio","vst"=>"application/x-vst","vsw"=>"application/vnd.visio","vsx"=>"application/vnd.visio","vtx"=>"application/vnd.visio","vxml"=>"text/xml","wav"=>"audio/wav","wax"=>"audio/x-ms-wax","wb1"=>"application/x-wb1","wb2"=>"application/x-wb2","wb3"=>"application/x-wb3","wbmp"=>"image/vnd.wap.wbmp","wiz"=>"application/msword","wk3"=>"application/x-wk3","wk4"=>"application/x-wk4","wkq"=>"application/x-wkq","wks"=>"application/x-wks","wm"=>"video/x-ms-wm","wma"=>"audio/x-ms-wma","wmd"=>"application/x-ms-wmd","wmf"=>"application/x-wmf","wml"=>"text/vnd.wap.wml","wmv"=>"video/x-ms-wmv","wmx"=>"video/x-ms-wmx","wmz"=>"application/x-ms-wmz","wp6"=>"application/x-wp6","wpd"=>"application/x-wpd","wpg"=>"application/x-wpg","wpl"=>"application/-wpl","wq1"=>"application/x-wq1","wr1"=>"application/x-wr1","wri"=>"application/x-wri","wrk"=>"application/x-wrk","ws"=>"application/x-ws","ws2"=>"application/x-ws","wsc"=>"text/scriptlet","wsdl"=>"text/xml","wvx"=>"video/x-ms-wvx","xdp"=>"application/vnd.adobe.xdp","xdr"=>"text/xml","xfd"=>"application/vnd.adobe.xfd","xfdf"=>"application/vnd.adobe.xfdf","xhtml"=>"text/html","xls"=>"application/-excel","xls"=>"application/x-xls","xlw"=>"application/x-xlw","xml"=>"text/xml","xpl"=>"audio/scpls","xq"=>"text/xml","xql"=>"text/xml","xquery"=>"text/xml","xsd"=>"text/xml","xsl"=>"text/xml","xslt"=>"text/xml","xwd"=>"application/x-xwd","x_b"=>"application/x-x_b","x_t"=>"application/x-x_t");
		return $lib[$ext];
	}
	 
}
