/*****这里的js函数用来检zx测，用户是否填写了制定的内容****/
/*****并实现提交表单*******/

function loginSubmit() {
	if(document.getElementById("username").value.length==0) {
		alert("请输入姓名。");
		return false;
	}

	if(document.getElementById("captchas").value.length==0) {
		alert("请输入验证码。");
		return false;	
	}

	var dt = new Date();  
	dt.setSeconds(dt.getSeconds() + 60);  
	document.cookie = "cookietest=1; expires=" + dt.toGMTString();  
	var cookiesEnabled = document.cookie.indexOf("cookietest=") != -1;  
	if(!cookiesEnabled) {  
		alert('未见测到Cookie，请启用浏览器的Cookie！ '); 
		return false;
	}

	document.getElementById("loginForm").submit();
	return true;

}