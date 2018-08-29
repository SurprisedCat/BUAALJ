<?php
class PregProcess{
	static $ipLj='/((1[0-9][0-9]\.)|(2[0-4][0-9]\.)|(25[0-5]\.)|([1-9][0-9]\.)|([0-9]\.)){3}((1[0-9][0-9])|(2[0-4][0-9])|(25[0-5])|([1-9][0-9])|([0-9]))/';
	static $dateLj='/\d{4}-\d{1,2}-\d{1,2}/';
	
	public static function ipPreg($para) {
		preg_match(self::$ipLj,$para,$results);
		return $results[0];
	}
	
	public static function datePreg($para){
		preg_match(self::$dateLj,$para,$results);
		return $results[0];
	}
}
