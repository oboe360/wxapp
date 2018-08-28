/* 初始化ghost变量 */
var obj;
// http://meifengweishang.com/mobile 测试服务器host1
// http://debug.hqmt360.com/mobile 测试服务器host2
// http://testapp.hqmt360.com/mobile 正式服务器地址1
// http：//webapp.hqmt360.com/mobile 正式服务器地址2
/**
 *  请根据后台要求选择服务器地址，除了更改此处域名还需要修改controllermanager.js的域名
 */
var phpUrl= "http://testapp.hqmt360.com/mobile";
var webUrl= "http://testapp.hqmt360.com/mobile";// 微信分享域名,除非微信后台修改了授权域名，否则不能更改为其他域名

/* 初始化ghost */
function ghostInit(func) {
  return function() {
    obj = new Ghost();
    obj.winName = api.winName;
    obj.frameName = api.frameName;

    //当处在frame中时,不设置监听事件
    if(!obj.frameName) {
      obj.frames = new GhostArray([]);
      obj.keyback(function(ret,err) {
      });
    }
    func();
  };
}

/* 获取ghost */
function ghost(winName,frameName) {

  /* 传入参数为空时,默认为当前窗口 */
  if(winName == undefined) {
    obj.winName = api.winName;
    obj.frameName = api.frameName
    return obj
  }

  /* frameName为空时,将传入的值赋值给winName */
  if(winName != undefined && frameName == undefined) {
    /* 清空obj的frameName的值 */
    if(obj.frameName != undefined) {
      obj.frameName = undefined;
    }
    obj.winName = winName;
    return obj;
  }

  obj.winName = winName;
  obj.frameName = frameName;

  return obj;
}

