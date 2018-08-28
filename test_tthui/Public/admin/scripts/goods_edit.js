/*global Qiniu */
/*global plupload */
/*global FileProgress */
/*global hljs */

var bind = function() {
  // alert('asd');
  var uploader = Qiniu.uploader({
    disable_statistics_report: false,
    runtimes: 'html5,flash,html4',
    browse_button: 'pickfiles',
    //container: 'container',
    //drop_element: 'container',
    max_file_size: '1024mb',
    flash_swf_url: 'bower_components/plupload/js/Moxie.swf',
    flash_swf_url: '../views/Moxie.swf',
    dragdrop: true,
    chunk_size: '4mb',
    multi_selection: !(moxie.core.utils.Env.OS.toLowerCase() === "ios"),
    //uptoken: 'XzB-CZAJTQb_gL0TI251y0IYstitNAEMILtNWa-h:qkPbopIYL_JHJjRmE2Ou3F3D-hc=:eyJzY29wZSI6ImJ1cnN0LWltYWdlcyIsImRlYWRsaW5lIjoxNTEzNTg2NjAwfQ==',
    //uptoken:'XzB-CZAJTQb_gL0TI251y0IYstitNAEMILtNWa-h:xoCPV00o0bEL9wxMF01V3TuPAgk=:eyJzY29wZSI6ImJ1cnN0LWltYWdlcyIsImRlYWRsaW5lIjoxNTEzNjU0NjQ2fQ==',
    uptoken_url:$("input[name='token_url']").val(),
    uptoken_func: function(){
        var ajax = new XMLHttpRequest();
        ajax.open('GET', $("input[name='token_url']").val(), false);
        ajax.setRequestHeader("If-Modified-Since", "0");
        ajax.send();
        if (ajax.status === 200) {
            //console.log(ajax.responseText);
            var res = JSON.parse(ajax.responseText);
            //console.log('custom uptoken_func:' + res.uptoken);
            return res.uptoken;
        } else {
            //console.log('custom uptoken_func err');
            return '';
        }
    },
    domain: $("input[name='field_url']").val(),
    get_new_uptoken: true,
    unique_names: true,
    auto_start: true,
    log_level: 5,
    init: {
      'BeforeChunkUpload': function(up, file) {
        //console.log("before chunk upload:", file.name);
      },
      'FilesAdded': function(up, files) {
        $('#table').show();
        $('#success').hide();
        // 文件添加进队列后,处理相关的事情
        plupload.each(files, function(file) {
          var progress = new FileProgress(file,
            'fsUploadProgress');
          progress.setStatus("等待...");
          progress.bindUploadCancel(up);
        });
      },
       // 每个文件上传前,处理相关的事情
      'BeforeUpload': function(up, file) {
        //console.log("this is a beforeupload function from init");
        var progress = new FileProgress(file, 'fsUploadProgress');
        var chunk_size = plupload.parseSize(this.getOption(
          'chunk_size'));
        if (up.runtime === 'html5' && chunk_size) {
          progress.setChunkProgess(chunk_size);
        }
      },
      // 每个文件上传时,处理相关的事情
      'UploadProgress': function(up, file) {
        var progress = new FileProgress(file, 'fsUploadProgress');
        var chunk_size = plupload.parseSize(this.getOption(
          'chunk_size'));
        progress.setProgress(file.percent + "%", file.speed,
          chunk_size);
      },
      'UploadComplete': function() {
        $('#success').show();
      },
      // 每个文件上传成功后,处理相关的事情
      // 其中 info.response 是文件上传成功后，服务端返回的json，形式如
      'FileUploaded': function(up, file, info) {
        var progress = new FileProgress(file, 'fsUploadProgress');
        //console.log("response:", info.response);
        //请求插入
        var key_img = JSON.parse(info.response);
        var url = $("input[name='field_url']").val()+key_img.key;
        $('#goods_video').val(url);
        $('#video').attr('src',url);
        $('#table').hide();
        // $.ajax({
        //      type: "get",
        //      url: "./up.php?space=space&m_id="+ $("input[name='field_name']").val(),
        //      data: {data_key:key_img.key},
        //      dataType: "JSON",
        //      success: function(data){
        //         if(data == '1'){
        //           alert('该文件插入数据库失败！请重新上传该单个文件！');
        //         }
                
        //      }
        //  });
        //alert(info.response);
        progress.setComplete(up, info.response);
      },
      //上传出错时,处理相关的事情
      'Error': function(up, err, errTip) {
          $('table').show();
          var progress = new FileProgress(err.file, 'fsUploadProgress');
          progress.setError();
          progress.setStatus(errTip);
        }
        // ,
        // 'Key': function(up, file) {
        //     var key = "";
        //     // do something with key
        //     return key
        // }
    }
  });

  //uploader.init();
  uploader.bind('BeforeUpload', function() {
    //console.log("hello man, i am going to upload a file");
  });
  uploader.bind('FileUploaded', function() {
    //console.log('hello man,a file is uploaded');
  });

  $('#container').on(
    'dragenter',
    function(e) {
      e.preventDefault();
      $('#container').addClass('draging');
      e.stopPropagation();
    }
  ).on('drop', function(e) {
    e.preventDefault();
    $('#container').removeClass('draging');
    e.stopPropagation();
  }).on('dragleave', function(e) {
    e.preventDefault();
    $('#container').removeClass('draging');
    e.stopPropagation();
  }).on('dragover', function(e) {
    e.preventDefault();
    $('#container').addClass('draging');
    e.stopPropagation();
  });



  $('#show_code').on('click', function() {
    $('#myModal-code').modal();
    $('pre code').each(function(i, e) {
      hljs.highlightBlock(e);
    });
  });


  $('body').on('click', 'table button.btn', function() {
    $(this).parents('tr').next().toggle();
  });


  var getRotate = function(url) {
    if (!url) {
      return 0;
    }
    var arr = url.split('/');
    for (var i = 0, len = arr.length; i < len; i++) {
      if (arr[i] === 'rotate') {
        return parseInt(arr[i + 1], 10);
      }
    }
    return 0;
  };

  $('#myModal-img .modal-body-footer').find('a').on('click', function() {
    var img = $('#myModal-img').find('.modal-body img');
    var key = img.data('key');
    var oldUrl = img.attr('src');
    var originHeight = parseInt(img.data('h'), 10);
    var fopArr = [];
    var rotate = getRotate(oldUrl);
    if (!$(this).hasClass('no-disable-click')) {
      $(this).addClass('disabled').siblings().removeClass('disabled');
      if ($(this).data('imagemogr') !== 'no-rotate') {
        fopArr.push({
          'fop': 'imageMogr2',
          'auto-orient': true,
          'strip': true,
          'rotate': rotate,
          'format': 'png'
        });
      }
    } else {
      $(this).siblings().removeClass('disabled');
      var imageMogr = $(this).data('imagemogr');
      if (imageMogr === 'left') {
        rotate = rotate - 90 < 0 ? rotate + 270 : rotate - 90;
      } else if (imageMogr === 'right') {
        rotate = rotate + 90 > 360 ? rotate - 270 : rotate + 90;
      }
      fopArr.push({
        'fop': 'imageMogr2',
        'auto-orient': true,
        'strip': true,
        'rotate': rotate,
        'format': 'png'
      });
    }

    $('#myModal-img .modal-body-footer').find('a.disabled').each(
      function() {

        var watermark = $(this).data('watermark');
        var imageView = $(this).data('imageview');
        var imageMogr = $(this).data('imagemogr');

        if (watermark) {
          fopArr.push({
            fop: 'watermark',
            mode: 1,
            image: 'http://www.b1.qiniudn.com/images/logo-2.png',
            dissolve: 100,
            gravity: watermark,
            dx: 100,
            dy: 100
          });
        }

        if (imageView) {
          var height;
          switch (imageView) {
            case 'large':
              height = originHeight;
              break;
            case 'middle':
              height = originHeight * 0.5;
              break;
            case 'small':
              height = originHeight * 0.1;
              break;
            default:
              height = originHeight;
              break;
          }
          fopArr.push({
            fop: 'imageView2',
            mode: 3,
            h: parseInt(height, 10),
            q: 100,
            format: 'png'
          });
        }

        if (imageMogr === 'no-rotate') {
          fopArr.push({
            'fop': 'imageMogr2',
            'auto-orient': true,
            'strip': true,
            'rotate': 0,
            'format': 'png'
          });
        }
      });

    var newUrl = Qiniu.pipeline(fopArr, key);

    var newImg = new Image();
    img.attr('src', '../images/loading.gif');
    newImg.onload = function() {
      img.attr('src', newUrl);
      img.parent('a').attr('href', newUrl);
    };
    newImg.src = newUrl;
    return false;
  });

};
