<?php
require('./init.php');
require_once('./libs/ConfigAnalysis.class.php');
if(!isset($_GET['id'])){
    header("Location:error.php");
}
$dblj = new DbLj(); 
$fileExsit = $dblj->selectOneRow("select * from ".$dbPre."fileinfo where id = '".$_GET['id']."';");
if(!$fileExsit){
    header("Location:error.php");
}
//获取文件，解析base64编码
$fileLj = new FileLJ($_GET['id'],true);
//将文件信息作为初始化参数交给初始分析类,表示只是显示已有的信息
$FirstStepResLj = new ConfigAnalysisLj($fileLj->info,$fileLj->getContents());

//$FirstStepResLj->resource->pregMatchLj()返回数据库更新情况
if(!$FirstStepResLj->resource->pregMatchLj()){
    die("配置更新失败");
}

$configRes = $FirstStepResLj->resource->showFirstStepRes();
$lineChart = array(10,40,20,30);

$smarty->assign("info",$fileLj->info);
$smarty->assign("configRes",$configRes);
$smarty->assign("lineChart",$lineChart);
$smarty->display("configevaluation.html");
?>