<?php

$rd = new Redis();

$rd->connect('127.0.0.1',6379);

for ($i=0;$i<10;$i++) {
    $rd->publish('redis_test',mt_rand(1000,10000));
}

