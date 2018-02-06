<?php

$meh = new Memcached('story_pool');
$meh->addServer('localhost',11211);

$meh->set('test','hello,this is test info for memecached.<br>');

echo $meh->get('test');

