<?php

/*
* 通过文件信息选择具体的功能类
*/ 
class ConfigAnalysisLj {

    public $resource;   
    //构造函数，以配置文件信息和配置文件文本内容为构造参数
    //第二个参数为空表示只是显示配置，不必分析配置
    function __construct($fileInfo,$fileContents="",$fileInfo2="",$fileContents2="",$fileInfo3="",$fileContents3="") {
        $type = $fileInfo['category'];
        $subtype = $fileInfo['file'];
        switch ($type) {
          case "OS":
            switch($subtype){
              case "windows":
                $this->resource = new OsWindows($fileInfo,$fileContents);
                break;
              case "AIX":
                echo '$this->resource = OsAix($fileInfo,$fileContents)';
                break;
              case "linux":
              case "Linux":
                $this->resource = new OsLinux($fileInfo,$fileContents);
                break;
              default:
                die("文件类型错误");
            }
            break;
          case "DB":
            switch($subtype){
              case "mysql":
                echo '$this->resource = DbMysql($fileInfo,$fileContents)';
                break;
              case "oracle":
                echo '$this->resource = DbOracle($fileInfo,$fileContents)';
                break;
              default:
                die("文件类型错误");
            }
            break;
          case "WL":
          switch($subtype){
            case "cisco":
            echo '$this->resource = WlCisco($fileInfo,$fileContents)';
            break;
          case "huawei":
            echo '$this->resource = WlHuawei($fileInfo,$fileContents)';
            break;
          default:
            die("文件类型错误");
          }
            break;
          case "MI":
          switch($subtype){
            case "apache-httpd":
            $this->resource = new MiApacheHttpd($fileInfo,$fileContents);
            break;
          case "tomcat-web.xml":
          case "tomcat-users.xml":
          case "tomcat-server.xml":
            echo '$resource = MiTomcat($fileInfo,$fileContents)';
            break;
          default:
            die("文件类型错误");
          }
            break;
          default:
            die("文件打开错误或不存在");
        }

    }

    function __destruct() {
		
	}

}

/*公共类，实现各种配置文件的公共功能，本身不能独立使用
 *需要继承后使用
 */
abstract class TypeProcess {
  protected $info;//存储文件名信息
  protected $contents;//存储文件内容
  protected $tableName;//数据库名称
  protected $dblj;//数据库连接
  function __construct($fileInfo,$fileContents="") {
    $this->info = $fileInfo;
    $this->tableName = name2Table($this->info['category'],$this->info['file']);
    //如果第二个参数是空，则表示展示项
    //如果第二个参数不是空的，则表示是分析项
    if(strcmp($fileContents,"")==0){//为空
    }
    else{
      $this->contents = $fileContents;
    }
    $this->dblj = new DbLj(); 
  }

  //返回一个关联数组，只有一个文件的数据
  public function showFirstStepRes() {
    return $this->dblj->selectOneRow("select * from ".$this->tableName." where id = '".$this->info["id"]."';");
  }

    //splitByCommand($cmd1,$cmd2)
    //1. 找到上下命令分割 函数实现
    //2. 分割成行
    protected function splitByCommand($cmd1,$cmd2){
      $match = array();
      //如果匹配的是最后一个命令，则需要匹配到文件的结尾
      if($cmd2==null){
        $cmd2 = ".*";
      }
      preg_match_all('!('.$cmd1.')[\s\S]*('.$cmd2.')!',$this->contents,$match);
      //去除最后一行，如果是最后一个命令则不需要去除最后一行
      if($cmd2 != ".*"){
        $match=preg_replace('!.*('.$cmd2.')$!','',$match[0][0],1);  
      }else {
        $match = $match[0][0];
      }
      //去除第一行
      $match=preg_replace('![^\n]+\n!','',$match,1);
      //按行分割,形成最终数组结果
      $match=preg_split("![\r\n]+!",$match,-1,PREG_SPLIT_NO_EMPTY);
      return $match;
    }

  //抽象方法，正则匹配抽象类
  abstract public function pregMatchLj();
}


