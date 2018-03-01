<?php
$pid = pcntl_fork();
if($pid < 0) {
    exit("Error: pcntl_fork\n");
}
elseif ($pid == 0) {
    echo "Hello, I am child.\n";
}
elseif ($pid>0) {
    echo "I am parent, child $pid running\n";
}
