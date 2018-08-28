/**
 * html代码转换成图片
 * @param {Object} window 浏览器window对象
 * @author axen(940029914@qq.com)
 */

(function (window) {
  var vango = {};
  vango.canvas = window.document.createElement("canvas"); // 初始化canvas
  vango.canvasContext = vango.canvas.getContext("2d"); // 获取canvas上下文
  vango.onload=function(result){}; // 合并图片完成时调用该方法
  /**
   * 合并图片
   * 目标html标签
   */
  vango.draw = function(el) {
    var node = $(el);
    this.canvas.width = node.attr("width");
    this.canvas.height = node.attr("height");
    this.canvasContext.rect(0,0,this.canvas.width,this.canvas.height);
    this.canvasContext.fillStyle = '#fff';
    this.canvasContext.fill();
    var children = node.children();
    // 逆序遍历内容
    this._drawContent({
      obj: children,
      index: children.length - 1
    });
  };

  /**
   * 归遍历所有元素，并将元素内容合并到图片上
   * @param object 遍历的元素信息
   */
  vango._drawContent = function(object) {
    var obj = object.obj; // 子元素数组
    var index = object.index; // 目标子元素的下标
    switch (obj[index].tagName) {
      // img标签
      case 'V-IMAGE':
        var img = new Image;
        var x = Number($(obj[index]).attr("x")); // 坐标x的值
        var y = Number($(obj[index]).attr("y")); // 坐标y的值
        var width = Number($(obj[index]).attr("width")); // 图片宽度
        var height = Number($(obj[index]).attr("height")); // 图片高度
        img.setAttribute("src",$(obj[index]).attr("src")); // 图片路径
        // 必须等img加载完成才执行下一步操作，否则图片会不显示
        img.onload = function() {
          vango.canvasContext.drawImage(img,x,y,width,height); // 将图片输入到合并图片中
          // 如果此元素为最后一个元素，则输出合并图片
          if(index == 0) {
	      	var base64 = vango.canvas.toDataURL('image/jpeg'); // 输出格式为jpg
	        vango.onload(base64);
	        return;
	      }
	      // 递归输入下一个元素
	      vango._drawContent({
			obj: obj,
			index: index - 1
	   	  });
        };
        break;
      // text标签
      case 'V-TEXT':
      	var x = Number($(obj[index]).attr("x")) ? Number($(obj[index]).attr("x")) : 0; // 坐标x的值
        var y = Number($(obj[index]).attr("y")) ? Number($(obj[index]).attr("y")) : 0; // 坐标y的值
        var textSize = $(obj[index]).attr("textSize") ? $(obj[index]).attr("textSize") : "30px"; // 字体大小
        var color = $(obj[index]).attr("color") ? $(obj[index]).attr("color") : "#000"; // 字体颜色
        var text = $(obj[index]).html(); // 文本内容
        vango.canvasContext.fillStyle = color;
        vango.canvasContext.font = textSize + " Arial";
    	vango.canvasContext.fillText(text,x,y)
    	// 如果此元素为最后一个元素，则输出合并图片
    	if(index == 0) {
	      var base64 = vango.canvas.toDataURL('image/jpeg');
	      vango.onload(base64);
	      return;
	    }
	    // 递归输入下一个元素
	    vango._drawContent({
			obj: obj,
			index: index - 1
	    });
        break;
    }
  }
  window.vango = vango;
})(window);
