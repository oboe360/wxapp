
function openWindow(json) {
	var animationAttr = getAnimationTypeAndDuration();
	var animationType = animationAttr.animation;
	var duration = animationAttr.duration;
	api.openWin({
		name:json.name,
		url:json.url,
		useWKWebView:false,
		slidBackEnabled:true,
		slidBackType:'edge',
		historyGestureEnabled:true,
		pageParam:json.pageParam,
		hScrollBarEnabled:false,
		vScrollBarEnabled:false,
		bounces:false,
		animation:{
			type:animationType,
			duration:duration
		}
	});
}
function openWindowa(json){
	var animationAttr = getAnimationTypeAndDuration();
	var animationType = animationAttr.animation;
	var duration = animationAttr.duration;
	api.openWin({
		name:'order',
		url:'../html/order.html',
		useWKWebView:true,
		slidBackEnabled:true,
		slidBackType:'edge',
		reload : true,
		historyGestureEnabled:true,
		pageParam:json.pageParam,
		hScrollBarEnabled:false,
		vScrollBarEnabled:false,
		animation:{
			type:animationType,
			subType:duration
		}
	});
}
function openFrame(json) {
	var animationAttr = getAnimationTypeAndDuration();
	var animationType = animationAttr.animation;
	var duration = animationAttr.duration;
	api.openFrame({
		name:json.name,
		url:json.url,
		historyGestureEnabled:true,
		pageParam:json.pageParam,
		hScrollBarEnabled:false,
		vScrollBarEnabled:false,
		bounces:false,
		rect:{
			x:0,
			y:0,
			w:'auto',
			h:'auto',
		},
		animation:{
			type:animationType,
			duration:duration
		}
	});
}


function getAnimationTypeAndDuration() {
	var systemType = api.systemType;
	switch(systemType) {
		case "android":
			return {animation:"push",duration:300};
		case "ios":
			return {animation:"push",duration:360};
	}
}

function closeFrame(name) {
	api.closeFrame({
		name:name
	});
}

function closeWindow(name) {
	api.closeWin({
		name:name
    });
    api.execScript({
		name : 'root',
        script : 'total_number();'
    });
}

// http://meifengweishang.com/mobile 测试服务器host1
// http://debug.hqmt360.com/mobile 测试服务器host2
// http://testapp.hqmt360.com/mobile 正式服务器地址1
// http：//webapp.hqmt360.com/mobile 正式服务器地址2
/**
 *  请根据后台要求选择服务器地址，除了更改此处域名还需要修改ghost.js的域名
 */

var phpUrl= "http://meifengweishang.com/mobile";
var webUrl= "http://testapp.hqmt360.com/mobile";// 微信分享域名,除非微信后台修改了授权域名，否则不能更改为其他域名
