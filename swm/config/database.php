<?php

return [
    'database_type' => 'mysql',
    'database_name' => 'linux_study',
    'server' => '127.0.0.1',
    'username' => 'master',
    'password' => 'master2018',
 
    // [optional]
    'charset' => 'utf8',
    'port' => 3306,
 
    // [optional] Table prefix
    'prefix' => '',
 
    // [optional] Enable logging (Logging is disabled by default for better performance)
    'logging' => false,
 
    // [optional] MySQL socket (shouldn't be used with server and port)
    //'socket' => '/tmp/mysql.sock',
 
    // [optional] driver_option for connection
    'option' => [
        //PDO::ATTR_PERSISTENT=>true,
    ],
 
    // [optional] Medoo will execute those commands after connected to the database for initialization
    'command' => [
        'SET SQL_MODE=ANSI_QUOTES',
    ]
];