/*OS Linux的具体处理
* 实现正则匹配类
*/
class OsLinux extends TypeProcess {
/*命令行配置文件分析流程
    * 1. 找到上下命令分割
    * 2. 分割成行
    * 3. 逐行匹配
    */
    /*一般配置文件分析流程
    * 1. 关键字匹配
    */


//正则匹配抽象类的实现
  public function pregMatchLj(){

    //OS Linux 匹配项
    /* id(不需要)
     * systeminfo_linux
     * ports_linux
     * passwordComplexity_linux
     * passwordLimit_linux
     * failLoginReduce_linux
     * auditd_linux
     * remoteRootLogin_linux  
     * fileLimit_linux
     */
    $assocArray = array(
      'systeminfo_linux'=> "",
      'ports_linux'=>"",
      'passwordComplexity_linux'=>'',
      'passwordLimit_linux'=>'',
      'failLoginReduce_linux'=>'',
      'auditd_linux'=>'',
      'remoteRootLogin_linux'=>'',
      'fileLimit_linux'=>'',
    );
  

    /****linux配置分析****/
    //systeminfo_linux
    $confArray = $this->splitByCommand("uname -a","ifconfig -a");
    $pregStr = "!.*!";
    $tempRes = preg_grep($pregStr,$confArray);
    $assocArray['systeminfo_linux'] = json_encode($tempRes[0]);
    //systeminfo_linux


    /**********识别分析系统端口信息************/
    //ports_linux
    $confArray = $this->splitByCommand("netstat -anp| grep LISTEN","cat /etc/shadow");
    
    //端口有两种模式的匹配
    $pregStr1 = "!0.0.0.0:(\d{1,})[^/]+\/([^# ]*)!";
    $tempRes1 = preg_grep($pregStr1,$confArray);
    $pregStr2 = "!:::(\d{1,})[^/]+\/([^# ]*)!";
    $tempRes2 = preg_grep($pregStr2,$confArray);
    $tempRes = array_merge($tempRes1,$tempRes2);
    //获取端口和运行再此端口的程序
    $mediaRes = array();
    foreach($tempRes as $value){
      if(preg_match($pregStr1,$value,$match1)){
        $mediaRes[] = $match1[1].":".$match1[2];
      }     
      elseif(preg_match($pregStr2,$value,$match2)){
        $meidaRes[] = $match2[1].":".$match2[2];
      }
    }
    $res = array();
    foreach($mediaRes as $value){
      $res[] = preg_split("!:!",$value);
    }
    $assocArray["ports_linux"] = json_encode($res);
    //ports_linux

    /**********识别分析弱口令************/
    //passwordComplexity_linux
    $confArray = $this->splitByCommand('cat /etc/shadow','cat /etc/login.defs');

    $pregStr='!^[\w]+:\$[^:]+!';
    $tempRes=preg_grep($pregStr,$confArray);
    $mediaRes = array();
    foreach($tempRes as $value){
      preg_match($pregStr,$value,$match);
      $mediaRes[] = $match[0];
    }
    $res = array();
    foreach($mediaRes as $value){
      $res[] = preg_split("!:!",$value);
    }
    $assocArray["passwordComplexity_linux"] = json_encode($res);
    //passwordComplexity_linux
    
   
    /**********识别分析口令复杂度************/
    //passwordLimit_linux	
    $confArray = $this->splitByCommand('cat /etc/login.defs','cat /etc/pam.d/su');

    $pregStr='!(PASS_MAX_DAYS)|(PASS_MIN_DAYS)|(PASS_MIN_LEN)|(PASS_WARN_AGE)!';
    $tempRes=preg_grep($pregStr,$confArray);
    $mediaRes = array();
    foreach($tempRes as $value){
      $mediaRes[] = $value;
    }
    $res = array();
    foreach($mediaRes as $value){
      $res[] = preg_split("!\s+!",$value);
    }
    $assocArray["passwordLimit_linux"] = json_encode($res);
    // /**********识别分析口令策略(lijuan 原版)************/
    // $para1='cat /etc/login.defs';
    // $para2='cat /etc/pam.d/su';
    // preg_match_all('!('.$para1.')[\s\S]*?('.$para2.')!',$this->contents,$match);
    // $match=preg_replace('!.*('.$para2.')$!','',$match[0][0],1);
    // $match=preg_replace('![^\n]+\n!','',$match,1);
    // preg_match_all('!PASS_MAX_DAYS\s+?(\d.+)!m',$match,$PASS_MAX_DAYS);
    // preg_match_all('!PASS_MIN_DAYS\s+?(\d.+)!m',$match,$PASS_MIN_DAYS);
    // preg_match_all('!PASS_MIN_LEN\s+?(\d.+)!m',$match,$PASS_MIN_LEN);
    // preg_match_all('!PASS_WARN_AGE\s+?(\d.+)!m',$match,$PASS_WARN_AGE);
    // $password_policy=array();
    // $password_policy['PASS_MAX_DAYS']=$PASS_MAX_DAYS[1][0];
    // $password_policy['PASS_MIN_DAYS']=$PASS_MIN_DAYS[1][0];
    // $password_policy['PASS_MIN_LEN']=$PASS_MIN_LEN[1][0];
    // $password_policy['PASS_WARN_AGE']=$PASS_WARN_AGE[1][0];
    // //$password_policy，密码策略：过期时间长度等
    // print_r($password_policy);
    //passwordLimit_linux	

       
    // $para1='cat /etc/pam.d/system-auth';
    // $para2='$';
    // preg_match_all('!('.$para1.')[\s\S]*?('.$para2.')!',$this->contents,$match);
    // $match=preg_replace('!.*('.$para2.')$!','',$match[0][0],1);
    // $match=preg_replace('![^\n]+\n!','',$match,1);
    // $password_complex=array();
    // $cxpara = array();
    // preg_match_all('!^password\s+requisite\s+pam_cracklib.so(.*)!m',$match,$temp);
    // foreach ($temp[1] as $var) 
    // {
    //   $cxpara[] = preg_split('!\s+!',$var,0,PREG_SPLIT_NO_EMPTY);
    // }
    // //$password_complex，密码复杂度配置结果
    // $password_complex=$cxpara[1];
    // print_r($password_complex);
    //failLoginReduce_linux	
    $confArray = $this->splitByCommand('cat /etc/pam.d/system-auth','cat /etc/security/access.conf');
    $pregStr='!password[\s]+requisite[\s]+pam_cracklib.so!';
    $tempRes=preg_grep($pregStr,$confArray);
    $mediaRes = array();
    //虽然只有一行，但是为了保证程序结构一致，用了foreach
    foreach($tempRes as $value){
      $pregStr='!\w+=-?\d+!';
      //需要匹配多个项目，返回一个二维数组
      preg_match_all($pregStr,$value,$match);
      foreach($match[0] as $innerValue){
        $mediaRes[] = $innerValue;
      }
    }
    $res = array();
    foreach($mediaRes as $value){
      $res[] = preg_split("!=!",$value);
    }
    $assocArray["failLoginReduce_linux"] = json_encode($res);
    //failLoginReduce_linux	

    // /**********识别分析日志审计************/
    // $para1='service --status-all';
    // $para2='cat /etc/pam.d/login';
    // preg_match_all('!('.$para1.')[\s\S]*?('.$para2.')!',$this->contents,$match);
    // $match=preg_replace('!.*('.$para2.')$!','',$match[0][0],1);
    // $match=preg_replace('![^\n]+\n!','',$match,1);
    // preg_match_all('!syslogd[\s\S]*?!m',$match,$audit_syslog);
    // print_r($audit_syslog);
    // //(running)|(stopped)
    // auditd_linux
    $confArray = $this->splitByCommand('service --status-all','cat /etc/*syslog*.conf');
    $pregStr='!^auditd!';
    $tempRes=preg_grep($pregStr,$confArray);
    $res = array();
    foreach($tempRes as $value){
      $res[] = $value;
    }
    $assocArray["auditd_linux"] = json_encode($res);
    // auditd_linux

// /**********识别分析远程登录************/
    // $para1='cat /etc/ssh/sshd_config';
    // $para2='cat /etc/inittab';
    // preg_match_all('!('.$para1.')[\s\S]*?('.$para2.')!',$this->contents,$match);
    // $match=preg_replace('!.*('.$para2.')$!','',$match[0][0],1);
    // $match=preg_replace('![^\n]+\n!','',$match,1);
    // preg_match_all('!Port\s+?(\d+)!m',$match,$sshPort);
    // preg_match_all('!PermitRootLogin\s+?(\S+)!m',$match,$PermitRootLogin);
    // $sshpolicy=array();
    // $sshpolicy['sshPort']=$sshPort[1][0];
    // $sshpolicy['PermitRootLogin']=$PermitRootLogin[1][0];
    // //$sshpolicy  ssh远程登录端口、允许/拒绝
    // print_r($sshpolicy);
    //remoteRootLogin_linux
    $confArray = $this->splitByCommand('cat /etc/ssh/sshd_config','ps -ef');
    $pregStr='!(^PermitRootLogin)|(Port\s+?(\d+))!';
    $tempRes=preg_grep($pregStr,$confArray);
    $mediaRes = array();
    foreach($tempRes as $value){
      $mediaRes[] = $value;
    }
    $res = array();
    foreach($tempRes as $value){
      $res[] = preg_split("!\s+!",$value);
    }
    $assocArray["remoteRootLogin_linux"] = json_encode($res);
    //remoteRootLogin_linux

    //fileLimit_linux
    $confArray = $this->splitByCommand('umask','cat /etc/profile');
    $pregStr='!.*!';
    $tempRes=preg_grep($pregStr,$confArray);
    $res = array();
    foreach($tempRes as $value){
      $res[] = $value;
    }
    $assocArray["fileLimit_linux"] = json_encode($res);
    //fileLimit_linux   

    $assocArray["riskResult"] = json_encode("0");
    /**结束：linux配置分析**/
    
    //集中更新,参数1关联数组，参数2表名称，参数3文件id
    $updateDbRes = $this->dblj->multipleUpdate($assocArray,$this->tableName,$this->info["id"]);
    //更新进度
    $this->dblj->query("update buaalj_fileinfo set status=2 where id='".$this->info["id"]."';");

    return $updateDbRes;
  }

}


