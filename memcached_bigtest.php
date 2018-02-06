<?php

$mh = new Memcached('test');

$mh->addServer('localhost',11211);

$data = [
    'id'=>'abcdefghijklmnopqrst',
    'ip'=>'192.168.124.3',
    'other'=>'1234567890123456789',
    'api_key'=>'aksdhkvsdfiho123ufd9838213-fdhsf32989hsdhsofsddskfhiwehifhdskfhdsj',
    'ticket'=>'dskfhkoeiwrhhfkdshfkdshfkaodoqodhodlasdlasdlshdshfk'
];

$total = 2000000;

$handle = 'set-mem';
if ($argc>1 && $argv[1]=='clear') {
    $handle = 'delete-mem';
}

if ($handle=='set-mem') {
    for ($i=1; $i<=$total; $i++) {
        $mh->set("node_$i",$data);
        if ( !($i%100) ) {
            echo "set node $i\n";
        }
    }
} elseif($handle=='delete-mem') {
    for ($i=1; $i<=$total; $i++) {
        $mh->delete("node_$i");
        if ( !($i%100) ) {
            echo "delete node $i\n";
        }
    }
}

