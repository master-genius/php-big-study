<?php

$mq = msg_get_queue(1234);

$msg = '';
$msgtype=0;
$errcode = 0;
$count = 1;

while (true) {
    usleep(100);
    msg_receive($mq,0,$msgtype,100,$msg,true,0,$errcode);
    if (!$errcode) {
        echo "Received message($count): ",$msg,"\n";
    }
    else {
        echo "Errcode:",$errcode,"\n";
    }
    $count += 1;
}

