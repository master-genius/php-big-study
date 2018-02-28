<?php

$cli = new swoole_http_client('127.0.0.1',9876);

$cli->on('message',function($cli,$frame){
    echo "Received: $frame->data\n";
    usleep(100000);
    $cli->push("Send: " . mt_rand(1000,10000));
});

$cli->upgrade('/',function($cli){
    $cli->push( time() );
});

