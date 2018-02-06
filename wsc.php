<?php

$cli = new swoole_http_client('127.0.0.1',4567);

$cli->on('message',function($cli,$frame){
    
    //do nothing

});

$cli->setData('token=123456');

$cli->upgrade('/',function($cli){
    go_on:;
    $cli->push(md5(time()));
    sleep(1);
    goto go_on;
});

