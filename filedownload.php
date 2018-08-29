<?php
require('./init.php');

$filelist = new DirectoryLJ("resources".DIRECTORY_SEPARATOR."shell");

$smarty->assign("fileCreateTime",$filelist->fileCreateTime());
$smarty->assign("fileUpdateTime",$filelist->fileUpdateTime());
$smarty->assign("shellDirPwd",$shellDirPwd);
$smarty->assign("dirlist",$filelist->contents);
$smarty->display("filedownload.html");
?>