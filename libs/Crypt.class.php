<?php
class AMPCrypt {
    private static function getKey(){
        return md5('LJBUAA');
    }
	
	//hash md5 加密
    public static function Hashmd5($value){
		return md5($value);
	}
	
	//base64_encode
	public static function b64Encode($value){
		return base64_encode($value);
	} 
	//base64_decode
	public static function b64Decode($value){
		return base64_decode($value);
	}

	//3des加密有长度限制，需注意
	//3des加密
    public static function encrypt3des($value){
         $td = mcrypt_module_open('tripledes', '', 'ecb', '');
         $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);
         $key = substr(self::getKey(), 0, mcrypt_enc_get_key_size($td));
         mcrypt_generic_init($td, $key, $iv);
         $ret = base64_encode(mcrypt_generic($td, $value));
         mcrypt_generic_deinit($td);
         mcrypt_module_close($td);
        return $ret;
    }
	//3des 解密 
    public static function dencrypt($value){
         $td = mcrypt_module_open('tripledes', '', 'ecb', '');
         $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);
         $key = substr(self::getKey(), 0, mcrypt_enc_get_key_size($td));
         $key = substr(self::getKey(), 0, mcrypt_enc_get_key_size($td));
         mcrypt_generic_init($td, $key, $iv);
         $ret = trim(mdecrypt_generic($td, base64_decode($value))) ;
         mcrypt_generic_deinit($td);
         mcrypt_module_close($td);
        return $ret;
	}
}