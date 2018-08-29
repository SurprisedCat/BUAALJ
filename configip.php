<?php
require('./init.php');

//状态转移关系
//1.配置上传(20%)-->2.初步分析（40%）-->3.访谈结果（60%）-->4.结果校正（80%）-->风险评估与建议（100%）

$fileMan = new FileManagement();
if(!isset($_GET['act'])){
	//直接显示
}
//显示出目前已经上传的文件列表--文件实现
// $dbContents = $fileMan->displayUploadFile('resources'.DIRECTORY_SEPARATOR.'confs'.DIRECTORY_SEPARATOR.'database');
// $miContents = $fileMan->displayUploadFile('resources'.DIRECTORY_SEPARATOR.'confs'.DIRECTORY_SEPARATOR.'middle');
// $osContents = $fileMan->displayUploadFile('resources'.DIRECTORY_SEPARATOR.'confs'.DIRECTORY_SEPARATOR.'os');
// $wlContents = $fileMan->displayUploadFile('resources'.DIRECTORY_SEPARATOR.'confs'.DIRECTORY_SEPARATOR.'router');

//显示出目前已经上传的文件列表--数据库实现
$dblj = new DbLj();
$sql_select = "select * from `buaalj_fileinfo` order by `company`";
if(!$res = $dblj->query($sql_select)) {
	die("数据库错误！读取文件错误！");
}
//contents 组合了所有的文件名称
$configFiles = array();
while($row=$res->fetch_array()) {
	//从数据库中读取每一行，并实现优化的赋值
	$temp['id'] = $row['id'];
	$temp['company']=$row['company'];
	$temp['ip']=$row['ip'];
	$temp['timestamp']=$row['timestamp'];
	$temp['category']=$row['category'];
	$temp['subcategory']=$row['subcategory'];
	$temp['status']=$row['status'];
	$configFiles[] = $temp;
}
//Smarty 开始
$smarty->assign("configFiles",$configFiles);
$smarty->display("configip.html");
?>