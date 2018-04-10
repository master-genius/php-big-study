<?php

$cli = new swoole_http_client('127.0.0.1',4567);

$cli->on('message',function($cli,$frame){
    
    //do nothing

});

//$cli->setHeaders([['push-connect-token','1001001'],]);

$cli->upgrade('/push_client/phpswoolewebsocket',function($cli){
    go_on:;
    $cli->push(strftime("%Y.%m.%d %H:%M:%S"));
    sleep(1);
    goto go_on;
});

