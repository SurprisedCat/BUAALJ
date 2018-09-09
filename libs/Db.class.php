<?php

class DbLj {
	
	private $conn;
	private $res;
	function __construct() {
		// 创建连接
		$dbServerName = '127.0.0.1';
		$dbUserName = "root";
		$dbPassword = "root";	
		$dbName = "buaalj";
		$this->conn = new mysqli($dbServerName, $dbUserName, $dbPassword,$dbName); 
		// 检测连接
		if ($this->conn->connect_error) {
			die("连接失败: " . $this->conn->connect_error);
		}
		// 通过对象方式设置字符编码
		$this->conn->set_charset('utf8');
	}
	
	public function query($sql) {
		return $this->conn->query($sql);
	}
	
	public function selectOneRow($sql) {
		$row = array();
		try{
			$tempRes = $this->conn->query($sql);
			$row = $tempRes->fetch_assoc();
		}
		catch (Exception $e){
			echo $e->getMessage();
			die();
		}
		finally {
			$tempRes->free();
		}	
		return $row;
	}

	//一次性更新大量数据，通过关联数组更新
	public function multipleUpdate($assocArray,$tableName,$id){
		$res = true;
		
		foreach($assocArray as $key => $value){
			//如果没有成功则终止更新，返回false
			if(!$this->query("update ".$tableName." set ".$key." ='".$value."' where id='".$id."';")){
				break;
			}
		}
		return $res;
	}

	function __destruct() {
		$this->conn->close();
	}
	
}
 


?>