class OsWindows extends TypeProcess {

  //正则匹配抽象类的实现
  function pregMatchLj(){
    //OS Windows 匹配项
    /* id(不需要)
     * sensitiveAuthority_windows
     * systeminfo_windows
     * passwordComplexity_windows
     * ports_windows
     * remoteLogin_windows
     * software_windows
     * accountLock_windows  
     * netShare_windows
     * auditStrategy_windows
     * firewall_windows
     * screenProtect_windows
     */
    $assocArray = array(
      'systeminfo_windows'=>"",
      'sensitiveAuthority_windows'=> "",
      'passwordComplexity_windows'=>'',
      'ports_windows'=>'',
      'remoteLogin_windows'=>'',
      'software_windows'=>'',
      'accountLock_windows'=>'',
      'netShare_windows'=>'',
      'auditStrategy_windows'=>'',
      'firewall_windows'=>'',
      'screenProtect_windows'=>'',
    );
    /*命令行配置文件分析流程
    * 1. 找到上下命令分割
    * 2. 分割成行
    * 3. 逐行匹配
    */
    /*一般配置文件分析流程
    * 1. 关键字匹配
    */

    /****windows配置分析****/
    //systeminfo_windows
    $confArray = $this->splitByCommand("1.systeminfo","2.port");
    $pregStr = "!(^os\s+名称)|(^os\s+版本)!i";
    $tempRes = preg_grep($pregStr,$confArray);
    $res = array();
    foreach($tempRes as $value){
      $res[] = preg_split("!:!",$value);
    }
    $assocArray['systeminfo_windows'] = json_encode($res);
    //systeminfo_windows

    //sensitiveAuthority_windows
    $confArray = $this->splitByCommand("6.local_strategy","7.share");
    $pregStr = "!(SeRemoteShutdownPrivilege)|(SeShutdownPrivilege)|(SeTakeOwnershipPrivilege)!i";
    $tempRes = preg_grep($pregStr,$confArray);
    $res = array();
    foreach($tempRes as $value){
      $res[] = preg_split("!\s+=\s+!",$value);
    }
    $assocArray['sensitiveAuthority_windows'] = json_encode($res);
    //sensitiveAuthority_windows

    //passwordComplexity_windows
    $confArray = $this->splitByCommand("6.local_strategy","7.share");
    $pregStr = "!(PasswordComplexity)|(MinimumPasswordLength)|(MinimumPasswordAge)|(MaximumPasswordAge)|(PasswordHistorySize)!i";
    $tempRes = preg_grep($pregStr,$confArray);
    $res = array();
    foreach($tempRes as $value){
      $res[] = preg_split("!\s+=\s+!",$value);
    }
    $assocArray['passwordComplexity_windows'] = json_encode($res);
    //passwordComplexity_windows

    //ports_windows
    $confArray = $this->splitByCommand("2.port","3.service");
    $pregStr = "!TCP\s+0.0.0.0:\d{1,5}!i";
    $tempRes = preg_grep($pregStr,$confArray);
    $res = array();
    foreach($tempRes as $value){
      preg_match("!0.0.0.0:(\d{1,5})!",$value,$match);
      $res[] = $match[1];
    }
    $assocArray['ports_windows'] = json_encode($res);
    //ports_windows

    //remoteLogin_windows
    $confArray = $this->splitByCommand("8.other",null);
    $pregStr = "!fDenyTSConnections!i";
    $tempRes = preg_grep($pregStr,$confArray);
    $res = array();
    foreach($tempRes as $value){
      $res = preg_split("!\s+REG_DWORD\s+!",$value);
      $res[0] = trim($res[0]);
    }
    $assocArray['remoteLogin_windows'] = json_encode($res);
    //remoteLogin_windows

    //software_windows
    $confArray = $this->splitByCommand("5.software","6.local_strategy");
    $pregStr = "!software_name:360Safe!i";
    $tempRes = preg_grep($pregStr,$confArray);
    if($tempRes != null){
      $assocArray['software_windows'] = json_encode("software_name:360Safe");
    } else {
      $assocArray['software_windows'] = json_encode(false);
    }
    //software_windows

    //accountLock_windows
    $confArray = $this->splitByCommand("6.local_strategy","7.share");
    $pregStr = "!(LockoutDuration)|(ResetLockoutCount)|(LockoutBadCount)!i";
    $tempRes = preg_grep($pregStr,$confArray);
    $res = array();
    foreach($tempRes as $value){
      $res[] = preg_split("!\s+=\s+!",$value);
    }
    $assocArray['accountLock_windows'] = json_encode($res);
    //accountLock_windows

    //netShare_windows
    $confArray = $this->splitByCommand("7.share","8.other");
    $pregStr = '!^\w+\$!i';
    $tempRes = preg_grep($pregStr,$confArray);
    $res = array();
    foreach($tempRes as $value){
      $res[] = preg_replace('!\$.*!','\$',$value);
    }
    $assocArray['netShare_windows'] = json_encode($res);
    //netShare_windows

    //auditStrategy_windows
    $confArray = $this->splitByCommand("6.local_strategy","7.share");
    $pregStr = "!(AuditAccountManage)|(AuditAccountLogon)|(AuditSystemEvents)|(AuditDSAccess)|(AuditProcessTracking)|(AuditPrivilegeUseuditObjectAccess)|(AuditLogonEvents)|(AuditPolicyChange)!i";
    $tempRes = preg_grep($pregStr,$confArray);
    $res = array();
    foreach($tempRes as $value){
      $res[] = preg_split("!\s+=\s+!",$value);
    }
    $assocArray['auditStrategy_windows'] = json_encode($res);
    //auditStrategy_windows

    //firewall_windows
    $confArray = $this->splitByCommand("8.other",null);
    $pregStr = "!EnableFirewall!i";
    $tempRes = preg_grep($pregStr,$confArray);
    $res = array();
    foreach($tempRes as $value){
      $res = preg_split("!\s+REG_DWORD\s+!",$value);
      $res[0] = trim($res[0]);
    }
    $assocArray['firewall_windows'] = json_encode($res);
    //firewall_windows

    //screenProtect_windows
    $confArray = $this->splitByCommand("8.other",null);
    $pregStr = "!ScreenSaveActive!i";
    $tempRes = preg_grep($pregStr,$confArray);
    $res = array();
    foreach($tempRes as $value){
      $res = preg_split("!\s+REG_SZ\s+!",$value);
      $res[0] = trim($res[0]);
    }
    $assocArray['screenProtect_windows'] = json_encode($res);
    //screenProtect_windows

    /****windows配置分析****/

    //集中更新,参数1关联数组，参数2表名称，参数3文件id
    $updateDbRes = $this->dblj->multipleUpdate($assocArray,$this->tableName,$this->info["id"]);
    //更新进度
    $this->dblj->query("update buaalj_fileinfo set status=2 where id='".$this->info["id"]."';");

    return $updateDbRes;
  }

}
//////李娟尝试!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
class MiApacheHttpd extends TypeProcess {

