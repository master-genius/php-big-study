<?php

//IP地址：192.168.56.101，端口号：2345
$sw_http_serv = new swoole_http_server('localhost',2345);

$sw_http_serv->set([
    'document_root' => '/var/www/swoole_static',
    'enable_static_handler' => true,
    'daemonize' => 1
]);

//注册request事件回调函数
$sw_http_serv->on('request',function($request,$response){
    $response->write("Error: file not found");
});
//启动服务
$sw_http_serv->start();

