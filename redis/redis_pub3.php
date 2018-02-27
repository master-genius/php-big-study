<?php

$rd = new Redis();

$rd->connect('127.0.0.1',6379);

$chan = 'chan1';

for ($i=0;$i<10;$i++) {
    for($j=0;$j<100;$j++) {
        $rd->publish($chan, time() . ':' . mt_rand(100,10000));
    }
    $chan = 'chan' . mt_rand(1,3);
}

