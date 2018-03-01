<?php
declare(ticks=10);
function signal_handler($sig) {
    switch ($sig) {
        case SIGINT:
            echo "get SIGINT signal\n";
            exit(0);
        case SIGTERM:
            echo "get SIGTERM signal\n";
            exit(0);
        default:;
    }
}
pcntl_signal(SIGINT, "signal_handler");
pcntl_signal(SIGTERM, "signal_handler");
echo posix_getpid() . "\n";
while (true) {
    usleep(100000);
}

