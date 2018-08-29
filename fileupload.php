<?php
require('./init.php');
//fileupload.php-->init.php-->TextProcess.class.php
$fileMan = new FileManagement();

if(!isset($_GET['act'])){
	//直接显示
}
else if($_GET['act']=="upload"){
	//执行上传的操作
	$fileMan->execUpload($_FILES);
	echo "<script>alert('上传成功');window.location.href = 'fileupload.php';</script>";
}
else if($_GET['act']=="delete"){
	//执行删除文件
	$fileMan->execDelete($_GET['id']);
	//删除数据库中文件数据
	//$dB->deleteitem();
	echo "<script>alert('删除成功');window.location.href = 'fileupload.php';</script>";
}
//显示出目前已经上传的文件列表
$dbContents = $fileMan->displayUploadFile('resources'.DIRECTORY_SEPARATOR.'confs'.DIRECTORY_SEPARATOR.'database');
$miContents = $fileMan->displayUploadFile('resources'.DIRECTORY_SEPARATOR.'confs'.DIRECTORY_SEPARATOR.'middle');
$osContents = $fileMan->displayUploadFile('resources'.DIRECTORY_SEPARATOR.'confs'.DIRECTORY_SEPARATOR.'os');
$wlContents = $fileMan->displayUploadFile('resources'.DIRECTORY_SEPARATOR.'confs'.DIRECTORY_SEPARATOR.'router');
//contents 组合了所有的文件名称
$contents = array();
$contents=array_merge($dbContents,$miContents,$osContents,$wlContents);
//该变量存所有的解析后的文件名数组
$uploadFiles = array();
foreach ($contents as $var){
	$uploadFiles[] = $fileMan->fileNameDecode($var);
}
//Smarty 开始
$smarty->assign("uploadFiles",$uploadFiles);
$smarty->display("fileupload.html");
?>	