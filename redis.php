<?php

$rd = new Redis();

$rd_calls = get_class_methods($rd);

/*
foreach ($rd_calls as $call) {
    echo $call . "\n";
}

*/

$rd->connect('127.0.0.1',6379);

for ($i=0;$i<10;$i++) {
    $rd->publish('test',mt_rand(10000,100000));
}

