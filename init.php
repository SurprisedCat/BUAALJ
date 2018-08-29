<?php
header("Content-type: text/html; charset=utf-8"); 
date_default_timezone_set("PRC");

require_once('./libs/Crypt.class.php');
require('./libs/Smarty.class.php');
require_once('./libs/Db.class.php');
require_once('./libs/TextProcess.class.php');
require_once('./libs/FileManagement.class.php');
//一些通过的变量
//数据库前缀
$dbPre = "buaalj_";


//shell 脚本目录位置
$shellDirPwd = 'resources'.DIRECTORY_SEPARATOR.'shell';

$smarty = new Smarty();
$smarty->assign("name","Juan Li");

//从文件名转换成数据库表名称
function name2Table($type,$subtype) {
    global $dbPre;
    $res = "";
    switch ($type) {
        case "OS":
          switch($subtype){
            case "windows":
              $res = $dbPre."config_windows";
              break;
            case "AIX":
              $res = $dbPre."config_aix";
              break;
            case "linux":
            case "Linux":
              $res = $dbPre."config_linux";
              break;
            default:
              die("文件名称转换为表名错误");
          }
          break;
        case "DB":
          switch($subtype){
            case "mysql":
              $res = $dbPre."config_mysql";
              break;
            case "oracle":
              $res = $dbPre."config_oracle";
              break;
            default:
              die("文件名称转换为表名错误");
          }
          break;
        case "WL":
        switch($subtype){
          case "cisco":
            $res = $dbPre."config_cisco";
          break;
        case "huawei":
          $res = $dbPre."config_huawei";
          break;
        default:
          die("文件名称转换为表名错误");
        }
          break;
        case "MI":
        switch($subtype){
          case "apache-httpd":
            $res = $dbPre."config_apache";
            break;
        case "tomcat-web.xml":
        case "tomcat-users.xml":
        case "tomcat-server.xml":
          $res = $dbPre."config_tomcat";
          break;
        default:
          die("文件名称转换为表名错误");
        }
          break;
        default:
          die("文件名错误或不存在");
      }
    return $res;
}



