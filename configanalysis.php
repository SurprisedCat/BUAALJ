<?php
require('./init.php');
require_once('./libs/ConfigAnalysis.class.php');

//包含配置分析类

//获取文件，解析base64编码
$fileLj = new FileLJ($_GET['id'],true);

//本页面从数据库中找到文件已经分析的内容显示
//将文件信息作为初始化参数交给初始分析类,表示只是显示已有的信息
$showLj = new ConfigAnalysisLj($fileLj->info);
$configRes = $showLj->resource->showFirstStepRes();
// echo name2Table("OS","windows");
// echo name2Table("OS","AIX");
// echo name2Table("OS","linux");
// echo name2Table("WL","cisco");
// echo name2Table("WL","huawei");
// echo name2Table("DB","mysql");
// echo name2Table("DB","oracle");
// echo name2Table("MI","apache-httpd");
// echo name2Table("MI","tomcat-server.xml");

//小标题字符company-->类别-->子类别-->ip
//初步分析按钮的id
$smarty->assign("info",$fileLj->info);
$smarty->assign("configRes",$configRes);
$smarty->display("configanalysis.html");
?>