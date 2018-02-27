<?php

pcntl_fork();
pcntl_fork();

$pid = posix_getpid();
echo "$pid running\n";

$mq = msg_get_queue(1234);

$msg_err=0;

for ($i=0;$i<100;$i++) {
    msg_send($mq,1,time() . ':' . mt_rand(100,1000),true,true,$msg_err);
}

