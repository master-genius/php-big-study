<?php

$mch = new Memcached('total');

$mch->addServer('192.168.0.106',11211);

if ($argc>1) {
    $r = $mch->set('test',$argv[1]);
    if (!$r) {
        echo "Error: failed to set value\nError code: ";
        echo $mch->getResultCode();
        echo "\n";
    } else {
        echo "success to set value\n";
    }
} else {
    $info = $mch->get('test');
    echo "$info\n";
}