/* 构造函数Ghost */
function Ghost() {
  /* 打开一个window,并根据是否为当前界面判断调用方法
   * 如果为当前界面,则调用api.openWin()
   * 否则调用api.execScript()
   */
  Ghost.prototype.win = function(val) {

    if(this.winName == api.winName && this.frameName == api.frameName) {
      if(typeof(val) == 'string') {
        api.openWin({
            name: val,
            url: val + '.html',
            slidBackType:'edge',
            // useWKWebView: true
        });
        return;
      }
      api.openWin(val);
    }else {
      var exec;

      if(typeof(val) == 'string') {
        exec = "ghost().win('"+ val+"');";
      }else {
        exec = "ghost().win("+JSON.stringify(val)+");";
      }

      api.execScript({
          name: this.winName,
          frameName: this.frameName,
          script: exec
      });

    }
  };

  /* 后退键监听 */
  Ghost.prototype.keyback = function(val,noBack) {
    var frames = this.frames;
    api.addEventListener({
        name: 'keyback'
    }, function(ret, err){
        if(ret){
          if(!noBack) {
            // alert('haha');
            if(frames && frames.length > 0) {
              var popFrame = frames.pop();
              ghost().close('frame',popFrame);
            }else {
              ghost().close();
            }
          }
          val(frames.pop(),ret,err);
        }else{
          alert(JSON.stringify(err));
        }
    });
  };

  /* 打开一个frame,并根据是否为当前界面判断调用方法
   * 如果为当前界面,则调用api.openWin()
   * 否则调用api.execScript()
   */
  Ghost.prototype.frame = function(val,noBack) {
    if(this.winName == api.winName && this.frameName == api.frameName) {
      if(typeof(val) == 'string') {
        api.openFrame({
            name: val,
            url: val + '.html',
        });
        if(!noBack) {
          if(this.frameName) {
            api.execScript({
                name: this.winName,
                script: "ghost().frames.push('"+val+"');"
            });
          }else {
            this.frames.push(val);
          }
        }
        return;
      }
      api.openFrame(val);
      if(!noBack) {
        if(this.frameName) {
          api.execScript({
              name: this.winName,
              script: "ghost().frames.push('"+val.name+"');"
          });
        }else {
          this.frames.push(val.name);
        }
      }
    }else {
      var exec;
      if(typeof(val) == 'string') {
        exec = "ghost().frame('"+ val+"');";
        if(!noBack){
          if(this.frameName){
            api.execScript({
              name:this.winName,
              script:"ghost().frames.push('"+val+"');"
            });
          }else {
              exec += "this.frames.push('"+val+"');";
          }     
        }
      }else {
        exec = "ghost().frame("+JSON.stringify(val)+");";
        if(!noBack){
          if(this.frameName){
            api.execScript({
              name:this.winName,
              script:"ghost().frames.push('"+val.name+"')"
            });
          }else {
              exec += "this.frames.push('"+val.name+"');";
          }     
        }
      }
      api.execScript({
          name: this.winName,
          frameName: this.frameName,
          script: exec
      });
    }
  };

  /* statusBar */
  Ghost.prototype.statusBar = function(color) {
    api.setStatusBarStyle({
        style: color
    });
  };

  /* toast控件 */
  Ghost.prototype.toast = function(val) {
    if(this.winName == api.winName && this.frameName == api.frameName) {
      if(typeof(val) == 'string') {
        api.toast({
            msg: val,
            duration: 2000,
            location: 'middle'
        });
        return;
      }
      api.toast(val);
      return;
    }
    var exec;
    if(typeof(val) == 'string') {
      exec = "ghost().toast('" + val + "');";
    }else {
      exec = "ghost().toast("+JSON.stringify(val)+");"
    }
    api.execScript({
        name: this.winName,
        frameName: this.frameName,
        script: exec
    });
  };

  /* 唤起外部app */
  Ghost.prototype.app = function(val, func) {
    if(this.winName == api.winName && this.frameName == api.frameName) {
      if(typeof(val) == 'string') {
        api.openApp({
            iosUrl: val,
            androidPkg: 'android.intent.action.VIEW',
            uri: val
        },func);
      }else {
        api.openApp(val,func);
      }
    }else {
      var exec;
      if(typeof(val) == 'string') {
        exec = "ghost().app('" + val + "',"+func+");";
      }else {
        exec = "ghost().app("+ JSON.stringify(val) + ","+func+");";
      }
      api.execScript({
          name: this.winName,
          frameName: this.frameName,
          script: exec
      });
    }
  };

  /* api的execScript封装,
   * 如果为在当前界面调用方法,则直接执行方法
   * 否则调用api.execScript()执行方法
   */
   Ghost.prototype.exec = function(func) {
     if(this.winName == api.winName && this.frameName == api.frameName) {
       if(typeof(func) == 'string') {
         api.execScript({
             name: this.winName,
             frameName: this.frameName,
             script: func
         });
         return;
       }
       func();
       return;
     }else {
       if(typeof(func) == 'string') {
         api.execScript({
             name: this.winName,
             frameName: this.frameName,
             script: "ghost().exec(\""+func+"\");"
         });
         return;
       }
       api.execScript({
           name: this.winName,
           frameName: this.frameName,
           script: "ghost().exec(" + func + ");"
       });
     }
   };

   /* 关闭一个frame或window */
  Ghost.prototype.close = function(type,name) {
    switch (type) {
      case 'frame':
      	if(!name) ghost(api.winName).exec("ghost().frames.remove('"+ api.frameName +"');");
      	else ghost(api.winName).exec("ghost().frames.remove('"+ name +"');");
        api.closeFrame({
          name: name
        });
        break;
      default:
        api.closeWin({
          name: name
        });
        break;
    }
  };

  /* 关闭到某个window */
  Ghost.prototype.closeTo = function(name) {
    api.closeToWin({
        name: name
    });
  };
}

function GhostArray(array) {
	// 构造函数窃取
    Array.apply(this);

}

GhostArray.prototype = Array.prototype;
GhostArray.prototype.constructor = GhostArray;
// 扩展Array,使其能够根据值删除元素
// 根据传入的值获取下标
GhostArray.prototype.indexOf = function(val) {
	for (var i = 0; i < this.length; i++) {
		if (this[i] == val) return i;
	}
	return -1;
};

// 删除元素
GhostArray.prototype.remove = function(val) {
	var index = this.indexOf(val);
	if (index > -1) {
		this.splice(index, 1);
	}
};