  //正则匹配抽象类的实现
  function pregMatchLj(){
    //MI apache 匹配项
    /* id(不需要)
     * prohibitDirectoryListing_apache
     * fileAccessForbiden_apache
     * ports_apache
     * closeHtaccess_apache
     * hideVersion_apache
     * logLevel_apache
     * logFolder_apache
     */
        $assocArray = array(
      'prohibitDirectoryListing_apache'=>"",
      'fileAccessForbiden_apache'=> "",
      'ports_apache'=>'',
      'closeHtaccess_apache'=>'',
      'hideVersion_apache'=>'',
      'logLevel_apache'=>'',
      'logFolder_apache'=>'',
    );

    /****apache配置分析****/
    //prohibitDirectoryListing_apache
    $confArray = $this->splitByCommand("\#",null);
    // print_r($confArray);
    $pregStr = "!(Options Indexes FollowSymLinks)|(Options -Indexes FollowSymLinks)|(Options FollowSymLinks)!i";
    //preg_match_all('!('.$cmd1.')[\s\S]*('.$cmd2.')!',$this->contents,$match);
    $tempRes = preg_grep($pregStr,$confArray);
    print_r($tempRes);
    $res = array();
    foreach($tempRes as $value){
      $res[] = preg_split("!:!",$value);
    }
    $assocArray['prohibitDirectoryListing_apache'] = json_encode($res);
    die();
    //prohibitDirectoryListing_apache

    /****apache配置分析****/
  }
}

