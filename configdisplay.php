<?php
require('./init.php');
$fileLj = new FileLJ($_GET['id'],true);

$smarty->assign("filecontent",$fileLj->getContents());
$smarty->assign("filename",$fileLj->name);
$smarty->display("configdisplay.html");
?>