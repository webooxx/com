<?php
class StringEncrypt{
	function encrypt($key, $text) {
		$text = trim($text);
		$iv = substr(md5($key), 0,mcrypt_get_iv_size (MCRYPT_CAST_256,MCRYPT_MODE_CFB));
		$code = mcrypt_cfb (MCRYPT_CAST_256, $key, $text, MCRYPT_ENCRYPT, $iv);
		return trim(chop(base64_encode($code)));
	}

	function decrypt($key, $code) {
		$code =  trim(chop(base64_decode($code)));
		$iv = substr(md5($key), 0,mcrypt_get_iv_size (MCRYPT_CAST_256,MCRYPT_MODE_CFB));
		$p_t = mcrypt_cfb (MCRYPT_CAST_256, $key, $code, MCRYPT_DECRYPT, $iv);
		return trim(chop($p_t));
	}
}
/*
$encryted_str=encrypt("php","phpStudy");
echo "加密后的字符串为:$encryted_str<br>";

$decrypt_str=decrypt("php",$encryted_str);
echo "解密后的字符串为:$decrypt_str<br>";
*/

?>