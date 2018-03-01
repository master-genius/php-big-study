<?php

$pid = pcntl_fork();
if($pid < 0) {
    exit("Error: pcntl_fork\n");
}
elseif ($pid == 0) {
    echo "Hello, I am child.\n";
    
}
elseif ($pid>0) {
    echo "I am parent, child $pid running\nWaiting child exit...\n";
    $status = 0;
    pcntl_waitpid($pid,$status);
    echo "Child status code : $status\n";
}
