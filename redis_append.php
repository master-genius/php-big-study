<?php

$rd = new Redis();

$rd->connect('127.0.0.1',6379);

$rd->append('test','php');

