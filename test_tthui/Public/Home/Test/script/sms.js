function sendSms(){	
	var mobile = $('#mobile_phone').val();
	// alert(mobile);
	var password = $('#mobile_pwd').val();
	// var yanzhengma=$("#code_input").val();
	var yanzhengma = '6666';
	//console.log(mobile);
	var flag = $('#flag').val();
	if (mobile.length == '') {
	  $(".alert-select-multiple").show();
      $(".alert-msg").html("请填写手机号码");	
	  return false;
	}
	if (mobile && /^1[3|4|5|7|8]\d{9}$/.test(mobile)) {
		//对的
		// alert(21)
	} else {
		//不对

		$(".alert-select-multiple").show();
        $(".alert-msg").html("请填写正确的手机号码");	
		return false;
	}
	if (password.length == '') {

		$(".alert-select-multiple").show();
        $(".alert-msg").html("请填写登录密码");
		//alert('请填写登录密码');
		return false;

	}
	if (password.length < 6) {

		$(".alert-select-multiple").show();
        $(".alert-msg").html("密码不能少于6个字符");
		return false;

		//alert("密码不能少于6个字符")
	} else {
		var reg = new RegExp("\\s");
		var r = password.substr(1).match(reg);
		if (r != null) {
			$(".alert-select-multiple").show();
        	$(".alert-msg").html("密码不能有空格");
			return false;

			//alert('不能有空格');
		}
	}
	// if(yanzhengma == ''){
	// 	$(".alert-select-multiple").show();
 //        $(".alert-msg").html("请填写验证码");
	// 	return;
	// }{:U('Home/Test/shop_check?is_check=0')}
	$.post("/Home/Test/send", {"mobile": mobile,"password":password,"yanzhengma":yanzhengma,"flag":"register"},function(data){
		if(data.code==1){
			$(".register").hide();
			$(".yanzhengma").show();
			RemainTime();
			//alert('手机验证码已经成功发送到您的手机');
		}else if (data.code == 3){
			yzm();
			$("#code_input").val('');
			// alert(data.msg);
			webToast(data.msg,"middle",3000);
		}else{
			if(data.msg){
			//RemainTime();
				webToast(data.msg,"middle",3000);
				// alert(data.msg);
			}else{
				webToast('手机验证码发送失败',"middle",3000);
				// alert('手机验证码发送失败');
			}
		}
	}, "json");
}
function sendSms2(){	
	var mobile = $('#mobile_phone').val();
	// alert(mobile);
	var password = $('#mobile_pwd').val();
	// var yanzhengma=$("#code_input").val();
	var yanzhengma = '6666';
	//console.log(mobile);
	var flag = $('#flag').val();
	if (mobile.length == '') {
	  $(".alert-select-multiple").show();
      $(".alert-msg").html("请填写手机号码");	
	  return false;
	}
	if (mobile && /^1[3|4|5|7|8]\d{9}$/.test(mobile)) {
		//对的
		// alert(21)
	} else {
		//不对

		$(".alert-select-multiple").show();
        $(".alert-msg").html("请填写正确的手机号码");	
		return false;
	}
	var host = window.location.host;
	$.post("/Home/Test/forget", {"mobile": mobile,"password":password,"yanzhengma":yanzhengma,"flag":"forget"},function(data){
		if(data.code==1){
			$(".register").hide();
			$(".yanzhengma").show();
			RemainTime();
			//alert('手机验证码已经成功发送到您的手机');
		}else if (data.code == 3){
			yzm();
			$("#code_input").val('');
			alert(data.msg);
			// webToast(data.msg,"middle",3000);
		}else{
			if(data.msg){
			//RemainTime();
				// webToast(data.msg,"middle",3000);
				alert(data.msg);
			}else{
				// webToast('手机验证码发送失败',"middle",3000);
				alert('手机验证码发送失败');
			}
		}
	}, "json");
}
function register2(){
	var status = true;
	var mobile = $('#mobile_phone').val();
	var mobile_pwd = $('#mobile_pwd').val();
	var mobile_code = $('#mobile_code').val();
	if(mobile.length == ''){
		// alert('请填写手机号码');
		webToast('请填写手机号码',"middle",3000);
		return false;
	}
	if(mobile_pwd.length == ''){
		// alert('请填写登录密码');
		webToast('请填写登录密码',"middle",3000);
		return false;
	}
	if(mobile_code.length == ''){
		// alert('请填写手机验证码');
		webToast('请填写手机验证码',"middle",3000);
		return false;
	}
	// if(!$("#agreement").attr("checked")){
	// 	alert('请阅读用户协议并同意');
	// 	return false;
	// }
	$.ajax({
		type: "POST",
		url: "sms.php?act=check",
		data: "mobile="+mobile+"&mobile_code="+mobile_code+"&flag=register",
		dataType: "json",
		async: false,
		success: function(result){
			if (result.code!=2){
				alert(result.msg);
				status = false;
			}
		}
	});
	return status;
}

function submitForget(){
	var status = true;
	var mobile = $('#mobile_phone').val();
    var mobile_code = $('#mobile_code').val();
	if(mobile.length == ''){
		// alert('请填写手机号码');
		webToast('请填写手机号码',"middle",3000);
		return false;
	}
	if(mobile_code.length == ''){
		// alert('请填写手机验证码');
		webToast('请填写手机验证码',"middle",3000);
		return false;
	}
	$.ajax({
		type: "POST",
		url: "sms.php?act=check",
		data: "mobile="+mobile+"&mobile_code="+mobile_code,
		dataType: "json",
		async: false,
		success: function(result){
			if (result.code!=2){
				alert(result.msg);
				status = false;
			}
		}
	});
	return status;
}
		
var iTime = 59;
var Account;
function RemainTime(){
  document.getElementById('zphone').disabled = true;
  var iSecond,sSecond="",sTime="";
  if (iTime >= 0){
    iSecond = parseInt(iTime%60);
    if (iSecond >= 0){
      sSecond = iSecond + "秒";
    }
    sTime=sSecond;
    if(iTime==0){
      clearTimeout(Account);
      sTime='获取手机验证码';
      iTime = 59;
      document.getElementById('zphone').disabled = false;
    }else{
      Account = setTimeout("RemainTime()",1000);
      iTime=iTime-1;
    }
  }else{
    sTime='没有倒计时';
  }
  document.getElementById('zphone').value = sTime;
}