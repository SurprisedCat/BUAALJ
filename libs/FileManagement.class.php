<?php

class FileLJ {
	
	private $targetFile;
	public $name;
	public $info;//从FileManagement类中获取文件名信息
	function __construct($fileName,$isEncode=false){
		if(!$isEncode) 
			$target = $fileName;
		else
			$target = AMPCrypt::b64Decode($fileName);
		
		$this->name = $target;
		//解析文件名找到文件路径
		$fileinfo = new FileManagement();
		//从FileManagement类中获取文件名信息
		$this->info = $fileinfo->fileNameDecode($target);
		$type = $this->info['category'];
		switch ($type) {
			case "OS":
				$targetDir = "os";
				break;
			case "DB":
				$targetDir = "database";
				break;
			case "WL":
				$targetDir = "router";
				break;
			case "MI":
				$targetDir = "middle";
				break;
			default:
				$targetDir = "";
		}
		$this->targetFile = sprintf('./resources/confs/%s/%s',
				$targetDir,$target);
	}
	
	static public function getCreateTime($filepath){
		return date("F d Y H:i:s.",filectime($filepath));
	}
	static public function getUpdateTime($filepath){
		return date("F d Y H:i:s.",filemtime($filepath));
	}
	
	public function getContents() {
		return file_get_contents($this->targetFile);
	}
}

class DirectoryLJ{ //继承了php 的 directory类
	private $directory;
	private $total;
	private $path;
	
	public $contents;
	public $fileCreateTime;
	public $fileUpdateTime;  
	
	function __construct($path) {
		try{
			$this->total = 0;
			$this->contents=array();
			$this->path = $path;
			if(false !== $this->directory = dir($path)){
				while(false !==($temp = $this->directory->read())){
					if($temp !== '.' && $temp !== '..'){
						$this->contents[] = $temp;
						$this->total++;
					}
					
				}
			}
			else
				throw new Exception('目录打开错误');
		}
		catch (Exception $e){
			echo 'Caught exception: '.$e->getMessage()."\n";
		}
	}		
	
	public function fileCreateTime(){
		$this->fileCreateTime=array();
		foreach($this->contents as $var){
			@$this->fileCreateTime[$var] = FileLJ::getCreateTime($this->path.DIRECTORY_SEPARATOR.$var);
		}
		return $this->fileCreateTime;
	}
	
	public function fileUpdateTime(){
		$this->fileUpdateTime=array();
		foreach($this->contents as $var){
			@$this->fileUpdateTime[$var] = FileLJ::getUpdateTime($this->path.DIRECTORY_SEPARATOR.$var);
		}
		return $this->fileUpdateTime;
	}
	
	function __destruct(){
		$this->directory->close();
	}
}
	

class FileManagement {
	public function displayUploadFile($dirpath){//获取目录内容
		$results = new DirectoryLJ($dirpath);
		return $results->contents;
	}
	
	//正则解析文件名
	public function fileNameDecode($filename){
		$fileinfo = array();
		//正则匹配ip
		//保留文件名称
		$fileinfo['id']=AMPCrypt::b64Encode($filename);
		$fileinfo['ip']=PregProcess::ipPreg($filename);
		//第一次分割文件 以IP为中心分割
		$temp=explode($fileinfo['ip'],$filename);
		//正则匹配日期
		$fileinfo['timestamp']=PregProcess::datePreg($temp[1]);
		//第二次分割文件
		$temp1 = explode('-',$temp[0],3);
		$fileinfo['company'] = $temp1[0];
		$fileinfo['category'] = $temp1[1];
		$fileinfo['file'] = substr($temp1[2],0,strlen($temp1[2])-1);
		$fileinfo['status']=1;//完成配置上传是状态1
		return $fileinfo;
	}
	public function execUpload($files) {//执行上传
		try {
			
			// Undefined | Multiple Files | $_FILES Corruption Attack
			// If this request falls under any of them, treat it invalid.
			if (
				!isset($_FILES['upfile']['error']) ||
				is_array($_FILES['upfile']['error'])
			) {
				throw new RuntimeException('Invalid parameters.');
			}

			// Check $_FILES['upfile']['error'] value.
			switch ($_FILES['upfile']['error']) {
				case UPLOAD_ERR_OK:
					break;
				case UPLOAD_ERR_NO_FILE:
					throw new RuntimeException('No file sent.');
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					throw new RuntimeException('Exceeded filesize limit.');
				default:
					throw new RuntimeException('Unknown errors.');
			}

			// You should also check filesize here. 
			if ($_FILES['upfile']['size'] > 80000000) {
				echo $_FILES['upfile']['size'];
				throw new RuntimeException('Exceeded filesize limit.');
			}
			
			//解析文件名放到不同的配置结果文件夹中
			//假设不正确的文件名都会被放到另一个目录
			$info = $this->fileNameDecode($_FILES['upfile']['name']);
			$type = $info['category'];
			switch ($type) {
				case "OS":
					$targetDir = "os";
					break;
				case "DB":
					$targetDir = "database";
					break;
				case "WL":
					$targetDir = "router";
					break;
				case "MI":
					$targetDir = "middle";
					break;
				default:
					$targetDir = "";
			}
			if (!move_uploaded_file(
				$_FILES['upfile']['tmp_name'],
				sprintf('./resources/confs/%s/%s',
				$targetDir,$_FILES['upfile']['name'])
			)) {
				throw new RuntimeException('Failed to move uploaded file.');
			}
			
			//echo 'File is uploaded successfully.';
			//创建文件数据库
			$dblj = new DbLj(); 
			try {
			$sql = "insert into buaalj_fileinfo (id,company,ip,timestamp,category,subcategory,status) values('".$info['id']."','".$info['company']."','".$info['ip']."','".$info['timestamp']."','".$info['category']."','".$info['file']."',1);";
			$res=$dblj->query($sql);
			$sql = "insert into ".name2Table($info['category'],$info['file'])." (id) values('".$info['id']."');";
			$res=$dblj->query($sql);
			}
			catch (Exception $e) {
				if(! $res) {
					die("数据库错误！插入错误！");
				}
			}
		} catch (RuntimeException $e) {

			echo $e->getMessage();
			die();
		}
	}
	public function execDelete($para) {//执行删除
		$filename = AMPCrypt::b64Decode($para);
		$info = $this->fileNameDecode($filename);
		$type = $info['category'];
		switch ($type) {
			case "OS":
				$targetDir = "os";
				break;
			case "DB":
				$targetDir = "database";
				break;
			case "WL":
				$targetDir = "router";
				break;
			case "MI":
				$targetDir = "middle";
				break;
			default:
				$targetDir = "";
		}
		@unlink(sprintf('./resources/confs/%s/%s',
				$targetDir,$filename));
		//创建文件数据库
		$dblj = new DbLj(); 
		try {
			$sql = "delete from buaalj_fileinfo where id='".$para."'";
			$res=$dblj->query($sql);
			$sql = "delete from ".name2Table($info['category'],$info['file'])." where id='".$para."'";
			$res=$dblj->query($sql);
		}
		catch (Exception $e) {
			if(! $res) {
				die("数据库错误！删除错误！");
			}
		}
		
	}
	
	
}