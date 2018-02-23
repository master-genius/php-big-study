<?php
namespace bravemaster\core;

class wdb
{

    static private $mdb = null;

    private $db_cfg = [
        'database_type' => 'mysql',
        'database_name' => 'wsmsg',
        'server' => '127.0.0.1',
        'username' => 'brave',
        'password' => 'b1001001',
     
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

    private function __construct()
    {

    }

    static public instance($config=[])
    {
        if (!empty($config)) {
            $this->db_cfg = $config;
            self::$mdb = null;
        }

        if (self::$mdb===null) {
            $this->db_cfg = include(ROOT_PATH . '../config/database.php');
            self::$mdb = \Medoo\Medoo($this->db_cfg);
        }
        return self::$mdb;
    }

}

