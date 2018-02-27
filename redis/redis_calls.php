<?php

$rd = new Redis();
$rd_calls = get_class_methods($rd);

foreach ($rd_calls as $call) {
    echo $call . "\n";
}

