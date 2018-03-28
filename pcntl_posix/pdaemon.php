<?php
$pid = pcntl_fork();
if ($pid<0) {
    exit(-1);
}
elseif ($pid > 0) {
    exit(0);
}
file_put_contents('/tmp/pdaemon.pid',posix_getpid());
echo "start daemon ... \n";
posix_setsid();
chdir('/');
while(true) {
    usleep(10000);
}

