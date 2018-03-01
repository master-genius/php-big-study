<?php

echo posix_getpid() . "\n"; //获取进程ID
echo posix_getppid() . "\n"; //获取父进程ID
echo posix_getcwd() . "\n"; //获取当前工作目录
var_dump(posix_uname()); //获取系统名称，版本等信息

