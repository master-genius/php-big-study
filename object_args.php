<?php

class test
{
    function abc()
    {
        echo "abc\n";
    }
}

function args_test($callback)
{
    $callback();
}

$t = new test();

args_test($t->abc);
