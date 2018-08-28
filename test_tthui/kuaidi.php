<?php
   header('Content-Type: text/html; charset=utf-8'); //网页编码
    $host = "http://goexpress.market.alicloudapi.com";
    $path = "/goexpress";
    $method = "GET";
    $appcode = "3cd7d112d60b491dbaf5859d93a2c50b";
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    $querys = "no=3982120782637&type=YD";
    $bodys = "";
    $url = $host . $path . "?" . $querys;
    //print_r( $url);die;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    //curl_setopt($curl, CURLOPT_HEADER, true); 如不输出json, 请打开这行代码，打印调试头部状态码。
    //状态码: 200 正常；400 URL无效；401 appCode错误； 403 次数用完； 500 API网管错误
    if (1 == strpos("$".$host, "https://"))
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    echo(curl_exec($curl));
?>