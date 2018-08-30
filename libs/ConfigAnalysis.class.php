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
            echo '$resource = MiApacheHttpd($fileInfo,$fileContents)';
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

    //splitByCommand($cmd1,$cmd2)
    //1. 找到上下命令分割 函数实现
    //2. 分割成行
  protected function splitByCommand($cmd1,$cmd2){
    $match = array();
    preg_match_all('!('.$cmd1.')[\s\S]*('.$cmd2.')!',$this->contents,$match);
    //去除最后一行
    $match=preg_replace('!.*('.$cmd2.')$!','',$match[0][0],1);
    //去除第一行
    $match=preg_replace('![^\n]+\n!','',$match,1);
    //按行分割,形成最终数组结果
    $match=preg_split("![\r\n]+!",$match,-1,PREG_SPLIT_NO_EMPTY);
    return $match;
  }
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
    $assocArray['systeminfo_linux'] = $tempRes[0];
    //systeminfo_linux


    /**********识别分析系统端口信息************/
    //ports_linux
    $confArray = $this->splitByCommand("netstat -anp| grep LISTEN","cat /etc/shadow");
    $pregStr = "!0.0.0.0:(\d{1,})[^/]+\/([^#-]*)!";
    $tempRes = preg_grep($pregStr,$confArray);
    $pregStr = "!:::(\d{1,})[^/]+\/([^#-]*)!";
    $tempRes2 = preg_grep($pregStr,$confArray);
    array_merge($tempRes,$tempRes2);
    var_dump($tempRes);
    //ports_linux
    echo "======================<br/>";
    /**********识别分析系统端口信息************/
    //字符串拆分成数组，按行处理
    $confArray = $this->splitByCommand("netstat -anp| grep LISTEN","cat /etc/shadow");
    $ports_re=array();
    foreach($confArray as $line){
      preg_match_all('!0.0.0.0:(\d{1,})[^/]+\/([^# ]*)!',$line,$re);
      if($re[0]!=null){
        $ports_re[$re[1][0]]=$re[2][0];
      }
      preg_match_all('!:::(\d{1,})[^/]+\/([^# ]*)!',$line,$re);
      if($re[0]!=null){
        //端口：$re[1][0]   程序：$re[2][0]   输出到$ports_re【端口：程序】字典中
        $ports_re[$re[1][0]]=$re[2][0];
      }
    }
    //$ports_re【端口：程序】字典
    print_r($ports_re);

    /**********识别分析弱口令************/
    $confArray = $this->splitByCommand('cat /etc/shadow','cat /etc/login.defs');
    $weakpassword_judgment=array();
    foreach($confArray as $line){
      preg_match_all('!^(\w+):(\$[^:]+)!',$line,$temp);
      if($temp[2]!=null){
        $weakpassword_judgment[$temp[1][0]]=$temp[2][0];
      }
    }
    //$weakpassword_judgment【再用用户名：加密口令】字典
    print_r($weakpassword_judgment);

    /**********识别分析口令复杂度************/
    $para1='cat /etc/pam.d/system-auth';
    $para2='$';
    preg_match_all('!('.$para1.')[\s\S]*?('.$para2.')!',$this->contents,$match);
    $match=preg_replace('!.*('.$para2.')$!','',$match[0][0],1);
    $match=preg_replace('![^\n]+\n!','',$match,1);
    $password_complex=array();
    $cxpara = array();
    preg_match_all('!^password\s+requisite\s+pam_cracklib.so(.*)!m',$match,$temp);
    foreach ($temp[1] as $var) 
    {
      $cxpara[] = preg_split('!\s+!',$var,0,PREG_SPLIT_NO_EMPTY);
    }
    //$password_complex，密码复杂度配置结果
    $password_complex=$cxpara[1];
    print_r($password_complex);

    /**********识别分析口令策略************/
    $para1='cat /etc/login.defs';
    $para2='cat /etc/pam.d/su';
    preg_match_all('!('.$para1.')[\s\S]*?('.$para2.')!',$this->contents,$match);
    $match=preg_replace('!.*('.$para2.')$!','',$match[0][0],1);
    $match=preg_replace('![^\n]+\n!','',$match,1);
    preg_match_all('!PASS_MAX_DAYS\s+?(\d.+)!m',$match,$PASS_MAX_DAYS);
    preg_match_all('!PASS_MIN_DAYS\s+?(\d.+)!m',$match,$PASS_MIN_DAYS);
    preg_match_all('!PASS_MIN_LEN\s+?(\d.+)!m',$match,$PASS_MIN_LEN);
    preg_match_all('!PASS_WARN_AGE\s+?(\d.+)!m',$match,$PASS_WARN_AGE);
    $password_policy=array();
    $password_policy['PASS_MAX_DAYS']=$PASS_MAX_DAYS[1][0];
    $password_policy['PASS_MIN_DAYS']=$PASS_MIN_DAYS[1][0];
    $password_policy['PASS_MIN_LEN']=$PASS_MIN_LEN[1][0];
    $password_policy['PASS_WARN_AGE']=$PASS_WARN_AGE[1][0];
    //$password_policy，密码策略：过期时间长度等
    print_r($password_policy);

    /**********识别分析登录失败处理************/

    /**********识别分析远程登录************/
    $para1='cat /etc/ssh/sshd_config';
    $para2='cat /etc/inittab';
    preg_match_all('!('.$para1.')[\s\S]*?('.$para2.')!',$this->contents,$match);
    $match=preg_replace('!.*('.$para2.')$!','',$match[0][0],1);
    $match=preg_replace('![^\n]+\n!','',$match,1);
    preg_match_all('!Port\s+?(\d+)!m',$match,$sshPort);
    preg_match_all('!PermitRootLogin\s+?(\S+)!m',$match,$PermitRootLogin);
    $sshpolicy=array();
    $sshpolicy['sshPort']=$sshPort[1][0];
    $sshpolicy['PermitRootLogin']=$PermitRootLogin[1][0];
    //$sshpolicy  ssh远程登录端口、允许/拒绝
    print_r($sshpolicy);

    /**********别分析弱口令************/

    /**********识别分析日志审计************/
    $para1='service --status-all';
    $para2='cat /etc/pam.d/login';
    preg_match_all('!('.$para1.')[\s\S]*?('.$para2.')!',$this->contents,$match);
    $match=preg_replace('!.*('.$para2.')$!','',$match[0][0],1);
    $match=preg_replace('![^\n]+\n!','',$match,1);
    preg_match_all('!syslogd[\s\S]*?!m',$match,$audit_syslog);
    print_r($audit_syslog);
    //(running)|(stopped)
    /**结束：linux配置分析**/

    echo '</pre>';
    
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
      'sensitiveAuthority_windows'=> "",
      'systeminfo_windows'=>"",
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

    //集中更新,参数1关联数组，参数2表名称，参数3文件id
    $updateDbRes = $this->dblj->multipleUpdate($assocArray,$this->tableName,$this->info["id"]);
    //更新进度
    $this->dblj->query("update buaalj_fileinfo set status=2 where id='".$this->info["id"]."';");

    return $updateDbRes;
  }

}