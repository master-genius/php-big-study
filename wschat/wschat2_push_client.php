<?php

$cli = new swoole_http_client('127.0.0.1',7654);

$cli->on('message',function($cli,$frame){
    
    //do nothing

});

$cli->upgrade('/?user_token=client_101',function($cli){
    go_on:;
    $cli->push(json_encode(['']));
    sleep(1);
    goto go_on;
});

